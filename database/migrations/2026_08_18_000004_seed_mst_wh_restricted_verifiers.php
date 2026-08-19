<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<int, array{name: string, npk: int}> */
    private const VERIFIERS = [
        ['name' => 'RAGIL ISHA RAHMANTO', 'npk' => 5639],
        ['name' => 'ARY RODJO PRASETYO', 'npk' => 5439],
    ];

    public function up(): void
    {
        $resolved = [];
        $activeValue = (int) config('warehouse.identity.active_user_value', 0);

        foreach (self::VERIFIERS as $definition) {
            $users = DB::table('users')
                ->where('npk', $definition['npk'])
                ->where('is_active', $activeValue)
                ->get(['id']);

            if ($users->count() !== 1) {
                throw new \RuntimeException(sprintf(
                    'Restricted verifier NPK %d harus cocok dengan tepat satu user aktif.',
                    $definition['npk'],
                ));
            }

            $resolved[] = (int) $users->first()->id;
        }

        DB::transaction(function () use ($resolved): void {
            foreach ($resolved as $userId) {
                DB::table('mst_wh_restricted_verifiers')->insertOrIgnore([
                    'user_id' => $userId,
                    'scope' => 'ALL',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        $npks = array_column(self::VERIFIERS, 'npk');
        $userIds = DB::table('users')->whereIn('npk', $npks)->pluck('id');

        DB::table('mst_wh_restricted_verifiers')
            ->whereIn('user_id', $userIds)
            ->where('scope', 'ALL')
            ->delete();
    }
};
