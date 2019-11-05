<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Locations;
use Illuminate\Support\Facades\DB;
use Validator;

class TidesCurrentsController extends Controller
{
    public function get_tide_current_by_date(Request $request) 
    {     
        $input = $request->all();
        $code = $input['code'];
        $date = $input['date'];
        $begin = $input['begin'];
        $end = $input['end'];
        $total_records = $input['total'];

        $location = Locations::getLocationByCode($code);

        if(empty($code) || empty($location))
        {
            return response()->json("Not found.", 404);
        }
        
        $location = $location->location;
        $tc_results = DB::select("SELECT * FROM " . self::getTableName(substr($date, 0, 4), $location) . " WHERE location = ? AND date >= ? limit ?,?", [$location, $date, 0, $total_records]);
        
        $results = [];
        $records = count($tc_results);
        self::add_to_array($tc_results, $results);
        
        if(($total_records - $records) > 0)
        {
            $tc_results = DB::select("SELECT * FROM ". self::getTableName(substr($date, 0, 4) + 1, $location) . " WHERE location = ? AND date >= ? limit ?,?", [$location, $date, 0, $total_records - $records]);
            self::add_to_array($tc_results, $results);
        }
        
        return response()->json(self::getTidesCurrentData($results, $begin, $end), 200);
    }
    
    public function get_prev_tide_current_by_date(Request $request) 
    {
        $input = $request->all();
        $code = $input['code'];
        $date = $input['date'];
        $begin = $input['begin'];
        $end = $input['end'];
        $total_records = $input['total'];
        
        $location = Locations::getLocationByCode($code);

        if(empty($code) || empty($location))
        {
            return response()->json("Not found.", 404);
        }
        
        $location = $location->location;
        $end_date = date('Y-m-d', strtotime($date . "-$total_records days"));
        $tc_results = DB::select("SELECT * FROM " . self::getTableName(substr($date, 0, 4), $location) . " WHERE location = ? AND date > ? AND date <= ? limit ?,?", [$location, $end_date, $date, 0, $total_records]);
        
        $results = [];
        $records = count($tc_results);
        self::add_to_array($tc_results, $results);
        
        if(($total_records - $records) > 0)
        {
            $tc_results = DB::select("SELECT * FROM ". self::getTableName(substr($date, 0, 4) + 1, $location) . " WHERE location = ? AND date > ? AND date <= ? limit ?,?", [$location, $end_date, $date, 0, $total_records - $records]);
            self::add_to_array($tc_results, $results);
        }

        return response()->json(self::getTidesCurrentData($results, $begin, $end), 200);
    }
    
    protected function getTableName($year = 2019, $location = 0)
    {
        $prefix = "tides_" . $year;
        if($location >= 0 && $location <= 500)
        {
            return $prefix . "_0_500";
        }
        else
        if($location >= 501 && $location <= 1000)
        {
            return $prefix . "_501_1000";
        }
        else
        if($location >= 1001 && $location <= 1500)
        {
            return $prefix . "_1001_1500";
        }
        else
        if($location >= 1501 && $location <= 2000)
        {
            return $prefix . "_1501_2000";
        }
        else
        if($location >= 2001 && $location <= 2500)
        {
            return $prefix . "_2001_2500";
        }
        else
        if($location >= 2501 && $location <= 3000)
        {
            return $prefix . "_2501_3000";
        }
        else
        if($location >= 3001 && $location <= 3500)
        {
            return $prefix . "_3001_3500";
        }
        else
        if($location >= 3501 && $location <= 4000)
        {
            return $prefix . "_3501_4000";
        }
        else
        if($location >= 4001 && $location <= 4500)
        {
            return $prefix . "_4001_4500";
        }
        else
        if($location >= 4501 && $location <= 5000)
        {
            return $prefix . "_4501_5000";
        }
        else
        if($location >= 5001 && $location <= 5500)
        {
            return $prefix . "_5001_5500";
        }
        else
        if($location >= 5501 && $location <= 6000)
        {
            return $prefix . "_5501_6000";
        }
        else
        if($location >= 6001 && $location <= 6500)
        {
            return $prefix . "_6001_6500";
        }
        else
        if($location >= 6501 && $location <= 7000)
        {
            return $prefix . "_6501_7000";
        }
        else
        if($location >= 7001 && $location <= 7500)
        {
            return $prefix . "_7001_7500";
        }
        else
        if($location >= 7501 && $location <= 8000)
        {
            return $prefix . "_7501_8000";
        }
        else
        if($location >= 8001 && $location <= 8500)
        {
            return $prefix . "_8001_8500";
        }
        else
        if($location >= 8501 && $location <= 9000)
        {
            return $prefix . "_8501_9000";
        }
        else
        if($location >= 9001 && $location <= 9123)
        {
            return $prefix . "_9001_9123";
        }
    }
    
