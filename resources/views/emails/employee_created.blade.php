<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido al GADMC Colta</title>
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
        }

        .content {
            padding: 20px;
            text-align: left;
        }

        .footer {
            background-color: #f5f5f5;
            padding: 10px;
            text-align: center;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #e0e0e0;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            margin: 20px auto; /* Centrando el botón */
        }

        .button:hover {
            background-color: #ccc;
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

        .button-container {
            text-align: center; /* Asegura que el contenedor del botón esté centrado */
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Bienvenido al GADMC Colta</h2>
        </div>
        <div class="content">
            <p>Hola, <strong>{{ $employee->getFullNameAttribute() }}</strong></p>
            <p>Estamos encantados de tenerte en nuestra empresa. A continuación, te proporcionamos tus credenciales de acceso:</p>
            <div class="details">
                <p><strong>Nombre de usuario:</strong> {{ $user->name }}</p>
                <p><strong>Contraseña:</strong> {{ $password }}</p>
            </div>
            <p>Puedes ingresar al sistema utilizando tu correo electrónico: <strong>{{ $user->email }}</strong></p>
            <div class="button-container">
                <a class="button" href="{{ $loginUrl }}">Iniciar sesión</a>
            </div>
            <p>Te deseamos mucho éxito en tu nuevo puesto.</p>
        </div>
        <div class="footer">
            <p>Gracias,</p>
            <p>Tu equipo de Talento Humano</p>
        </div>
    </div>
</body>
</html>
        