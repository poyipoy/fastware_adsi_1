<?php

namespace App\Http\Controllers\magang;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use TCPDF;
use ZipArchive;

class ConvertController extends Controller
{
    public function index()
    {
        return view('convert.wordtopdf');
    }

    public function convert(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|mimes:doc,docx|max:10240', // max 10MB per file
        ]);

        $uploadedFiles = $request->file('files');
        $convertedFiles = [];
        $errors = [];

        foreach ($uploadedFiles as $file) {
            try {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $pdfName = $originalName . '.pdf';
                $pdfPath = 'temp/' . $pdfName;

                // Convert Word to PDF
                $this->convertWordToPdf($file->getRealPath(), storage_path('app/public/' . $pdfPath));

                $convertedFiles[] = [
                    'original' => $file->getClientOriginalName(),
                    'pdf' => $pdfName,
                    'path' => $pdfPath,
                ];
            } catch (\Exception $e) {
                $errors[] = 'Error converting ' . $file->getClientOriginalName() . ': ' . $e->getMessage();
            }
        }

        if (count($convertedFiles) > 1) {
            // Create ZIP if multiple files
            $zipName = 'converted_files_' . time() . '.zip';
            $zipPath = storage_path('app/public/temp/' . $zipName);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
                foreach ($convertedFiles as $file) {
                    $zip->addFile(storage_path('app/public/' . $file['path']), $file['pdf']);
                }
                $zip->close();

                // Clean up individual PDFs
                foreach ($convertedFiles as $file) {
                    Storage::delete('public/' . $file['path']);
                }

                return response()->download($zipPath)->deleteFileAfterSend(true);
            } else {
                $errors[] = 'Failed to create ZIP file.';
            }
        } elseif (count($convertedFiles) == 1) {
            // Download single PDF
            $file = $convertedFiles[0];
            return response()->download(storage_path('app/public/' . $file['path']))->deleteFileAfterSend(true);
        }

        // Return with errors if any
        return back()->withErrors($errors)->withInput();
    }

    private function convertWordToPdf($wordPath, $pdfPath)
    {
        // Ensure the directory exists
        $directory = dirname($pdfPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Check if file exists and is readable
        if (!file_exists($wordPath) || !is_readable($wordPath)) {
            throw new \Exception('Word file not found or not readable');
        }

        // Try to load Word document with error handling
        try {
            $phpWord = IOFactory::load($wordPath);
        } catch (\Exception $e) {
            throw new \Exception('Failed to load Word document: ' . $e->getMessage());
        }

        // Create PDF
        $pdf = new TCPDF();
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Laravel App');
        $pdf->SetTitle('Converted PDF');
        $pdf->SetSubject('Word to PDF Conversion');
        $pdf->SetKeywords('PDF, conversion');

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set default monospaced font
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        // Set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);

        // Set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // Set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // Set some language-dependent strings (optional)
        if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
            require_once(dirname(__FILE__).'/lang/eng.php');
            $pdf->setLanguageArray($l);
        }

        $pdf->AddPage();

        // Convert Word content to HTML (simplified)
        $html = $this->wordToHtml($phpWord);

        // Output HTML to PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Close and output PDF document
        $pdf->Output($pdfPath, 'F');
    }

    private function wordToHtml($phpWord)
    {
        $html = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
                    $html .= '<p>';
                    foreach ($element->getElements() as $textElement) {
                        if ($textElement instanceof \PhpOffice\PhpWord\Element\Text) {
                            $text = $textElement->getText();
                            $fontStyle = $textElement->getFontStyle();
                            if ($fontStyle) {
                                $html .= '<span style="';
                                if ($fontStyle->isBold()) $html .= 'font-weight: bold; ';
                                if ($fontStyle->isItalic()) $html .= 'font-style: italic; ';
                                if ($fontStyle->getUnderline()) $html .= 'text-decoration: underline; ';
                                $size = $fontStyle->getSize();
                                if ($size) $html .= 'font-size: ' . $size . 'pt; ';
                                $color = $fontStyle->getColor();
                                if ($color) $html .= 'color: #' . $color . '; ';
                                $html .= '">' . htmlspecialchars($text) . '</span>';
                            } else {
                                $html .= htmlspecialchars($text);
                            }
                        }
                    }
                    $html .= '</p>';
                } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextBreak) {
                    $html .= '<br>';
                }
            }
        }

        return $html;
    }
}
