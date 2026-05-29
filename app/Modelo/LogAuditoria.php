<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Capa MODELO - Bitacora de acciones de escritura (RNF-10).
 *
 * La tabla usa la columna "fecha" en lugar del par created_at/updated_at,
 * por eso se desactivan los timestamps automaticos de Eloquent.
 * El diseno exige que cada entrada quede asociada al usuario que ejecuto
 * la accion (observacion H-07 de la revision tecnica).
 */
class LogAuditoria extends Model
{
    protected $table = 'log_auditoria';

    protected $primaryKey = 'log_id';

    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'fecha',
        'entidad',
        'registro_id',
        'accion',
        'detalle',
        'ip_origen',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id', 'usuario_id');
    }

    /** Entradas de auditoria referidas a un registro concreto. */
    public function scopeDelRegistro(Builder $query, string $entidad, int $registroId): Builder
    {
        return $query->where('entidad', $entidad)->where('registro_id', $registroId);
    }
}
