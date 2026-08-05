{{--
    Capa VISTA - Resultado del reporte en pantalla.

    Es la misma plantilla para los tres reportes: recibe un
    ResultadoReporte y lo dibuja. Las columnas, los totales y las
    agrupaciones vienen resueltos desde el generador, de modo que agregar
    un reporte nuevo no obliga a escribir otra vista.
--}}

{{-- Tarjetas KPI con los totales consolidados --}}
@if ($reporte->totales !== [])
    <div class="row g-3 mb-3">
        @foreach ($reporte->totales as $etiqueta => $valor)
            <div class="col-6 col-md-4 col-xl-3">
                <div class="card kpi-card h-100">
                    <div class="card-body py-3">
                        <div class="kpi-titulo">{{ $etiqueta }}</div>
                        <div class="kpi-valor">{{ $valor }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Tabla de resultados --}}
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <span class="fw-semibold">{{ $reporte->titulo }}</span>
            <div class="small text-muted fw-normal">{{ $reporte->filtrosLegibles() }}</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-light">{{ $reporte->cantidadFilas() }} registro(s)</span>
            <button type="button" class="btn btn-sm btn-outline-secondary no-imprimir" onclick="window.print()">
                <i class="bi bi-printer"></i>
            </button>
        </div>
    </div>

    @if ($reporte->estaVacio())
        {{-- Caso sin datos: la Figura 5.6 lo contempla explicitamente --}}
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox display-4 d-block mb-3 opacity-50"></i>
            <p class="mb-1"><strong>No hay datos con los filtros aplicados.</strong></p>
            <p class="small mb-0">Ajuste los filtros y vuelva a generar el reporte.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0 tabla-padron">
                <thead class="table-light">
                    <tr>
                        <th style="width:3rem">#</th>
                        @foreach ($reporte->columnas as $columna)
                            <th>{{ $columna }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                @foreach ($reporte->filas as $i => $fila)
                    <tr>
                        <td class="text-muted small">{{ $i + 1 }}</td>
                        @foreach ($fila as $valor)
                            <td class="small">{{ $valor }}</td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Subtotales por criterio --}}
@if (! $reporte->estaVacio() && $reporte->agrupaciones !== [])
    <div class="row g-3 mt-0">
        @foreach ($reporte->agrupaciones as $nombre => $valores)
            @continue($valores === [])
            <div class="col-12 col-lg-4">
                <div class="card h-100">
                    <div class="card-header">{{ $nombre }}</div>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 tabla-padron">
                            <tbody>
                            @foreach ($valores as $etiqueta => $valor)
                                <tr>
                                    <td class="small">{{ $etiqueta }}</td>
                                    <td class="text-end fw-semibold" style="width:5rem">{{ $valor }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

{{-- Pie con la trazabilidad --}}
<p class="small text-muted mt-3">
    Generado el {{ now()->format('d/m/Y H:i') }} por
    {{ auth()->user()->nombre_mostrado }} ·
    Los datos corresponden a los registros vigentes al momento de la consulta.
</p>
