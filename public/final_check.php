<?php
header('Content-Type: text/plain');

// PHP Syntax check via eval approach
$filesToCheck = [
    'C:/laragon/www/fastware_adsi_1/app/Services/HR/HRRoleAccessService.php',
    'C:/laragon/www/fastware_adsi_1/app/Http/Controllers/PenilaianTCController.php',
    'C:/laragon/www/fastware_adsi_1/app/Services/HR/JobPositionAccessService.php',
];

echo "=== PHP SYNTAX CHECK ===\n\n";

foreach ($filesToCheck as $file) {
    $basename = basename($file);
    if (!file_exists($file)) {
        echo "  ✗ $basename: FILE NOT FOUND\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $tokens = token_get_all($content, TOKEN_PARSE);
    echo "  ✓ $basename: Syntax OK\n";
}

echo "\n=== VERIFIKASI KONTEN FILE ===\n\n";

// Check HRRoleAccessService
echo "--- HRRoleAccessService.php ---\n";
$content = file_get_contents('C:/laragon/www/fastware_adsi_1/app/Services/HR/HRRoleAccessService.php');
$checks = [
    'FULL_ACCESS_USER_IDS constant defined' => strpos($content, "private const FULL_ACCESS_USER_IDS = [1, 91]") !== false,
    'HRGA/PDCA block removed' => strpos($content, 'Dynamic check: Has HRGA or PDCA') === false,
    'hasFullAccess uses FULL_ACCESS_USER_IDS' => strpos($content, 'self::FULL_ACCESS_USER_IDS') !== false,
    'fullAccessUserIds() method exists' => strpos($content, 'public function fullAccessUserIds()') !== false,
    'No reference to old FULL_ACCESS_USERNAMES' => strpos($content, 'FULL_ACCESS_USERNAMES') === false,
    'No reference to old FULL_ACCESS_USERS' => strpos($content, 'FULL_ACCESS_USERS') === false,
];
foreach ($checks as $label => $passed) {
    echo "  " . ($passed ? '✓' : '✗') . " $label\n";
}

echo "\n=== VERIFIKASI LOGIKA CONTROLLER ===\n";
$controllerContent = file_get_contents('C:/laragon/www/fastware_adsi_1/app/Http/Controllers/PenilaianTCController.php');
$controllerChecks = [
    'abortUnlessCompetencyLevel method exists' => strpos($controllerContent, 'private function abortUnlessCompetencyLevel') !== false,
    'indexTrs has abort guard' => strpos($controllerContent, '$this->abortUnlessCompetencyLevel($level)') !== false,
];
foreach ($controllerChecks as $label => $passed) {
    echo "  " . ($passed ? '✓' : '✗') . " $label\n";
}

echo "\n=== VERIFIKASI LOGIKA ACCESS DI DATABASE ===\n";
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::capture();
$kernel->handle($request);

$service = new \App\Services\HR\HRRoleAccessService();

// Check user ID 1 and 91 have full access
$user1 = \App\Models\User::find(1);
$user91 = \App\Models\User::find(91);
$userOther = \App\Models\User::whereNotIn('id', [1, 91])->first();

if ($user1) {
    $fa1 = $service->hasFullAccess($user1) ? 'YES ✓' : 'NO ✗';
    echo "  User ID 1 ({$user1->name}): hasFullAccess = $fa1\n";
}
if ($user91) {
    $fa91 = $service->hasFullAccess($user91) ? 'YES ✓' : 'NO ✗';
    echo "  User ID 91 ({$user91->name}): hasFullAccess = $fa91\n";
}
if ($userOther) {
    $faOther = $service->hasFullAccess($userOther) ? 'YES ✗' : 'NO ✓';
    echo "  User ID {$userOther->id} ({$userOther->name}): hasFullAccess = $faOther\n";
}
