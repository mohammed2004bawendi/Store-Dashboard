<?php

namespace App\Support\Http;

trait ApiResponseTrait
{
    public function success($data = [], $message = 'تم بنجاح', $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    public function error($message = 'حدث خطأ ما', $code = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $code);
    }
}
