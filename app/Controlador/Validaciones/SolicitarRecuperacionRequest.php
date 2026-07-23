<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion de la solicitud de recuperacion (HU-17 / CA-HU17-01).
 *
 * Solo se valida el FORMATO del correo. Deliberadamente NO se usa la
 * regla "exists": hacerlo revelaria si el correo esta registrado, que es
 * justo lo que prohibe el criterio CA-HU17-03.
 */
class SolicitarRecuperacionRequest extends FormRequest
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
            'correo_institucional' => ['required', 'email:rfc', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'correo_institucional.required' => 'Ingrese su correo institucional.',
            'correo_institucional.email' => 'Ingrese un correo electronico valido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['correo_institucional' => 'correo institucional'];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'correo_institucional' => mb_strtolower(trim((string) $this->input('correo_institucional'))),
        ]);
    }
}
