<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('name', 'like', '%LINA UNIARSIH%')->first();
if ($user) {
    echo "User ID: " . $user->id . "\n";
    echo "Name: " . $user->name . "\n";
    echo "Job Position ID in users table: " . $user->job_position_id . "\n";
    
    $jp = \App\Models\MstJobPosition::find($user->job_position_id);
    echo "Job Position Name: " . ($jp ? $jp->position_name : 'None') . "\n";

    // check user_job_positions
    $ujps = \Illuminate\Support\Facades\DB::table('user_job_positions')->where('user_id', $user->id)->get();
    echo "user_job_positions entries for Lina:\n";
    foreach ($ujps as $ujp) {
        $jp2 = \App\Models\MstJobPosition::find($ujp->mst_job_position_id);
        echo " - Job Position ID: " . $ujp->mst_job_position_id . " -> " . ($jp2 ? $jp2->position_name : 'Unknown') . "\n";
    }

    // check TrsPenilaianTc
    $penilaianCount = \App\Models\TrsPenilaianTc::where('id_user', $user->id)->count();
    echo "Total TrsPenilaianTc records for Lina: " . $penilaianCount . "\n";

} else {
    echo "LINA UNIARSIH not found.\n";
}
