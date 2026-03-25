<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DocumentationController extends Controller
{
    public function index(Request $request)
    {
        $docsPath = base_path('docs/DOCUMENTATION_MD');

        $documents = collect();

        if (File::isDirectory($docsPath)) {
            $documents = collect(File::files($docsPath))
                ->filter(fn ($file) => strtolower($file->getExtension()) === 'md')
                ->sortBy(fn ($file) => strtolower($file->getFilename()), SORT_NATURAL)
                ->map(function ($file) {
                    $fileName = $file->getFilename();
                    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
                    $title = str_replace('_', ' ', $baseName);
                    $title = preg_replace_callback('/^(\d+)([a-z])\b/i', fn ($m) => $m[1] . strtoupper($m[2]), $title);

                    return [
                        'fileName' => $fileName,
                        'title' => $title,
                    ];
                })
                ->values();
        }

        $selected = $request->query('doc');
        $selectedDocument = $documents->firstWhere('fileName', $selected) ?? $documents->first();

        $contentHtml = '';

        if ($selectedDocument) {
            $content = File::get($docsPath . DIRECTORY_SEPARATOR . $selectedDocument['fileName']);
            $contentHtml = Str::markdown($content, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
        }

        return Inertia::render('documentation', [
            'documents' => $documents,
            'activeDocument' => $selectedDocument,
            'contentHtml' => $contentHtml,
        ]);
    }
}
