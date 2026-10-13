<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const YUSUF_NPK = 5472;

    public function up(): void
    {
        $activeValue = (int) config('warehouse.identity.active_user_value', 0);
        $user = DB::table('users')
            ->where('npk', self::YUSUF_NPK)
            ->where('is_active', $activeValue)
            ->first(['id']);

        if ($user !== null) {
            DB::table('mst_wh_restricted_verifiers')->insertOrIgnore([
                'user_id' => (int) $user->id,
                'scope' => 'ALL',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $user = DB::table('users')->where('npk', self::YUSUF_NPK)->first(['id']);
        if ($user !== null) {
            DB::table('mst_wh_restricted_verifiers')
                ->where('user_id', (int) $user->id)
                ->where('scope', 'ALL')
                ->delete();
        }
    }
};
