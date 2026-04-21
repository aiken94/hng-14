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

/****************************************************************
 * Stage 2 helpers                                              *
 ****************************************************************/

if(!function_exists("ea_items_per_page_sg2")){
    /**
     * Items to show perPage
     *
     * @return int
     */
    function ea_items_per_page_sg2(): int
    {
        $items  =   request()->query('limit');

        if(is_numeric($items) && $items <= 50){
            return   $items;
        }

        return 10;
    }
}

if(!function_exists("ea_pagination_attr_sg2")){
    /**
     * Custom pagination attributes
     *
     * @param $collection
     * @return array
     */
    function ea_pagination_attr_sg2($collection): array
    {
        return [
            'status'    => 'success',
            'page'      => $collection->currentPage(),
            'limit'     => $collection->perPage(),
            'total'     => $collection->total(),
        ];
    }
}
