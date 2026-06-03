<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de la baja logica del trabajador (HU-04).
 *
 * El motivo es obligatorio porque queda registrado en el padron y en el
 * log de auditoria, y es el sustento administrativo de la baja.
 */
class DesactivarPersonalRequest extends FormRequest
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
            'motivo_baja' => ['required', 'string', 'min:10', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo_baja.required' => 'Indique el motivo de la baja.',
            'motivo_baja.min' => 'El motivo debe explicar la baja (minimo 10 caracteres).',
        ];
    }
}
