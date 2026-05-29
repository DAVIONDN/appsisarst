<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Capa MODELO - Perfil funcional que agrupa permisos (RF-12).
 */
class Rol extends Model
{
    public const ADMIN_SISTEMA = 'ADMIN_SISTEMA';

    public const ADMIN_RRHH = 'ADMIN_RRHH';

    public const JEFE_AREA = 'JEFE_AREA';

    public const GERENTE_RED = 'GERENTE_RED';

    protected $table = 'rol';

    protected $primaryKey = 'rol_id';

    protected $fillable = [
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

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'usuario_rol', 'rol_id', 'usuario_id');
    }

    public function permisos(): BelongsToMany
    {
        return $this->belongsToMany(Permiso::class, 'rol_permiso', 'rol_id', 'permiso_id');
    }
}
