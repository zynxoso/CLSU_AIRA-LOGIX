<?php

namespace App\Http\Controllers;

use App\Http\Requests\MisoAccomplishmentRequest;
use App\Jobs\PerformMisoExtractionJob;
use App\Models\MisoAccomplishment;
use App\Services\MisoAccomplishmentSyncService;
use App\Services\MisoTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class MisoAccomplishmentController extends Controller
{
    public function __construct(
        protected MisoAccomplishmentSyncService $misoSyncService
    ) {}

    public function intake(Request $request): Response
    {
        $tab = $this->resolveTab($request->query('tab'));

        return Inertia::render('miso-intake', [
            'tab' => $tab,
            'category' => $this->misoSyncService->categoryForTab($tab),
            'categoryOptions' => MisoAccomplishment::categoryOptions(),
        ]);
    }

    public function smartScan(Request $request): Response
    {
        $tab = $this->resolveTab($request->query('tab'));

        return Inertia::render('miso-smart-scan', [
            'tab' => $tab,
            'category' => $this->misoSyncService->categoryForTab($tab),
            'categoryOptions' => MisoAccomplishment::categoryOptions(),
        ]);
    }

    public function edit(int $id): Response
    {
        $record = MisoAccomplishment::findOrFail($id);

        return Inertia::render('miso-edit', [
            'request' => $record,
            'tab' => $this->tabForCategory($record->category),
            'category' => $record->category,
            'categoryOptions' => MisoAccomplishment::categoryOptions(),
        ]);
    }

    public function store(MisoAccomplishmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['source_file'] = 'manual';
        $validated['source_hash'] = hash('sha256', 'manual|'.Str::uuid());

        MisoAccomplishment::create($validated);

        return redirect()
            ->route('dashboard', ['tab' => $this->tabForCategory($validated['category'])])
            ->with('success', 'MISO accomplishment record added successfully.');
    }

    public function update(MisoAccomplishmentRequest $request, int $id): RedirectResponse
    {
        $validated = $request->validated();

        $record = MisoAccomplishment::findOrFail($id);
        $record->update($validated);

        return redirect()
            ->route('dashboard', ['tab' => $this->tabForCategory($record->category)])
            ->with('success', 'MISO accomplishment record updated successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $record = MisoAccomplishment::withTrashed()->findOrFail($id);
        $tab = $this->tabForCategory($record->category);

        if ($record->trashed()) {
            $record->forceDelete();

            return redirect()
                ->route('dashboard', ['tab' => $tab])
                ->with('success', 'MISO accomplishment record permanently deleted.');
        }

        $record->delete();

        return redirect()
            ->route('dashboard', ['tab' => $tab])
            ->with('success', 'MISO accomplishment record archived successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $record = MisoAccomplishment::withTrashed()->findOrFail($id);
        $record->restore();

        return redirect()
            ->route('dashboard', ['tab' => $this->tabForCategory($record->category)])
            ->with('success', 'MISO accomplishment record restored successfully.');
    }

    public function download(int $id, MisoTemplateService $service): BinaryFileResponse|RedirectResponse
    {
        $record = MisoAccomplishment::findOrFail($id);

        try {
            $fileName = $service->generateDocx($record);
        } catch (Throwable $e) {
            $this->logControllerError('download', $e, [
                'record_id' => $record->id,
                'category' => $record->category,
            ]);

            $templateError = Str::limit($e->getMessage(), 350);

            return redirect()
                ->route('dashboard', ['tab' => $this->tabForCategory($record->category)])
                ->with('error', 'MISO template issue: '.$templateError);
        }

        return response()
            ->download(storage_path('app/public/reports/miso/'.$fileName))
            ->deleteFileAfterSend(true);
    }

    public function extract(Request $request): JsonResponse
    {
        $allowedCategories = array_keys(MisoAccomplishment::categoryOptions());

        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'category' => ['required', 'string', Rule::in($allowedCategories)],
        ]);

        $originalName = null;

        try {
            $file = $request->file('file');
            $originalName = $file?->getClientOriginalName() ?? 'unknown';

            $path = $file->store('miso_forms_tmp');
            $fullPath = Storage::path($path);
            $jobId = 'miso_ext_'.Str::random(32);

            Cache::put("miso_extraction_{$jobId}_status", 'queued', 600);
            Cache::put("miso_extraction_{$jobId}_meta", [
                'original_filename' => $originalName,
                'path' => $path,
                'user_id' => auth()->id(),
                'category' => $validated['category'],
            ], 600);

            PerformMisoExtractionJob::dispatch($fullPath, $jobId, $validated['category']);

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'status' => 'queued',
            ]);
        } catch (Throwable $e) {
            $this->logControllerError('extract', $e, [
                'original_filename' => $originalName,
                'category' => $validated['category'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate extraction. Please contact support.',
            ], 500);
        }
    }

    public function checkStatus(string $jobId): JsonResponse
    {
        $meta = Cache::get("miso_extraction_{$jobId}_meta", []);
        $userId = auth()->id();

        if (empty($meta) || !isset($meta['user_id']) || $meta['user_id'] !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or job not found.',
            ], 403);
        }

        $status = Cache::get("miso_extraction_{$jobId}_status", 'unknown');
        $result = Cache::get("miso_extraction_{$jobId}_result", []);
        $error = Cache::get("miso_extraction_{$jobId}_error");

        return response()->json([
            'success' => true,
            'status' => $status,
            'data' => $result,
            'error' => $error,
            'meta' => $meta,
        ]);
    }

    public function storeManual(Request $request): JsonResponse
    {
        $allowedCategories = array_keys(MisoAccomplishment::categoryOptions());

        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in($allowedCategories)],
            'record_no' => 'nullable|string|max:50',
            'project_title' => 'required|string|max:2000',
            'project_lead' => 'nullable|string|max:1000',
            'project_members' => 'nullable|string',
            'budget_cost' => 'nullable|string|max:255',
            'implementing_unit' => 'nullable|string|max:1000',
            'target_activities' => 'nullable|string',
            'intended_duration' => 'nullable|string|max:255',
            'start_date' => 'nullable|string|max:255',
            'target_end_date' => 'nullable|string|max:255',
            'reporting_period' => 'nullable|string|max:255',
            'completion_percentage' => 'nullable|string|max:255',
            'overall_status' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        $validated['source_file'] = 'manual-api';
        $validated['source_hash'] = hash('sha256', 'manual-api|'.$validated['category'].'|'.Str::uuid());
        $validated['overall_status'] = $validated['overall_status'] ?? 'Pending';

        $record = MisoAccomplishment::create($validated);

        return response()->json([
            'success' => true,
            'id' => $record->id,
            'message' => 'Record saved successfully.',
        ]);
    }

    public function storeBatch(Request $request): JsonResponse
    {
        $allowedCategories = array_keys(MisoAccomplishment::categoryOptions());

        $validated = $request->validate([
            'category' => ['required', 'string', Rule::in($allowedCategories)],
            'requests' => 'required|array|min:1',
            'requests.*.category' => ['nullable', 'string', Rule::in($allowedCategories)],
            'requests.*.record_no' => 'nullable|string|max:50',
            'requests.*.project_title' => 'nullable|string|max:2000',
            'requests.*.project_lead' => 'nullable|string|max:1000',
            'requests.*.project_members' => 'nullable|string',
            'requests.*.budget_cost' => 'nullable|string|max:255',
            'requests.*.implementing_unit' => 'nullable|string|max:1000',
            'requests.*.target_activities' => 'nullable|string',
            'requests.*.intended_duration' => 'nullable|string|max:255',
            'requests.*.start_date' => 'nullable|string|max:255',
            'requests.*.target_end_date' => 'nullable|string|max:255',
            'requests.*.reporting_period' => 'nullable|string|max:255',
            'requests.*.completion_percentage' => 'nullable|string|max:255',
            'requests.*.overall_status' => 'nullable|string|max:255',
            'requests.*.remarks' => 'nullable|string',
        ]);

        $now = now();
        $rows = [];

        foreach ($validated['requests'] as $index => $entry) {
            $category = $entry['category'] ?? $validated['category'];
            if (!in_array($category, $allowedCategories, true)) {
                continue;
            }

            $row = [
                'category' => $category,
                'record_no' => $this->trimOrNull($entry['record_no'] ?? null),
                'project_title' => $this->trimOrNull($entry['project_title'] ?? null),
                'project_lead' => $this->trimOrNull($entry['project_lead'] ?? null),
                'project_members' => $this->trimOrNull($entry['project_members'] ?? null),
                'budget_cost' => $this->trimOrNull($entry['budget_cost'] ?? null),
                'implementing_unit' => $this->trimOrNull($entry['implementing_unit'] ?? null),
                'target_activities' => $this->trimOrNull($entry['target_activities'] ?? null),
                'intended_duration' => $this->trimOrNull($entry['intended_duration'] ?? null),
                'start_date' => $this->trimOrNull($entry['start_date'] ?? null),
                'target_end_date' => $this->trimOrNull($entry['target_end_date'] ?? null),
                'reporting_period' => $this->trimOrNull($entry['reporting_period'] ?? null),
                'completion_percentage' => $this->trimOrNull($entry['completion_percentage'] ?? null),
                'overall_status' => $this->trimOrNull($entry['overall_status'] ?? null) ?? 'Pending',
                'remarks' => $this->trimOrNull($entry['remarks'] ?? null),
            ];

            if ($this->isImportRowEmpty($row)) {
                continue;
            }

            $row['project_title'] = $row['project_title'] ?? 'Untitled Project';
            $row['source_file'] = 'import-batch';
            $row['source_row'] = $index + 1;
            $row['source_hash'] = hash('sha256', 'import-batch|'.$category.'|'.Str::uuid());
            $row['created_at'] = $now;
            $row['updated_at'] = $now;

            $rows[] = $row;
        }

        if (count($rows) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No valid rows were found in the extraction result.',
            ], 422);
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            MisoAccomplishment::insert($chunk);
        }

        return response()->json([
            'success' => true,
            'message' => count($rows).' records saved successfully.',
        ]);
    }

    public function export(Request $request)
    {
        $query = $this->buildExportQuery($request)->orderBy('id');
        $tab = (string) $request->query('tab', MisoAccomplishmentSyncService::TAB_MISO_DATA);
        $fileName = 'MISO_Accomplishments_'.$tab.'_'.now()->format('Y-m-d_H-i').'.csv';

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = [
            'No.',
            'Category',
            'Project Title',
            'Project Lead',
            'Project Members',
            'Budget/Cost',
            'Implementing Unit',
            'Target Activities',
            'Intended Duration',
            'Start Date',
            'Target End Date',
            'Reporting Period',
            'Completion %',
            'Overall Status',
            'Remarks',
            'Source File',
            'Updated At',
        ];

        $callback = function () use ($query, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($query->cursor() as $record) {
                fputcsv($file, $this->mapExportRow($record));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportXlsx(Request $request)
    {
        $query = $this->buildExportQuery($request)->orderBy('id');
        $tab = (string) $request->query('tab', MisoAccomplishmentSyncService::TAB_MISO_DATA);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('MISO Accomplishments');

        $headers = [[
            'No.',
            'Category',
            'Project Title',
            'Project Lead',
            'Project Members',
            'Budget/Cost',
            'Implementing Unit',
            'Target Activities',
            'Intended Duration',
            'Start Date',
            'Target End Date',
            'Reporting Period',
            'Completion %',
            'Overall Status',
            'Remarks',
            'Source File',
            'Updated At',
        ]];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10b981']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rowNumber = 2;
        foreach ($query->cursor() as $record) {
            $sheet->fromArray([$this->mapExportRow($record)], null, "A{$rowNumber}");
            $rowNumber++;
        }

        foreach (range('A', 'Q') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $fileName = 'MISO_Accomplishments_'.$tab.'_'.now()->format('Y-m-d_H-i').'.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function tabForCategory(string $category): string
    {
        return $this->misoSyncService->tabForCategory($category);
    }

    protected function resolveTab(?string $tab): string
    {
        $tabMap = $this->misoSyncService->tabCategoryMap();

        if ($tab !== null && isset($tabMap[$tab])) {
            return $tab;
        }

        return MisoAccomplishmentSyncService::TAB_MISO_DATA;
    }

    protected function trimOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    protected function buildExportQuery(Request $request)
    {
        $query = MisoAccomplishment::query();

        $tab = (string) $request->query('tab', MisoAccomplishmentSyncService::TAB_MISO_DATA);
        $tabMap = $this->misoSyncService->tabCategoryMap();
        if (isset($tabMap[$tab])) {
            $query->where('category', $tabMap[$tab]);
        }

        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('project_title', 'like', "%{$search}%")
                    ->orWhere('project_lead', 'like', "%{$search}%")
                    ->orWhere('implementing_unit', 'like', "%{$search}%")
                    ->orWhere('target_activities', 'like', "%{$search}%")
                    ->orWhere('remarks', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('overall_status', (string) $request->status);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('implementing_unit', (string) $request->type);
        }

        if ($request->filled('requester') && $request->requester !== 'all') {
            $query->where('project_lead', (string) $request->requester);
        }

        if ($request->filled('ids')) {
            $ids = array_filter(explode(',', (string) $request->query('ids')));
            $query->whereIn('id', $ids);
        }

        return $query;
    }

    protected function mapExportRow(MisoAccomplishment $record): array
    {
        $categoryLabel = MisoAccomplishment::categoryOptions()[$record->category] ?? $record->category;

        return [
            $record->record_no,
            $categoryLabel,
            $record->project_title,
            $record->project_lead,
            $record->project_members,
            $record->budget_cost,
            $record->implementing_unit,
            $record->target_activities,
            $record->intended_duration,
            $record->start_date,
            $record->target_end_date,
            $record->reporting_period,
            $record->completion_percentage,
            $record->overall_status,
            $record->remarks,
            $record->source_file,
            $record->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param array<string, string|null> $row
     */
    protected function isImportRowEmpty(array $row): bool
    {
        foreach ([
            'record_no',
            'project_title',
            'project_lead',
            'project_members',
            'budget_cost',
            'implementing_unit',
            'target_activities',
            'intended_duration',
            'start_date',
            'target_end_date',
            'reporting_period',
            'completion_percentage',
            'overall_status',
            'remarks',
        ] as $field) {
            if (($row[$field] ?? null) !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function logControllerError(string $action, Throwable $e, array $context = []): void
    {
        Log::error("MisoAccomplishmentController@{$action} failed", array_merge([
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ], $context));
    }
}
