<?php



namespace App\Http\Controllers\Leave;

use App\Http\Controllers\Controller;

use App\Services\Leave\LeaveCommentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class LeaveCommentController extends Controller
{
    protected $leaveCommentService;

    public function __construct(LeaveCommentService $leaveCommentService)
    {
        $this->leaveCommentService = $leaveCommentService;
    }

    public function update(Request $request, int $employee_id, int $comment_id): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|string|in:Aprobado,Rechazado,Corregir',
            'comment' => 'nullable|string',
            'rejection_reason_id' => 'nullable|integer|exists:rejection_reasons,id|prohibited_if:action,Aprobado',
            'is_first_approval' => 'nullable|boolean',
            'delegate_id' => 'nullable|integer|exists:employees,id|required_if:is_first_approval,true',
            'delegate_reason' => 'nullable|string|required_if:is_first_approval,true',
            'responsibilities' => 'nullable|array|required_if:is_first_approval,true',
            'responsibilities.*' => 'integer|exists:position_responsibilities,id',
        ]);

        if ($data['action'] === 'Rechazado' && !isset($data['rejection_reason_id'])) {
            return response()->json([
                'status' => false,
                'msg' => 'El motivo de rechazo es obligatorio'
            ], 422);
        }

        return $this->leaveCommentService->updateCommentAction($employee_id, $comment_id, $data);
    }


}
