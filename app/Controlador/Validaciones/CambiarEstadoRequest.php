<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use App\Modelo\MovimientoInstitucional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion del cambio de estado del movimiento (HU-10).
 *
 * Solo valida el formato. Que la transicion sea legal segun la maquina de
 * estados, y que una rotacion aprobada traiga su area de destino, son
 * reglas de negocio y las verifica MovimientoService.
 */
class CambiarEstadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'estado' => [
                'required',
                Rule::in([
                    MovimientoInstitucional::APROBADO,
                    MovimientoInstitucional::RECHAZADO,
                    MovimientoInstitucional::FINALIZADO,
                ]),
            ],
            // ck_movimiento_rechazo: rechazar exige motivo.
            'motivo_rechazo' => [
                Rule::requiredIf(fn () => $this->input('estado') === MovimientoInstitucional::RECHAZADO),
                'nullable', 'string', 'min:10', 'max:255',
            ],
            'area_destino_id' => ['nullable', 'integer', Rule::exists('area', 'area_id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.required' => 'Indique el nuevo estado del movimiento.',
            'estado.in' => 'Solo se puede aprobar, rechazar o finalizar un movimiento.',
            'motivo_rechazo.required' => 'Para rechazar el movimiento debe indicar el motivo.',
            'motivo_rechazo.min' => 'El motivo del rechazo debe explicarse (minimo 10 caracteres).',
            'area_destino_id.exists' => 'El area de destino seleccionada no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'motivo_rechazo' => 'motivo del rechazo',
            'area_destino_id' => 'area de destino',
        ];
    }
}
