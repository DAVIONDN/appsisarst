<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion de la asignacion masiva de un horario (CA-HU16-02).
 *
 * Un mismo horario puede asignarse a varios trabajadores a la vez.
 */
class AsignarHorarioRequest extends FormRequest
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
            'personal_ids' => ['required', 'array', 'min:1'],
            'personal_ids.*' => ['integer', Rule::exists('personal', 'personal_id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'personal_ids.required' => 'Seleccione al menos un trabajador.',
            'personal_ids.min' => 'Seleccione al menos un trabajador.',
            'personal_ids.*.exists' => 'Uno de los trabajadores seleccionados no existe en el padron.',
        ];
    }
}
