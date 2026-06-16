<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use App\Modelo\Horario;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion del catalogo de horarios (HU-16 / CA-HU16-01).
 *
 * Las reglas replican las restricciones de la base:
 *   ck_horario_rango      -> hora_salida > hora_entrada
 *   ck_horario_tolerancia -> tolerancia_min entre 0 y 60
 *   uq_horario_nombre     -> el nombre es unico
 */
class HorarioRequest extends FormRequest
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
        $horario = $this->route('horario');
        $idActual = $horario instanceof Horario ? $horario->horario_id : null;

        return [
            'nombre' => [
                'required',
                'string',
                'max:80',
                Rule::unique('horario', 'nombre')->ignore($idActual, 'horario_id'),
            ],
            'hora_entrada' => ['required', 'date_format:H:i'],
            'hora_salida' => ['required', 'date_format:H:i', 'after:hora_entrada'],
            'tolerancia_min' => ['required', 'integer', 'between:0,60'],
            'dias_laborales' => ['required', Rule::in(array_keys(Horario::DIAS_LABORALES))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.unique' => 'Ya existe un horario con ese nombre.',
            'hora_salida.after' => 'La hora de salida debe ser posterior a la de entrada.',
            'tolerancia_min.between' => 'La tolerancia debe estar entre 0 y 60 minutos.',
            'dias_laborales.in' => 'Seleccione un patron de dias laborales valido.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hora_entrada' => 'hora de entrada',
            'hora_salida' => 'hora de salida',
            'tolerancia_min' => 'tolerancia',
            'dias_laborales' => 'dias laborales',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        return [
            'nombre' => trim($this->string('nombre')->toString()),
            'hora_entrada' => $this->string('hora_entrada')->toString().':00',
            'hora_salida' => $this->string('hora_salida')->toString().':00',
            'tolerancia_min' => $this->integer('tolerancia_min'),
            'dias_laborales' => $this->string('dias_laborales')->toString(),
        ];
    }
}
