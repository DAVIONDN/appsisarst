<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Capa MODELO - Movimiento institucional (RF-08 a RF-10).
 *
 * Nucleo del Sprint 3: comisiones de servicio, rotaciones, licencias,
 * permisos y vacaciones del personal.
 *
 * La maquina de estados unica que fijo la observacion H-09 de la revision
 * tecnica es:
 *
 *      PENDIENTE ──> APROBADO ──> FINALIZADO
 *          └──────> RECHAZADO
 *
 * No hay otras transiciones. La base la impone con el disparador
 * tg_movimiento_transicion; aqui se replica para poder explicarla al
 * usuario antes de que MySQL rechace la fila.
 */
class MovimientoInstitucional extends Model
{
    public const PENDIENTE = 'PENDIENTE';

    public const APROBADO = 'APROBADO';

    public const RECHAZADO = 'RECHAZADO';

    public const FINALIZADO = 'FINALIZADO';

    public const ESTADOS = [self::PENDIENTE, self::APROBADO, self::RECHAZADO, self::FINALIZADO];

    /**
     * Transiciones permitidas por la maquina de estados del diseno.
     *
     * @var array<string, list<string>>
     */
    public const TRANSICIONES = [
        self::PENDIENTE => [self::APROBADO, self::RECHAZADO],
        self::APROBADO => [self::FINALIZADO],
        self::RECHAZADO => [],
        self::FINALIZADO => [],
    ];

    /** Color semantico por estado (CA-HU09-03), segun el mockup del Sprint 3. */
    public const COLORES = [
        self::PENDIENTE => 'warning',
        self::APROBADO => 'success',
        self::RECHAZADO => 'danger',
        self::FINALIZADO => 'secondary',
    ];

    public const ICONOS = [
        self::PENDIENTE => 'bi-hourglass-split',
        self::APROBADO => 'bi-check-circle-fill',
        self::RECHAZADO => 'bi-x-circle-fill',
        self::FINALIZADO => 'bi-flag-fill',
    ];

    /** Nombres de tipo que exigen elegir establecimiento de destino. */
    public const TIPO_ROTACION = 'ROTACION';

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

    // -----------------------------------------------------------------
    //  Relaciones
    // -----------------------------------------------------------------

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

    // -----------------------------------------------------------------
    //  Maquina de estados
    // -----------------------------------------------------------------

    /** Estados a los que este movimiento puede pasar desde el actual. */
    public function transicionesPosibles(): array
    {
        return self::TRANSICIONES[$this->estado] ?? [];
    }

    public function puedeTransicionarA(string $estado): bool
    {
        return in_array($estado, $this->transicionesPosibles(), true);
    }

    /** Verdadero si el movimiento ya cerro su ciclo y no admite cambios. */
    public function getEsTerminalAttribute(): bool
    {
        return $this->transicionesPosibles() === [];
    }

    /** Solo los movimientos PENDIENTES se pueden editar o eliminar. */
    public function getEsEditableAttribute(): bool
    {
        return $this->estado === self::PENDIENTE;
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

    /** Dias que abarca el movimiento, ambos extremos incluidos. */
    public function getDiasAttribute(): int
    {
        if ($this->fecha_inicio === null || $this->fecha_fin === null) {
            return 0;
        }

        return (int) abs($this->fecha_inicio->diffInDays($this->fecha_fin)) + 1;
    }

    public function getPeriodoAttribute(): string
    {
        return sprintf(
            '%s al %s',
            $this->fecha_inicio?->format('d/m/Y'),
            $this->fecha_fin?->format('d/m/Y')
        );
    }

    /** Verdadero si el periodo del movimiento incluye la fecha indicada. */
    public function cubre(Carbon $fecha): bool
    {
        return $this->fecha_inicio !== null
            && $this->fecha_fin !== null
            && $fecha->betweenIncluded($this->fecha_inicio, $this->fecha_fin);
    }

    /**
     * Situacion del movimiento respecto de hoy, para el listado.
     * Es informativa: no sustituye al estado, que es el dato oficial.
     */
    public function getSituacionAttribute(): string
    {
        if (in_array($this->estado, [self::RECHAZADO, self::FINALIZADO], true)) {
            return 'Cerrado';
        }

        $hoy = Carbon::today();

        if ($this->fecha_fin !== null && $this->fecha_fin->lt($hoy)) {
            return 'Periodo vencido';
        }

        if ($this->cubre($hoy)) {
            return 'En curso';
        }

        return 'Programado';
    }

    /** Aprobado cuyo periodo ya termino: candidato a FINALIZADO. */
    public function getEsFinalizableAttribute(): bool
    {
        return $this->estado === self::APROBADO
            && $this->fecha_fin !== null
            && $this->fecha_fin->lt(Carbon::today());
    }

    // -----------------------------------------------------------------
    //  Scopes de consulta (HU-09)
    // -----------------------------------------------------------------

    /** Movimientos que aun comprometen al trabajador. */
    public function scopeVigentes(Builder $query): Builder
    {
        return $query->whereIn('estado', [self::PENDIENTE, self::APROBADO]);
    }

    public function scopeDePersonal(Builder $query, int|string|null $personalId): Builder
    {
        return $query->when($personalId, fn (Builder $q) => $q->where('personal_id', $personalId));
    }

    public function scopeDeTipo(Builder $query, int|string|null $tipoId): Builder
    {
        return $query->when($tipoId, fn (Builder $q) => $q->where('tipo_movimiento_id', $tipoId));
    }

    public function scopeDeEstado(Builder $query, ?string $estado): Builder
    {
        return $query->when($estado, fn (Builder $q) => $q->where('estado', $estado));
    }

    public function scopeDeArea(Builder $query, int|string|null $areaId): Builder
    {
        return $query->when(
            $areaId,
            fn (Builder $q) => $q->whereHas('personal', fn (Builder $p) => $p->where('area_id', $areaId))
        );
    }

    /**
     * Movimientos que se cruzan con el rango indicado.
     * Un movimiento entra si cualquier parte de su periodo cae dentro.
     */
    public function scopeEnPeriodo(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        return $query
            ->when($desde, fn (Builder $q) => $q->whereDate('fecha_fin', '>=', $desde))
            ->when($hasta, fn (Builder $q) => $q->whereDate('fecha_inicio', '<=', $hasta));
    }

    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($texto): void {
            $like = "%{$texto}%";
            $q->where('motivo', 'like', $like)
                ->orWhereHas('personal', function (Builder $p) use ($like): void {
                    $p->where('dni', 'like', $like)
                        ->orWhere('nombres', 'like', $like)
                        ->orWhere('apellidos', 'like', $like);
                });
        });
    }
}
