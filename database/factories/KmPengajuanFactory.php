<?php

namespace Database\Factories;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Models\KmKategori;
use App\Models\KmPengajuan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KmPengajuan>
 */
class KmPengajuanFactory extends Factory
{
    protected $model = KmPengajuan::class;

    public function definition(): array
    {
        return [
            'id_user' => User::factory(),
            'id_km_kategori' => KmKategori::factory(),
            'judul' => fake()->sentence(4),
            'keterangan' => fake()->paragraph(),
            'posisi' => 'All Employee',
            'persetujuan' => KmDocumentStatus::DRAFT->legacyApprovalValue(),
            'status' => KmDocumentStatus::DRAFT->value,
        ];
    }

    public function draft(): static
    {
        return $this->status(KmDocumentStatus::DRAFT);
    }

    public function pending(): static
    {
        return $this->status(KmDocumentStatus::PENDING_APPROVAL);
    }

    public function published(): static
    {
        return $this->status(KmDocumentStatus::PUBLISHED);
    }

    public function inactive(): static
    {
        return $this->status(KmDocumentStatus::INACTIVE);
    }

    private function status(KmDocumentStatus $status): static
    {
        return $this->state(fn (): array => [
            'status' => $status->value,
            'persetujuan' => $status->legacyApprovalValue(),
        ]);
    }
}
