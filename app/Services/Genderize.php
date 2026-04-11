<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Promises\LazyPromise;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class Genderize
{
    private string $base_url = 'https://api.genderize.io';
    private ?string $api_key;
    private ?array $rate_limits;

    public function __construct(public string $name)
    {
        $this->api_key  =   config("genderize.apikey");
    }

    /**
     * @throws ConnectionException
     */
    public function classify($country_id = null): array
    {
        try {
            $url        =   $this->base_url."?name=".$this->name.($this->api_key ? "&apikey=Elixir.".$this->api_key : null);

            if($country_id){
                $url .= "&country_id=".$country_id;
            }

            $api        =   self::_api($url);
            $headers    =   $api->headers();
            $body       =   $api->json();

            if($api->ok()){
                $this->rate_limits  = [
                    "limit"     =>  $headers["x-rate-limit-limit"],
                    "remaining" =>  $headers["x-rate-limit-remaining"],
                    "reset"     =>  $headers["x-rate-limit-reset"],
                ];

                return array_merge($body, ["limits" => $this->rate_limits, "status" => "success"]);
            }

            return [
                "status"    => "error",
                "message"   =>  $body["error"]
            ];
        } catch (Exception $exception) {
            debugger($exception->getMessage(), "Genderize Error:");

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
