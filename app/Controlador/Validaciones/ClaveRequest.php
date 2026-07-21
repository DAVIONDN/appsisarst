<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion de una contrasena nueva.
 *
 * La usan tres flujos: el restablecimiento administrativo (HU-11), el
 * restablecimiento por enlace (HU-17 / CA-HU17-04) y el cambio de la
 * propia clave.
 *
 * La politica minima -8 caracteres con letras y numeros- responde al
 * RNF-02, que exige almacenamiento seguro; una clave trivial haria
 * irrelevante el hash.
 */
class ClaveRequest extends FormRequest
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
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Ingrese la contrasena nueva.',
            'password.confirmed' => 'La confirmacion de la contrasena no coincide.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['password' => 'contrasena'];
    }
}
