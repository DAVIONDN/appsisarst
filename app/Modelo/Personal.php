<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Capa MODELO - Padron unico del personal (RF-01 a RF-04, RF-20).
 *
 * Nucleo del Sprint 1. La tabla no sigue las convenciones de Laravel
 * (nombre en singular y clave primaria propia), por eso se declaran
 * explicitamente $table y $primaryKey.
 *
 * Columnas de la tabla (base de datos):
 * @property int         $personal_id
 * @property int         $area_id
 * @property int|null    $horario_id
 * @property string      $dni              8 digitos, unico (CA-HU01-03)
 * @property string      $nombres
 * @property string      $apellidos
 * @property string      $cargo
 * @property string      $condicion_laboral  Uno de CONDICIONES
 * @property string|null $telefono
 * @property string|null $correo
 * @property \Illuminate\Support\Carbon $fecha_ingreso
 * @property string      $estado           ACTIVO | INACTIVO (HU-04)
 * @property string|null $motivo_baja      Solo cuando estado = INACTIVO
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 *
 * Atributos derivados (no almacenados en la base):
 * @property-read string $nombre_completo  "Apellidos, Nombres" para listados
 * @property-read bool   $es_activo        true si estado === ACTIVO
 * @property-read int    $anios_servicio   Anios desde fecha_ingreso hasta hoy
 *
 * Relaciones:
 * @property-read \App\Modelo\Area|null                                        $area
 * @property-read \App\Modelo\Horario|null                                     $horario
 * @property-read \App\Modelo\Usuario|null                                     $usuario
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modelo\Asistencia>               $asistencias
 * @property-read \Illuminate\Database\Eloquent\Collection<int,\App\Modelo\MovimientoInstitucional>  $movimientos
 */
class Personal extends Model
{
    public const ESTADO_ACTIVO = 'ACTIVO';

    public const ESTADO_INACTIVO = 'INACTIVO';

    /** Condiciones laborales admitidas en el padron de la Red de Salud. */
    public const CONDICIONES = [
        'NOMBRADO',
        'CAS',
        'TERCEROS',
        'PRACTICANTE',
        'SERUMS',
    ];

    protected $table = 'personal';

    protected $primaryKey = 'personal_id';

    protected $fillable = [
        'area_id',
        'horario_id',
        'dni',
        'nombres',
        'apellidos',
        'cargo',
        'condicion_laboral',
        'telefono',
        'correo',
        'fecha_ingreso',
        'estado',
        'motivo_baja',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ingreso' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    //  Relaciones
    // -----------------------------------------------------------------

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id', 'area_id');
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(Horario::class, 'horario_id', 'horario_id');
    }

    /** Cuenta de acceso asociada (opcional, se administra en el Sprint 4). */
    public function usuario(): HasOne
    {
        return $this->hasOne(Usuario::class, 'personal_id', 'personal_id');
    }

    /** Asistencias del trabajador (Sprint 2). Se leen en el historial HU-18. */
    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'personal_id', 'personal_id');
    }

    /** Movimientos institucionales (Sprint 3). Se leen en el historial HU-18. */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInstitucional::class, 'personal_id', 'personal_id');
    }

    // -----------------------------------------------------------------
    //  Atributos derivados
    // -----------------------------------------------------------------

    /** "Quispe Huaman, Maria Elena" - formato usado en listados y reportes. */
    public function getNombreCompletoAttribute(): string
    {
        return "{$this->apellidos}, {$this->nombres}";
    }

    public function getEsActivoAttribute(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }

    /** Anios de servicio cumplidos desde la fecha de ingreso (ficha HU-18). */
    public function getAniosServicioAttribute(): int
    {
        return $this->fecha_ingreso ? (int) abs($this->fecha_ingreso->diffInYears(now())) : 0;
    }

    // -----------------------------------------------------------------
    //  Scopes de consulta (HU-03: filtros por area, cargo y condicion)
    // -----------------------------------------------------------------

    public function scopeActivo(Builder $query): Builder
    {
        return $query->where('personal.estado', self::ESTADO_ACTIVO);
    }

    public function scopeDeArea(Builder $query, int|string|null $areaId): Builder
    {
        return $query->when($areaId, fn (Builder $q) => $q->where('personal.area_id', $areaId));
    }

    public function scopeDeEstablecimiento(Builder $query, int|string|null $establecimientoId): Builder
    {
        return $query->when(
            $establecimientoId,
            fn (Builder $q) => $q->whereHas(
                'area',
                fn (Builder $a) => $a->where('establecimiento_id', $establecimientoId)
            )
        );
    }

    public function scopeDeCondicion(Builder $query, ?string $condicion): Builder
    {
        return $query->when($condicion, fn (Builder $q) => $q->where('condicion_laboral', $condicion));
    }

    public function scopeDeEstado(Builder $query, ?string $estado): Builder
    {
        return $query->when($estado, fn (Builder $q) => $q->where('personal.estado', $estado));
    }

    /** Busqueda libre por DNI, nombres, apellidos o cargo. */
    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($texto): void {
            $like = "%{$texto}%";
            $q->where('dni', 'like', $like)
                ->orWhere('nombres', 'like', $like)
                ->orWhere('apellidos', 'like', $like)
                ->orWhere('cargo', 'like', $like);
        });
    }

    /**
     * Filtro por cargo (coincidencia parcial, el cargo es texto libre
     * en la base y no tiene catalogo cerrado, a diferencia de condicion_laboral).
     */
    public function scopeDeCargo(Builder $query, ?string $cargo): Builder
    {
        return $query->when($cargo, fn (Builder $q) => $q->where('cargo', 'like', "%{$cargo}%"));
    }
}
