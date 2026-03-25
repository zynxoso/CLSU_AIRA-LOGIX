<?php

namespace App\Services;

use App\Models\IctServiceRequest;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class LogSyncService
{
    /**
     * Mapping of DB columns to Spreadsheet headers
     */
    protected $mapping = [
        'Service Request Control No.'   => 'control_no',
        'Timestamp'                    => 'timestamp_str',
        'Name'                         => 'name',
        'Position'                     => 'position',
        'Office/Unit'                  => 'office_unit',
        'Contact No.'                  => 'contact_no',
        'Date of Request'              => 'date_of_request',
        'Requested Date of Completion' => 'requested_completion_date',
        'Type of Request'              => 'request_type',
        'Location/Venue'               => 'location_venue',
        'Brief Description f Request(Please Specify)' => 'request_description',
        'Recieve By'                   => 'received_by',
        'Date/Time'                    => 'receive_date_time',
        'Action Taken'                 => 'action_taken',
        'Recommendation/Conclusion'    => 'recommendation_conclusion',
        'Client Feedback No.'          => 'client_feedback_no',
        'DATE/TIME STARTED'            => 'date_time_started',
        'DATE /TIME COMPLETED'         => 'date_time_completed',
        'Conducted By'                 => 'conducted_by',
        'Noted By'                     => 'noted_by',
        'Status'                       => 'status',
    ];

    /**
     * Import data from a CSV/Excel file
     */
    public function import($filePath)
    {
        Log::info("LogSyncService: Starting spreadsheet import.");
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        Log::info("LogSyncService: Spreadsheet loaded. Found " . count($rows) . " raw rows.");

        $headers = array_shift($rows);
        $headerMap = [];

        foreach ($headers as $index => $label) {
            $label = trim($label);
            if (isset($this->mapping[$label])) {
                $headerMap[$index] = $this->mapping[$label];
            }
        }
        
        Log::info("LogSyncService: Header mapping complete. Found " . count($headerMap) . " valid columns.");

        $importedCount = 0;
        foreach ($rows as $rowIndex => $row) {
            $data = [];
            $hasData = false;

            foreach ($headerMap as $index => $dbField) {
                $val = $row[$index] ?? null;
                $data[$dbField] = $val;
                if (!empty($val)) $hasData = true;
            }

            if ($hasData) {
                // Avoid matching on encrypted fields; dedupe only by plaintext identifiers.
                if (!empty($data['control_no'])) {
                    $identifier = ['control_no' => $data['control_no']];
                    IctServiceRequest::updateOrCreate($identifier, $data);
                } elseif (!empty($data['timestamp_str']) || !empty($data['client_feedback_no'])) {
                    $identifier = [
                        'timestamp_str' => $data['timestamp_str'] ?? null,
                        'client_feedback_no' => $data['client_feedback_no'] ?? null,
                    ];
                    IctServiceRequest::updateOrCreate($identifier, $data);
                } else {
                    IctServiceRequest::create($data);
                }

                $importedCount++;

                if ($importedCount % 50 === 0) {
                    Log::info("LogSyncService: Progress: processed {$importedCount} records...");
                }
            }
        }

        Log::info("LogSyncService: Import finished. Successfully processed {$importedCount} records.");
        return $importedCount;
    }

    /**
     * Export data to a formatted file (The "Vice-Versa" log)
     * Supports 'xlsx' and 'csv'
     */
    public function export($records, $format = 'xlsx')
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = array_keys($this->mapping);
        $sheet->fromArray($headers, NULL, 'A1');

        $requests = $records;
        $rowNumber = 2;

        foreach ($requests as $request) {
            $rowData = [];
            foreach ($this->mapping as $header => $field) {
                $rowData[] = $request->$field;
            }
            $sheet->fromArray($rowData, NULL, 'A' . $rowNumber);
            $rowNumber++;
        }

        $extension = ($format === 'csv') ? 'csv' : 'xlsx';
        $fileName = 'Shared_ICT_Logs_' . date('Y-m-d') . '.' . $extension;
        
        // Ensure directory exists via Storage
        Storage::disk('public')->makeDirectory('');

        $path = Storage::disk('public')->path($fileName);
        
        $writer = ($format === 'csv') 
            ? \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Csv')
            : new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $writer->save($path);

        return $fileName;
    }
}
