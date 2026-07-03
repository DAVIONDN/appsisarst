<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion del registro y la edicion de movimientos (HU-08).
 *
 * Cubre CA-HU08-01 (elegir el tipo), CA-HU08-02 (asociar a personal del
 * padron) y CA-HU08-03 (la fecha de fin no puede ser anterior a la de
 * inicio, que respalda la restriccion ck_movimiento_fechas).
 *
 * La exigencia de establecimiento de destino segun el tipo, el estado del
 * trabajador y el control de solapamiento son reglas de negocio y viven en
 * MovimientoService.
 */
class MovimientoRequest extends FormRequest
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
            'tipo_movimiento_id' => ['required', 'integer', Rule::exists('tipo_movimiento', 'tipo_movimiento_id')],
            'establecimiento_destino_id' => [
                'nullable', 'integer', Rule::exists('establecimiento', 'establecimiento_id'),
            ],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
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
            'tipo_movimiento_id.required' => 'Seleccione el tipo de movimiento.',
            'fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',
            'motivo.required' => 'Indique el motivo del movimiento.',
            'motivo.min' => 'El motivo debe explicar el movimiento (minimo 10 caracteres).',
            'establecimiento_destino_id.exists' => 'El establecimiento de destino no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'personal_id' => 'trabajador',
            'tipo_movimiento_id' => 'tipo de movimiento',
            'establecimiento_destino_id' => 'establecimiento de destino',
            'fecha_inicio' => 'fecha de inicio',
            'fecha_fin' => 'fecha de fin',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'motivo' => trim((string) $this->input('motivo')),
            'establecimiento_destino_id' => $this->input('establecimiento_destino_id') !== ''
                ? $this->input('establecimiento_destino_id')
                : null,
        ]);
    }
}
