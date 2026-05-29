<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Capa MODELO - Area de trabajo.
 *
 * Tabla: area. Resuelve la pertenencia del personal; el diseno (obs. H-08)
 * elimino el atributo redundante "area" de la clase Personal.
 */
class Area extends Model
{
    protected $table = 'area';

    protected $primaryKey = 'area_id';

    protected $fillable = [
        'establecimiento_id',
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function establecimiento(): BelongsTo
    {
        return $this->belongsTo(Establecimiento::class, 'establecimiento_id', 'establecimiento_id');
    }

    public function personal(): HasMany
    {
        return $this->hasMany(Personal::class, 'area_id', 'area_id');
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true);
    }
}
