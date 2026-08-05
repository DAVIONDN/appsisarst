{{--
    Capa VISTA - Plantilla del PDF (RF-18).

    La consume ExportadorPdf a traves de dompdf. Se escribe con CSS basico
    y tablas porque dompdf no soporta flexbox ni grid, y las hojas de
    estilo del panel no le sirven.

    Es la misma plantilla para los tres reportes: recibe un
    ResultadoReporte y lo dibuja.
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $reporte->titulo }}</title>
    <style>
        @page { margin: 90px 30px 60px 30px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5px;
            color: #212529;
            margin: 0;
        }

        header {
            position: fixed;
            top: -70px; left: 0; right: 0;
            height: 60px;
            border-bottom: 2px solid #0d5aa7;
        }

        header .marca {
            font-size: 16px;
            font-weight: bold;
            color: #0b2d4d;
            letter-spacing: 1px;
        }

        header .sub { font-size: 8px; color: #6c757d; }
        header .titulo { font-size: 11px; font-weight: bold; margin-top: 4px; }

        footer {
            position: fixed;
            bottom: -40px; left: 0; right: 0;
            height: 30px;
            font-size: 7.5px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 4px;
        }

        .pagina:after { content: counter(page); }

        .filtros {
            background: #f2f5f9;
            border-left: 3px solid #0d5aa7;
            padding: 6px 8px;
            margin-bottom: 10px;
            font-size: 8px;
        }

        /*
            table-layout: fixed reparte el ancho entre las columnas y obliga
            al texto a partirse. Sin esto, con muchas columnas la tabla se
            desborda del papel y las ultimas columnas quedan cortadas.
        */
        table.datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            table-layout: fixed;
        }

        table.datos th {
            background: #0d5aa7;
            color: #fff;
            font-size: {{ $tamanoLetra }}px;
            text-align: left;
            padding: 4px 4px;
            border: 0.5px solid #0b2d4d;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        table.datos td {
            padding: 3px 4px;
            border: 0.5px solid #dee2e6;
            font-size: {{ $tamanoLetra }}px;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        /* La columna del numero de fila no necesita ancho completo. */
        table.datos th.n, table.datos td.n { width: 20px; text-align: right; }

        table.datos tr:nth-child(even) td { background: #f8f9fa; }

        h2 {
            font-size: 10px;
            color: #0b2d4d;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 3px;
            margin: 12px 0 6px;
        }

        table.resumen { width: 60%; border-collapse: collapse; margin-bottom: 10px; }
        table.resumen td { padding: 3px 6px; border-bottom: 0.5px solid #e9ecef; font-size: 8px; }
        table.resumen td.valor { text-align: right; font-weight: bold; width: 70px; }

        .vacio {
            text-align: center;
            padding: 30px;
            color: #6c757d;
            border: 1px dashed #dee2e6;
        }
    </style>
</head>
<body>

<header>
    <span class="marca">SISARST</span>
    <span class="sub"> · Sistema Web Integrado de Gestion del Personal — Red de Salud Tayacaja</span>
    <div class="titulo">{{ $reporte->titulo }}</div>
</header>

<footer>
    Generado el {{ $generadoEn->format('d/m/Y H:i') }} por {{ $generadoPor }} ·
    Documento de uso administrativo interno · Ley N.° 29733 de Proteccion de Datos Personales ·
    Pagina <span class="pagina"></span>
</footer>

<main>
    <div class="filtros">
        <strong>Filtros aplicados:</strong> {{ $reporte->filtrosLegibles() }}<br>
        <strong>Registros:</strong> {{ $reporte->cantidadFilas() }}
    </div>

    @if ($reporte->estaVacio())
        <div class="vacio">No hay datos que mostrar con los filtros aplicados.</div>
    @else
        <table class="datos">
            <thead>
                <tr>
                    <th class="n">#</th>
                    @foreach ($reporte->columnas as $columna)
                        <th>{{ $columna }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach ($reporte->filas as $i => $fila)
                <tr>
                    <td class="n">{{ $i + 1 }}</td>
                    @foreach ($fila as $valor)
                        <td>{{ $valor }}</td>
                    @endforeach
                </tr>
            @endforeach
            </tbody>
        </table>

        @if ($reporte->totales !== [])
            <h2>Resumen</h2>
            <table class="resumen">
                @foreach ($reporte->totales as $etiqueta => $valor)
                    <tr>
                        <td>{{ $etiqueta }}</td>
                        <td class="valor">{{ $valor }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @foreach ($reporte->agrupaciones as $nombre => $valores)
            @continue($valores === [])
            <h2>{{ $nombre }}</h2>
            <table class="resumen">
                @foreach ($valores as $etiqueta => $valor)
                    <tr>
                        <td>{{ $etiqueta }}</td>
                        <td class="valor">{{ $valor }}</td>
                    </tr>
                @endforeach
            </table>
        @endforeach
    @endif
</main>

</body>
</html>
