<?php

use App\Http\Controllers\Address\AddressController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\DashboardStatisticsController;
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
use App\Http\Controllers\Employee\Schedules\WorkScheduleController;
use App\Http\Controllers\Leave\LeaveCommentController;
use App\Http\Controllers\Leave\LeaveController;
use App\Http\Controllers\Leave\LeaveStateController;
use App\Http\Controllers\Leave\LeaveStatisticsController;
use App\Http\Controllers\Leave\LeaveTypeController;
use App\Http\Controllers\Leave\RejectionReasonController;
use App\Http\Controllers\Leave\SubrogationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Organization\DirectionController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\PositionController;
use App\Http\Controllers\Organization\UnitController;
use App\Http\Controllers\ReportExcel\LeaveExportController;
use App\Http\Controllers\Reports\LeaveReportController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Role\RoleController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserStateController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [ResetPasswordController::class, "changePassword"]);
Route::get('employees/{employee}/leave-statistics/export', [LeaveExportController::class, 'export']);



Route::get('/statistics/aprobaciones/mes/{employeeId}', [LeaveStatisticsController::class, 'getAprobacionesPorMes']);
Route::get('/statistics/aprobaciones/tipo/{employeeId}', [LeaveStatisticsController::class, 'getAprobacionesPorTipo']);
Route::get('/dashboard-statistics', [DashboardStatisticsController::class, 'getStatistics']);

// Ruta para obtener las estadísticas de permisos por estado para un empleado solicitante
Route::get('/dashboard-statistics/solicitudes/{employeeId}', [DashboardStatisticsController::class, 'getSolicitudesPermisosPorEmpleado']);

// Ruta para probar que funcione correctamente las exportaciones 
Route::get('/export-approved-leaves', [ReportController::class, 'approvedLeavesReport']);

Route::get('/leave-report', [LeaveReportController::class, 'generateReport']);


Route::middleware('auth:sanctum')->group(function () {

    Broadcast::routes();

    // Notificaciones
    // Obtener notificaciones no leídas
    Route::get('/notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
    // Marcar notificaciones como leídas
    Route::post('/notifications/read', [NotificationController::class, 'markAsRead']);


    // Configuración 
    Route::apiResource('configurations', ConfigurationController::class);


    Route::post('logout', [AuthController::class, 'logout']);

    Route::apiResources([
        'users' => UserController::class,
        'employees' => EmployeeController::class,
        'org-directions' => DirectionController::class,
        'units' => UnitController::class,
        'positions' => PositionController::class,
    ], );

    // Organización
    Route::get('/org-directions-all', [DirectionController::class, 'indexIncludingDeleted']);
    Route::post('/org-directions/toggle-status/{id}', [DirectionController::class, 'toggleStatus']);

    // Unidades
    Route::get('/units-all', [UnitController::class, 'indexIncludingDeleted']);
    Route::post('/units/toggle-status/{id}', [UnitController::class, 'toggleStatus']);

    // Posiciones
    Route::get('/positions-all', [PositionController::class, 'indexIncludingDeleted']);
    Route::post('/positions/toggle-status/{id}', [PositionController::class, 'toggleStatus']);


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
    // Cambiar contraseña
    Route::post('/change-password', [UserController::class, 'changePassword']);

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
        // Rutas específicas para las razones de rechazo
        Route::get('rejection-reasons-all', [RejectionReasonController::class, 'indexIncludingDeleted']);
        Route::post('rejection-reasons/toggle-status/{id}', [RejectionReasonController::class, 'toggleStatus']);
        //Rutas específicas para los tipos de permisos
        Route::get('types-all', [LeaveTypeController::class, 'indexIncludingDeleted']);
        Route::post('types/toggle-status/{id}', [LeaveTypeController::class, 'toggleStatus']);

        // Ruta para obtener candidatos para subrogación
        Route::get('/{leaveId}/subrogation/candidates', [SubrogationController::class, 'getCandidates']);

    });

    // Ruta para obtener las subrogaciones de un empleado
    Route::get('/subrogations/employee/{employeeId}', [SubrogationController::class, 'listByEmployee']);

    Route::get('/subrogations/all', [SubrogationController::class, 'listAllSubrogations']);
    Route::get('/subrogations/assigned-by/{employeeId}', [SubrogationController::class, 'listAssignedByEmployee']);


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
            'work-schedules' => WorkScheduleController::class,
        ]);
        Route::post('leaves', [LeaveController::class, 'store']);
        Route::get('leaves/assigned', [LeaveController::class, 'getFilteredLeaves']);
        Route::get('leaves', [LeaveController::class, 'getEmployeeLeaves']);
        Route::get('leave-statistics', [LeaveController::class, 'getLeaveStatistics']);
        Route::patch('comments/{comment}', [LeaveCommentController::class, 'update']);
        Route::patch('leaves/{leave}', [LeaveController::class, 'update']);

        Route::put('work-schedules', [WorkScheduleController::class, 'updateMultiple']);
    });



    Route::get('/provinces', [AddressController::class, 'getProvinces']);
    Route::get('/cantons/{province_id}', [AddressController::class, 'getCantons']);
    Route::get('/parishes/{canton_id}', [AddressController::class, 'getParishes']);


    Route::get('directions', [OrganizationController::class, 'getDirections']);
    Route::get('directions/{directionId}/units-positions', [OrganizationController::class, 'getUnitsAndPositions']);
    Route::get('units/{unitId}/positions', [OrganizationController::class, 'getPositions']);



});