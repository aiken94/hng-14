<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\Agify;
use App\Services\Genderize;
use App\Services\Nationalize;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        if(!($response = self::_cache_key($request))) {
            $profile_query = Profile::query();

            // apply filters
            $profile_query
                // filter by gender
                ->when($gender = $request->query('gender'), fn ($q) => $q->where('gender', '=', strtolower($gender)))
                // filter by age group
                ->when($age_group = $request->query('age_group'), fn ($q) => $profile_query->where('age_group', '=', strtolower($age_group)))
                // filter by country id if passed through query
                ->when($country_id = $request->query('country_id'), fn ($q) => $profile_query->where('country_id', '=', strtoupper($country_id)))
                // filter by min_age and max_age
                ->when($request->filled('min_age') && $request->filled('max_age'), function ($q) use ($request) {
                    $q->whereBetween('age', [$request->min_age, $request->max_age]);
                })
                // filter by only min_age
                ->when($request->filled('min_age') && !$request->filled('max_age'), function ($q) use ($request) {
                    $q->where('age', '>=', $request->min_age);
                })
                // filter by only max_age
                ->when(!$request->filled('min_age') && $request->filled('max_age'), function ($q) use ($request) {
                    $q->where('age', '<=', $request->max_age);
                })
                // filter by min_gender_probability
                ->when($request->filled('min_gender_probability'), function ($q) use ($request) {
                    $q->where('gender_probability', '>=', $request->min_gender_probability);
                })
                // filter by min_country_probability
                ->when($request->filled('min_country_probability'), function ($q) use ($request) {
                    $q->where('country_probability', '>=', $request->min_country_probability);
                });

            // apply sorters and order
            $sortable   = in_array(strtolower($request->sort_by), ['age', 'created_at', 'gender_probability']);
            $orderable  = in_array(strtolower($request->order), ['asc', 'desc']);
            $profile_query->when($sortable && $orderable, function ($q) use ($request) {
                $q->orderBy($request->sort_by, $request->order);
            });

            // paginate and transform the data from the query to fit response structure
            $profiles   = $profile_query->paginate(ea_items_per_page_sg2());

            $data       = $profiles->transform(function ($profile) {
                return [
                    "id"                    => $profile->id,
                    "name"                  => $profile->name,
                    "gender"                => $profile->gender,
                    "gender_probability"    => $profile->gender_probability,
                    "age"                   => $profile->age,
                    "age_group"             => $profile->age_group,
                    "country_id"            => $profile->country_id,
                    "country_name"          => $profile->country_name,
                    "country_probability"   => $profile->country_probability,
                    "created_at"            => $profile->created_at,
                ];
            });

            // let's cache this results for similar requests
            if($profiles->count()) {
                $response   = array_merge(ea_pagination_attr_sg2($profiles), ['data' => $data]);

                // cache the data
                self::_cache_key($request, 'set', $response);
            }
        }

        return  response()->json($response);
    }

    public function store(Request $request)
    {
        try {
            if(!$request->has('name')){
                return ea_api_error_response("Missing or empty name parameter");
            }

            $name           =   $request->string('name');

            // no existing record was found create it
            if(is_numeric($name) || !$name){
                return ea_api_error_response("name is not a string", 422);
            }

            // check whether the name already exists then return it
            if($profile = Profile::query()->where('name', '=', $name)->first()) {
                return $this->show($profile, message: 'Profile already exists');
            }

            // instantiate the Genderize API Service
            $genderize      =   new Genderize($name);
            $genderize      =   $genderize->classify();

            // instantiate the Agify API Service
            $agify          =   Agify::name($name);

            // instantiate the Nationalize Service
            $nationalize    =   Nationalize::name($name);

            if(count($agify) && $genderize["status"] == "success" && count($nationalize)){
                // Extract the API response data from Agify, Genderize & Nationalize
                $count          =   $genderize["count"];
                $gender         =   $genderize["gender"];
                $probability    =   $genderize["probability"];
                $age            =   $agify["age"];
                $country        =   $nationalize["country"] ?? [];

                // did we get an age value from agify api
                if(!is_numeric($age)){
                    return ea_api_error_response("Agify returned an invalid response", 502);
                }

                // gender and count value are required from genderize
                if(!$gender || !$count){
                    return ea_api_error_response("Genderize returned an invalid response", 502);
                }

                // country data must be returned
                if(!count($country)){
                    return ea_api_error_response("Nationalize returned an invalid response", 502);
                }

                // group the agify data into child, teenage, adult or senior
                $age_group      =   match (true){
                    $age >= 0 && $age <= 12   =>  "child",
                    $age >= 13 && $age <= 19  =>  "teenager",
                    $age >= 20 && $age <= 59  =>  "adult",
                    default                   =>  "senior",
                };

                // pick the country with the highest probability from nationalization
                $country_id     =   collect($country)
                    ->sortByDesc('probability')
                    ->values()
                    ->first();

                // we will clear the /profiles cache time a new store request is made
                ea_store_profile_clear_cache();

                $profile    = Profile::query()
                    ->create([
                        "name"                  => $name,
                        "gender"                => $gender,
                        "gender_probability"    => $probability,
                        "sample_size"           => $count,
                        "age"                   => $age,
                        "age_group"             => $age_group,
                        "country_id"            => $country_id['country_id'],
                        "country_probability"   => ea_convert_to_2_decimals($country_id['probability'])
                    ]);

                // cache the data immediately for show()

                return $this->show($profile, 201);
            }
            else {
                return ea_api_error_response("Upstream failure", 502);
            }
        }catch (Exception $exception){
            return ea_api_error_response($exception->getMessage(), 500);
        }
    }

    public function show(Profile $profile, $response_code = 200, ?string $message = null)
    {
        if(!$profile->exists){
            return ea_api_error_response('Profile not found', 404);
        }

        if(Cache::has('profile:'.$profile->id)){
            $profile_data   = Cache::get('profile:'.$profile->id);
        }
        else {
            $profile_data   = Cache::remember('profile:'.$profile->id, now()->addDay(), fn() => $profile);
        }

        // structure the response
        $structure       =   [
            "status"    => "success",
            "message"   => $message,
            "data"      => [
                "id"                    => $profile_data->id,
                "name"                  => $profile_data->name,
                "gender"                => $profile_data->gender,
                "gender_probability"    => $profile_data->gender_probability,
                "sample_size"           => $profile_data->sample_size,
                "age"                   => $profile_data->age,
                "age_group"             => $profile_data->age_group,
                "country_id"            => $profile_data->country_id,
                "country_probability"   => $profile_data->country_probability,
                "created_at"            => $profile_data->created_at
            ]
        ];

        $data   =   array_filter($structure, fn($value) => !is_null($value));

        return response()->json($data, $response_code);
    }

    public function destroy($id)
    {
        // find the profile
        $profile    =   Profile::query()
            ->find($id);

        // throw a 404 error response
        if(!$profile){
            return ea_api_error_response('Profile not found', 404);
        }

        // clear the cache store
        ea_store_profile_clear_cache();

        // delete the profile
        $profile->delete();

        return response()->json([], 204);
    }

    private static function _cache_key($request, string $type = 'get', mixed $data = [])
    {
        // set the default request cache key
        $cache_key      =   'profiles';

        // filter by gender
        if($gender = $request->query('gender')){
            $cache_key.=    ':gender-'.strtolower($gender);
        }

        // filter by country id if passed through query
        if($country = $request->query('country_id')){
            $cache_key.=    ':iso-'.strtoupper($country);
        }

        // filter by age group
        if($group = $request->query('age_group')){
            $cache_key.=    ':group-'.strtolower($group);
        }

        // filter by min_age
        if($min = $request->query('min_age')){
            $cache_key.=    ':mia-'.$min;
        }

        // filter by max_age
        if($max = $request->query('max_age')){
            $cache_key.=    ':mxa-'.$max;
        }

        // filter by min_gender_probability
        if($mgp = $request->query('min_gender_probability')){
            $cache_key.=    ':mgp-'.$mgp;
        }

        // filter by min_country_probability
        if($mcp = $request->query('min_country_probability')){
            $cache_key.=    ':mcp-'.$mcp;
        }

        // pagination page
        if($page = $request->query('page')){
            $cache_key.=    ':page-'.$page;
        }

        // pagination limit
        if($limit = $request->query('limit')){
            $cache_key.=    ':limit-'.$limit;
        }

        // sorter
        if($sort = $request->query('sort_by')){
            $cache_key.=    ':sort-'.$sort;
        }

        // order
        if($order = $request->query('order')){
            $cache_key.=    ':order-'.$order;
        }

        // create the cache
        if($type == 'set'){
            Cache::remember($cache_key, now()->addDay(), fn() => $data);

            return $data;
        }

        return Cache::get($cache_key);
    }
}
