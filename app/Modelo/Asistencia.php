<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capa MODELO - Registro diario de asistencia (RF-05 a RF-07).
 *
 * Nucleo del Sprint 2. La tardanza y la falta NO son entidades aparte:
 * son estados de la jornada, tal como corrigio la observacion H-02 de la
 * revision tecnica. Por eso hay un unico registro por trabajador y dia,
 * garantizado por el indice uq_asistencia_personal_fecha.
 *
 * La base impone la restriccion ck_asistencia_coherencia:
 *   TARDANZA     -> minutos_tardanza > 0  y hora_entrada NO nula
 *   PUNTUAL      -> minutos_tardanza = 0  y hora_entrada NO nula
 *   FALTA        -> minutos_tardanza = 0  y hora_entrada nula
 *   JUSTIFICADO  -> minutos_tardanza = 0  y hora_entrada nula
 */
class Asistencia extends Model
{
    public const PUNTUAL = 'PUNTUAL';

    public const TARDANZA = 'TARDANZA';

    public const FALTA = 'FALTA';

    public const JUSTIFICADO = 'JUSTIFICADO';

    public const ORIGEN_MANUAL = 'MANUAL';

    public const ORIGEN_AUTOMATICO = 'AUTOMATICO';

    public const ESTADOS = [self::PUNTUAL, self::TARDANZA, self::FALTA, self::JUSTIFICADO];

    /** Estados en los que el trabajador si se presento a laborar. */
    public const ESTADOS_CON_ENTRADA = [self::PUNTUAL, self::TARDANZA];

    /** Color semantico por estado (Bootstrap 5.3), segun el diseno UX/UI. */
    public const COLORES = [
        self::PUNTUAL => 'success',
        self::TARDANZA => 'warning',
        self::FALTA => 'danger',
        self::JUSTIFICADO => 'info',
    ];

    public const ICONOS = [
        self::PUNTUAL => 'bi-check-circle-fill',
        self::TARDANZA => 'bi-clock-fill',
        self::FALTA => 'bi-x-circle-fill',
        self::JUSTIFICADO => 'bi-file-earmark-text-fill',
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

    // -----------------------------------------------------------------
    //  Relaciones
    // -----------------------------------------------------------------

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id', 'personal_id');
    }

    // -----------------------------------------------------------------
    //  Atributos derivados
    // -----------------------------------------------------------------

    public function getColorAttribute(): string
    {
        return self::COLORES[$this->estado] ?? 'secondary';
    }

    public function getIconoAttribute(): string
    {
        return self::ICONOS[$this->estado] ?? 'bi-dash-circle';
    }

    /** Verdadero si ya marco entrada pero aun no marca su salida. */
    public function getJornadaAbiertaAttribute(): bool
    {
        return $this->hora_entrada !== null && $this->hora_salida === null;
    }

    /** Horas trabajadas en la jornada, con dos decimales. */
    public function getHorasTrabajadasAttribute(): ?float
    {
        if ($this->hora_entrada === null || $this->hora_salida === null) {
            return null;
        }

        $minutos = $this->aMinutos((string) $this->hora_salida) - $this->aMinutos((string) $this->hora_entrada);

        return round(max(0, $minutos) / 60, 2);
    }

    public function getEntradaCortaAttribute(): string
    {
        return $this->hora_entrada ? substr((string) $this->hora_entrada, 0, 5) : '--:--';
    }

    public function getSalidaCortaAttribute(): string
    {
        return $this->hora_salida ? substr((string) $this->hora_salida, 0, 5) : '--:--';
    }

    // -----------------------------------------------------------------
    //  Scopes de consulta (HU-06)
    // -----------------------------------------------------------------

    public function scopeEntreFechas(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        return $query
            ->when($desde, fn (Builder $q) => $q->whereDate('asistencia.fecha', '>=', $desde))
            ->when($hasta, fn (Builder $q) => $q->whereDate('asistencia.fecha', '<=', $hasta));
    }

    public function scopeDePersonal(Builder $query, int|string|null $personalId): Builder
    {
        return $query->when($personalId, fn (Builder $q) => $q->where('asistencia.personal_id', $personalId));
    }

    public function scopeDeEstado(Builder $query, ?string $estado): Builder
    {
        return $query->when($estado, fn (Builder $q) => $q->where('asistencia.estado', $estado));
    }

    public function scopeDeArea(Builder $query, int|string|null $areaId): Builder
    {
        return $query->when(
            $areaId,
            fn (Builder $q) => $q->whereHas('personal', fn (Builder $p) => $p->where('area_id', $areaId))
        );
    }

    public function scopeDeCargo(Builder $query, ?string $cargo): Builder
    {
        return $query->when(
            $cargo,
            fn (Builder $q) => $q->whereHas('personal', fn (Builder $p) => $p->where('cargo', 'like', "%{$cargo}%"))
        );
    }

    /** Jornadas sin marcacion de salida. */
    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->whereNotNull('hora_entrada')->whereNull('hora_salida');
    }

    private function aMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($hora, 0, 5)));

        return $h * 60 + $m;
    }
}
