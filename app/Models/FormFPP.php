<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FormFPP extends Model
{
    protected $table = 'form_f_p_p_s';
    use HasFactory;
    protected $fillable = [
        'id', 'id_fpp', 'pemohon', 'tanggal',
        'mesin', 'section', 'lokasi',
        'kendala', 'gambar', 'status', 'status_2', 'status_catatan'
    ];

    public function mesins()
    {
        return $this->hasMany(Mesin::class, 'id_fpp', 'id_fpp')->where('status', 'closed');
    }

    protected $dates = ['created_at', 'updated_at'];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d | H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d | H:i:s');
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

    public function tindaklanjuts()
    {
        return $this->hasMany(TindakLanjut::class, 'id_fpp', 'id_fpp');
    }

    public function tindaklanjut()
    {
        return $this->hasMany(TindakLanjut::class, 'id_fpp', 'id_fpp');
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
