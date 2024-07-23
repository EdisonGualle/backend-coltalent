<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\User\UserService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        try {
            $users = $this->userService->getAllUsers();
            return $this->respondWithSuccess('Usuarios obtenidos exitosamente.', $users);
        } catch (Exception $e) {
            return $this->respondWithError('Error al obtener los usuarios: ' . $e->getMessage(), 500);
        }
    }

    public function show($id)
    {
        try {
            $user = $this->userService->getUserById($id);
            return $this->respondWithSuccess('Usuario obtenido exitosamente.', $user);
        } catch (Exception $e) {
            return $this->respondWithError('Error al obtener el usuario: ' . $e->getMessage(), 500);
        }
    }

    public function store(CreateUserRequest $request)
    {
        try {
            $user = $this->userService->createUser($request->all());
            return $this->respondWithSuccess('Usuario creado exitosamente.', $user);
        } catch (Exception $e) {
            return $this->respondWithError('Error al crear el usuario: ' . $e->getMessage(), 500);
        }
    }

    public function update(UpdateUserRequest $request, $id)
    {
        try {
            $user = $this->userService->updateUser($id, $request->validated());
            return $this->respondWithSuccess('Usuario actualizado exitosamente.', $user);
        } catch (Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function updateUserPhoto(Request $request, $id)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            $user = $this->userService->updateUserPhoto($id, $request->file('photo'));
            return $this->respondWithSuccess('Foto de perfil actualizada exitosamente.', $user);
        } catch (Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function disableUser($id)
    {
        try {
            $user = $this->userService->disableUser($id);
            return $this->respondWithSuccess('Usuario deshabilitado exitosamente.', $user);
        } catch (Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function enableUser($id)
    {
        try {
            $user = $this->userService->enableUser($id);
            return $this->respondWithSuccess('Usuario habilitado exitosamente.', $user);
        } catch (Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function userAuth()
    {
        try {
            $user = $this->userService->getUserAuth();
            return $this->respondWithSuccess('Datos del usuario obtenidos correctamente.', $user);
        } catch (Exception $e) {
            return $this->respondWithError('Error al obtener los datos del usuario: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->userService->deleteUser($id);
            return $this->respondWithSuccess('Usuario eliminado exitosamente.');
        } catch (Exception $e) {
            return $this->respondWithError($e->getMessage(), 500);
        }
    }

    public function showConfiguration($id)
    {
        try {
            $userConfiguration = $this->userService->getUserConfiguration($id);
            return $this->respondWithSuccess('Configuración de usuario obtenida exitosamente.', $userConfiguration);
        } catch (Exception $e) {
            return $this->respondWithError('Error al obtener la configuración de usuario: ' . $e->getMessage(), 500);
        }
    }


    public function changePassword(Request $request)
{
    // Validar la solicitud
    $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:8|confirmed',
    ]);

    try {
        $message = $this->userService->changePassword(Auth::id(), $request->current_password, $request->new_password);
        return $this->respondWithSuccess($message);
    } catch (Exception $e) {
        return $this->respondWithError($e->getMessage(), 500);
    }
}


    private function respondWithSuccess(string $message, $data = []): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => true,
            'msg' => $message,
            'data' => $data,
        ]);
    }

    private function respondWithError(string $message, int $statusCode): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'status' => false,
            'msg' => $message,
        ], $statusCode);
    }
}