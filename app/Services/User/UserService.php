<?php

namespace App\Services\User;


use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\Other\UserState;
use App\Models\Role;
use Exception;

class UserService
{


    public function getUserConfiguration($id)
    {
        $user = User::with('employee.contact')->findOrFail($id);

        return [
            'full_name' => $user->employee->getFullNameAttribute(),
            'personal_phone' => $user->employee->contact->personal_phone,
            'personal_email' => $user->employee->contact->personal_email,
            'photo' => $user->photo,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
    public function getAllUsers()
    {
        $users = User::with('userState', 'role')->get();
        $usersData = $users->map(function ($user) {
            $userData = $user->toArray();
            unset ($userData['user_state_id'], $userData['role_id']);
            return $userData;
        });
        return $usersData;
    }

    public function getUserById($id)
    {
        $user = User::with('userState', 'role')->findOrFail($id);
        $userData = $user->toArray();
        unset($userData['user_state_id'], $userData['role_id']);
        return $userData;
    }
    public function createUser(array $data): array
    {
        try {
            $data['user_state_id'] = $data['user_state_id'] ?? UserState::where('name', 'Activo')->firstOrFail()->id;
            $data['password'] = Hash::make($data['password']);
    
            // Asignar rol por defecto si no se proporciona uno
            $data['role_id'] = $data['role_id'] ?? Role::where('name', 'empleado')->firstOrFail()->id;
    
            $user = User::create($data);
    
            // Cargar relaciones role y user_state
            $user->load('role', 'userState');
    
            // Obtener los datos del usuario con las relaciones cargadas
            $userData = $user->toArray();
    
            // Eliminar role_id y user_state_id del array de datos
            unset($userData['role_id'], $userData['user_state_id']);
    
            return $userData;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
    
    
    public function updateUser($id, $data)
    {
        $user = User::with('userState', 'role')->findOrFail($id);

        DB::beginTransaction();
        try {
            // Actualizar los datos del usuario
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            $user->update($data);

            DB::commit();

            // Eliminar el campo user_state_id de la respuesta
            $userData = $user->toArray();
            unset($userData['user_state_id'], $userData['role_id']);

            return $userData;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    public function updateUserPhoto($id, $photo)
    {
        $user = User::with('userState', 'role')->findOrFail($id);

        $oldPhotoPath = $user->photo;

        DB::beginTransaction();
        try {
            // Subir la nueva foto y obtener su ruta
            $newPhotoPath = $photo->store('users_photo', 'public');

            // Asignar la nueva foto al usuario
            $user->photo = $newPhotoPath;
            $user->save();

            // Eliminar la foto anterior si existe y es diferente a la nueva
            if ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
                Storage::disk('public')->delete($oldPhotoPath);
            }
            DB::commit();
            // Eliminar el campo user_state_id de la respuesta
            $userData = $user->toArray();
            unset($userData['user_state_id'], $userData['role_id']);
            return $userData;

        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al actualizar la foto de perfil: ' . $e->getMessage());
        }
    }

    public function getUserAuth()
    {
        $user = Auth::user();
    
        if (!$user) {
            throw new Exception("El usuario no está autenticado.");
        }
    
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'photo' => $user->photo,
            'state' => $user->userState?->name ?? null,
            'role' => $user->role?->name ?? null,
        ];
    }

    
    public function disableUser($id)
    {
        $user = User::findOrFail($id);
        $inactiveStateId = UserState::where('name', 'Inactivo')->firstOrFail()->id;

        DB::beginTransaction();
        try {
            $user->user_state_id = $inactiveStateId;
            $user->save();
            DB::commit();

            $userData = $user->load('userState', 'role')->toArray();
            unset($userData['user_state_id'], $userData['role_id']);
    
            return $userData;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function enableUser($id)
{
    $user = User::findOrFail($id);
    $activeStateId = UserState::where('name', 'Activo')->firstOrFail()->id;

    DB::beginTransaction();
    try {
        $user->user_state_id = $activeStateId;
        $user->save();
        DB::commit();

        $userData = $user->load('userState', 'role')->toArray();
        unset($userData['user_state_id'], $userData['role_id']);

        return $userData;
    } catch (Exception $e) {
        DB::rollBack();
        throw new Exception('Error al habilitar al usuario: ' . $e->getMessage());
    }
}

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        DB::beginTransaction();
        try {
            // Eliminar la foto de perfil si existe
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            // Eliminar todos los tokens de autenticación del usuario
            $user->tokens()->delete();

            // Eliminar el usuario
            $user->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al eliminar el usuario: ' . $e->getMessage());
        }
    }

}