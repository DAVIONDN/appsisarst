<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validacion del formulario de acceso (RF-13).
 *
 * El Sprint 1 implementa el minimo necesario para que el control de acceso
 * por rol del padron sea verificable (CA-HU03-03 y CA-HU18-03). El flujo
 * completo de seguridad -bloqueo por intentos y recuperacion de clave-
 * corresponde al Sprint 4.
 */
class LoginRequest extends FormRequest
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
            'username' => ['required', 'string', 'max:40'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Ingrese su nombre de usuario.',
            'password.required' => 'Ingrese su contrasena.',
        ];
    }
}
