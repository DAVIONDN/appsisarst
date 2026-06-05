{{-- Mensajes de resultado del sistema --}}

{{-- Exito --}}
@if (session('exito'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-1"></i> {{ session('exito') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Advertencia de acceso denegado / URL invalida --}}
@if (session('warning'))
    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-shield-exclamation fs-5 flex-shrink-0 mt-1"></i>
        <div>
            <strong>Acceso restringido</strong><br>
            <span class="small">{{ session('warning') }}</span>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Error general --}}
@if ($errors->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Errores de validacion --}}
@if ($errors->any() && $errors->count() > ($errors->has('error') ? 1 : 0))
    <div class="alert alert-warning" role="alert">
        <strong><i class="bi bi-exclamation-circle-fill me-1"></i> Revise los datos ingresados:</strong>
        <ul class="mb-0 mt-2 small">
            @foreach ($errors->all() as $mensaje)
                @continue($mensaje === $errors->first('error'))
                <li>{{ $mensaje }}</li>
            @endforeach
        </ul>
    </div>
@endif
