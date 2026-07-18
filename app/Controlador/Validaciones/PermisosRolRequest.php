<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion de la configuracion de permisos de un rol (CA-HU12-03).
 *
 * Un rol puede quedarse sin permisos (queda inoperante pero valido), asi
 * que la lista admite estar vacia. La unica salvaguarda -que
 * ADMIN_SISTEMA conserve el modulo USUARIOS- es una regla de negocio y
 * vive en RolPermisoService.
 */
class PermisosRolRequest extends FormRequest
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
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['integer', Rule::exists('permiso', 'permiso_id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'permisos.*.exists' => 'Uno de los permisos seleccionados no existe en el catalogo.',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function permisoIds(): array
    {
        return array_map('intval', (array) $this->input('permisos', []));
    }
}
