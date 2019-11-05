<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Locations;

class LocationsController extends Controller
{
    public function index()
    {
        $locations = Locations::getLocations();
        return view('index')
            ->with('locations', $locations);
    }

    public function detail($id)
    {
        $location = Locations::getLocationById($id);       
        return view('location-detail')
            ->with('location', $location);
    }

    public function search(Request $request)
    {
        $input = $request->all();
        $locations = Locations::searchLocations($input['keyword']);
        return response()->json($locations, 200);
    }

    public function aboutUs()
    {
        return view('about-us');
    }
}
