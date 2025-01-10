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
use Illuminate\Support\Facades\Log;

class UserService
{

    public function getUserConfiguration($id)
    {
        $user = User::with('employee.contact')->findOrFail($id);

        return [
            'full_name' => $user->employee->getFullNameAttribute(),
            'personal_phone' => $user->employee->contact->personal_phone ?? null,
            'personal_email' => $user->employee->contact->personal_email ?? null,
            'photo' => $user->photo ?? null,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
    
    public function getAllUsers()
    {
        $users = User::with('userState', 'role', 'employee')->get();
        $usersData = $users->map(function ($user) {
            $userData = $user->toArray();

            if ($user->employee) {
                $name = $user->employee->getNameAttribute();
            } else {
                $name = null;
            }
            $userData['employee_name'] = $name;
            unset ($userData['user_state_id'], $userData['role_id'], $userData['employee']);

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
            
            // Generate a random password
            $password = $this->generateRandomPassword();
            $data['password'] = Hash::make($password);

            // Asignar rol por defecto si no se proporciona uno
            $data['role_id'] = $data['role_id'] ?? Role::where('name', 'empleado')->firstOrFail()->id;

            $user = User::create($data);

            // Cargar relaciones role y user_state
            $user->load('role', 'userState');

            // Obtener los datos del usuario con las relaciones cargadas
            $userData = $user->toArray();

            // Eliminar role_id y user_state_id del array de datos
            unset($userData['role_id'], $userData['user_state_id']);

            // Add the generated password to the response
            $userData['password'] = $password;

            return $userData;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function generateRandomPassword(): string
    {
        // Generate a random password using a combination of letters, numbers, and symbols
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
        $password = '';
        $length = 10;

        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $password;
    }


    public function updateUser($id, $data)
    {
        $user = User::findOrFail($id);
    
        DB::beginTransaction();
        try {
            // Actualizar los datos del usuario
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }
            DB::commit();
    
            $userData = $user->load('userState', 'role', 'employee')->toArray();
            if ($user->employee) {
                $userData['employee_name'] = $user->employee->getNameAttribute();
            } else {
                $userData['employee_name'] = null;
            }
         
            return $userData;
    
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al actualizar el usuario: ' . $e->getMessage());
        }
    }
    
    public function updateUserPhoto($id, $photo)
    {
        $user = User::findOrFail($id);
    
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
    
            return response()->json([
                'photo' => $newPhotoPath
            ]);
    
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
            'employee_id' => $user->employee_id,
            'employee_name' => $user->employee->getNameAttribute(),
            'name' => $user->name,
            'email' => $user->email,
            'photo' => $user->photo,
            'state' => $user->userState?->name ?? null,
            'role' => $user->role?->name ?? null,
        ];
    }


    public function disableUser($id)
    {
        $currentUserId = auth()->id(); // Obtener el ID del usuario actual
        if ($id == $currentUserId) {
            throw new Exception('No se puede desactivar a sí mismo');
        }
    
        $user = User::findOrFail($id);
        $inactiveStateId = UserState::where('name', 'Inactivo')->firstOrFail()->id;
    
        DB::beginTransaction();
        try {
            $user->user_state_id = $inactiveStateId;
            $user->save();
            DB::commit();
    
            $userData = $user->load('userState', 'role', 'employee')->toArray();
            if ($user->employee) {
                $userData['employee_name'] = $user->employee->getNameAttribute();
            } else {
                $userData['employee_name'] = null;
            }
            unset($userData['user_state_id'], $userData['role_id'], $userData['employee']);
    
            return $userData;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error al desactivar el usuario: ' . $e->getMessage());
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
    
            $userData = $user->load('userState', 'role', 'employee')->toArray();
            if ($user->employee) {
                $userData['employee_name'] = $user->employee->getNameAttribute();
            } else {
                $userData['employee_name'] = null;
            }
            unset($userData['user_state_id'], $userData['role_id'], $userData['employee']);
    
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


    public function changePassword($userId, $currentPassword, $newPassword)
{
    $user = User::findOrFail($userId);

    // Verificar la contraseña actual
    if (!Hash::check($currentPassword, $user->password)) {
        throw new Exception('La contraseña actual no es correcta.');
    }

    // Actualizar la contraseña
    $user->password = Hash::make($newPassword);
    $user->save();

    return 'Contraseña actualizada correctamente.';
}

}