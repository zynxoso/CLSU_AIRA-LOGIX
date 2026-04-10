<?php

namespace App\Services;

use App\Models\MisoAccomplishment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class MisoAccomplishmentSyncService
{
    public const TAB_MISO_DATA = 'miso-data';
    public const TAB_MISO_NETWORK = 'miso-network';
    public const TAB_MISO_SYSTEMS = 'miso-systems';

    /**
     * Keep labels centralized so controller and frontend stay in sync.
     *
     * @return array<string, string>
     */
    public function tabLabels(): array
    {
        return [
            self::TAB_MISO_DATA => 'MISO Accomplishments Data',
            self::TAB_MISO_NETWORK => 'Network / Cybersec / Tech Support',
            self::TAB_MISO_SYSTEMS => 'Systems Development / QA',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function tabCategoryMap(): array
    {
        return [
            self::TAB_MISO_DATA => MisoAccomplishment::CATEGORY_DATA_MANAGEMENT,
            self::TAB_MISO_NETWORK => MisoAccomplishment::CATEGORY_NETWORK,
            self::TAB_MISO_SYSTEMS => MisoAccomplishment::CATEGORY_SYSTEMS_DEVELOPMENT,
        ];
    }

    public function categoryForTab(string $tab): string
    {
        $map = $this->tabCategoryMap();

        return $map[$tab] ?? MisoAccomplishment::CATEGORY_DATA_MANAGEMENT;
    }

    public function tabForCategory(string $category): string
    {
        $map = array_flip($this->tabCategoryMap());

        return $map[$category] ?? self::TAB_MISO_DATA;
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function extractFromFile(string $filePath, string $category): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('Uploaded file was not found.');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['csv', 'xls', 'xlsx'], true)) {
            throw new RuntimeException('Unsupported format. Please upload CSV or XLSX files.');
        }

        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        if (count($rows) === 0) {
            return [];
        }

        $headerRowIndex = $this->findHeaderRowIndex($rows) ?? 0;
        $headers = $rows[$headerRowIndex] ?? [];
        $headerMap = $this->buildHeaderMap($headers);

        if (count($headerMap) === 0) {
            throw new RuntimeException('Could not detect MISO columns from the uploaded spreadsheet.');
        }

        $records = [];

        foreach (array_slice($rows, $headerRowIndex + 1, null, true) as $arrayIndex => $row) {
            $record = $this->mapRow($row, $headerMap);

            if ($this->isRowSkippable($record)) {
                continue;
            }

            $record['category'] = $category;
            $record['record_no'] = $record['record_no'] ?: (string) ($arrayIndex - $headerRowIndex);
            $record['overall_status'] = $record['overall_status'] ?: 'Pending';

            $records[] = $record;
        }

        return $records;
    }

    public function syncIfNeeded(): void
    {
        $sources = $this->sourceFiles();
        $signature = $this->buildSignature($sources);

        if ($signature === null) {
            return;
        }

        $cacheKey = 'miso_accomplishments.docs_signature';
        if (Cache::get($cacheKey) === $signature) {
            return;
        }

        $this->syncFromSources($sources);
        Cache::forever($cacheKey, $signature);
    }

    /**
     * @param array<string, string> $sources
     */
    protected function syncFromSources(array $sources): void
    {
        foreach ($sources as $category => $absolutePath) {
            if (!is_file($absolutePath)) {
                continue;
            }

            try {
                $spreadsheet = IOFactory::load($absolutePath);
                $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                if (count($rows) === 0) {
                    continue;
                }

                $headerRowIndex = $this->findHeaderRowIndex($rows);
                if ($headerRowIndex === null) {
                    continue;
                }

                $headers = $rows[$headerRowIndex] ?? [];
                $headerMap = $this->buildHeaderMap($headers);

                if (count($headerMap) === 0) {
                    continue;
                }

                $relativeFilePath = str_replace('\\', '/', str_replace(base_path().DIRECTORY_SEPARATOR, '', $absolutePath));

                $upserts = [];
                $seenHashes = [];
                $now = now();

                foreach (array_slice($rows, $headerRowIndex + 1, null, true) as $arrayIndex => $row) {
                    $sourceRow = $arrayIndex + 1;
                    $record = $this->mapRow($row, $headerMap);

                    if ($this->isRowSkippable($record)) {
                        continue;
                    }

                    $sourceHash = hash('sha256', $category.'|'.$relativeFilePath.'|'.$sourceRow);
                    $seenHashes[] = $sourceHash;

                    $upserts[] = [
                        'category' => $category,
                        'source_file' => $relativeFilePath,
                        'source_row' => $sourceRow,
                        'source_hash' => $sourceHash,
                        'record_no' => $record['record_no'] ?: (string) ($sourceRow - $headerRowIndex - 1),
                        'project_title' => $record['project_title'],
                        'project_lead' => $record['project_lead'],
                        'project_members' => $record['project_members'],
                        'budget_cost' => $record['budget_cost'],
                        'implementing_unit' => $record['implementing_unit'],
                        'target_activities' => $record['target_activities'],
                        'intended_duration' => $record['intended_duration'],
                        'start_date' => $record['start_date'],
                        'target_end_date' => $record['target_end_date'],
                        'reporting_period' => $record['reporting_period'],
                        'completion_percentage' => $record['completion_percentage'],
                        'overall_status' => $record['overall_status'],
                        'remarks' => $record['remarks'],
                        'deleted_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (count($upserts) === 0) {
                    continue;
                }

                foreach (array_chunk($upserts, 200) as $chunk) {
                    MisoAccomplishment::upsert(
                        $chunk,
                        ['source_hash'],
                        [
                            'category',
                            'source_file',
                            'source_row',
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
                            'deleted_at',
                            'updated_at',
                        ]
                    );
                }

                MisoAccomplishment::query()
                    ->where('category', $category)
                    ->where('source_file', $relativeFilePath)
                    ->whereNotIn('source_hash', $seenHashes)
                    ->forceDelete();
            } catch (Throwable $e) {
                Log::warning('MISO CSV sync failed for source file.', [
                    'category' => $category,
                    'source' => $absolutePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return array<string, string>
     */
    protected function sourceFiles(): array
    {
        return [
            MisoAccomplishment::CATEGORY_DATA_MANAGEMENT => base_path('docs/MISO Accomplishment.xlsx - Data Management, Analytics, AI.csv'),
            MisoAccomplishment::CATEGORY_NETWORK => base_path('docs/MISO Accomplishment.xlsx - Network, Cybersec, Tech Supp .csv'),
            MisoAccomplishment::CATEGORY_SYSTEMS_DEVELOPMENT => base_path('docs/MISO Accomplishment.xlsx - Systems Development, QA.csv'),
        ];
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    protected function findHeaderRowIndex(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $normalized = collect($row)
                ->map(fn ($value) => $this->normalizeText((string) ($value ?? '')))
                ->filter()
                ->implode(' | ');

            if (str_contains($normalized, 'project title') && str_contains($normalized, 'project lead')) {
                return (int) $index;
            }
        }

        return null;
    }

    /**
     * @param array<int, mixed> $headers
     * @return array<int, string>
     */
    protected function buildHeaderMap(array $headers): array
    {
        $map = [];

        foreach ($headers as $columnIndex => $header) {
            $normalized = $this->normalizeText((string) $header);
            if ($normalized === '') {
                continue;
            }

            if ($normalized === 'no.' || $normalized === 'no') {
                $map[$columnIndex] = 'record_no';
                continue;
            }

            if (str_contains($normalized, 'project title') || str_contains($normalized, 'project objective')) {
                $map[$columnIndex] = 'project_title';
                continue;
            }

            if (str_contains($normalized, 'project lead')) {
                $map[$columnIndex] = 'project_lead';
                continue;
            }

            if (str_contains($normalized, 'project member')) {
                $map[$columnIndex] = 'project_members';
                continue;
            }

            if (str_contains($normalized, 'budget/cost') || str_contains($normalized, 'budget cost')) {
                $map[$columnIndex] = 'budget_cost';
                continue;
            }

            if (str_contains($normalized, 'implementing unit')) {
                $map[$columnIndex] = 'implementing_unit';
                continue;
            }

            if (str_contains($normalized, 'target activities')) {
                $map[$columnIndex] = 'target_activities';
                continue;
            }

            if (str_contains($normalized, 'intended duration')) {
                $map[$columnIndex] = 'intended_duration';
                continue;
            }

            if (str_contains($normalized, 'start date')) {
                $map[$columnIndex] = 'start_date';
                continue;
            }

            if (str_contains($normalized, 'target end date')) {
                $map[$columnIndex] = 'target_end_date';
                continue;
            }

            if (str_contains($normalized, 'current reporting period') || str_contains($normalized, 'target period')) {
                $map[$columnIndex] = 'reporting_period';
                continue;
            }

            if (str_contains($normalized, 'percentage of completion') || $normalized === 'completion') {
                $map[$columnIndex] = 'completion_percentage';
                continue;
            }

            if (str_contains($normalized, 'overall status') || $normalized === 'status') {
                $map[$columnIndex] = 'overall_status';
                continue;
            }

            if (str_contains($normalized, 'remarks/actual accomplishments') || str_contains($normalized, 'actual accomplishments') || $normalized === 'remarks') {
                $map[$columnIndex] = 'remarks';
            }
        }

        return $map;
    }

    /**
     * @param array<int, mixed> $row
     * @param array<int, string> $headerMap
     * @return array<string, string|null>
     */
    protected function mapRow(array $row, array $headerMap): array
    {
        $record = [
            'record_no' => null,
            'project_title' => null,
            'project_lead' => null,
            'project_members' => null,
            'budget_cost' => null,
            'implementing_unit' => null,
            'target_activities' => null,
            'intended_duration' => null,
            'start_date' => null,
            'target_end_date' => null,
            'reporting_period' => null,
            'completion_percentage' => null,
            'overall_status' => null,
            'remarks' => null,
        ];

        foreach ($headerMap as $columnIndex => $field) {
            $record[$field] = $this->cleanCellValue($row[$columnIndex] ?? null);
        }

        return $record;
    }

    /**
     * @param array<string, string|null> $record
     */
    protected function isRowSkippable(array $record): bool
    {
        $projectTitle = trim((string) ($record['project_title'] ?? ''));
        $projectLead = trim((string) ($record['project_lead'] ?? ''));
        $targetActivities = trim((string) ($record['target_activities'] ?? ''));
        $status = trim((string) ($record['overall_status'] ?? ''));
        $reportingPeriod = trim((string) ($record['reporting_period'] ?? ''));

        if ($projectTitle === '' && $projectLead === '' && $targetActivities === '' && $status === '') {
            return true;
        }

        if ($projectTitle !== '' && $projectLead === '' && $targetActivities === '' && $status === '' && $reportingPeriod === '') {
            $normalizedTitle = strtolower($projectTitle);

            if (str_starts_with($normalizedTitle, 'as of q') || $normalizedTitle === 'student project') {
                return true;
            }
        }

        return false;
    }

    protected function cleanCellValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));

        if ($normalized === '') {
            return null;
        }

        return $normalized;
    }

    protected function normalizeText(string $value): string
    {
        $value = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $value);
        $value = preg_replace('/\s+/', ' ', strtolower(trim($value)));

        return $value ?? '';
    }

    /**
     * @param array<string, string> $sources
     */
    protected function buildSignature(array $sources): ?string
    {
        $parts = [];

        foreach ($sources as $category => $path) {
            if (!is_file($path)) {
                continue;
            }

            $parts[] = $category.':'.filesize($path).':'.filemtime($path);
        }

        if (count($parts) === 0) {
            return null;
        }

        return hash('sha256', implode('|', $parts));
    }
}
