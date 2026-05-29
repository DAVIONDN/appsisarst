<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capa MODELO - Reporte generado con sus filtros (RF-15 a RF-18).
 *
 * Se declara desde el Sprint 1 para completar los modelos del esquema;
 * su uso funcional corresponde al Sprint 5.
 */
class Reporte extends Model
{
    protected $table = 'reporte';

    protected $primaryKey = 'reporte_id';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'tipo',
        'parametros',
        'formato',
        'fecha_generacion',
    ];

    protected function casts(): array
    {
        return [
            'parametros' => 'array',
            'fecha_generacion' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }
}
