<?php

namespace Tests\Feature\KnowledgeManagement;

use App\Models\KmPengajuan;
use App\Models\User;
use App\Services\KnowledgeManagement\KmFileService;

final class KmDocumentUpdateValidationTest extends KmTestCase
{
    public function test_unmigrated_draft_cannot_be_updated_without_replacement_file(): void
    {
        $owner = $this->owner(4101, 'Legacy Document Owner');
        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'judul' => 'Judul legacy',
            'keterangan' => 'Keterangan legacy',
            'file' => 'legacy.pdf',
            'file_name' => 'Legacy.pdf',
            'file_disk' => null,
            'file_path' => null,
            'file_original_name' => null,
            'file_mime_type' => null,
            'file_size_bytes' => null,
            'file_checksum_sha256' => null,
            'file_migrated_at' => null,
        ]);

        $response = $this->actingAs($owner)
            ->from(route('pengajuanKM'))
            ->put(route('updateKM'), [
                'id' => $document->getKey(),
                'judul' => 'Judul yang tidak boleh tersimpan',
                'keterangan' => 'Keterangan yang tidak boleh tersimpan',
            ]);

        $response->assertRedirect(route('pengajuanKM'))->assertSessionHasErrors('file');

        $document->refresh();
        $this->assertSame('Judul legacy', $document->judul);
        $this->assertSame('Keterangan legacy', $document->keterangan);
        $this->assertNull($document->file_disk);
        $this->assertNull($document->file_path);
    }

    public function test_draft_with_complete_private_metadata_can_update_without_replacing_file(): void
    {
        $owner = $this->owner(4102, 'Private Document Owner');
        $checksum = str_repeat('a', 64);
        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'judul' => 'Judul awal',
            'keterangan' => 'Keterangan awal',
        ]);
        $path = 'documents/'.$document->getKey().'/11111111-1111-1111-1111-111111111111.pdf';
        $document->forceFill([
            'file' => basename($path),
            'file_name' => 'Pedoman.pdf',
            'file_disk' => KmFileService::DISK,
            'file_path' => $path,
            'file_original_name' => 'Pedoman.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => 123,
            'file_checksum_sha256' => $checksum,
            'file_migrated_at' => now(),
        ])->save();

        $response = $this->actingAs($owner)
            ->from(route('pengajuanKM'))
            ->put(route('updateKM'), [
                'id' => $document->getKey(),
                'judul' => 'Judul diperbarui',
                'keterangan' => 'Keterangan diperbarui',
            ]);

        $response->assertRedirect(route('pengajuanKM'))->assertSessionHasNoErrors();

        $document->refresh();
        $this->assertSame('Judul diperbarui', $document->judul);
        $this->assertSame('Keterangan diperbarui', $document->keterangan);
        $this->assertSame(KmFileService::DISK, $document->file_disk);
        $this->assertSame($path, $document->file_path);
        $this->assertSame($checksum, $document->file_checksum_sha256);
    }

    public function test_partial_private_metadata_still_requires_a_replacement_file(): void
    {
        $owner = $this->owner(4103, 'Partial Metadata Owner');
        $document = KmPengajuan::factory()->draft()->for($owner, 'user')->create([
            'judul' => 'Judul partial',
            'keterangan' => 'Keterangan partial',
            'file_disk' => KmFileService::DISK,
            'file_path' => 'documents/4103/partial.pdf',
            'file_original_name' => 'Partial.pdf',
            'file_mime_type' => 'application/pdf',
            'file_size_bytes' => 123,
            'file_checksum_sha256' => null,
            'file_migrated_at' => null,
        ]);

        $response = $this->actingAs($owner)
            ->from(route('pengajuanKM'))
            ->put(route('updateKM'), [
                'id' => $document->getKey(),
                'judul' => 'Judul tidak tersimpan',
                'keterangan' => 'Keterangan tidak tersimpan',
            ]);

        $response->assertRedirect(route('pengajuanKM'))->assertSessionHasErrors('file');
        $this->assertSame('Judul partial', $document->fresh()->judul);
    }

    private function owner(int $id, string $name): User
    {
        return User::factory()->create([
            'id' => $id,
            'name' => $name,
            'role_id' => 4,
        ]);
    }
}
