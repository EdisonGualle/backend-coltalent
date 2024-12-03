<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Subrogación</title>
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
        }

        .header {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
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

        .responsibilities {
            margin-left: 20px;
            list-style-type: disc;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            Notificación de Subrogación
        </div>
        <div class="content">
            <p>Hola, <strong>{{ $details['delegateName'] }}</strong>,</p>
            <p>
                Se te ha asignado una subrogación con las siguientes responsabilidades, como parte del proceso de gestión de talento humano:
            </p>
            <div class="details">
                <p><strong>Asignado por:</strong> {{ $details['assignerName'] }}</p>
                <p><strong>Fecha de inicio:</strong> {{ $details['startDate'] }}</p>
                <p><strong>Fecha de fin:</strong> {{ $details['endDate'] ?? 'Indefinida' }}</p>
                <p><strong>Razón:</strong> {{ $details['reason'] }}</p>
                <p><strong>Responsabilidades:</strong></p>
                <ul class="responsibilities">
                    @foreach ($details['responsibilities'] as $responsibility)
                        <li>{{ $responsibility }}</li>
                    @endforeach
                </ul>
            </div>
            <p>
                Por favor, asegúrate de cumplir con las responsabilidades asignadas durante el periodo indicado. Si tienes alguna consulta, no dudes en comunicarte con el área de Talento Humano.
            </p>
        </div>
        <div class="footer">
            Gracias,<br>
            Tu equipo de Talento Humano
        </div>
    </div>
</body>

</html>
