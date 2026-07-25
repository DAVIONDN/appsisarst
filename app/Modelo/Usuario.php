<?php

declare(strict_types=1);

namespace App\Modelo;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Carbon;

/**
 * Capa MODELO - Cuenta de acceso al sistema (RF-11, RF-12, RF-13, RF-14).
 *
 * Nucleo del Sprint 4. Concentra los tres atributos de seguridad que el
 * diagrama de clases del Sprint 4 exige:
 *
 *   - password_hash      la clave nunca en texto claro (RNF-02)
 *   - intentos_fallidos  soporta el bloqueo por intentos (DS-RF13)
 *   - token_recuperacion y token_expira, para HU-17
 *
 * Ajustes obligatorios respecto de las convenciones de Laravel:
 *   - la tabla se llama "usuario" y su clave es "usuario_id";
 *   - la contrasena vive en "password_hash", no en "password";
 *   - la tabla no tiene "remember_token", asi que se desactiva
 *     "recordar sesion" en lugar de alterar el esquema aprobado.
 */
class Usuario extends Authenticatable
{
    public const ESTADO_ACTIVO = 'ACTIVO';

    public const ESTADO_INACTIVO = 'INACTIVO';

    public const ESTADO_BLOQUEADO = 'BLOQUEADO';

    public const ESTADOS = [self::ESTADO_ACTIVO, self::ESTADO_INACTIVO, self::ESTADO_BLOQUEADO];

    /**
     * Intentos fallidos consecutivos tras los cuales la cuenta se bloquea.
     * Lo fija el diagrama DS-RF13: "bloquea temporalmente la cuenta al
     * quinto intento". La restriccion ck_usuario_intentos admite hasta 10.
     */
    public const MAX_INTENTOS = 5;

    /** Vigencia del enlace de recuperacion, en minutos (CA-HU17-02). */
    public const MINUTOS_TOKEN = 30;

    public const COLORES = [
        self::ESTADO_ACTIVO => 'success',
        self::ESTADO_INACTIVO => 'secondary',
        self::ESTADO_BLOQUEADO => 'danger',
    ];

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

    /** Direccion a la que se envia el enlace de recuperacion (HU-17). */
    public function getEmailForPasswordReset(): string
    {
        return (string) $this->correo_institucional;
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
    //  Estado de la cuenta
    // -----------------------------------------------------------------

    public function estaActivo(): bool
    {
        return $this->estado === self::ESTADO_ACTIVO;
    }

    public function estaBloqueado(): bool
    {
        return $this->estado === self::ESTADO_BLOQUEADO;
    }

    public function getColorAttribute(): string
    {
        return self::COLORES[$this->estado] ?? 'secondary';
    }

    /** Intentos que le quedan antes de que la cuenta se bloquee. */
    public function getIntentosRestantesAttribute(): int
    {
        return max(0, self::MAX_INTENTOS - (int) $this->intentos_fallidos);
    }

    // -----------------------------------------------------------------
    //  Recuperacion de contrasena (HU-17)
    // -----------------------------------------------------------------

    /**
     * Verdadero si la cuenta tiene un enlace de recuperacion vigente.
     * El token es de un solo uso: al restablecer la clave se limpia.
     */
    public function tieneTokenVigente(): bool
    {
        return $this->token_recuperacion !== null
            && $this->token_expira !== null
            && $this->token_expira->isFuture();
    }

    /**
     * Comprueba el token recibido contra el hash guardado.
     *
     * En la base se almacena el HASH del token, no el token mismo: si
     * alguien leyera la tabla no podria construir un enlace valido. Es el
     * mismo criterio con el que se guarda la contrasena (RNF-02).
     */
    public function tokenCoincide(string $token): bool
    {
        return $this->tieneTokenVigente()
            && hash_equals((string) $this->token_recuperacion, hash('sha256', $token));
    }

    public function minutosParaExpirar(): int
    {
        if (! $this->tieneTokenVigente()) {
            return 0;
        }

        return (int) ceil(Carbon::now()->diffInMinutes($this->token_expira, false));
    }

    // -----------------------------------------------------------------
    //  Roles y permisos (RF-12)
    // -----------------------------------------------------------------

    /** Verdadero si el usuario tiene asignado alguno de los roles indicados. */
    public function tieneRol(string ...$nombres): bool
    {
        return $this->roles->whereIn('nombre', $nombres)->isNotEmpty();
    }

    /**
     * Verifica un permiso concreto: modulo + accion.
     * Ej.: tienePermiso('USUARIOS', 'ESCRIBIR').
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
     * Area del usuario a traves de su ficha de personal.
     * El Jefe de Area solo consulta al personal de esta area.
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

    public function getRolesLegiblesAttribute(): string
    {
        return $this->roles->pluck('nombre')->implode(', ') ?: 'Sin rol asignado';
    }

    // -----------------------------------------------------------------
    //  Scopes de consulta (HU-11)
    // -----------------------------------------------------------------

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_ACTIVO);
    }

    public function scopeDeEstado(Builder $query, ?string $estado): Builder
    {
        return $query->when($estado, fn (Builder $q) => $q->where('usuario.estado', $estado));
    }

    public function scopeDeRol(Builder $query, int|string|null $rolId): Builder
    {
        return $query->when(
            $rolId,
            fn (Builder $q) => $q->whereHas('roles', fn (Builder $r) => $r->where('rol.rol_id', $rolId))
        );
    }

    /** Cuentas sin ningun rol: no pueden hacer nada dentro del sistema. */
    public function scopeSinRol(Builder $query): Builder
    {
        return $query->whereDoesntHave('roles');
    }

    public function scopeBuscar(Builder $query, ?string $texto): Builder
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($texto): void {
            $like = "%{$texto}%";
            $q->where('username', 'like', $like)
                ->orWhere('correo_institucional', 'like', $like)
                ->orWhereHas('personal', function (Builder $p) use ($like): void {
                    $p->where('dni', 'like', $like)
                        ->orWhere('nombres', 'like', $like)
                        ->orWhere('apellidos', 'like', $like);
                });
        });
    }
}
