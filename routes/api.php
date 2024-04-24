<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Employee\Backgrounds\PublicationController;
use App\Http\Controllers\Employee\Backgrounds\WorkExperienceController;
use App\Http\Controllers\Employee\Backgrounds\WorkReferenceController;
use App\Http\Controllers\Employee\Education\FormalEducationController;
use App\Http\Controllers\Employee\Education\TrainingController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Organization\DepartmentController;
use App\Http\Controllers\Organization\PositionController;
use App\Http\Controllers\Organization\UnitController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;


Route::post('login', [AuthController::class, 'login']); 
Route::post('forgot-password',[ResetPasswordController::class, "changePassword"]);

//     Route::apiResources([
//         'users' => UserController::class,
//         'employees' => EmployeeController::class,
//         'departaments'=> DepartmentController::class,
//         'units' => UnitController::class,
//         'positions' => PositionController::class,
//     ], [
//         'users' => ['index', 'show', 'update', 'destroy'],
//         'employees' => ['index', 'show', 'store', 'update', 'destroy'],
//     ]); 







// Route::middleware('auth:sanctum')->group(function () {
//     // Cerrar sesión

//     Route::post('logout', [AuthController::class, 'logout']);

//     Route::apiResources([
//         'users' => UserController::class,
//         'employees' => EmployeeController::class,
//         'departaments'=> DepartmentController::class,
//         'units' => UnitController::class,
//         'positions' => PositionController::class,
//     ], [
//         'users' => ['index', 'show', 'store', 'update', 'destroy'],
//         'employees' => ['index', 'show', 'store', 'update', 'destroy'],
//     ]); 
// });


// Rutas finales - pulir 

// Route::post('login', [AuthController::class, 'login']); 

Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResources([
        'users' => UserController::class,
        'employees' => EmployeeController::class,
        'departaments'=> DepartmentController::class,
        'units' => UnitController::class,
        'positions' => PositionController::class,
    ], [
        'users' => ['index', 'show', 'store', 'update', 'destroy'],
        'employees' => ['index', 'show', 'store', 'update', 'destroy'],
    ]); 

    //User
    Route::get('/user-auth', [UserController::class, 'userAuth']);



    Route::prefix('users/{user}')->middleware('verifyUserExists')->group(function () {
        Route::get('configuration', [UserController::class, 'showConfiguration']);
        Route::put('configuration', [UserController::class, 'updateConfiguration']);
        Route::post('photo', [UserController::class, 'updateUserPhoto']);
        Route::put('disable', [UserController::class, 'disableUser']);
        Route::put('enable', [UserController::class, 'enableUser']);
    });
    
    Route::prefix('employees/{employee}')->middleware('verifyEmployeeExists')->group(function () {
        Route::apiResources([
            'trainings' => TrainingController::class,
            'formal-educations' => FormalEducationController::class,
            'publications' => PublicationController::class,
            'work-experiences' => WorkExperienceController::class,
            'work-references' => WorkReferenceController::class,
        ]);
    });
});