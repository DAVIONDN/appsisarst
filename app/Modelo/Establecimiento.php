<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Capa MODELO - Establecimiento de salud.
 *
 * Tabla: establecimiento. Agrupa las areas de trabajo dentro de una microrred
 * y es el destino de los movimientos institucionales (Sprint 3).
 */
class Establecimiento extends Model
{
    protected $table = 'establecimiento';

    protected $primaryKey = 'establecimiento_id';

    protected $fillable = [
        'codigo',
        'nombre',
        'tipo',
        'microrred',
        'direccion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    /** Areas de trabajo que pertenecen al establecimiento. */
    public function areas(): HasMany
    {
        return $this->hasMany(Area::class, 'establecimiento_id', 'establecimiento_id');
    }

    /** Movimientos institucionales que tienen a este establecimiento como destino. */
    public function movimientosRecibidos(): HasMany
    {
        return $this->hasMany(
            MovimientoInstitucional::class,
            'establecimiento_destino_id',
            'establecimiento_id'
        );
    }
}
