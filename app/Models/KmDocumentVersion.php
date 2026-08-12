<?php

namespace App\Models;

use App\Enums\KnowledgeManagement\KmProcessingStatus;
use App\Enums\KnowledgeManagement\KmVersionChangeType;
use App\Enums\KnowledgeManagement\KmVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KmDocumentVersion extends Model
{
    protected $fillable = [
        'km_pengajuan_id',
        'version_major',
        'version_minor',
        'change_type',
        'change_note',
        'version_status',
        'title',
        'synopsis',
        'category_id',
        'audience',
        'reading_minutes',
        'original_disk',
        'original_path',
        'original_name',
        'original_mime_type',
        'original_size_bytes',
        'original_checksum_sha256',
        'normalized_pdf_disk',
        'normalized_pdf_path',
        'normalized_pdf_size_bytes',
        'normalized_pdf_checksum_sha256',
        'page_count',
        'extracted_text',
        'processing_status',
        'antivirus_status',
        'processing_attempts',
        'last_error',
        'next_attempt_at',
        'processing_started_at',
        'processed_at',
        'created_by',
        'approved_by',
        'published_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'version_major' => 'integer',
        'version_minor' => 'integer',
        'change_type' => KmVersionChangeType::class,
        'version_status' => KmVersionStatus::class,
        'reading_minutes' => 'integer',
        'original_size_bytes' => 'integer',
        'normalized_pdf_size_bytes' => 'integer',
        'page_count' => 'integer',
        'processing_status' => KmProcessingStatus::class,
        'processing_attempts' => 'integer',
        'next_attempt_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processed_at' => 'datetime',
        'published_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(KmPengajuan::class, 'km_pengajuan_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KmKategori::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            KmTag::class,
            'km_document_version_tags',
            'document_version_id',
            'km_tag_id',
        )->withTimestamps();
    }

    public function coAuthors(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'km_document_version_authors',
            'document_version_id',
            'user_id',
        )->withTimestamps();
    }

    public function targetDepartments(): BelongsToMany
    {
        return $this->belongsToMany(
            MstDepartment::class,
            'km_document_version_departments',
            'document_version_id',
            'department_id',
        )->withTimestamps();
    }

    public function targetJobPositions(): BelongsToMany
    {
        return $this->belongsToMany(
            MstJobPosition::class,
            'km_document_version_job_positions',
            'document_version_id',
            'job_position_id',
        )->withTimestamps();
    }

    public function number(): string
    {
        return $this->version_major.'.'.$this->version_minor;
    }

    public function isReady(): bool
    {
        return $this->processing_status === KmProcessingStatus::READY
            && is_string($this->normalized_pdf_path)
            && $this->normalized_pdf_path !== ''
            && is_string($this->normalized_pdf_checksum_sha256)
            && preg_match('/\A[a-f0-9]{64}\z/i', $this->normalized_pdf_checksum_sha256) === 1;
    }

    public function hasOriginalFile(): bool
    {
        return $this->original_disk === 'km_private'
            && is_string($this->original_path)
            && $this->original_path !== ''
            && is_string($this->original_checksum_sha256)
            && preg_match('/\A[a-f0-9]{64}\z/i', $this->original_checksum_sha256) === 1;
    }
}
