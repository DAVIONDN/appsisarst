<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Capa MODELO - Horario de trabajo (catalogo, RF-19 / HU-16).
 *
 * En el Sprint 1 solo se usa en modo lectura: el formulario del padron
 * permite asignar un horario ya existente al trabajador. Su mantenimiento
 * (alta y edicion de horarios) corresponde al Sprint 2.
 */
class Horario extends Model
{
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

    public function personal(): HasMany
    {
        return $this->hasMany(Personal::class, 'horario_id', 'horario_id');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true);
    }

    /** Etiqueta legible para los combos de la vista: "Administrativo (08:00 - 16:00)". */
    public function getEtiquetaAttribute(): string
    {
        return sprintf(
            '%s (%s - %s)',
            $this->nombre,
            substr((string) $this->hora_entrada, 0, 5),
            substr((string) $this->hora_salida, 0, 5)
        );
    }
}
