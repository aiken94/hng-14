<?php

use Illuminate\Http\JsonResponse;

if(!function_exists("ea_debugger")){
    function debugger(mixed $data, string $title = "Debugger:"): void
    {
        if(App::environment(['develop', 'local', 'staging'])) {
            if(is_string($data)){
                Log::info($title." ".$data);
            }
            else {
                Log::info($title." ", (array) $data);
            }
        }
    }
}

if(!function_exists("ea_api_error_response")){
    function ea_api_error_response(string $error, int $code = 400): JsonResponse
    {
        return response()->json([
            "success"   => "error",
            "message"   => $error,
        ], $code);
    }
}
