<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DetailDboCrp extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];
    protected $table = 'detail_dbo_crp';
    protected $fillable = [
        'crp_id',
        'month',
        'pi',
        'ca',
        'due_date',
        'remark',
        'check_crp',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function crp()
    {
        return $this->belongsTo(MstDboCrp::class, 'crp_id', 'id');
    }
}
