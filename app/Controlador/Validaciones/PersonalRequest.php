<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use App\Modelo\Personal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion del formulario del padron (HU-01 y HU-02).
 *
 * Cubre los criterios CA-HU01-02 (campos obligatorios), CA-HU01-03 (DNI
 * unico), CA-HU01-05 (el registro solo se completa si todo valida) y
 * CA-HU02-03 (los datos modificados son validados).
 *
 * Los campos obligatorios son los que fija la historia HU-01: DNI, nombres,
 * apellidos, cargo, area, condicion laboral, telefono y correo. Nota: la
 * base de datos admite NULL en telefono y correo, de modo que la regla mas
 * estricta la impone la aplicacion, no el esquema.
 */
class PersonalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorizacion se resuelve en el middleware "permiso" de la ruta.
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $personal = $this->route('personal');
        $idActual = $personal instanceof Personal ? $personal->personal_id : null;

        return [
            'dni' => [
                'required',
                'digits:8',
                Rule::unique('personal', 'dni')->ignore($idActual, 'personal_id'),
            ],
            // Solo letras (incluyendo tildes y ñ), espacios, guiones y puntos (CA-HU01-02)
            'nombres'  => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s\-\.\']+$/u'],
            'apellidos' => ['required', 'string', 'max:100', 'regex:/^[\p{L}\s\-\.\']+$/u'],
            // Cargo: empieza con letra, puede tener letras/espacios/guiones/puntos/barras
            // y opcionalmente termina en un ordinal numerico ("Nivel 2", "Grado III").
            // Rechaza mezclas aleatorias como "qwqw122" (CA-HU01-02).
            'cargo'    => ['required', 'string', 'min:3', 'max:80',
                           'regex:/^[\p{L}][\p{L}\s\-\.\/]*(\s[\dIVXivx]{1,5})?$/u'],
            'area_id'  => ['required', 'integer', Rule::exists('area', 'area_id')],
            'horario_id' => ['nullable', 'integer', Rule::exists('horario', 'horario_id')],
            'condicion_laboral' => ['required', Rule::in(Personal::CONDICIONES)],
            // Solo digitos, +, - y espacios; minimo 6 caracteres (CA-HU01-02)
            'telefono' => ['required', 'string', 'max:15', 'regex:/^[0-9+\- ]{6,15}$/'],
            'correo'   => ['required', 'email:rfc', 'max:120'],
            'fecha_ingreso' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'dni.required'      => 'El DNI es obligatorio.',
            'dni.digits'        => 'El DNI debe tener exactamente 8 digitos numericos.',
            'dni.unique'        => 'Ya existe un trabajador registrado con ese DNI.',
            'nombres.required'  => 'El nombre es obligatorio.',
            'nombres.regex'     => 'Los nombres solo deben contener letras, espacios y guiones (sin numeros).',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.regex'   => 'Los apellidos solo deben contener letras, espacios y guiones (sin numeros).',
            'telefono.required' => 'El telefono es obligatorio.',
            'telefono.regex'    => 'El telefono solo admite digitos (0-9), espacios, "+" y "-". No se permiten letras.',
            'cargo.required'    => 'El cargo es obligatorio.',
            'cargo.min'         => 'El cargo debe tener al menos 3 caracteres.',
            'cargo.regex'       => 'El cargo debe iniciar con letras. No se permiten mezclas de letras y numeros (ej: qwqw122). Ejemplo valido: Medico Cirujano.',
            'correo.required'   => 'El correo electronico es obligatorio.',
            'correo.email'      => 'Ingrese un correo electronico valido (ejemplo: nombre@dominio.com).',
            'fecha_ingreso.before_or_equal' => 'La fecha de ingreso no puede ser futura.',
            'condicion_laboral.in' => 'Seleccione una condicion laboral valida.',
            'area_id.exists'    => 'El area seleccionada no existe en el sistema.',
            'horario_id.exists' => 'El horario seleccionado no existe en el sistema.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'dni' => 'DNI',
            'area_id' => 'area',
            'horario_id' => 'horario',
            'condicion_laboral' => 'condicion laboral',
            'fecha_ingreso' => 'fecha de ingreso',
            'correo' => 'correo electronico',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dni' => trim((string) $this->input('dni')),
            'nombres' => trim((string) $this->input('nombres')),
            'apellidos' => trim((string) $this->input('apellidos')),
            'cargo' => trim((string) $this->input('cargo')),
            'correo' => mb_strtolower(trim((string) $this->input('correo'))),
            'horario_id' => $this->input('horario_id') !== '' ? $this->input('horario_id') : null,
        ]);
    }
}
