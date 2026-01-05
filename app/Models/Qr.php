<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Qr extends Model
{
    use HasFactory;

    protected $fillable = [
        'qr_code',
        'order_code',
        'product_code',
        'product_name',
        'machine',
        'created_by',
        'current_stage_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(QrStageLog::class);
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'current_stage_id');
    }
}
