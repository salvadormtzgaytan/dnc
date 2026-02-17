<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DncNotificationThreshold extends Model
{
    use HasFactory;
    /**
     * Nombre explícito de la tabla, para no usar la convención dnc_notification_thresholds.
     *
     * @var string
     */
    protected $table = 'notification_thresholds';
    /**
     * Los campos que se pueden asignar masivamente.
     *
     * @var array<string>
     */
    protected $fillable = [
        'dnc_id',
        'days_before',
    ];

    /**
     * Relación inversa a la DNC propietaria de este umbral.
     *
     * @return BelongsTo
     */
    public function dnc(): BelongsTo
    {
        return $this->belongsTo(Dnc::class);
    }
}
