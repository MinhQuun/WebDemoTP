<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrStageLog extends Model
{
    use HasFactory;

    public const STATUS_DONE = 'done';
    public const STATUS_ERROR = 'error';
    public const STATUS_IN_PROGRESS = 'in_progress';

    protected $fillable = [
        'qr_id',
        'stage_id',
        'status',
        'performed_at',
        'performed_by',
        'quantity',
        'note',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function qr(): BelongsTo
    {
        return $this->belongsTo(Qr::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }
}
