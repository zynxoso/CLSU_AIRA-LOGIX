<?php

namespace App\Services\Extraction;

use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;

class SpreadsheetExtractor
{
    public function extract(string $filePath): array
    {
        $spreadsheet = SpreadsheetIOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return [];
        }

        $rawHeaders = array_map(static fn ($header) => trim((string) $header), array_shift($rows));
        $headerToFieldMap = $this->buildHeaderMap($rawHeaders);

        $records = [];
        foreach ($rows as $row) {
            $record = $this->emptyRecord();
            $hasData = false;

            foreach ($headerToFieldMap as $index => $field) {
                $value = isset($row[$index]) ? trim((string) $row[$index]) : null;
                if ($value === '') {
                    $value = null;
                }

                $record[$field] = $value;
                if ($value !== null) {
                    $hasData = true;
                }
            }

            if ($hasData) {
                $records[] = $record;
            }
        }

        return $records;
    }

    protected function buildHeaderMap(array $headers): array
    {
        $aliases = [
            'control no' => 'control_no',
            'service request control no' => 'control_no',
            'timestamp' => 'timestamp_str',
            'client feedback no' => 'client_feedback_no',
            'name' => 'name',
            'position' => 'position',
            'office unit' => 'office_unit',
            'office/unit' => 'office_unit',
            'contact no' => 'contact_no',
            'date of request' => 'date_of_request',
            'requested date of completion' => 'requested_completion_date',
            'requested completion date' => 'requested_completion_date',
            'type of request' => 'request_type',
            'request type' => 'request_type',
            'location venue' => 'location_venue',
            'location/venue' => 'location_venue',
            'brief description f request please specify' => 'request_description',
            'brief description of request please specify' => 'request_description',
            'description' => 'request_description',
            'received by' => 'received_by',
            'recieve by' => 'received_by',
            'date time' => 'receive_date_time',
            'action taken' => 'action_taken',
            'recommendation conclusion' => 'recommendation_conclusion',
            'status' => 'status',
            'date/time started' => 'date_time_started',
            'date time started' => 'date_time_started',
            'date /time completed' => 'date_time_completed',
            'date/time completed' => 'date_time_completed',
            'date time completed' => 'date_time_completed',
            'conducted by' => 'conducted_by',
            'noted by' => 'noted_by',
        ];

        $map = [];
        foreach ($headers as $index => $header) {
            $normalized = $this->normalizeHeader($header);
            if (isset($aliases[$normalized])) {
                $map[$index] = $aliases[$normalized];
            }
        }

        return $map;
    }

    protected function normalizeHeader(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9\s\/]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', (string) $value);

        return trim((string) $value);
    }

    protected function emptyRecord(): array
    {
        return [
            'control_no' => null,
            'timestamp_str' => null,
            'client_feedback_no' => null,
            'name' => null,
            'position' => null,
            'office_unit' => null,
            'contact_no' => null,
            'date_of_request' => null,
            'requested_completion_date' => null,
            'request_type' => null,
            'location_venue' => null,
            'request_description' => null,
            'received_by' => null,
            'receive_date_time' => null,
            'action_taken' => null,
            'recommendation_conclusion' => null,
            'status' => null,
            'date_time_started' => null,
            'date_time_completed' => null,
            'conducted_by' => null,
            'noted_by' => null,
        ];
    }
}
