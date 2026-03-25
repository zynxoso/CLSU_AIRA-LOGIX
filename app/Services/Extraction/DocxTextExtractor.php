<?php

namespace App\Services\Extraction;

use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class DocxTextExtractor
{
    public function extractText(string $path): string
    {
        $phpWord = WordIOFactory::load($path);
        $text = "";

        foreach ($phpWord->getSections() as $section) {
            $text .= $this->extractElementsText($section->getElements());
        }

        return $text;
    }

    protected function extractElementsText(array $elements): string
    {
        $text = "";
        foreach ($elements as $element) {
            if (method_exists($element, 'getText')) {
                $text .= $element->getText() . " ";
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                foreach ($element->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $text .= $this->extractElementsText($cell->getElements()) . " | ";
                    }
                    $text .= "\n";
                }
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                foreach ($element->getElements() as $textElement) {
                    if (method_exists($textElement, 'getText')) {
                        $text .= $textElement->getText();
                    }
                }
                $text .= " ";
            } elseif (method_exists($element, 'getElements')) {
                $text .= $this->extractElementsText($element->getElements());
            }
        }
        return $text;
    }
}
