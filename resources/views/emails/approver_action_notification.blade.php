<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Acción Realizada</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .header {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
        }

        .content {
            padding: 20px;
        }

        .footer {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: center;
        }

        .details {
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .details p {
            margin: 5px 0;
        }

        .highlight {
            font-weight: bold;
            color: #4CAF50;
        }

        .rejection {
            color: red;
            font-weight: bold;
        }

        .evaluation, .comment {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Notificación de Acción Realizada</h2>
        </div>
        <div class="content">
            <p>Hola, <strong>{{ $approverName }}</strong></p>
            <p>Has tomado la siguiente acción sobre la solicitud de permiso del empleado <span class="highlight">{{ $employeeName }}</span>:</p>
            <div class="details">
                <p><strong>Tipo de permiso:</strong> {{ $leaveType }}</p>
                <p><strong>Motivo:</strong> {{ $leaveReason }}</p>
                <p><strong>Fecha de inicio:</strong> {{ $startDate }}</p>
                @if ($endDate)
                    <p><strong>Fecha de fin:</strong> {{ $endDate }}</p>
                @endif
                @if ($startTime && $endTime)
                    <p><strong>Hora de inicio:</strong> {{ $startTime }}</p>
                    <p><strong>Hora de fin:</strong> {{ $endTime }}</p>
                @endif
                <p><strong>Duración total:</strong> {{ $duration }}</p>
            </div>
            <p>Acción tomada: 
                @if ($headerColor === 'green')
                    <span class="highlight" style="color: green;">{{ $action }}</span>
                @elseif ($headerColor === 'blue')
                    <span class="highlight" style="color: blue;">{{ $action }}</span>
                @else
                    <span class="rejection">{{ $action }}</span>
                @endif
            </p>
            @if ($comment)
                <p class="comment"><strong>Comentario:</strong> {{ $comment }}</p>
            @endif
            @if ($isRejection)
                <p class="rejection"><strong>Motivo de rechazo:</strong> {{ $rejectionReason }}</p>
            @endif
            <p class="evaluation"><strong>Fecha de acción:</strong> {{ $evaluationDate }}</p>
        </div>
        <div class="footer">
            <p>Gracias,</p>
            <p>Tu equipo de Talento Humano</p>
        </div>
    </div>
</body>
</html>
