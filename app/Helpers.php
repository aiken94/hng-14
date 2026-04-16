<?php

use Illuminate\Http\JsonResponse;

/****************************************************************
 * General helpers                                              *
 ****************************************************************/

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
    function ea_api_error_response(string $error, int $code = 400, $data = null): JsonResponse
    {
        debugger($data, $error);

        return response()->json([
            "status"    => "error",
            "message"   => $error,
        ], $code);
    }
}

if (!function_exists("ea_convert_to_2_decimals")) {
    function ea_convert_to_2_decimals($float): float|int
    {
        return number_format(floor($float * 100) / 100, 2);
    }
}

/****************************************************************
 * Stage 1 helpers                                              *
 ****************************************************************/
if (!function_exists("ea_store_profile_clear_cache")) {
    function ea_store_profile_clear_cache(): void
    {
        cache()->clear();
    }
}
