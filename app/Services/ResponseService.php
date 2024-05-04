<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class ResponseService
{
    protected function successResponse(string $message, $data = null, int $status = 200): JsonResponse
    {
        $response = ['status' => true, 'msg' => $message];
        if (!is_null($data)) {
            $response['data'] = $data;
        }
        return response()->json($response, $status);
    }

    protected function errorResponse(string $message, int $status): JsonResponse
    {
        return response()->json(['status' => false, 'msg' => $message], $status);
    }
}



