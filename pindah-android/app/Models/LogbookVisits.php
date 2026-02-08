<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogbookVisits extends Model
{
    use HasFactory;

    protected $table = 'logbook_visits';

    protected $fillable = [
        'id_user',
        'customer_name',
        'new_customer_name',
        'pic_cust',
        'jabatan',
        'visit_result',
        'attachment',
        'location',
        'visit_date',
        'file'
    ];

    /**
     * Get the URL for the logbook visit's attachment.
     *
     * @param  string|null  $value
     * @return string|null
     */
    public function getAttachmentAttribute($value)
    {
        if ($value) {
            // Gunakan asset() helper untuk mendapatkan URL publik
            // Path: public/assets/sales_report/kunjungan/
            return asset('assets/sales_report/kunjungan/' . $value);
        }

        return null;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
