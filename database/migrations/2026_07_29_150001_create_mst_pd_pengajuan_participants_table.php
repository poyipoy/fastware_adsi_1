<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_pd_pengajuan_participants', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('people_development_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('people_development_id', 'pd_participants_parent_fk')
                ->references('id')->on('mst_pd_pengajuans')->cascadeOnDelete();
            $table->foreign('user_id', 'pd_participants_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['people_development_id', 'user_id'], 'pd_participants_parent_user_unique');
            $table->index('user_id', 'pd_participants_user_index');
        });

        $backfillable = DB::table('mst_pd_pengajuans as pd')
            ->join('users', 'users.id', '=', 'pd.id_user')
            ->where('pd.is_sharing_knowledge', 1)
            ->whereNotNull('pd.id_user')
            ->get(['pd.id', 'pd.id_user']);

        if ($backfillable->isNotEmpty()) {
            $now = now();
            DB::table('mst_pd_pengajuan_participants')->insert(
                $backfillable->map(fn ($row) => [
                    'people_development_id' => $row->id,
                    'user_id' => $row->id_user,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all(),
            );
        }

        $unresolved = DB::table('mst_pd_pengajuans as pd')
            ->leftJoin('users', 'users.id', '=', 'pd.id_user')
            ->where('pd.is_sharing_knowledge', 1)
            ->where(fn ($query) => $query->whereNull('pd.id_user')->orWhereNull('users.id'))
            ->pluck('pd.id')
            ->all();

        if ($unresolved !== []) {
            Log::warning('Sharing Knowledge legacy tidak dapat diinferensikan participant-nya.', [
                'people_development_ids' => $unresolved,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mst_pd_pengajuan_participants');
    }
};
