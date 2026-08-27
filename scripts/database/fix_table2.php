<?php
// Script to add objective_learning and sharing_knowledge textareas to Table 2 block
$filepath = 'c:\laragon\www\fastware_adsi_1\resources\views\people_development\edit_develop_hrga.blade.php';
$content = file_get_contents($filepath);
$lines = explode("\n", $content);
$total = count($lines);

echo "Total lines: $total<br>\n";

// The new content to insert 
$newBlock = '                                             <div class="col-12">
                                                 <div class="card border-0 bg-light rounded-3 mt-2">
                                                     <div class="card-body p-2">
                                                         <h6 class="small fw-bold text-muted mb-2"><i class="bi bi-lightbulb-fill me-1 text-warning"></i>Tindak Lanjut Pasca Training</h6>
                                                         <div class="row g-2">
                                                             <div class="col-md-6">
                                                                 <div class="form-floating">
                                                                     <textarea class="form-control form-control-sm" id="sharing_knowledge_${item.id}" name="sharing_knowledge[]" placeholder="Sharing Knowledge" style="height:80px;">${item.sharing_knowledge || \'\'}</textarea>
                                                                     <label class="small"><i class="bi bi-people me-1"></i>Sharing Knowledge</label>
                                                                 </div>
                                                             </div>
                                                             <div class="col-md-6">
                                                                 <div class="form-floating">
                                                                     <textarea class="form-control form-control-sm" id="objective_learning_${item.id}" name="objective_learning[]" placeholder="Objective Learning" style="height:80px;">${item.objective_learning || \'\'}</textarea>
                                                                     <label class="small"><i class="bi bi-bullseye me-1"></i>Objective Learning</label>
                                                                 </div>
                                                             </div>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>';

// Find all lines containing 'col-12 mt-3 d-flex justify-content-end gap-2'
$pattern = 'col-12 mt-3 d-flex justify-content-end gap-2';
$occurrences = [];
foreach ($lines as $i => $line) {
    if (strpos($line, $pattern) !== false) {
        $occurrences[] = $i;
        echo "Found pattern at line: " . ($i+1) . "<br>\n";
    }
}

echo "Total occurrences: " . count($occurrences) . "<br>\n";

if (count($occurrences) >= 2) {
    $insertBefore = $occurrences[1]; // Insert before second occurrence
    echo "Inserting before line: " . ($insertBefore + 1) . "<br>\n";
    
    // Insert new lines
    array_splice($lines, $insertBefore, 0, explode("\n", $newBlock));
    
    $newContent = implode("\n", $lines);
    file_put_contents($filepath, $newContent);
    
    echo "<strong>SUCCESS!</strong> Inserted block before original line " . ($insertBefore + 1) . "<br>\n";
    echo "New total lines: " . count($lines) . "<br>\n";
} elseif (count($occurrences) === 1) {
    echo "ERROR: Only 1 occurrence found. Context:<br>\n";
    $idx = $occurrences[0];
    for ($j = max(0, $idx-5); $j <= min($total-1, $idx+5); $j++) {
        echo ($j+1) . ": " . htmlspecialchars(substr($lines[$j], 0, 120)) . "<br>\n";
    }
} else {
    echo "ERROR: Pattern not found! Let's check around line 880:<br>\n";
    for ($j = 875; $j < 892 && $j < $total; $j++) {
        echo ($j+1) . ": " . htmlspecialchars(substr($lines[$j], 0, 150)) . "<br>\n";
    }
}
