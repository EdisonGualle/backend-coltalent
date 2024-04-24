<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Other\UserState;
use App\Models\User;
use Illuminate\Http\Request;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Models\PasswordResetToken;

class ResetPasswordController extends Controller
{
    public function changePassword(Request $request)
    {
        try {
            // Buscar al usuario por el nombre de usuario o correo en la tabla 'users'
            $user = User::where('name', $request->name)
                ->orWhere('email', $request->email)
                ->first(['name', 'email', 'user_state_id']);

            if ($user && isset($user->email)) {
                // Verificar si el usuario está inactivo
                if (!$this->isUserActive($user)) {
                    $mensajeError = $request->name
                        ? 'Usuario inactivo.'
                        : 'Correo inactivo.';

                    return response()->json(['success' => false, 'Mensaje' => 'Error. ' . $mensajeError]);
                }

                // Generar un token aleatorio
                $token = Str::random(60);

                // Construir la URL con el token
                $domain = URL::to('/');
                $url = $domain . '/recuperar-contraseña?token=' . $token;

                // Configurar datos para el correo
                $datos['url'] = $url;
                $datos['email'] = $user->email;
                $datos['title'] = "Recuperar Contraseña";
                $datos['body'] = 'Por favor, haz clic en el enlace para restablecer tu contraseña:';

                // Enviar el correo
                Mail::send('correoRecuperacionContrasena', ['datos' => $datos], function ($message) use ($datos) {
                    $message->to($datos['email'])->subject($datos['title']);
                });

                // Obtener la fecha y hora actual
                $now = Carbon::now()->format('Y-m-d H:i:s');

                // Buscar si ya existe un registro para este correo electrónico
                $existingToken = PasswordResetToken::where('email', $user->email)->first();

                if ($existingToken) {
                    // Si ya existe un registro, actualiza el token existente
                    $existingToken->update([
                        'token' => $token,
                        'created_at' => $now
                    ]);
                } else {
                    // Si no existe un registro, crea uno nuevo
                    PasswordResetToken::create([
                        'email' => $user->email,
                        'token' => $token,
                        'created_at' => $now
                    ]);
                }

                // Respuesta JSON de éxito
                return response()->json([
                    'success' => true,
                    'msg' => 'Por favor revisa tu correo para restablecer tu contraseña',
                    'email_enviado' => $datos['email']
                ]);
            } else {
                return response()->json(['success' => false, 'msg' => 'Credenciales inválidas.']);
            }
        } catch (\Exception $e) {
            // Manejar excepciones y devolver una respuesta JSON
            return response()->json(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    // Función para verificar si el usuario está activo
    private function isUserActive($user)
    {
        $estadoActivo = UserState::where('name', 'Activo')->first();
        return $user->userState->name == $estadoActivo->name;
    }

    // Cargar vista para restablecer contraseña
    public function cargarRestablecerContraseña(Request $request)
    {
        // Buscar el registro en la tabla password_reset_tokens
        $restablecerDatos = PasswordResetToken::where('token', $request->token)->first();

        if ($restablecerDatos) {
            // Si se encuentra, buscar al usuario por el correo en la tabla 'users'
            $user = User::where('email', $restablecerDatos->email)->first();

            if ($user) {

                // Devolver la vista con la información del usuario
                return view('restablecerContraseña', compact('user'));
            } else {
                // El correo no corresponde a un usuario
                return view('404');
            }
        } else {
            // No se encontró el token en la tabla password_reset_tokens
            return view('404');
        }
    }

    // Funcionalidad para restablecer la contraseña
    public function restablecerContraseña(Request $request)
    {

        // Verifica si el formulario se envió correctamente
        if ($request->isMethod('post')) {
            // Valida los datos del formulario
            $request->validate([
                'password' => 'required|string|min:6|confirmed',
            ]);

            // Encuentra al usuario por su ID
            $user = User::find($request->id);


            if ($user) {
                // Actualiza la contraseña del usuario con el hash
                $user->password = Hash::make($request->password);
                $user->save();

                // Elimina el token de restablecimiento asociado con el correo del empleado
                PasswordResetToken::where('email', $user->email)->delete();

               // Mensaje de éxito con estilos
            return response('<div style="text-align: center; background-color: #f3f4f6; padding: 20px; border-radius: 10px;"><h1 style="color: #333;">Tu contraseña se ha restablecido exitosamente.</h1></div>');
            } else {
                // Usuario no encontrado
                return response()->json(['successful' => false, 'error' => 'Usuario no encontrado'], 404);
            }
        }
    }
}