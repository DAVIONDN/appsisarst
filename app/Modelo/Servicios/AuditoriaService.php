<?php

declare(strict_types=1);

namespace App\Modelo\Servicios;

use App\Modelo\LogAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Servicio de auditoria (RNF-10).
 *
 * Centraliza la escritura del log para que ningun controlador arme la
 * entrada a mano. Cada registro guarda quien ejecuto la accion, cuando,
 * sobre que entidad y desde que IP, tal como exige la observacion H-07
 * del documento de diseno.
 */
class AuditoriaService
{
    public function __construct(private readonly Request $request)
    {
    }

    /**
     * Deja constancia de una accion de escritura.
     *
     * @param  string  $entidad  Nombre de la tabla afectada (p. ej. "personal").
     * @param  int|null  $registroId  Clave primaria del registro afectado.
     * @param  string  $accion  Verbo del negocio (REGISTRAR_PERSONAL, ...).
     * @param  string|null  $detalle  Texto legible para el auditor.
     */
    public function registrar(
        string $entidad,
        ?int $registroId,
        string $accion,
        ?string $detalle = null
    ): LogAuditoria {
        return LogAuditoria::create([
            'usuario_id' => Auth::id(),
            'fecha' => now(),
            'entidad' => $entidad,
            'registro_id' => $registroId,
            'accion' => $accion,
            'detalle' => $detalle,
            'ip_origen' => $this->request->ip(),
        ]);
    }
}
