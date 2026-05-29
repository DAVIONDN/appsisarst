<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Capa MODELO - Permiso por modulo y tipo de accion (CA-HU12-03).
 */
class Permiso extends Model
{
    public const LEER = 'LEER';

    public const ESCRIBIR = 'ESCRIBIR';

    public const EDITAR = 'EDITAR';

    public const ELIMINAR = 'ELIMINAR';

    public const MODULO_PADRON = 'PADRON';

    /** La tabla permiso solo tiene created_at. */
    public const UPDATED_AT = null;

    protected $table = 'permiso';

    protected $primaryKey = 'permiso_id';

    protected $fillable = [
        'modulo',
        'accion',
        'descripcion',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'rol_permiso', 'permiso_id', 'rol_id');
    }

    public function getCodigoAttribute(): string
    {
        return "{$this->modulo}.{$this->accion}";
    }
}
