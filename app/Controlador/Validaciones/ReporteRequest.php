<?php

declare(strict_types=1);

namespace App\Controlador\Validaciones;

use App\Modelo\Asistencia;
use App\Modelo\MovimientoInstitucional;
use App\Modelo\Personal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validacion de los filtros de los tres reportes (HU-13, HU-14, HU-15).
 *
 * Un unico FormRequest para los tres porque comparten la mayor parte de
 * los filtros; los propios de cada uno son opcionales y se validan solo
 * si vienen presentes.
 *
 * Cubre CA-HU13-01, CA-HU14-01 y CA-HU15-01.
 */
class ReporteRequest extends FormRequest
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
            // Comunes
            'area_id' => ['nullable', 'integer', Rule::exists('area', 'area_id')],
            'personal_id' => ['nullable', 'integer', Rule::exists('personal', 'personal_id')],
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date', 'after_or_equal:desde'],
            'formato' => ['nullable', Rule::in(['PDF', 'EXCEL'])],

            // HU-13
            'establecimiento_id' => [
                'nullable', 'integer', Rule::exists('establecimiento', 'establecimiento_id'),
            ],
            'cargo' => ['nullable', 'string', 'max:80'],
            'condicion_laboral' => ['nullable', Rule::in(Personal::CONDICIONES)],

            // HU-15
            'tipo_movimiento_id' => [
                'nullable', 'integer', Rule::exists('tipo_movimiento', 'tipo_movimiento_id'),
            ],

            // Estado: su conjunto valido depende del reporte, por eso se
            // valida contra la union de los tres y el generador ignora
            // cualquier valor que no le corresponda.
            'estado' => [
                'nullable',
                Rule::in(array_merge(
                    [Personal::ESTADO_ACTIVO, Personal::ESTADO_INACTIVO],
                    Asistencia::ESTADOS,
                    MovimientoInstitucional::ESTADOS
                )),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hasta.after_or_equal' => 'La fecha final no puede ser anterior a la inicial.',
            'formato.in' => 'Solo se puede exportar a PDF o a Excel.',
            'condicion_laboral.in' => 'Seleccione una condicion laboral valida.',
            'estado.in' => 'El estado seleccionado no corresponde a este reporte.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'area_id' => 'area',
            'personal_id' => 'trabajador',
            'establecimiento_id' => 'establecimiento',
            'tipo_movimiento_id' => 'tipo de movimiento',
            'condicion_laboral' => 'condicion laboral',
            'desde' => 'fecha inicial',
            'hasta' => 'fecha final',
        ];
    }

    /**
     * Filtros ya limpios: se descartan los vacios para que el generador
     * no tenga que distinguir entre "" y null.
     *
     * @return array<string, mixed>
     */
    public function filtros(): array
    {
        return array_filter(
            $this->only([
                'establecimiento_id', 'area_id', 'personal_id', 'cargo',
                'condicion_laboral', 'tipo_movimiento_id', 'estado',
                'desde', 'hasta',
            ]),
            fn ($valor): bool => $valor !== null && $valor !== ''
        );
    }
}
