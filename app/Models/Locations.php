<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Locations extends Model
{
    protected $table = 'locations';

    protected $fillable = [
        'name',
        'location',
        'latitude ',
        'longitude',
        'code',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    use SoftDeletes;

    static public function getLocations()
    {
        return self::all();
    }

    static public function searchLocations($keyword)
    {        
        return self::select('*')->where('name', 'LIKE', '%' . $keyword . '%')->get();
    }

    static public function getLocationById($id)
    {        
        return self::select('*')->where('id', '=', $id)->first();
    }

    static public function getLocationByCode($code)
    {        
        return self::select('*')->where('code', '=', $code)->first();
    }

    static public function getLocationByLongitude($longitude)
    {        
        return self::select('*')->where('longitude', '>=', $longitude)->orderBy('longitude', 'ASC')->first();
    }

    static public function getLocationByName($name, $str, $str_replace)
    {        
        return self::select(['name', 'latitude', 'longitude', 'code'])->where('name', 'LIKE', '%' . $name . '%')->take(10)->get()
        ->map(function ($item) use ($str, $str_replace) {
            $item->name = trim(str_replace($str, $str_replace, $item->name));
            return $item;
        });
    }
}
