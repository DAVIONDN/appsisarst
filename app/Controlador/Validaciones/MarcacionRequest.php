<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion de la marcacion de entrada y salida (HU-05).
 *
 * Cubre CA-HU05-01 (sello de tiempo) y CA-HU05-02 (la asistencia se asocia
 * a personal del padron). La verificacion de que el trabajador este ACTIVO
 * y la de no duplicar la jornada viven en AsistenciaService, porque son
 * reglas de negocio y no de formato.
 */
class MarcacionRequest extends FormRequest
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
            'personal_id' => ['required', 'integer', Rule::exists('personal', 'personal_id')],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'tipo' => ['required', Rule::in(['ENTRADA', 'SALIDA'])],
            'hora' => ['required', 'date_format:H:i'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'personal_id.required' => 'Seleccione al trabajador.',
            'personal_id.exists' => 'El trabajador seleccionado no existe en el padron.',
            'fecha.before_or_equal' => 'No se puede registrar asistencia de una fecha futura.',
            'hora.date_format' => 'La hora debe tener el formato HH:MM (por ejemplo 08:15).',
            'tipo.in' => 'La marcacion debe ser de entrada o de salida.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'personal_id' => 'trabajador',
            'fecha' => 'fecha',
            'hora' => 'hora',
        ];
    }

    /** Hora normalizada al formato HH:MM:SS que espera la columna TIME. */
    public function horaCompleta(): string
    {
        return $this->string('hora')->toString().':00';
    }
}
