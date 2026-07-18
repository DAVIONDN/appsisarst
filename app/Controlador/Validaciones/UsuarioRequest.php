<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use App\Modelo\Usuario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validacion del alta y la edicion de cuentas (HU-11).
 *
 * Cubre CA-HU11-01 (asociar a personal del padron), CA-HU11-02 (editar
 * correo, personal y estado) y CA-HU11-04 (no duplicar username ni
 * correo, que respaldan los indices uq_usuario_username y
 * uq_usuario_correo).
 *
 * La contrasena solo se exige al crear: al editar tiene su propia
 * operacion, para que un cambio de correo no arrastre por descuido un
 * cambio de clave.
 */
class UsuarioRequest extends FormRequest
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
        $usuario = $this->route('usuario');
        $idActual = $usuario instanceof Usuario ? $usuario->usuario_id : null;
        $esAlta = $idActual === null;

        return [
            'personal_id' => [
                'required', 'integer',
                Rule::exists('personal', 'personal_id'),
                Rule::unique('usuario', 'personal_id')->ignore($idActual, 'usuario_id'),
            ],
            'username' => [
                'required', 'string', 'min:4', 'max:40',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('usuario', 'username')->ignore($idActual, 'usuario_id'),
            ],
            'correo_institucional' => [
                'required', 'email:rfc', 'max:120',
                Rule::unique('usuario', 'correo_institucional')->ignore($idActual, 'usuario_id'),
            ],
            'password' => $esAlta
                ? ['required', 'confirmed', Password::min(8)->letters()->numbers()]
                : ['nullable'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('rol', 'rol_id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'personal_id.required' => 'Seleccione al trabajador del padron.',
            'personal_id.unique' => 'Ese trabajador ya tiene una cuenta de acceso.',
            'username.unique' => 'Ese nombre de usuario ya esta en uso.',
            'username.min' => 'El nombre de usuario debe tener al menos 4 caracteres.',
            'username.regex' => 'El nombre de usuario solo admite minusculas, numeros, punto, guion y guion bajo.',
            'correo_institucional.unique' => 'Ese correo institucional ya esta registrado en otra cuenta.',
            'password.confirmed' => 'La confirmacion de la contrasena no coincide.',
            'roles.required' => 'Asigne al menos un rol: una cuenta sin rol no puede hacer nada.',
            'roles.min' => 'Asigne al menos un rol.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'personal_id' => 'trabajador',
            'username' => 'nombre de usuario',
            'correo_institucional' => 'correo institucional',
            'password' => 'contrasena',
            'roles' => 'roles',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'correo_institucional' => mb_strtolower(trim((string) $this->input('correo_institucional'))),
        ]);
    }
}
