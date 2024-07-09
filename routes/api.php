<?php

use App\Http\Controllers\Address\AddressController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Employee\Backgrounds\LanguageController;
use App\Http\Controllers\Employee\Backgrounds\PublicationController;
use App\Http\Controllers\Employee\Backgrounds\PublicationTypeController;
use App\Http\Controllers\Employee\Backgrounds\WorkExperienceController;
use App\Http\Controllers\Employee\Backgrounds\WorkReferenceController;
use App\Http\Controllers\Employee\Education\EducationLevelController;
use App\Http\Controllers\Employee\Education\EducationStateController;
use App\Http\Controllers\Employee\Education\FormalEducationController;
use App\Http\Controllers\Employee\Education\TrainingController;
use App\Http\Controllers\Employee\Education\TrainingTypeController;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Leave\LeaveCommentController;
use App\Http\Controllers\Leave\LeaveController;
use App\Http\Controllers\Leave\LeaveStateController;
use App\Http\Controllers\Leave\LeaveTypeController;
use App\Http\Controllers\Leave\RejectionReasonController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Organization\DirectionController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\PositionController;
use App\Http\Controllers\Organization\UnitController;
use App\Http\Controllers\ReportExcel\LeaveExportController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserStateController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;


Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [ResetPasswordController::class, "changePassword"]);
Route::get('employees/{employee}/leave-statistics/export', [LeaveExportController::class, 'export']);



Route::middleware('auth:sanctum')->group(function () {

    Broadcast::routes();

    // Notificaciones
    // Obtener notificaciones no leídas
    Route::get('/notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
    // Marcar notificaciones como leídas
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);


    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResources([
        'users' => UserController::class,
        'employees' => EmployeeController::class,
        'org-directions' => DirectionController::class,
        'units' => UnitController::class,
        'positions' => PositionController::class,
    ], );

    // Estados de educación
    Route::get('education/states', [EducationStateController::class, 'index']);
    Route::get('education/states/{state}', [EducationStateController::class, 'show']);

    // Niveles de educación
    Route::get('education/levels', [EducationLevelController::class, 'index']);
    Route::get('education/levels/{level}', [EducationLevelController::class, 'show']);

    // Tipos de publicaciones
    Route::get('publications/types', [PublicationTypeController::class, 'index']);
    Route::get('publications/types/{type}', [PublicationTypeController::class, 'show']);

    // Tipos de formación
    Route::get('trainings/types', [TrainingTypeController::class, 'index']);
    Route::get('trainings/types/{type}', [TrainingTypeController::class, 'show']);



    //User
    Route::get('/user-auth', [UserController::class, 'userAuth']);

    //Roles
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('roles/{role}', [RoleController::class, 'show']);

    //Estados
    Route::get('user/states', [UserStateController::class, 'index']);
    Route::get('/user/states/{state}', [UserStateController::class, 'show']);


    // Leave
    Route::prefix('leaves')->group(function () {
        Route::apiResources([
            'types' => LeaveTypeController::class,
            'rejection-reasons' => RejectionReasonController::class,
        ]);
        Route::get('states', [LeaveStateController::class, 'index']);
        Route::get('states/{id}', [LeaveStateController::class, 'show']);
    });



    //Employees
    Route::get('employees', [EmployeeController::class, 'index']);
    Route::get('employees/{employee}', [EmployeeController::class, 'show']);

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
            'languages' => LanguageController::class,
            'formal-educations' => FormalEducationController::class,
            'publications' => PublicationController::class,
            'work-experiences' => WorkExperienceController::class,
            'work-references' => WorkReferenceController::class,
        ]);
        Route::post('leaves', [LeaveController::class, 'store']);
        Route::get('leaves/assigned', [LeaveController::class, 'getFilteredLeaves']);
        Route::get('leaves', [LeaveController::class, 'getEmployeeLeaves']);
        Route::get('leave-statistics', [LeaveController::class, 'getLeaveStatistics']);
        Route::patch('comments/{comment}', [LeaveCommentController::class, 'update']);
        Route::patch('leaves/{leave}', [LeaveController::class, 'update']);
    });



    Route::get('/provinces', [AddressController::class, 'getProvinces']);
    Route::get('/cantons/{province_id}', [AddressController::class, 'getCantons']);
    Route::get('/parishes/{canton_id}', [AddressController::class, 'getParishes']);


    Route::get('directions', [OrganizationController::class, 'getDirections']);
    Route::get('directions/{directionId}/units-positions', [OrganizationController::class, 'getUnitsAndPositions']);
    Route::get('units/{unitId}/positions', [OrganizationController::class, 'getPositions']);
});