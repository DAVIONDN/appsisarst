<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Capa MODELO - Cuenta de acceso al sistema (RF-11, RF-13).
 *
 * Ajustes obligatorios respecto de las convenciones de Laravel:
 *   - la tabla se llama "usuario" y su clave es "usuario_id";
 *   - la contrasena vive en "password_hash", no en "password";
 *   - la tabla no tiene columna "remember_token", por lo que se desactiva
 *     la funcion de "recordar sesion" en lugar de alterar el esquema
 *     aprobado en el documento de diseno.
 *
 * La administracion completa de cuentas corresponde al Sprint 4 (HU-11 y
 * HU-12); en el Sprint 1 el modelo se usa para autenticar y para resolver
 * los permisos que restringen el acceso al padron (CA-HU03-03).
 */
class Usuario extends Authenticatable
{
    public const ESTADO_ACTIVO = 'ACTIVO';

    public const ESTADO_INACTIVO = 'INACTIVO';

    public const ESTADO_BLOQUEADO = 'BLOQUEADO';

    protected $table = 'usuario';

    protected $primaryKey = 'usuario_id';

    protected $fillable = [
        'personal_id',
        'username',
        'password_hash',
        'correo_institucional',
        'estado',
    ];

    protected $hidden = [
        'password_hash',
        'token_recuperacion',
    ];

    protected function casts(): array
    {
        return [
            'intentos_fallidos' => 'integer',
            'token_expira' => 'datetime',
            'ultimo_acceso' => 'datetime',
        ];
    }

    // -----------------------------------------------------------------
    //  Contrato de autenticacion
    // -----------------------------------------------------------------

    /** Laravel busca "password"; la base de datos guarda "password_hash". */
    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    /** Desactiva "recordarme": la tabla usuario no tiene remember_token. */
    public function getRememberTokenName(): ?string
    {
        return null;
    }

    // -----------------------------------------------------------------
    //  Relaciones
    // -----------------------------------------------------------------

    public function personal(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id', 'personal_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'usuario_rol', 'usuario_id', 'rol_id')
            ->withPivot(['fecha_asignacion', 'asignado_por']);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LogAuditoria::class, 'usuario_id', 'usuario_id');
    }

    // -----------------------------------------------------------------
    //  Reglas de negocio de acceso
    // -----------------------------------------------------------------

    public function estaActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }

    /** Verdadero si el usuario tiene asignado alguno de los roles indicados. */
    public function tieneRol(string ...$nombres): bool
    {
        return $this->roles->whereIn('nombre', $nombres)->isNotEmpty();
    }

    /**
     * Verifica un permiso concreto: modulo + accion.
     * Ej.: tienePermiso('PADRON', 'ESCRIBIR').
     */
    public function tienePermiso(string $modulo, string $accion): bool
    {
        return $this->roles
            ->pluck('permisos')
            ->flatten()
            ->contains(
                fn (Permiso $permiso): bool => $permiso->modulo === $modulo
                    && $permiso->accion === $accion
            );
    }

    /**
     * Area a la que pertenece el usuario a traves de su ficha de personal.
     * El Jefe de Area solo consulta al personal de esta area (CA-HU03-03).
     */
    public function areaId(): ?int
    {
        $areaId = $this->personal?->area_id;

        return $areaId === null ? null : (int) $areaId;
    }

    public function getNombreMostradoAttribute(): string
    {
        return $this->personal?->nombre_completo ?? $this->username;
    }
}
