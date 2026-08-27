<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrsLogbookVisits extends Model
{
    use HasFactory;

    protected $table = 'trs_logbook_visits';

    protected $fillable = [
        'id_user',
        'customer_name',
        'keterangan',
        'plan_visit',
        'is_active',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_name', 'name_customer');
    }
}
