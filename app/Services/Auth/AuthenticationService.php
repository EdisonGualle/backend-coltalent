<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\Other\State;
use App\Models\Other\UserState;
use App\Utilities\TimeUtility;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AuthenticationService
{
    protected $timeUtility;

    public function __construct(TimeUtility $timeUtility)
    {
        $this->timeUtility = $timeUtility;
    }

    public function authenticate($username, $email, $password, $maxAttempts, $lockoutTime)
    {
        $user = $this->getUserByCredentials($username, $email);

        if (!$user) {
            return $this->handleUserNotFound($username, $email);
        }

        if (!$this->isUserActive($user)) {
            return $this->handleInactiveUser($username, $email);
        }

        $failedAttempts = $user->failed_attempts ?? 0;

        if ($failedAttempts >= $maxAttempts) {
            if ($this->isUserBlocked($user, $lockoutTime)) {
                $remainingTime = $this->getRemainingBlockTime($user);
                return $this->handleBlockedUser($remainingTime);
            } else {
                $user->update(['failed_attempts' => 0]);
            }
        }

        if ($this->attemptLogin($user, $password)) {
            $user->update(['failed_attempts' => 0]);
            return $this->handleSuccessfulLogin($user);
        }

        $failedAttempts++;
        $user->update(['failed_attempts' => $failedAttempts]);

        if ($failedAttempts >= $maxAttempts) {
            $blockedUntil = now()->addMinutes($lockoutTime);
            $user->update(['blocked_until' => $blockedUntil]);
            $remainingTime = $lockoutTime * 60;
            return $this->handleBlockedUser($remainingTime);
        }

        return $this->handleFailedLogin();
    }

    protected function getUserByCredentials($username, $email)
    {
        return User::where(function ($query) use ($username, $email) {
            $query->where('name', $username)
                ->orWhere('email', $email);
        })->first();
    }



    protected function isUserActive($user)
    {
        $activeState = UserState::where('name', 'Activo')->first();
        return $user->user_state_id == $activeState->id;
    }


    protected function isUserBlocked($user, $lockoutTime)
    {
        $blockedUntil = $user->blocked_until;

        // Asegurarse de que $blockedUntil sea un objeto Carbon
        $blockedUntil = Carbon::parse($blockedUntil);

        return $blockedUntil && now()->lt($blockedUntil->addMinutes($lockoutTime));
    }

    protected function getRemainingBlockTime($user)
    {
        $blockedUntil = $user->blocked_until;
        return now()->diffInSeconds($blockedUntil);
    }

    protected function attemptLogin($user, $password)
    {
        return Auth::attempt(['email' => $user->email, 'password' => $password]);
    }

    protected function handleUserNotFound($username, $email)
    {
        $message = 'Credenciales incorrectas.';

        return ['successful' => false, 'message' => $message];
    }

    protected function handleInactiveUser($username, $email)
    {
        $message = $username
            ? 'Usuario inactivo.'
            : 'Correo inactivo.';

        return ['successful' => false, 'message' => $message];
    }

    protected function handleBlockedUser($remainingTime)
    {
        $formattedTime = $this->timeUtility->formatTime($remainingTime);
        $message = "Usuario bloqueado. Intenta nuevamente en $formattedTime.";

        return ['successful' => false, 'message' => $message, 'remainingTime' => $remainingTime];
    }

    protected function handleSuccessfulLogin($user)
    {
        // Eliminar tokens existentes antes de crear uno nuevo
        $user->tokens()->delete();

        // Generar un nuevo token
        $token = $user->createToken('API_TOKEN')->plainTextToken;

        return [
            'successful' => true,
            'message' => 'Inicio de sesión exitoso',
            'id' => $user->id,
            'user' => $user->name,
            'email' => $user->email,
            'photo' => $user->photo,
            'state' => $user->userState?->name ?? null,
            'role' => $user->role?->name ?? null,
            'token' => $token,
        ];
    }

    protected function handleFailedLogin()
    {
        return ['successful' => false, 'message' => 'Contraseña incorrecta.'];
    }
}