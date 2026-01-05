<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(QrStageLog::class);
    }

    public function qrsAsCurrentStage(): HasMany
    {
        return $this->hasMany(Qr::class, 'current_stage_id');
    }
}
