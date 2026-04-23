<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
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

            $response = self::_profile_query_results($profile_query, $request);
        }

        return  response()->json($response);
    }

    public function search(Request $request)
    {
        // valid query parameter
        if(!$request->has('q')){
            return ea_api_error_response("Missing or empty parameter");
        }

        $q = strtolower(trim($request->query('q')));

        // non string error
        if(is_numeric($q) || !$q){
            return ea_api_error_response("Invalid parameter type", 422);
        }

        $plain_english_filters =   [
            "young males",
            "females above 30",
            "people from angola",
            "adult males from kenya",
            "male and female teenagers above 17",
        ];

        // search did not match plain english
        if(!in_array($q, $plain_english_filters)){
            return ea_api_error_response("Unable to interpret query");
        }

        if(!($response = self::_cache_key($request))) {
            $query = Profile::query();

            // get and set request gender
            $genders = [];

            if (str_contains($q, 'male')) {
                $genders[] = 'male';
            }

            if (str_contains($q, 'female')) {
                $genders[] = 'female';
            }

            if (!empty($genders)) {
                $query->whereIn('gender', $genders);
            }

            // country filter
            ea_get_countries_sg2()
                ->each(function($country, $code) use ($q, $query) {
                    if(str_contains($q, strtolower($country))) {
                        $query->where('country_name', '=', ucfirst($country));
                    }
                });

            // age grouping detection
            if (str_contains($q, 'child')) {
                $query->where('age_group', 'child');
            }

            if (str_contains($q, 'teen') || str_contains($q, 'teenager')) {
                $query->where('age_group', 'teenager');
            }

            if (str_contains($q, 'adult')) {
                $query->where('age_group', 'adult');
            }

            if (str_contains($q, 'senior')) {
                $query->where('age_group', 'senior');
            }

            // age range modifier

            // above / over ages
            if (preg_match('/(above|over)\s+(\d+)/', $q, $match)) {
                $query->where('age', '>', (int) $match[2]);
            }

            // below / under ages
            if (preg_match('/(below|under)\s+(\d+)/', $q, $match)) {
                $query->where('age', '<', (int) $match[2]);
            }

            // between X and Y ages
            if (preg_match('/between\s+(\d+)\s+and\s+(\d+)/', $q, $match)) {
                $query->whereBetween('age', [(int) $match[1], (int) $match[2]]);
            }

            // old profiles
            if (str_contains($q, 'old')) {
                $query->where('age', '>', 24);
            }

            // young profiles
            if (str_contains($q, 'young')) {
                $query->whereBetween('age', [16, 24]);
            }

            $response  = self::_profile_query_results($query, $request);
        }

        return  response()->json($response);
    }

    public function show($id, $response_code = 200, ?string $message = null)
    {
        $profile    =   Profile::find($id);

        if(!$profile){
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
                "age"                   => $profile_data->age,
                "age_group"             => $profile_data->age_group,
                "country_id"            => $profile_data->country_id,
                "country_name"          => $profile_data->country_name,
                "country_probability"   => $profile_data->country_probability,
                "created_at"            => $profile_data->created_at
            ]
        ];

        $data   =   array_filter($structure, fn($value) => !is_null($value));

        return response()->json($data, $response_code);
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

        // search
        if($q = $request->query('q')){
            $cache_key.=    ':q-'.str_replace(' ', '', $q);
        }

        // create the cache
        if($type == 'set'){
            Cache::remember($cache_key, now()->addDay(), fn() => $data);

            return $data;
        }

        return Cache::get($cache_key);
    }

    private static function _profile_query_results($profile_query, Request $request)
    {
        // paginate and transform the data from the query to fit response structure
        $profiles   = $profile_query->paginate(ea_items_per_page_sg2());

        $response   = ea_pagination_attr_sg2($profiles);

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
            $response   = array_merge($response, ['data' => $data]);

            // cache the data
            self::_cache_key($request, 'set', $response);
        }

        return $response;
    }
}
