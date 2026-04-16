<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Promises\LazyPromise;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Nationalize
{
    const string base_url = 'https://api.nationalize.io';

    public static function name($name, $country_id = null): array
    {
        try {
            $url        =   self::base_url."?name=".$name;

            if($country_id){
                $url .= "&country_id=".$country_id;
            }

            $api        =   self::_api($url);
            $headers    =   $api->headers();
            $body       =   $api->json();

            debugger($body, "Nationalize API data:");

            if($api->ok()){
                return $body;
            }

            return [
                "status"    => "error",
                "message"   =>  $body["error"]
            ];
        } catch (Exception $exception) {
            debugger($exception->getMessage(), "Nationalize Error:");

            return [
                "status"    => "error",
                "message"   =>  $exception->getMessage()
            ];
        }
    }

    /**
     * @throws ConnectionException
     */
    private static function _api($url): LazyPromise|PromiseInterface|Response
    {
        return Http::acceptJson()->get($url);
    }
}