    protected function getTidesCurrentData($results = [], $begin, $end)
	{
	    if($results)
	    {
	        $arrs = [];
	        $i = 1; $j = 0;
	        foreach($results as $key => $value)
	        {	         
	            if($j == $end)
	            {
	                break;
	            }
	            if($i >= $begin + 1)
	            {
	                $arrs[$key] = $value;
	                $j++;
	            }
	            $i++; 
	        }
	    }	  
	    
	    return $arrs;
	}
	
	protected function add_to_array($tc_results, &$results = [])
	{
	    if($tc_results)
	    {
    	    foreach($tc_results as $res)
    	    {
                $result = [];    
                if(!is_null($res->high1) && !empty($res->high1))
                {	        
                    $result['high1'] = $res->high1;
                }
                if(!is_null($res->low1) && !empty($res->low1))
                {	        
                    $result['low1'] = $res->low1;
                }
                if(!is_null($res->high2) && !empty($res->high2))
                {
                    $result['high2'] = $res->high2;
                }
                if(!is_null($res->low2) && !empty($res->low2))
                {
                    $result['low2'] = $res->low2;
                }
                if(!is_null($res->high3) && !empty($res->high3))
                {
                    $result['high3'] = $res->high3;
                }
                if(!is_null($res->low3) && !empty($res->low3))
                {
                    $result['low3'] = $res->low3;
                }
                if(!is_null($res->high4) && !empty($res->high4))
                {
                    $result['high4'] = $res->high4;
                }
                if(!is_null($res->low4) && !empty($res->low4))
                {
                    $result['low4'] = $res->low4;
                }
                if(!is_null($res->high5) && !empty($res->high5))
                {
                    $result['high5'] = $res->high5;
                }
                if(!is_null($res->low5) && !empty($res->low5))
                {
                    $result['low5'] = $res->low5;
                }
                if(!is_null($res->slack1) && !empty($res->slack1))
                {
                    $result['slack1'] = $res->slack1;
                }
                if(!is_null($res->flood1) && !empty($res->flood1))
                {
                    $result['flood1'] = $res->flood1;
                }
                if(!is_null($res->slack2) && !empty($res->slack2))
                {
                    $result['slack2'] = $res->slack2;
                }
                if(!is_null($res->ebb1) && !empty($res->ebb1))
                {
                    $result['ebb1'] = $res->ebb1;
                }
                if(!is_null($res->slack3) && !empty($res->slack3))
                {
                    $result['slack3'] = $res->slack3;
                }
                if(!is_null($res->flood2) && !empty($res->flood2))
                {
                    $result['flood2'] = $res->flood2;
                }
                if(!is_null($res->slack4) && !empty($res->slack4))
                {
                    $result['slack4'] = $res->slack4;
                }
                if(!is_null($res->ebb2) && !empty($res->ebb2))
                {
                    $result['ebb2'] = $res->ebb2;
                }
                if(!is_null($res->slack5) && !empty($res->slack5))
                {
                    $result['slack5'] = $res->slack5;
                }
                if(!is_null($res->flood3) && !empty($res->flood3))
                {
                    $result['flood3'] = $res->flood3;
                }
                if(!is_null($res->slack6) && !empty($res->slack6))
                {
                    $result['slack6'] = $res->slack6;
                }
                if(!is_null($res->moon) && !empty($res->moon))
                {
                    $result['moon'] = $res->moon;
                }
                if(!is_null($res->sunrise) && !empty($res->sunrise))
                {
                    $result['sunrise'] = $res->sunrise;
                }
                if(!is_null($res->sunset) && !empty($res->sunset))
                {
                    $result['sunset'] = $res->sunset;
                }
                if(!is_null($res->moonrise) && !empty($res->moonrise))
                {
                    $result['moonrise'] = $res->moonrise;
                }
                if(!is_null($res->moonset) && !empty($res->moonset))
                {
                    $result['moonset'] = $res->moonset;
                }    	        
    	   
    	        $results[$res->date] = $result;
    	    }
	    }
	}
	
    public function get_info_location(Request $request) 
    {
	    $input = $request->all();
        $code = $input['code'];      
        
        $location = Locations::getLocationByCode($code);

        if(empty($code) || empty($location))
        {
            return response()->json("Not found.", 404);
        }        
	    
	    $results = []; 
	    $results['name'] = $location->name;
	    $results['latitude'] = $location->latitude;
	    $results['longitude'] = $location->longitude;
        
        return response()->json($results, 200);
	}
	
    public function get_near_locations(Request $request)
    {
	    $input = $request->all();       

        $rules = [
            'long' => 'required|string'
        ];
        $validator = Validator::make($input, $rules);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 404);
        }  

        $long = $input['long'];
        $location = Locations::getLocationByLongitude($long);

        if(is_null($location))
        {
            return response()->json([], 404);
        }   
	    
	    $name = str_replace(["Current"], "", explode(",", $location->name));
        $results = Locations::getLocationByName(trim($name[sizeof($name)-1]), 'Current', '');
  
        return response()->json($results, 200);
	}
}
