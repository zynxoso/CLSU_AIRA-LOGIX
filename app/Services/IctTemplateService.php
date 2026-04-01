<?php

namespace App\Services;

use App\Models\IctServiceRequest;
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IctTemplateService
{
    /**
     * Generate a filled DOCX from entry
     */
    public function generateDocx(IctServiceRequest $request)
    {
        Log::info("IctTemplateService: Generating DOCX for Request ID: " . ($request->control_no ?? $request->id));
        $templatePath = base_path('docs/ICT Service Request Form.docx');
        
        if (!file_exists($templatePath)) {
            Log::error("IctTemplateService: Master template MISSING at [{$templatePath}]");
            throw new \Exception("Master Template not found at: " . $templatePath);
        }

        Log::info("IctTemplateService: Loading TemplateProcessor.");
        $templateProcessor = new TemplateProcessor($templatePath);

        // Map database fields to Word placeholders
        // Note: You must update the .docx file with these ${tag} placeholders
        $templateProcessor->setValues([
            'control_no'    => $request->control_no ?: '________________',
            'timestamp'     => $request->timestamp_str ?: '________________',
            'client_name'   => $request->name ?: '________________',
            'position'      => $request->position ?: '________________',
            'office'        => $request->office_unit ?: '________________',
            'contact'       => $request->contact_no ?: '________________',
            'date_req'      => $request->date_of_request ? \Carbon\Carbon::parse($request->date_of_request)->format('Y-m-d') : '________________',
            'date_comp'     => $request->requested_completion_date ? \Carbon\Carbon::parse($request->requested_completion_date)->format('Y-m-d') : '________________',
            'venue'         => $request->location_venue ?: '________________',
            'description'   => $request->request_description ?: '________________',
            'received_by'   => $request->received_by ?: '________________',
            'received_at'   => $request->receive_date_time ? \Carbon\Carbon::parse($request->receive_date_time)->format('Y-m-d h:i A') : '________________',
            'action'        => $request->action_taken ?: '________________',
            'recommendation' => $request->recommendation_conclusion ?: '________________',
            'feedback_no'   => $request->client_feedback_no ?: '________________',
            'start_time'    => $request->date_time_started ? \Carbon\Carbon::parse($request->date_time_started)->format('Y-m-d h:i A') : '________________',
            'end_time'      => $request->date_time_completed ? \Carbon\Carbon::parse($request->date_time_completed)->format('Y-m-d h:i A') : '________________',
            'conducted_by'  => $request->conducted_by ?: '________________',
            'noted_by'      => $request->noted_by ?: '________________',
        ]);

        // Checkbox Logic for "Type of Request"
        // Use exact matching to prevent substring collisions (e.g. "Network" inside "ICT Technical Support")
        $requestType = trim($request->request_type ?? '');
        
        $templateProcessor->setValue('type_ts',    $requestType === 'ICT Technical Support'       ? '☑' : '☐');
        $templateProcessor->setValue('type_sd',    $requestType === 'System Development/Enhancement' ? '☑' : '☐');
        $templateProcessor->setValue('type_ni',    $requestType === 'Network/Internet Connection'  ? '☑' : '☐');
        $templateProcessor->setValue('type_other', $requestType === 'Others'                       ? '☑' : '☐');

        // Checkbox Logic for "Status" (Bottom of your form)
        $status = $request->status ?? '';
        $templateProcessor->setValue('status_open', stripos($status, 'Open') !== false ? '☑' : '☐');
        $templateProcessor->setValue('status_ip', stripos($status, 'Progress') !== false ? '☑' : '☐');
        $templateProcessor->setValue('status_res', stripos($status, 'Resolved') !== false ? '☑' : '☐');
        $templateProcessor->setValue('status_esc', stripos($status, 'Escalated') !== false ? '☑' : '☐');

        $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->name ?: 'Unknown');
        $fileName = $safeName . '_ICT_Form_' . ($request->control_no ?? $request->id) . '.docx';
        
        // Ensure directory exists via Storage
        Storage::disk('public')->makeDirectory('reports');
        $outputPath = Storage::disk('public')->path('reports/' . $fileName);


        $templateProcessor->saveAs($outputPath);

        return $fileName;
    }

    /**
     * Generate DOCX files for many requests and pack them into one ZIP.
     */
    public function generateBulkDocxZip(Collection $requests): string
    {
        if ($requests->isEmpty()) {
            throw new \Exception('No records available for DOCX export.');
        }

        $reportsDir = Storage::disk('public')->path('reports');
        Storage::disk('public')->makeDirectory('reports');


        $timestamp = now()->format('Ymd_His');
        $zipFileName = "ICT_Forms_Bulk_{$timestamp}.zip";
        $zipPath = Storage::disk('public')->path($zipFileName);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \Exception('Failed to create DOCX ZIP archive.');
        }

        foreach ($requests as $request) {
            $docxFileName = $this->generateDocx($request);
            $docxFullPath = $reportsDir . DIRECTORY_SEPARATOR . $docxFileName;

            if (file_exists($docxFullPath)) {
                // Use requester name for the filename inside the ZIP
                // Sanitize name for filesystem
                $safeName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->name ?: 'Unknown');
                $zipInternalName = $safeName . '_' . ($request->control_no ?? $request->id) . '.docx';
                
                $zip->addFile($docxFullPath, $zipInternalName);
            }
        }

        $zip->close();

        return $zipFileName;
    }
}
