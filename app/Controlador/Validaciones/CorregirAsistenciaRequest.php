<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use App\Modelo\Asistencia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion de la correccion manual de una jornada.
 *
 * Solo valida formato. La coherencia entre estado, hora de entrada y hora
 * de salida -que la base impone con ck_asistencia_coherencia- la verifica
 * AsistenciaService, para poder dar un mensaje entendible en lugar de un
 * error de MySQL.
 */
class CorregirAsistenciaRequest extends FormRequest
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
            'estado' => ['required', Rule::in(Asistencia::ESTADOS)],
            'hora_entrada' => ['nullable', 'date_format:H:i'],
            'hora_salida' => ['nullable', 'date_format:H:i', 'after_or_equal:hora_entrada'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'estado.in' => 'Seleccione un estado valido: puntual, tardanza, falta o justificado.',
            'hora_entrada.date_format' => 'La hora de entrada debe tener el formato HH:MM.',
            'hora_salida.date_format' => 'La hora de salida debe tener el formato HH:MM.',
            'hora_salida.after_or_equal' => 'La hora de salida no puede ser anterior a la de entrada.',
        ];
    }

    /**
     * Datos ya normalizados al formato TIME de la base.
     *
     * @return array<string, mixed>
     */
    public function datosNormalizados(): array
    {
        return [
            'estado' => $this->string('estado')->toString(),
            'hora_entrada' => $this->input('hora_entrada') ? $this->input('hora_entrada').':00' : null,
            'hora_salida' => $this->input('hora_salida') ? $this->input('hora_salida').':00' : null,
            'observacion' => $this->input('observacion'),
        ];
    }
}
