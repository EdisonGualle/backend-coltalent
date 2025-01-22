<?php

use App\Http\Controllers\Address\AddressController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Calendar\WeeklyScheduleController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\Contracts\ContractController;
use App\Http\Controllers\Contracts\ContractTypeController;
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
use App\Http\Controllers\Holidays\HolidayAssignmentController;
use App\Http\Controllers\Holidays\HolidayController;
use App\Http\Controllers\Holidays\HolidayWorkRecordController;
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
use App\Http\Controllers\Schedules\DailyOverrideController;
use App\Http\Controllers\Schedules\EmployeeScheduleController;
use App\Http\Controllers\Schedules\ScheduleController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserStateController;
use App\Http\Controllers\Work\OvertimeWorkController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [ResetPasswordController::class, "changePassword"]);

Route::middleware('auth:sanctum')->group(function () {

    Broadcast::routes();

    Route::get('employees/{employee}/leave-statistics/export', [LeaveExportController::class, 'export']);
    Route::get('/leave-report', [LeaveReportController::class, 'generateReport']);

    // Ruta para obtener las estadísticas de permisos por estado para un empleado solicitante
    Route::get('/dashboard-statistics/solicitudes/{employeeId}', [DashboardStatisticsController::class, 'getSolicitudesPermisosPorEmpleado']);


    Route::get('/statistics/aprobaciones/mes/{employeeId}', [LeaveStatisticsController::class, 'getAprobacionesPorMes']);
    Route::get('/statistics/aprobaciones/tipo/{employeeId}', [LeaveStatisticsController::class, 'getAprobacionesPorTipo']);
    Route::get('/dashboard-statistics', [DashboardStatisticsController::class, 'getStatistics']);

    // Ruta para probar que funcione correctamente las exportaciones 
    Route::get('/export-approved-leaves', [ReportController::class, 'approvedLeavesReport']);



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



    // Rutas para los tipos de contrato
    Route::prefix('contract-types')->group(function () {
        Route::get('/', [ContractTypeController::class, 'index']);
        Route::post('/', [ContractTypeController::class, 'store']);
        Route::get('/{id}', [ContractTypeController::class, 'show']);
        Route::put('/{id}', [ContractTypeController::class, 'update']);
        Route::delete('/{id}', [ContractTypeController::class, 'destroy']);
        Route::patch('/{id}/restore', [ContractTypeController::class, 'restore']);
    });

    // Rutas para los contratos de empleados
    Route::prefix('contracts')->group(function () {
        Route::get('/', [ContractController::class, 'index']);
        Route::post('/', [ContractController::class, 'store']);
        Route::get('/{id}', [ContractController::class, 'show']);
        Route::patch('/{id}/renew', [ContractController::class, 'renew']);
        Route::patch('/{id}/terminate', [ContractController::class, 'terminate']);
    });

    // Rutas para los horarios de trabajo
    Route::prefix('schedules')->group(function () {
        Route::get('/', [ScheduleController::class, 'index']);
        Route::post('/', [ScheduleController::class, 'store']);
        Route::get('/{id}', [ScheduleController::class, 'show']);
        Route::put('/{id}', [ScheduleController::class, 'update']);
        Route::delete('/{id}', [ScheduleController::class, 'destroy']);
        Route::patch('/{id}/restore', [ScheduleController::class, 'restore']);

    });

    // Rutas para las asignaciones de horarios a empleados
    Route::prefix('employee-schedules')->group(function () {
        Route::get('/', [EmployeeScheduleController::class, 'index']);
        Route::get('/{employee_id}/active', [EmployeeScheduleController::class, 'activeSchedules']);
        Route::post('/{employee_id}', [EmployeeScheduleController::class, 'store']);
        Route::patch('/{employee_id}/change', [EmployeeScheduleController::class, 'change']);
        Route::delete('/{id}/delete', [EmployeeScheduleController::class, 'destroy']);
        Route::patch('/{id}/restore', [EmployeeScheduleController::class, 'restore']);
    });

    // Rutas para los ajustes temporales
    Route::prefix('daily-overrides')->group(function () {
        Route::get('/', [DailyOverrideController::class, 'index']);
        Route::post('/', [DailyOverrideController::class, 'store']);
        Route::put('/{id}', [DailyOverrideController::class, 'update']);
        Route::delete('/{id}', [DailyOverrideController::class, 'destroy']);
        Route::patch('/{id}/restore', [DailyOverrideController::class, 'restore']);
        Route::get('/employee/{employee_id}', [DailyOverrideController::class, 'getByEmployee']);

    });

    // Rutas para los días festivos
    Route::prefix('holidays')->group(function () {
        Route::get('/assignable', [HolidayController::class, 'assignable']);
        Route::get('/', [HolidayController::class, 'index']);
        Route::post('/', [HolidayController::class, 'store']);
        Route::get('/{id}', [HolidayController::class, 'show']);
        Route::put('/{id}', [HolidayController::class, 'update']);
        Route::delete('/{id}', [HolidayController::class, 'destroy']);
        Route::patch('/{id}/restore', [HolidayController::class, 'restore']);
    });

    // Rutas para las asignaciones de días festivos a empleados
    Route::prefix('holiday-assignments')->group(function () {
        Route::post('/{holidayId}', [HolidayAssignmentController::class, 'store']);
        Route::get('/', [HolidayAssignmentController::class, 'index']);
        Route::get('/employee/{employeeId
        }', [HolidayAssignmentController::class, 'showByEmployee']);
        Route::delete('/', [HolidayAssignmentController::class, 'destroy']);
    });

    // Rutas para los registros de trabajo en días festivos
    Route::prefix('holiday-work-records')->group(function () {
        Route::post('/', [HolidayWorkRecordController::class, 'store']); // Crear en rango
        Route::get('/', [HolidayWorkRecordController::class, 'index']); // Obtener todos los registros activos
        Route::get('/employee/{employeeId}', [HolidayWorkRecordController::class, 'showByEmployee']); // Obtener registros por empleado
        Route::get('/{recordId}', [HolidayWorkRecordController::class, 'show']); // Obtener un registro específico
        Route::delete('/', [HolidayWorkRecordController::class, 'destroy']); // Eliminar en rango
    });


    // Rutas para gestionar registros de trabajo en días festivos, descanso u horas extra
    Route::prefix('overtime-work')->group(function () {
        Route::post('/', [OvertimeWorkController::class, 'store']); // Crear un registro de trabajo
        Route::get('/', [OvertimeWorkController::class, 'index']); // Obtener todos los registros activos
        Route::get('/employee/{employeeId}', [OvertimeWorkController::class, 'showByEmployee']); // Obtener registros de un empleado
        Route::get('/{recordId}', [OvertimeWorkController::class, 'show']); // Obtener un registro específico
        Route::delete('/', [OvertimeWorkController::class, 'destroy']); // Eliminar registros en rango
    });



    Route::get('employees/contracts/active', [EmployeeController::class, 'activeEmployees']);
    Route::get('employees/{employeeId}/calendar', [CalendarController::class, 'generateCalendar']);
    Route::get('employees/{employeeId}/weekly-schedule', [WeeklyScheduleController::class, 'getWeeklySchedule']);

});