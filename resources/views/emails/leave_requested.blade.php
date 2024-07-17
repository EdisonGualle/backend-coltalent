<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Constancia de solicitud de permiso</title>
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

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            margin: 20px auto;
            /* Centrando el botón */
        }

        .button:hover {
            background-color: #45a049;
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

        .button-container {
            text-align: center;
            /* Asegura que el contenedor del botón esté centrado */
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>Constancia de solicitud de permiso</h2>
        </div>
        <div class="content">
            <p>Hola, <strong>{{ $employeeName }}</strong></p>
            <p>Has solicitado un permiso de tipo <span class="highlight">{{ $leaveType }}</span> para las siguientes
                fechas:</p>
            <div class="details">
                <p><strong>Fecha de inicio:</strong> {{ $startDate }}</p>
                @if ($endDate)
                    <p><strong>Fecha de fin:</strong> {{ $endDate }}</p>
                @endif
                @if ($startTime && $endTime)
                    <p><strong>Hora de inicio:</strong> {{ $startTime }}</p>
                    <p><strong>Hora de fin:</strong> {{ $endTime }}</p>
                @endif
                <p><strong>Duración total:</strong> {{ $duration }}</p>
                <p><strong>Motivo:</strong> {{ $leaveReason }}</p>
            </div>
            <p>El permiso está pendiente de aprobación por <strong>{{ $approverName }}</strong>.</p>
            <p>Te notificaremos una vez que se haya tomado una decisión.</p>
        </div>
        <div class="footer">
            <p>Gracias,</p>
            <p>Tu equipo de Talento Humano</p>
        </div>
    </div>
</body>

</html>