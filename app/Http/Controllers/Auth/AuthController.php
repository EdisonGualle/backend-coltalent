<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Auth\AuthenticationService;
use App\Services\Config\ConfigurationService;
use App\Utilities\TimeUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    protected $authService;
    protected $configService;
    protected $timeUtility;

    public function __construct(
        AuthenticationService $authService,
        ConfigurationService $configService,
        TimeUtility $timeUtility
    ) {
        $this->authService = $authService;
        $this->configService = $configService;
        $this->timeUtility = $timeUtility;
    }

    public function login(LoginRequest $request)
    {
        $maxAttempts = $this->configService->getMaxAttempts();
        $lockoutTime = $this->configService->getLockoutTime();

        $result = $this->authService->authenticate(
            $request->input('user'),
            $request->input('email'),
            $request->input('password'),
            $maxAttempts,
            $lockoutTime
        );

        if ($result['successful']) {
            return response()->json([
                'status' => true,
                'msg' => $result['message'],
                'data' => [
                    'id' => $result['id'],
                    'name' => $result['user'],
                    'email' => $result['email'],
                    'photo' => $result['photo'],
                    'state' => $result['state'],
                    'role' => $result['role'],
                    'token' => $result['token'],
                ]
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'msg' => $result['message']
            ], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['mensaje' => 'Sesión cerrada correctamente'], 200);
    }

  
}