<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capa MODELO - Registro diario de asistencia (RF-05 a RF-07).
 *
 * El Sprint 1 lo consume solo en modo lectura, dentro del historial
 * laboral del trabajador (HU-18). Su gestion completa es del Sprint 2.
 */
class Asistencia extends Model
{
    public const PUNTUAL = 'PUNTUAL';

    public const TARDANZA = 'TARDANZA';

    public const FALTA = 'FALTA';

    public const JUSTIFICADO = 'JUSTIFICADO';

    /** Color semantico de cada estado (Bootstrap 5.3), segun el diseno UX/UI. */
    public const COLORES = [
        self::PUNTUAL => 'success',
        self::TARDANZA => 'warning',
        self::FALTA => 'danger',
        self::JUSTIFICADO => 'info',
    ];

    protected $table = 'asistencia';

    protected $primaryKey = 'asistencia_id';

    protected $fillable = [
        'personal_id',
        'fecha',
        'hora_entrada',
        'hora_salida',
        'estado',
        'minutos_tardanza',
        'origen',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'minutos_tardanza' => 'integer',
        ];
    }

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id', 'personal_id');
    }

    public function getColorAttribute(): string
    {
        return self::COLORES[$this->estado] ?? 'secondary';
    }

    public function scopeEntreFechas(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        return $query
            ->when($desde, fn (Builder $q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn (Builder $q) => $q->whereDate('fecha', '<=', $hasta));
    }
}
