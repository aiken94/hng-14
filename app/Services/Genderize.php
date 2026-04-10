<?php

namespace App\Services;

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
    public function classify($country_id = null): LazyPromise|PromiseInterface|Response
    {
        $url    =   $this->base_url."?name=".$this->name.($this->api_key ? "&apikey=Elixir.".$this->api_key : null);

        if($country_id){
            $url .= "&country_id=".$country_id;
        }

        return self::_api($url);
    }

    /**
     * @throws ConnectionException
     */
    private static function _api($url): LazyPromise|PromiseInterface|Response
    {
        return Http::acceptJson()->get($url);
    }
}
