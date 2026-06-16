<?php

declare(strict_types=1);

namespace App\Modelo;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Capa MODELO - Horario de trabajo (RF-19 / HU-16).
 *
 * Nucleo del Sprint 2 junto con Asistencia: el horario es la referencia
 * contra la cual se evaluan las tardanzas y las faltas. Un mismo horario
 * lo comparten varios trabajadores (CA-HU16-02), por eso es un catalogo y
 * no un atributo del personal.
 */
class Horario extends Model
{
    /** Dias laborales admitidos en el catalogo. */
    public const DIAS_LABORALES = [
        'LUN-VIE' => 'Lunes a viernes',
        'LUN-SAB' => 'Lunes a sabado',
        'LUN-DOM' => 'Todos los dias',
    ];

    /** Numero de dia de la semana segun Carbon: 0 = domingo. */
    private const DIAS_POR_PATRON = [
        'LUN-VIE' => [1, 2, 3, 4, 5],
        'LUN-SAB' => [1, 2, 3, 4, 5, 6],
        'LUN-DOM' => [0, 1, 2, 3, 4, 5, 6],
    ];

    protected $table = 'horario';

    protected $primaryKey = 'horario_id';

    protected $fillable = [
        'nombre',
        'hora_entrada',
        'hora_salida',
        'tolerancia_min',
        'dias_laborales',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tolerancia_min' => 'integer',
            'activo' => 'boolean',
        ];
    }

    // -----------------------------------------------------------------
    //  Relaciones
    // -----------------------------------------------------------------

    /** Trabajadores que tienen asignado este horario. */
    public function personal(): HasMany
    {
        return $this->hasMany(Personal::class, 'horario_id', 'horario_id');
    }

    // -----------------------------------------------------------------
    //  Reglas de evaluacion (base de HU-07)
    // -----------------------------------------------------------------

    /**
     * Minutos de tardanza de una marcacion de entrada.
     *
     * Devuelve 0 si la entrada cae dentro de la hora de ingreso mas la
     * tolerancia configurada (CA-HU07-01). Nunca devuelve negativos:
     * llegar antes no compensa una tardanza posterior.
     */
    public function minutosTardanza(string $horaEntrada): int
    {
        $limite = $this->minutosDesdeMedianoche((string) $this->hora_entrada) + $this->tolerancia_min;
        $marcada = $this->minutosDesdeMedianoche($horaEntrada);

        return max(0, $marcada - $limite);
    }

    /** Hora limite de ingreso sin tardanza, en formato HH:MM. */
    public function getHoraLimiteAttribute(): string
    {
        $minutos = $this->minutosDesdeMedianoche((string) $this->hora_entrada) + $this->tolerancia_min;

        return sprintf('%02d:%02d', intdiv($minutos, 60) % 24, $minutos % 60);
    }

    /** Verdadero si la fecha indicada es dia laborable para este horario. */
    public function esDiaLaborable(CarbonInterface $fecha): bool
    {
        $dias = self::DIAS_POR_PATRON[$this->dias_laborales] ?? self::DIAS_POR_PATRON['LUN-VIE'];

        return in_array($fecha->dayOfWeek, $dias, true);
    }

    /** Etiqueta legible para los combos: "Administrativo (08:00 - 16:00)". */
    public function getEtiquetaAttribute(): string
    {
        return sprintf('%s (%s - %s)', $this->nombre, $this->horaCorta('hora_entrada'), $this->horaCorta('hora_salida'));
    }

    public function horaCorta(string $campo): string
    {
        return substr((string) $this->{$campo}, 0, 5);
    }

    public function getDiasLegiblesAttribute(): string
    {
        return self::DIAS_LABORALES[$this->dias_laborales] ?? $this->dias_laborales;
    }

    // -----------------------------------------------------------------
    //  Scopes
    // -----------------------------------------------------------------

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim((string) $texto);

        return $texto === ''
            ? $query
            : $query->where('nombre', 'like', "%{$texto}%");
    }

    // -----------------------------------------------------------------
    //  Apoyo interno
    // -----------------------------------------------------------------

    private function minutosDesdeMedianoche(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', substr($hora, 0, 5)));

        return $h * 60 + $m;
    }
}
