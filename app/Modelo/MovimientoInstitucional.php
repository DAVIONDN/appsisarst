<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capa MODELO - Comisiones, rotaciones, licencias y permisos (RF-08 a RF-10).
 *
 * El Sprint 1 lo usa en dos puntos: el historial laboral (HU-18) y la
 * verificacion previa a la desactivacion de un trabajador (DS-HU04:
 * "verifica que el personal no tenga movimientos vigentes").
 */
class MovimientoInstitucional extends Model
{
    public const PENDIENTE = 'PENDIENTE';

    public const APROBADO = 'APROBADO';

    public const RECHAZADO = 'RECHAZADO';

    public const FINALIZADO = 'FINALIZADO';

    /** Color semantico por estado, segun el mockup del Sprint 3. */
    public const COLORES = [
        self::PENDIENTE => 'warning',
        self::APROBADO => 'success',
        self::RECHAZADO => 'danger',
        self::FINALIZADO => 'secondary',
    ];

    protected $table = 'movimiento_institucional';

    protected $primaryKey = 'movimiento_id';

    protected $fillable = [
        'personal_id',
        'tipo_movimiento_id',
        'establecimiento_destino_id',
        'fecha_inicio',
        'fecha_fin',
        'motivo',
        'estado',
        'motivo_rechazo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id', 'personal_id');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoMovimiento::class, 'tipo_movimiento_id', 'tipo_movimiento_id');
    }

    public function establecimientoDestino(): BelongsTo
    {
        return $this->belongsTo(
            Establecimiento::class,
            'establecimiento_destino_id',
            'establecimiento_id'
        );
    }

    public function getColorAttribute(): string
    {
        return self::COLORES[$this->estado] ?? 'secondary';
    }

    public function getDiasAttribute(): int
    {
        if ($this->fecha_inicio === null || $this->fecha_fin === null) {
            return 0;
        }

        return (int) abs($this->fecha_inicio->diffInDays($this->fecha_fin)) + 1;
    }

    /** Movimientos que aun comprometen al trabajador (pendientes o aprobados). */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereIn('estado', [self::PENDIENTE, self::APROBADO]);
    }
}
