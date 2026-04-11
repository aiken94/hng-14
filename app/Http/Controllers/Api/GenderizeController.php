<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Genderize;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;

class GenderizeController extends Controller
{
    /**
     * @throws ConnectionException
     */
    public function classify(Request $request)
    {
        $name           =   $request->query('name');

        if(!$request->has('name')){
            return ea_api_error_response("Missing or empty name parameter");
        }

        if(is_numeric($name) || !$name){
            return ea_api_error_response("name is not a string", 422);
        }

        // Genderize the request by calling the api

        // instantiate the Genderize API Service
        $genderize      =   new Genderize($name);

        $response       =   $genderize->classify();

        if($response["status"] == "success"){
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
                    "name"              => $name,
                    "gender"            => $gender,
                    "probability"       => $probability,
                    "sample_size"       => $count,
                    "is_confident"      => $probability >= 0.7 && $count >= 100,
                    "processed_at"      => now()->tz('UTC')
                ]
            ]);
        }
        else {
            return ea_api_error_response("Upstream or server failure", 500);
        }
    }
}
