/**
 * SISARST - Comportamiento del frontend (capa Vista).
 *
 * Solo interaccion de interfaz: confirmaciones, selector de fechas y
 * graficos del tablero. Ninguna regla de negocio vive aqui.
 */
import * as bootstrap from 'bootstrap';
import Swal from 'sweetalert2';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import Chart from 'chart.js/auto';

window.bootstrap = bootstrap;
window.Swal = Swal;
window.Chart = Chart;

document.addEventListener('DOMContentLoaded', () => {
    // --- Selector de fechas en espanol -------------------------------
    flatpickr('.js-fecha', {
        locale: Spanish,
        dateFormat: 'Y-m-d',
        allowInput: true,
        maxDate: 'today',
    });

    // --- Menu lateral en pantallas pequenas --------------------------
    const botonMenu = document.querySelector('.js-toggle-menu');
    if (botonMenu) {
        botonMenu.addEventListener('click', () => {
            document.querySelector('.sisarst-sidebar')?.classList.toggle('abierta');
        });
    }

    // --- Mensajes de resultado (SweetAlert2) -------------------------
    const alerta = document.getElementById('js-mensaje');
    if (alerta) {
        Swal.fire({
            icon: alerta.dataset.tipo || 'success',
            title: alerta.dataset.titulo || '',
            text: alerta.dataset.texto || '',
            timer: alerta.dataset.tipo === 'error' ? undefined : 3200,
            showConfirmButton: alerta.dataset.tipo === 'error',
            confirmButtonColor: '#0d5aa7',
        });
    }

    // --- HU-04: confirmacion antes de desactivar (CA-HU04-02) --------
    document.querySelectorAll('.js-desactivar').forEach((boton) => {
        boton.addEventListener('click', async (evento) => {
            evento.preventDefault();
            const formulario = document.querySelector(boton.dataset.form);

            const { value: motivo, isConfirmed } = await Swal.fire({
                title: '¿Desactivar al trabajador?',
                html: `<p class="mb-2">${boton.dataset.nombre}</p>
                       <p class="small text-muted mb-0">El registro se conserva en el padron
                       con estado <strong>INACTIVO</strong> y su historial permanece
                       disponible para auditoria.</p>`,
                input: 'textarea',
                inputLabel: 'Motivo de la baja',
                inputPlaceholder: 'Ej.: Renuncia voluntaria con fecha 30/06/2026',
                inputAttributes: { maxlength: 255 },
                inputValidator: (valor) => {
                    if (!valor || valor.trim().length < 10) {
                        return 'Escriba el motivo de la baja (minimo 10 caracteres).';
                    }
                    return null;
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si, desactivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            });

            if (isConfirmed && formulario) {
                formulario.querySelector('input[name="motivo_baja"]').value = motivo.trim();
                formulario.submit();
            }
        });
    });

    // --- Reactivacion -------------------------------------------------
    document.querySelectorAll('.js-reactivar').forEach((boton) => {
        boton.addEventListener('click', async (evento) => {
            evento.preventDefault();
            const formulario = document.querySelector(boton.dataset.form);

            const { isConfirmed } = await Swal.fire({
                title: '¿Reactivar al trabajador?',
                text: boton.dataset.nombre,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Si, reactivar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#198754',
            });

            if (isConfirmed && formulario) {
                formulario.submit();
            }
        });
    });

    // --- Confirmacion generica (horarios, etc.) ----------------------
    // Botones con clase js-confirmar: usa data-titulo, data-texto y data-form
    document.querySelectorAll('.js-confirmar').forEach((boton) => {
        boton.addEventListener('click', async (evento) => {
            evento.preventDefault();
            const formulario = document.querySelector(boton.dataset.form);

            const { isConfirmed } = await Swal.fire({
                title: boton.dataset.titulo || '¿Confirmar accion?',
                text: boton.dataset.texto || '',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Si, continuar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#dc3545',
            });

            if (isConfirmed && formulario) {
                formulario.submit();
            }
        });
    });

    // --- HU-08: toggle establecimiento de destino -------------------
    // El select tipo_movimiento_id tiene data-requiere-destino="1|0" y
    // data-destino="#bloque-destino". Al cambiar el tipo se muestra u
    // oculta el campo de destino y se ajusta el atributo required.
    document.querySelectorAll('select[data-destino]').forEach((select) => {
        const bloque = document.querySelector(select.dataset.destino);
        const campo  = bloque?.querySelector('select, input');

        function actualizarDestino() {
            const opcion = select.options[select.selectedIndex];
            const requiere = opcion?.dataset?.requiereDestino === '1';
            if (bloque) {
                bloque.style.display = requiere ? '' : 'none';
            }
            if (campo) {
                if (requiere) {
                    campo.setAttribute('required', '');
                } else {
                    campo.removeAttribute('required');
                    campo.value = '';
                }
            }
        }

        // Estado inicial al cargar (edicion con valor prellenado)
        actualizarDestino();
        select.addEventListener('change', actualizarDestino);
    });

    // --- Flatpickr para filtros de fecha sin restriccion de hoy ------
    // Los filtros de movimientos pueden apuntar a fechas futuras
    flatpickr('.js-fecha-libre', {
        locale: Spanish,
        dateFormat: 'Y-m-d',
        allowInput: true,
    });

    // --- HU-11: auto-completar correo al seleccionar trabajador ------
    // El select personal_id tiene data-correo-target="correo_institucional"
    // y data-username-target="username". Al elegir un trabajador, se
    // pre-rellena el correo y se sugiere el username desde los apellidos.
    const selectPersonal = document.querySelector('select[data-correo-target]');
    if (selectPersonal) {
        const campoCorreo   = document.getElementById(selectPersonal.dataset.correoTarget);
        const campoUsername = document.getElementById(selectPersonal.dataset.usernameTarget);

        selectPersonal.addEventListener('change', () => {
            const opcion = selectPersonal.options[selectPersonal.selectedIndex];
            const correo  = opcion?.dataset?.correo  ?? '';
            const nombres = opcion?.dataset?.nombres ?? '';

            // Solo rellena si el campo esta vacio (no sobreescribe lo que
            // el usuario ya escribio a mano ni el valor del old() en edicion)
            if (campoCorreo && campoCorreo.value === '') {
                campoCorreo.value = correo;
            }

            // Sugiere username = primera parte del apellido (sin tildes)
            if (campoUsername && campoUsername.value === '' && nombres !== '') {
                const sugerencia = nombres
                    .normalize('NFD')
                    .replace(/[̀-ͯ]/g, '')  // quita tildes
                    .replace(/[^a-z0-9.]/g, '');       // solo chars validos
                campoUsername.value = sugerencia.slice(0, 40);
            }
        });
    }

    // --- Graficos del tablero ----------------------------------------
    dibujarGrafico('grafico-condicion', 'doughnut');
    dibujarGrafico('grafico-area', 'bar');
});

function dibujarGrafico(id, tipo) {
    const lienzo = document.getElementById(id);
    if (!lienzo) return;

    const etiquetas = JSON.parse(lienzo.dataset.etiquetas || '[]');
    const valores = JSON.parse(lienzo.dataset.valores || '[]');
    if (etiquetas.length === 0) return;

    new Chart(lienzo, {
        type: tipo,
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Trabajadores',
                data: valores,
                backgroundColor: [
                    '#0d5aa7', '#198754', '#ffc107', '#dc3545',
                    '#0dcaf0', '#6f42c1', '#fd7e14', '#20c997',
                ],
                borderWidth: 0,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: tipo === 'doughnut' ? 'bottom' : 'none', display: tipo === 'doughnut' },
            },
            scales: tipo === 'bar'
                ? { y: { beginAtZero: true, ticks: { precision: 0 } } }
                : {},
        },
    });
}
