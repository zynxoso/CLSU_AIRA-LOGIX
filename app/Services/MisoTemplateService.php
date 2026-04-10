<?php

namespace App\Services;

use App\Models\MisoAccomplishment;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class MisoTemplateService
{
    /**
     * @return array<int, string>
     */
    public static function requiredTemplatePlaceholders(): array
    {
        return array_keys(self::requiredPlaceholderAliases());
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected static function requiredPlaceholderAliases(): array
    {
        return [
            'project_title' => ['project_title', 'project_objective', 'project_objective_feature'],
            'project_lead' => ['project_lead'],
            'implementing_unit' => ['implementing_unit', 'unit_office'],
            'target_activities' => ['target_activities'],
            'intended_duration' => ['intended_duration', 'project_duration'],
            'start_date' => ['start_date', 'project_start_date'],
            'target_end_date' => ['target_end_date'],
            'reporting_period' => ['reporting_period', 'target_period', 'current_reporting_period'],
            'completion_percentage' => ['completion_percentage', 'actual_accomplishments'],
            'overall_status' => ['overall_status', 'status', 'status_text'],
            'remarks' => ['remarks', 'remarks_next_steps', 'additional_description_remarks'],
            'status_on_track' => ['status_on_track'],
            'status_on_delayed' => ['status_on_delayed', 'status_delayed'],
            'status_on_completed' => ['status_on_completed', 'status_completed'],
            'status_on_cancelled' => ['status_on_cancelled', 'status_cancelled'],
        ];
    }

    protected static function normalizeTemplateDocumentXml(string $xml): string
    {
        $xml = preg_replace('/<w:proofErr[^>]*\/>/', '', $xml) ?? $xml;

        // Word sometimes splits these status placeholder names into adjacent runs.
        $xml = preg_replace(
            '/status_on_<\/w:t>\s*<\/w:r>\s*<w:r[^>]*>\s*<w:rPr>.*?<\/w:rPr>\s*<w:t>(delayed|completed|cancelled)<\/w:t>/s',
            'status_on_$1</w:t>',
            $xml
        ) ?? $xml;

        // Convert legacy $(name) placeholders into ${name} placeholders.
        $xml = preg_replace('/<w:t>\$\(<\/w:t>(.*?)<w:t>\)<\/w:t>/s', '<w:t>${</w:t>$1<w:t>}</w:t>', $xml) ?? $xml;

        // Fix mixed syntax ${name) by correcting the closing marker.
        $xml = preg_replace('/<w:t>\$\{<\/w:t>(.*?)<w:t>\)<\/w:t>/s', '<w:t>${</w:t>$1<w:t>}</w:t>', $xml) ?? $xml;

        return $xml;
    }

    /**
     * @return array{0: string, 1: bool}
     */
    protected static function normalizedTemplatePath(string $templatePath): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($templatePath) !== true) {
            return [$templatePath, false];
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!is_string($documentXml) || $documentXml === '') {
            return [$templatePath, false];
        }

        $normalizedXml = self::normalizeTemplateDocumentXml($documentXml);
        if ($normalizedXml === $documentXml) {
            return [$templatePath, false];
        }

        $tempPath = storage_path('app/miso_template_normalized_'.uniqid('', true).'.docx');
        if (!copy($templatePath, $tempPath)) {
            return [$templatePath, false];
        }

        $tempZip = new \ZipArchive();
        if ($tempZip->open($tempPath) !== true) {
            @unlink($tempPath);
            return [$templatePath, false];
        }

        $tempZip->addFromString('word/document.xml', $normalizedXml);
        $tempZip->close();

        return [$tempPath, true];
    }

    /**
     * @param array<int, string> $variables
     * @return array<int, string>
     */
    protected static function malformedTemplateVariables(array $variables): array
    {
        return array_values(array_filter($variables, static function (string $variable): bool {
            return str_contains($variable, '<')
                || str_contains($variable, '>')
                || str_contains($variable, '</w:')
                || str_contains($variable, '/w:')
                || str_contains($variable, '${');
        }));
    }

    /**
     * @param array<int, string> $variables
     * @return array<int, string>
     */
    protected static function missingRequiredPlaceholders(array $variables): array
    {
        $available = array_flip($variables);
        $missing = [];

        foreach (self::requiredPlaceholderAliases() as $canonical => $aliases) {
            $found = false;

            foreach ($aliases as $alias) {
                if (isset($available[$alias])) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $missing[] = $canonical;
            }
        }

        return $missing;
    }

    /**
     * @param array<int, string> $placeholders
     */
    protected static function placeholdersAsWordMacros(array $placeholders): string
    {
        return implode(', ', array_map(
            static fn (string $placeholder): string => '${'.$placeholder.'}',
            $placeholders
        ));
    }

    /**
     * @return array<string, string>
     */
    protected static function templateValues(MisoAccomplishment $record): array
    {
        $status = strtolower(trim((string) ($record->overall_status ?? '')));
        $statusOnTrack = str_contains($status, 'on track');
        $statusDelayed = str_contains($status, 'delay');
        $statusCompleted = str_contains($status, 'complete');
        $statusCancelled = str_contains($status, 'cancel') || str_contains($status, 'on-hold') || str_contains($status, 'on hold');

        $recordNo = $record->record_no ?: 'N/A';
        $projectTitle = $record->project_title ?: 'N/A';
        $projectLead = $record->project_lead ?: 'N/A';
        $projectMembers = $record->project_members ?: 'N/A';
        $budgetCost = $record->budget_cost ?: 'N/A';
        $implementingUnit = $record->implementing_unit ?: 'N/A';
        $targetActivities = $record->target_activities ?: 'N/A';
        $intendedDuration = $record->intended_duration ?: 'N/A';
        $startDate = $record->start_date ?: 'N/A';
        $targetEndDate = $record->target_end_date ?: 'N/A';
        $reportingPeriod = $record->reporting_period ?: 'N/A';
        $completionPercentage = $record->completion_percentage ?: 'N/A';
        $overallStatus = $record->overall_status ?: 'N/A';
        $remarks = $record->remarks ?: 'N/A';

        return [
            'record_no' => $recordNo,
            'project_title' => $projectTitle,
            'project_objective' => $projectTitle,
            'project_objective_feature' => $projectTitle,
            'project_lead' => $projectLead,
            'project_members' => $projectMembers,
            'budget_cost' => $budgetCost,
            'implementing_unit' => $implementingUnit,
            'unit_office' => $implementingUnit,
            'target_activities' => $targetActivities,
            'intended_duration' => $intendedDuration,
            'project_duration' => $intendedDuration,
            'start_date' => $startDate,
            'project_start_date' => $startDate,
            'target_end_date' => $targetEndDate,
            'reporting_period' => $reportingPeriod,
            'target_period' => $reportingPeriod,
            'current_reporting_period' => $reportingPeriod,
            'completion_percentage' => $completionPercentage,
            'actual_accomplishments' => $completionPercentage,
            'overall_status' => $overallStatus,
            'status' => $overallStatus,
            'status_text' => $overallStatus,
            'remarks' => $remarks,
            'remarks_next_steps' => $remarks,
            'additional_description_remarks' => $remarks,
            'status_on_track' => $statusOnTrack ? '☑' : '☐',
            'status_on_delayed' => $statusDelayed ? '☑' : '☐',
            'status_delayed' => $statusDelayed ? '☑' : '☐',
            'status_on_completed' => $statusCompleted ? '☑' : '☐',
            'status_completed' => $statusCompleted ? '☑' : '☐',
            'status_on_cancelled' => $statusCancelled ? '☑' : '☐',
            'status_cancelled' => $statusCancelled ? '☑' : '☐',
        ];
    }

    public function generateDocx(MisoAccomplishment $record): string
    {
        $templatePath = base_path('docs/MISO-ICT-PROJECT-STATUS-REPORT-FORM.docx');

        if (!file_exists($templatePath)) {
            throw new \RuntimeException('MISO master template not found at: '.$templatePath);
        }

        [$resolvedTemplatePath, $isTemporaryTemplate] = self::normalizedTemplatePath($templatePath);

        try {
            $template = new TemplateProcessor($resolvedTemplatePath);
            $variables = array_values(array_unique($template->getVariables()));

            if (count($variables) === 0) {
                $required = self::placeholdersAsWordMacros(self::requiredTemplatePlaceholders());

                throw new \RuntimeException(
                    'MISO master template has no placeholders. Add these placeholders to the DOCX: '.$required
                );
            }

            $malformedVariables = self::malformedTemplateVariables($variables);
            if (count($malformedVariables) > 0) {
                throw new \RuntimeException(
                    'MISO master template has malformed placeholders. Ensure placeholders use exact ${name} syntax, are not split across formatting runs, and each has a closing brace `}`.'
                );
            }

            $missingRequired = self::missingRequiredPlaceholders($variables);
            if (count($missingRequired) > 0) {
                throw new \RuntimeException(
                    'MISO master template is missing placeholders: '
                    .self::placeholdersAsWordMacros($missingRequired)
                    .'. Use exact ${...} names (not $(...)).'
                );
            }

            $available = array_flip($variables);
            $resolvedValues = [];

            foreach (self::templateValues($record) as $placeholder => $value) {
                if (isset($available[$placeholder])) {
                    $resolvedValues[$placeholder] = $value;
                }
            }

            $template->setValues($resolvedValues);

            $safeTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', (string) ($record->project_title ?: 'miso_project'));
            $fileName = $safeTitle.'_MISO_Report_'.$record->id.'.docx';

            Storage::disk('public')->makeDirectory('reports/miso');
            $outputPath = Storage::disk('public')->path('reports/miso/'.$fileName);
            $template->saveAs($outputPath);

            return $fileName;
        } finally {
            if ($isTemporaryTemplate && file_exists($resolvedTemplatePath)) {
                @unlink($resolvedTemplatePath);
            }
        }
    }
}
