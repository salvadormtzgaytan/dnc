<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Resultado del examen</title>

    <style>
        @font-face {
            font-family: 'Comex';
            src: url('{{ storage_path("fonts/comex/comex-regular.ttf") }}') format('truetype');
        }

        body {
            font-family: 'Comex', 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .certificate-table {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            border-collapse: collapse;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .image-cell {
            width: 45%;
            vertical-align: top;
            background: #006992;
        }

        .content-cell {
            width: 55%;
            vertical-align: top;
            padding: 20px;
            background: #0085b7;
            color: white;
        }

        .certificate-image {
            width: 100%;
            height: 330px;
            object-fit: cover;
            display: block;
        }

        .placeholder-image {
            width: 100%;
            height: 330px;
            background: linear-gradient(135deg, #006992 0%, #0085b7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
        }

        .exam-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
            color: white;
            text-transform: uppercase;
        }

        .attempt-info {
            font-size: 14px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.8);
        }

        .grade-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        .grade-table td {
            padding: 5px 0;
            vertical-align: middle;
        }

        .grade-percentage {
            font-size: 28px;
            font-weight: bold;
            color: white;
        }

        .grade-text {
            font-size: 14px;
            padding-left: 10px;
        }

        .time-table {
            width: 100%;
            border-collapse: collapse;
        }

        .time-table td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 16px;
        }

        .time-label {
            font-weight: bold;
            width: 100px;
            color: rgba(255, 255, 255, 0.9);
            padding-right: 5px;
            /* espacio tras la etiqueta */
            font-size: 16px;
        }

        .divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.2);
            margin: 10px 0;
        }

        .compact-metrics {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        .compact-metrics th {
            background-color: #0085b7;
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: bold;
        }

        .compact-metrics td {
            padding: 15px;
            text-align: center;
            background-color: white;
            border: 1px solid #e5e7eb;
        }

        .metric-main {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            display: block;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 14px;
            color: #6b7280;
            display: block;
        }

        .ranking-detail {
            font-size: 16px;
            color: #6b7280;
            display: block;
            margin-top: 5px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .header-text {
            flex: 1;
        }

        .header-logo {
            margin-left: 20px;
            text-align: right;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-subtitle {
            font-size: 16px;
            color: #555;
            margin-bottom: 15px;
        }

        /* Estilos para la tabla de resultados */
        .results-table {
            width: 100%;
            max-width: 800px;
            margin: 30px auto;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .results-table td {
            padding: 20px;
            vertical-align: top;
            border: 1px solid #e5e7eb;
            background-color: white;
        }

        .results-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            display: block;
        }

        .chart-image {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 0 auto 15px auto;
        }

        .level-tag {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }

        .recommendations-list {
            margin: 0;
            padding-left: 20px;
        }

        .recommendations-list li {
            margin-bottom: 8px;
        }

        .content-cell {
            padding: 10px;
            /* antes 20px */
        }

        /* Métricas compactas */
        .compact-metrics th {
            padding: 8px;
            /* antes 12px */
            font-size: 12px;
            /* más pequeño */
        }

        .compact-metrics td {
            padding: 8px;
            /* antes 15px */
        }

        .metric-main {
            font-size: 18px;
            /* antes 24px */
        }

        .metric-label {
            font-size: 12px;
            /* antes 14px */
        }

        .ranking-detail {
            font-size: 12px;
            /* antes 16px */
            margin-top: 3px;
            /* un pelín menos */
        }

        /* Calificación */
        .grade-percentage {
            font-size: 22px;
            /* antes 28px */
        }

        .grade-text {
            font-size: 12px;
            /* antes 14px */
            padding-left: 5px;
            /* antes 10px */
        }

        .grade-table td {
            padding: 3px 0;
            /* antes 5px 0 */
        }

        /* Tiempos */
        .time-table td {
            padding: 2px 0;
            /* antes 3px 0 */
            font-size: 14px;
            /* antes 16px */
        }

        .time-label {
            font-size: 14px;
            /* antes 16px */
            width: 80px;
            /* antes 100px */
            padding-right: 4px;
            /* antes 5px */
        }

        /* Resultados (gráfico + feedback) */
        .results-table td {
            padding: 8px;
            /* antes 20px */
            font-size: 12px;
            /* todo texto más pequeño */
        }

        .results-title {
            font-size: 16px;
            /* antes 18px */
            margin-bottom: 10px;
            color: #006992;
            /* un poco más de separación */
        }

        .chart-image {
            margin-bottom: 10px;
            /* antes 15px */
        }

        .level-tag {
            padding: 0;
            /* antes 5px 15px */
            font-size: 12px;
            /* antes 14px */
        }

        .level-critical {
            color: #ef4444;
            /* Rojo para <=50 */
        }

        .level-medium {
            color: #f59e0b;
            /* Amarillo para <=75 */
        }

        .level-high {
            color: #10b981;
            /* Verde para <=100 */
        }

        .results-domain {
            display: block;
            width: 100%;
            text-align: center;

        }

    </style>
</head>
<body>


    <!-- Encabezado con logo a la derecha -->
    <div class="header-container">
        <div class="header-logo">
            <img src="{{ asset('img/logo-email.png') }}" alt="Comex" style="height: 40px;">
        </div>
    </div>
    <table class="certificate-table">
        <tr>
            <!-- Celda de la imagen -->
            <td class="image-cell">
                @if ($exam->image_path && file_exists(public_path('storage/' . $exam->image_path)))
                <img src="{{ storage_path('app/public/' . $exam->image_path) }}" alt="{{ $exam->name }}" class="certificate-image">
                @else
                <div class="placeholder-image">
                    {{ $exam->name }}
                </div>
                @endif
            </td>

            <!-- Celda del contenido -->
            <td class="content-cell">
                <h1 class="exam-title"> {{ $exam->name }}</h1>

                <div class="attempt-info">
                    Intento #{{ $attemptNumber }}<br>
                    {{ $attempt->user->name }} — {{ $attempt->user->email }}
                </div>

                <div class="divider"></div>

                <!-- Tabla para calificación -->
                <table class="grade-table">
                    <tr>
                        <td style="width: 30%;">
                            <span class="grade-percentage">{{ $percentage }}%</span>
                        </td>
                        <td>
                            <span class="grade-text">CALIFICACIÓN ({{ $earnedScore }} / {{ $maxScore }} pts)</span>
                        </td>
                    </tr>
                </table>

                <div class="divider"></div>

                <!-- Tabla para tiempos -->
                <table class="time-table">
                    <tr>
                        <td class="time-label">Inicio:</td>
                        <td>{{ $started->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="time-label">Fin:</td>
                        <td>{{ $finished->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="time-label">Duración:</td>
                        <td>{{ $durationFormatted }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Contenedor de métricas -->
    <table class="compact-metrics">
        <thead>
            <tr>
                <th style="background-color:#eab308;">CONTESTADAS</th>
                <th style="background-color:#22c55e;">CORRECTAS</th>
                <th style="background-color:#3b82f6;">RANKING</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <span class="metric-main">{{ count($attempt->answers) }}</span>
                    <span class="metric-label">Preguntas</span>
                </td>
                <td>
                    <span class="metric-main">{{ $correctCount }}</span>
                    <span class="metric-label">Respuestas</span>
                </td>
                <td>
                    <span class="metric-main">{{ $rankPosition }}</span>
                    <span class="ranking-detail">
                        @if ($dealership)
                        en {{ $dealership->name }}
                        @else
                        global
                        @endif
                        <br>de {{ $rankTotalUsers }} participantes
                    </span>
                </td>
            </tr>
        </tbody>
    </table>
    <!-- Tabla de resultados (imagen y recomendaciones) -->
    <table class="results-table">
        <tr>
            <!-- Columna de nivel de dominio -->
            <td width="50%">
                <span class="results-title results-domain">Nivel de dominio: <span class="level-text 
                @if($percentage <= 50) level-critical
                @elseif($percentage <= 75) level-medium
                @else level-high
                @endif">
                        {{ \App\Utils\ScoreColorHelper::level($percentage) }}
                    </span> </span>
                @if(!empty($chartImage))
                <img src="{{ $chartImage }}" class="chart-image">
                @endif

            </td>

            <!-- Columna de recomendaciones -->
            <td width="50%">
                <span class="results-title"> Retroalimentación personalizada</span>
                <div>
                    {!! $feedbackText !!}
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
