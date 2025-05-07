<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Testing\Fluent\Concerns\Has;

class TrsDboCrp extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    use HasFactory;
    protected $table = 'trs_dbo_crp';
    protected $fillable = [
        'mst_id',
        'nm_category',
        'detail_activity',
        'no_po',
        'date',
        'qty',
        'price_before',
        'price_after',
        'price_sell',
        'total_cost_before',
        'total_cost_after',
        'total_cost_crp',
    ];
}
