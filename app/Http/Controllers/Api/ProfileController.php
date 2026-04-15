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
        if($profiles = self::_cache_key($request, 'get')){
            return  response()->json([
                'status'    => 'success',
                'count'     => $profiles->count(),
                'data'      => $profiles,
            ]);
        }
        else {
            $profile_query  = Profile::query();

            // filter by gender
            if($gender = $request->query('gender')){
                $profile_query->where('gender', $gender);
            }

            // filter by country id if passed through query
            if($country_id = $request->query('country_id')){
                $profile_query->where('country_id', $country_id);
            }

            // filter by age group
            if($age_group = $request->query('age_group')){
                $profile_query->where('age_group', $age_group);
            }

            // transform the data from the query to fit response structure
            $profiles       = $profile_query->get()
                ->transform(function ($profile) {
                    return [
                        "id"            => $profile->id,
                        "name"          => $profile->name,
                        "gender"        => $profile->gender,
                        "age"           => $profile->age,
                        "age_group"     => $profile->age_group,
                        "country_id"    => $profile->country_id
                    ];
                });

            // let's cache this results for similar requests

            return  response()->json([
                'status'    => 'success',
                'count'     =>  $profiles->count(),
                'data'      => $profiles,
            ]);
        }
    }

    public function store()
    {

        // we will have to clear the /profiles cache cache time a new store request is made
    }

    public function show(Profile $profile)
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

        return response()->json([
            "status"    => "success",
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
        ]);
    }

    public function destroy($id)
    {
        // find the profile
        $profile    =   Profile::query()
            ->find($id);

        // throw a 404 error response
        if(!$profile->exists){
            return ea_api_error_response('Profile not found', 404);
        }

        // delete cache data
        if(Cache::has('profile:'.$profile->id)) {
            Cache::forget('profile:' . $profile->id);
        }

        // delete the profile
        $profile->delete();

        return response()->json([], 204);
    }

    private static function _cache_key($request, $type = 'get')
    {
        // set the default request cache key
        $cache_key      =   'profiles-cache.';

        // filter by gender
        if($gender = $request->query('gender')){
            $cache_key.=    'gender';
        }

        // filter by country id if passed through query
        if($country_id = $request->query('country_id')){
            $cache_key.=    'country_id';
        }

        // filter by age group
        if($age_group = $request->query('age_group')){
            $cache_key.=    'age_group';
        }

        if($type == 'set'){
            return $cache_key;
        }

        return Cache::get($cache_key);
    }
}
