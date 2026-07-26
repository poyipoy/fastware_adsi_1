<?php

namespace App\Models;

use App\Enums\KnowledgeManagement\KmDocumentStatus;
use App\Enums\KnowledgeManagement\KmThumbnailStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KmPengajuan extends Model
{
    use HasFactory;

    protected $table = 'km_pengajuans';

    protected $fillable = [
        'id_user',
        'id_km_kategori',
        'judul',
        'keterangan',
        'posisi',
        'sub_kategori',
        'image',
        'file',
        'file_name',
        'file_disk',
        'file_path',
        'file_original_name',
        'file_mime_type',
        'file_size_bytes',
        'file_checksum_sha256',
        'file_migrated_at',
        'persetujuan',
        'status_baca',
        'status',
        'modified_by',
        // Thumbnail pipeline
        'thumbnail_path',
        'thumbnail_status',
        'thumbnail_source_checksum',
        'thumbnail_generated_at',
        'thumbnail_failure_reason',
        // Authoring metadata
        'reading_minutes',
        'draft_revision',
        'autosaved_at',
    ];

    protected $casts = [
        'id_user' => 'integer',
        'id_km_kategori' => 'integer',
        'status' => 'integer',
        'file_size_bytes' => 'integer',
        'file_migrated_at' => 'datetime',
        // Thumbnail pipeline
        'thumbnail_status' => KmThumbnailStatus::class,
        'thumbnail_generated_at' => 'datetime',
        // Authoring metadata
        'reading_minutes' => 'integer',
        'draft_revision' => 'integer',
        'autosaved_at' => 'datetime',
    ];

    public function documentStatus(): ?KmDocumentStatus
    {
        return KmDocumentStatus::tryFrom((int) $this->status);
    }

    public function isPreviewableFile(): bool
    {
        return $this->hasCompletePrivateFileMetadata()
            && in_array($this->file_mime_type, ['application/pdf', 'application/x-pdf'], true);
    }

    public function hasCompletePrivateFileMetadata(): bool
    {
        if ($this->file_disk !== 'km_private') {
            return false;
        }

        if (! is_string($this->file_path)
            || preg_match(
                '#^documents/'.preg_quote((string) $this->getKey(), '#').'/[a-f0-9-]+\.(pdf|ppt|pptx)$#i',
                str_replace('\\', '/', $this->file_path),
            ) !== 1
        ) {
            return false;
        }

        if (! (is_string($this->file_original_name)
            && trim($this->file_original_name) !== ''
            && is_string($this->file_mime_type)
            && trim($this->file_mime_type) !== ''
            && $this->file_size_bytes !== null
            && (int) $this->file_size_bytes >= 0
            && is_string($this->file_checksum_sha256)
            && preg_match('/\A[a-f0-9]{64}\z/i', $this->file_checksum_sha256) === 1
            && $this->file_migrated_at !== null)) {
            return false;
        }

        $extension = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
        $allowedMimes = match ($extension) {
            'pdf' => ['application/pdf', 'application/x-pdf'],
            'ppt' => [
                'application/vnd.ms-powerpoint',
                'application/mspowerpoint',
                'application/powerpoint',
                'application/x-mspowerpoint',
            ],
            'pptx' => [
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'application/zip',
            ],
            default => [],
        };

        return in_array($this->file_mime_type, $allowedMimes, true);
    }

    // Relasi dengan KmTransaksi
    public function kmTransaksi()
    {
        return $this->hasMany(KmTransaksi::class, 'id_km_pengajuan');
    }

    // Relasi dengan User
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relasi dengan User
    public function kmKategori()
    {
        return $this->belongsTo(KmKategori::class, 'id_km_kategori');
    }

    public function kmSukas()
    {
        return $this->hasMany(KmSuka::class, 'id_km_pengajuan');
    }

    public function insights()
    {
        return $this->hasMany(Insight::class, 'id_km_pengajuan');
    }

    public function kmLihatBukus()
    {
        return $this->hasMany(KmLihatBuku::class, 'id_km_pengajuan');
    }

    public function approvalEvents(): HasMany
    {
        return $this->hasMany(KmApprovalEvent::class, 'km_pengajuan_id');
    }

    /**
     * Bookmark untuk dokumen ini.
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(KmBookmark::class, 'km_pengajuan_id');
    }

    /**
     * Cek apakah user tertentu sudah mem-bookmark dokumen ini.
     * Gunakan withExists() atau eager-load untuk efisiensi di listing.
     */
    public function bookmarkedBy(User $user): bool
    {
        return $this->bookmarks()->where('user_id', $user->getKey())->exists();
    }

    /**
     * Tag yang dimiliki dokumen ini (many-to-many via km_document_tag).
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            KmTag::class,
            'km_document_tag',
            'km_pengajuan_id',
            'km_tag_id'
        )->withTimestamps();
    }

    /**
     * Co-author dokumen ini (atribusi saja, bukan grant otorisasi).
     * Relasi ke User melalui km_document_authors.
     */
    public function coAuthors(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'km_document_authors',
            'km_pengajuan_id',
            'user_id'
        )->withTimestamps();
    }
}
