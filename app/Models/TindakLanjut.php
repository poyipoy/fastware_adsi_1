<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class TindakLanjut extends Model
{
    use HasFactory;
    protected $table = 'tindak_lanjuts';
    protected $fillable = [
        'id', 'id_fpp', 'tindak_lanjut', 'due_date', 'schedule_pengecekan', 'pic',
        'attachment_file', 'note', 'status'
    ];

    public function formFpp()
    {
        return $this->belongsTo(FormFpp::class, 'id_fpp', 'id_fpp');
    }

    public function getStatusBackgroundColorAttribute()
    {
        $status = strtolower($this->attributes['status']);

        switch ($status) {
            case '0':
                return 'red'; // Mengubah warna menjadi 'green' untuk status 0 (Open)
            case '1':
                return 'orange'; // Mengubah warna menjadi 'orange' untuk status 1 (On Progress)
            case '2':
                return 'cyan'; // Mengubah warna menjadi 'blue' untuk status 2 (Finish)
            case '3':
                return 'green'; // Mengubah warna menjadi 'black' untuk status 3 (Closed)
            default:
                return 'transparent'; // Mengembalikan 'transparent' untuk nilai lain
        }
    }

    public function ubahText()
    {
        $status = $this->attributes['status'];

        switch ($status) {
            case '0':
                return 'Open';
            case '1':
                return 'On Progress';
            case '2':
                return 'Finish';
            case '3':
                return 'Closed';
            default:
                return 'Unknown';
        }
    }

    public function getAttachmentButtonClass()
    {
        $fileExtension = pathinfo($this->attachment_file, PATHINFO_EXTENSION);
        switch ($fileExtension) {
            case 'png':
            case 'jpg':
            case 'jpeg':
            case 'gif':
                return 'btn btn-dark';
            case 'pdf':
                return 'btn btn-danger';
            case 'doc':
            case 'docx':
                return 'btn btn-primary';
            case 'xls':
            case 'xlsx':
                return 'btn btn-success';
            default:
                return 'btn btn-secondary';
        }
    }

    public function getAttachmentButtonIcon()
    {
        $fileExtension = pathinfo($this->attachment_file, PATHINFO_EXTENSION);
        switch ($fileExtension) {
            case 'png':
            case 'jpg':
            case 'jpeg':
            case 'gif':
                return 'bi bi-image';
            case 'pdf':
                return 'bi bi-file-earmark-pdf';
            case 'doc':
            case 'docx':
                return 'bi bi-file-earmark-word';
            case 'xls':
            case 'xlsx':
                return 'bi bi-file-earmark-spreadsheet';
            default:
                return 'bi bi-file-earmark';
        }
    }
}
