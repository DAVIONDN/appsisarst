{{--
    Capa VISTA - Botonera compartida por los tres reportes.

    Los botones de exportacion se generan recorriendo los exportadores
    registrados: si manana se agrega una estrategia CSV, su boton aparece
    solo. La vista no conoce ningun formato en concreto, que es lo que
    persigue el patron Estrategia de la Figura 5.5.
--}}
<div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-search me-1"></i> Generar reporte
    </button>

    <a href="{{ $ruta }}" class="btn btn-outline-secondary">
        <i class="bi bi-x-circle me-1"></i> Limpiar filtros
    </a>

    <div class="ms-auto d-flex flex-wrap gap-2">
        {{-- RF-18: exportacion como extension opcional del reporte --}}
        @foreach ($exportadores as $exportador)
            <button type="submit" name="formato" value="{{ $exportador->formato() }}"
                    class="btn btn-outline-{{ $exportador->formato() === 'PDF' ? 'danger' : 'success' }}">
                <i class="bi {{ $exportador->icono() }} me-1"></i> {{ $exportador->etiqueta() }}
            </button>
        @endforeach
    </div>
</div>

<p class="small text-muted mt-2 mb-0">
    <i class="bi bi-info-circle me-1"></i>
    La exportacion aplica los mismos filtros que la consulta en pantalla.
    Cada generacion queda registrada con su autor y sus filtros (RNF-10).
</p>
