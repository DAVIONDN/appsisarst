<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Capa MODELO - Catalogo de tipos de movimiento institucional.
 */
class TipoMovimiento extends Model
{
    /** La tabla tipo_movimiento solo tiene created_at. */
    public const UPDATED_AT = null;

    protected $table = 'tipo_movimiento';

    protected $primaryKey = 'tipo_movimiento_id';

    protected $fillable = [
        'nombre',
        'descripcion',
        'requiere_destino',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_destino' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(
            MovimientoInstitucional::class,
            'tipo_movimiento_id',
            'tipo_movimiento_id'
        );
    }
}
