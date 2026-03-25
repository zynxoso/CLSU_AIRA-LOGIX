<?php

namespace App\Http\Controllers;

use App\Models\IctServiceRequest;
use App\Services\IctExtractionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Services\IctTemplateService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;
use Exception;



class IctServiceRequestController extends Controller
{
    public function __construct(
        protected IctExtractionService $extractionService
    ) {}

    public function intake()
    {
        return Inertia::render('intake');
    }

    public function smartScan()
    {
        return Inertia::render('smart-scan');
    }

    public function logControllerError(string $action, Throwable $e, array $context = []): void
    {
        Log::error("IctServiceRequestController@{$action} failed", array_merge([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
        'user_id' => auth()->id(),
        'ip' => request()->ip(),
        'url' => request()->fullUrl(),
        'method' => request()->method(),
    ], $context));
    }

    public function extract(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        try {
            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            
            // Store temporarily for the background job
            $path = $file->store('ict_forms_tmp');
            $fullPath = \Illuminate\Support\Facades\Storage::path($path);
            
            \Illuminate\Support\Facades\Log::info("Smart Scan Import Initiated - Extracting from " . strtoupper($file->getClientOriginalExtension()) . " file: [{$originalName}]");

            $jobId = uniqid('ext_', true);
            \App\Jobs\PerformExtractionJob::dispatch($fullPath, $jobId);

            \Illuminate\Support\Facades\Cache::put("extraction_{$jobId}_status", 'queued', 600);
            \Illuminate\Support\Facades\Cache::put("extraction_{$jobId}_meta", [
                'original_filename' => $originalName,
                'path' => $path
            ], 600);

            return response()->json([
                'success' => true,
                'job_id' => $jobId,
                'status' => 'queued'
            ]);
        } catch (Exception $e) {
            $this->logControllerError('extract', $e, [
                'original_filename' => $originalName ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate extraction: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkStatus($jobId)
    {
        $status = \Illuminate\Support\Facades\Cache::get("extraction_{$jobId}_status", 'unknown');
        $result = \Illuminate\Support\Facades\Cache::get("extraction_{$jobId}_result");
        $error = \Illuminate\Support\Facades\Cache::get("extraction_{$jobId}_error");
        $meta = \Illuminate\Support\Facades\Cache::get("extraction_{$jobId}_meta", []);

        return response()->json([
            'success' => true,
            'status' => $status,
            'data' => $result,
            'error' => $error,
            'meta' => $meta
        ]);
    }

    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'control_no' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'office_unit' => 'required|string|max:255',
            'contact_no' => 'nullable|string|max:255',
            'date_of_request' => 'required|date',
            'requested_completion_date' => 'nullable|date',
            'request_type' => 'required|string|max:255',
            'location_venue' => 'nullable|string|max:255',
            'request_description' => 'required|string',
            'status' => 'required|string|max:255',
        ]);

        $item = IctServiceRequest::create($validated);

        return response()->json([
            'success' => true,
            'id' => $item->id,
            'message' => 'Record saved successfully.'
        ]);
    }

    public function storeBatch(Request $request)
    {
        try {
            $validated = $request->validate([
                'requests' => 'required|array',
                'requests.*.name' => 'nullable|string|max:255',
                'requests.*.control_no' => 'nullable|string|max:255',
                'requests.*.position' => 'nullable|string|max:255',
                'requests.*.office_unit' => 'nullable|string|max:255',
                'requests.*.contact_no' => 'nullable|string|max:255',
                'requests.*.date_of_request' => 'nullable|date',
                'requests.*.requested_completion_date' => 'nullable|date',
                'requests.*.request_type' => 'nullable|string|max:255',
                'requests.*.location_venue' => 'nullable|string|max:255',
                'requests.*.request_description' => 'nullable|string',
                'requests.*.status' => 'nullable|string|max:255',
            ]);

            $now = now();
            $normalized = collect($validated['requests'])
                ->map(function (array $reqData) use ($now) {
                    // Provide defaults for nullable required fields in DB if any.
                    $name = $reqData['name'] ?? 'Unknown';
                    $record = [
                        'control_no' => empty($reqData['control_no']) ? null : trim((string) $reqData['control_no']),
                        'name' => $name,
                        'name_index' => IctServiceRequest::generateNameIndex($name),
                        'position' => $reqData['position'] ?? null,
                        'office_unit' => $reqData['office_unit'] ?? 'Unknown',
                        'contact_no' => $reqData['contact_no'] ?? null,
                        'date_of_request' => $reqData['date_of_request'] ?? $now,
                        'requested_completion_date' => $reqData['requested_completion_date'] ?? null,
                        'request_type' => $reqData['request_type'] ?? 'Classification pending',
                        'location_venue' => $reqData['location_venue'] ?? null,
                        'request_description' => $reqData['request_description'] ?? 'No description provided',
                        'status' => $reqData['status'] ?? 'Open',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    return $record;
                })
                ->filter(function (array $record) {
                    foreach ($record as $key => $value) {
                        if (in_array($key, ['created_at', 'updated_at'], true)) {
                            continue;
                        }

                        if ($value !== null && $value !== '') {
                            return true;
                        }
                    }

                    return false;
                })
                ->values();

            $savedCount = $normalized->count();

            DB::transaction(function () use ($normalized) {
                $withControlNo = $normalized->filter(fn (array $row) => !empty($row['control_no']))->values();
                $withoutControlNo = $normalized->filter(fn (array $row) => empty($row['control_no']))->values();

                foreach ($withControlNo->chunk(250) as $chunk) {
                    IctServiceRequest::upsert(
                        $chunk->all(),
                        ['control_no'],
                        [
                            'name',
                            'name_index',
                            'position',
                            'office_unit',
                            'contact_no',
                            'date_of_request',
                            'requested_completion_date',
                            'request_type',
                            'location_venue',
                            'request_description',
                            'status',
                            'updated_at',
                        ]
                    );
                }

                foreach ($withoutControlNo->chunk(250) as $chunk) {
                    IctServiceRequest::insert($chunk->all());
                }
            });

            \Illuminate\Support\Facades\Log::info("Smart Scan Batch Import Completed - Extracted & saved {$savedCount} records.");

            return response()->json([
                'success' => true,
                'message' => $savedCount . ' records saved successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->logControllerError('storeBatch.validation', $e, [
                'validation_errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logControllerError('storeBatch', $e, [
                'request_count' => count($request->input('requests', [])),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process import: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        if ($request->hasFile('file')) {
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB
            ]);

            try {
                $path = $request->file('file')->store('ict_forms');
                $extractedData = $this->extractionService->extractFromFile(Storage::path($path));
                
                $finalData = array_merge([
                    'status' => 'Open',
                    'meta' => [
                        'original_filename' => $request->file('file')->getClientOriginalName(),
                        'file_path' => $path,
                    ]
                ], $extractedData);

                $ictRequest = IctServiceRequest::create($finalData);

                return redirect()->route('dashboard')->with('success', 'Request created from file extraction.');
            } catch (Exception $e) {
                return back()->withErrors(['file' => 'Extraction failed: ' . $e->getMessage()]);
            }
        }

        // Manual form submission
        $validated = $request->validate([
            'control_no' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'office_unit' => 'required|string|max:255',
            'contact_no' => 'nullable|string|max:255',
            'request_type' => 'required|string|max:255',
            'date_of_request' => 'required|date',
            'requested_completion_date' => 'nullable|date',
            'location_venue' => 'required|string|max:255',
            'request_description' => 'required|string',
            'status' => 'required|string|max:255',
            'conducted_by' => 'nullable|string|max:255',
            'noted_by' => 'nullable|string|max:255',
            'action_taken' => 'nullable|string',
            'recommendation_conclusion' => 'nullable|string',
            'client_feedback_no' => 'nullable|string|max:255',
            'received_by' => 'nullable|string|max:255',
            'receive_date_time' => 'nullable|string|max:255',
            'date_time_started' => 'nullable|string|max:255',
            'date_time_completed' => 'nullable|string|max:255',
        ]);

        IctServiceRequest::create($validated);

        return redirect()->route('dashboard')->with('success', 'New record added manually.');
    }

    public function edit($id)
    {
        $request = IctServiceRequest::findOrFail($id);
        return Inertia::render('requests/edit', [
            'request' => $request
        ]);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'control_no' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'office_unit' => 'required|string|max:255',
            'contact_no' => 'nullable|string|max:255',
            'request_type' => 'required|string|max:255',
            'date_of_request' => 'required|date',
            'requested_completion_date' => 'nullable|date',
            'location_venue' => 'required|string|max:255',
            'request_description' => 'required|string',
            'status' => 'required|string|max:255',
            'conducted_by' => 'nullable|string|max:255',
            'noted_by' => 'nullable|string|max:255',
            'action_taken' => 'nullable|string',
            'recommendation_conclusion' => 'nullable|string',
            'client_feedback_no' => 'nullable|string|max:255',
            'received_by' => 'nullable|string|max:255',
            'receive_date_time' => 'nullable|string|max:255',
            'date_time_started' => 'nullable|string|max:255',
            'date_time_completed' => 'nullable|string|max:255',
        ]);

        $ictRequest = IctServiceRequest::findOrFail($id);
        $ictRequest->update($validated);

        return redirect()->route('dashboard')->with('success', 'Request updated successfully');
    }

    public function destroy($id)
    {
        $ictRequest = IctServiceRequest::withTrashed()->findOrFail($id);
        if ($ictRequest->trashed()) {
            $ictRequest->forceDelete();
            return redirect()->route('dashboard')->with('success', 'Request permanently deleted');
        } else {
            $ictRequest->delete();
            return redirect()->route('dashboard')->with('success', 'Request archived successfully');
        }
    }

    public function restore($id)
    {
        $ictRequest = IctServiceRequest::withTrashed()->findOrFail($id);
        $ictRequest->restore();

        return redirect()->route('dashboard')->with('success', 'Request restored successfully');
    }

    public function exportBulkDocx(Request $request, IctTemplateService $service)
    {
        $query = $this->buildExportQuery($request);
        $requests = $query->get();

        if ($requests->isEmpty()) {
            return back()->with('error', 'No records found to export.');
        }

        $zipFileName = $service->generateBulkDocxZip($requests);
        
        return response()->download(storage_path('app/public/' . $zipFileName))->deleteFileAfterSend(true);
    }

    public function download($id, IctTemplateService $service)
    {
        $ictRequest = IctServiceRequest::findOrFail($id);
        $fileName = $service->generateDocx($ictRequest);
        
        return response()->download(storage_path('app/public/reports/' . $fileName))->deleteFileAfterSend(true);
    }

    public function export(Request $request)
    {
        $query = $this->buildExportQuery($request)->orderBy('id');
        $fileName = 'ICT_Service_Requests_' . now()->format('Y-m-d_H-i') . '.csv';

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = [
            'Name', 'Contact No', 'Position', 'Date of Request',
            'Office/Unit', 'Requested date of completion', 'TYPE OF REQUEST', 
            'LOCATION / VENUE', 'BRIEF DESCRIPTION OF REQUEST', 'RECEIVED BY', 
            'DATE/TIME', 'ACTION TAKEN', 'RECOMMENDATION / CONCLUSION', 'Status', 
            'CLIENT FEEDBACK NO.', 'SERVICE REQUEST CTRL. NO.', 'DATE & TIME STARTED', 
            'DATE & TIME COMPLETED', 'Conducted by', 'NOTED BY'
        ];

        $callback = function () use ($query, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($query->cursor() as $req) {
                fputcsv($file, $this->mapDetailedExportRow($req));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportXlsx(Request $request)
    {
        $query = $this->buildExportQuery($request)->orderBy('id');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('ICT Requests');

        // Headers
        $headers = [
            'Name', 'Contact No', 'Position', 'Date of Request',
            'Office/Unit', 'Requested date of completion', 'TYPE OF REQUEST', 
            'LOCATION / VENUE', 'BRIEF DESCRIPTION OF REQUEST', 'RECEIVED BY', 
            'DATE/TIME', 'ACTION TAKEN', 'RECOMMENDATION / CONCLUSION', 'Status', 
            'CLIENT FEEDBACK NO.', 'SERVICE REQUEST CTRL. NO.', 'DATE & TIME STARTED', 
            'DATE & TIME COMPLETED', 'Conducted by', 'NOTED BY'
        ];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Styling the header
        $headerRange = 'A1:T1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '10b981']], // Emerald-500
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $rowNumber = 2;
        foreach ($query->cursor() as $req) {
            $sheet->fromArray([$this->mapDetailedExportRow($req)], null, "A{$rowNumber}");
            $rowNumber++;
        }

        // Auto-sizing columns
        foreach (range('A', 'T') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $fileName = 'ICT_Service_Requests_' . now()->format('Y-m-d_H-i') . '.xlsx';
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportCsv(Request $request)

    {
        $query = IctServiceRequest::query();
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('control_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('request_type', 'like', "%{$search}%");
            });
        }

        $fileName = 'ict_requests_export_' . now()->format('Ymd_His') . '.csv';
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Control No', 'Name', 'Office/Unit', 'Type', 'Status', 'Date Created', 'Description']);

            foreach ($query->orderBy('id')->cursor() as $req) {
                fputcsv($file, [
                    $req->id,
                    $req->control_no,
                    $req->name,
                    $req->office_unit,
                    $req->request_type,
                    $req->status,
                    $req->created_at->format('Y-m-d H:i:s'),
                    $req->request_description
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function buildExportQuery(Request $request)
    {
        $query = IctServiceRequest::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('control_no', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('request_type', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('request_type', $request->type);
        }

        if ($request->filled('requester') && $request->requester !== 'all') {
            $query->where('name', $request->requester);
        }

        if ($request->filled('ids')) {
            $ids = explode(',', (string) $request->get('ids'));
            $query->whereIn('id', $ids);
        }

        return $query;
    }

    protected function mapDetailedExportRow(IctServiceRequest $req): array
    {
        return [
            $req->name,
            $req->contact_no,
            $req->position,
            $req->date_of_request ? $req->date_of_request->format('m/d/Y') : '',
            $req->office_unit,
            $req->requested_completion_date ? $req->requested_completion_date->format('m/d/Y') : '',
            $req->request_type,
            $req->location_venue,
            $req->request_description,
            $req->received_by,
            $req->receive_date_time ? $req->receive_date_time->format('m/d/Y') : '',
            $req->action_taken,
            $req->recommendation_conclusion,
            $req->status,
            $req->client_feedback_no,
            $req->control_no,
            $req->date_time_started ? $req->date_time_started->format('m/d/Y') : '',
            $req->date_time_completed ? $req->date_time_completed->format('m/d/Y') : '',
            $req->conducted_by,
            $req->noted_by,
        ];
    }
}


