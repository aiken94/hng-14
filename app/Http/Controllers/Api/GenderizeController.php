<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Genderize;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GenderizeController extends Controller
{
    public function classify(Request $request)
    {
        //$validation = Validator::make($request->all(), [
        //    "name" => ["required", "string", "max:255"],
        //]);

        if(!$request->has('name')){
            return ea_api_error_response("Missing or empty name parameter");
        }

        if(!is_string($request->query('name'))){
            return ea_api_error_response("name is not a string", 422);
        }

        // Genderize the request by calling the api
        try {
            $name           =   $request->query('name');

            // instantiate the Genderize API Service
            $genderize      =   new Genderize($name);

            $response       =   $genderize->classify();

            // Extracting gender, probability, and count from the API response
            $count          =   $response["count"];
            $gender         =   $response["gender"];
            $probability    =   $response["probability"];

            if(!$gender || !$count){
                return ea_api_error_response("No prediction available for the provided name", 422);
            }

            return response()->json([
                "status"    => "success",
                "data"      => [
                    "name"              => $nam,
                    "gender"            => $gender,
                    "probability"       => $probability,
                    "same_size"         => $count,
                    "is_confident"      => (bool) $probability >= 0.7 && $count >= 100,
                    "processed_at"      => now()->tz('UTC'),
                ]
            ]);
        } catch (Exception $exception) {
            return ea_api_error_response($exception->getMessage(), 500);
        }
    }
}
