<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$inputFileType = 'Xlsx';
$inputFileName = __DIR__ . '/ADASI_Mapping_JobPosition_Section_Dept.xlsx';

try {
    $spreadsheet = IOFactory::load($inputFileName);
    $data = [];
    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
        $sheetName = $worksheet->getTitle();
        $data[$sheetName] = $worksheet->toArray(null, true, true, true);
    }
    
    echo json_encode($data, JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
