<?php
/**
 * GeoMaps Addon for JReviews
 * Copyright (C) 2006-2009 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class GeocodingComponent extends S2Component {
        
    var $Config;
    
    /**
    * Status codes, based on Google API
    * 200 = success
    * 620 = too fast
    * other = failure
    */
    /**
     * HTTP APIS
     */    
    var $_API = array(
		'google'=>'{google_url}/maps/api/geocode/json?address=%s&sensor=false'
        ,'tiny_geocoder'=>'http://tinygeocoder.com/create-api.php?q=%s'
//        ,'http://dyngeometry.com/web/Developer.aspx' => Check this one
    );
    
    function startup(&$controller)
    {
        $this->Config = &$controller->Config;
    }
    /**
     * Geocoding using the Google http access
     *
     * @param array $address
     * @return array with geocoding info
     */
    function geocode($address) 
    {     
        if(empty($this->_API)){
            return false;    
        }
        
        foreach($this->_API AS $service=>$api)
        {
            $response = $this->{$service}($address);                    
            
            if($response && Sanitize::getInt($response,'status')==200)
            {
                return $response;
            }
            
            // Status is error, unset this service from the API and use only the remaining ones
            if(isset($this->_API[$service]) && (!$response || ($response['status']!=200 && $response['status']!=620))) 
            {
                unset($this->_API[$service]);    
            }
            
        }
    }
    
    function google($address)
    {
        $this->_API['google'] = str_replace('{google_url}',Sanitize::getString($this->Config,'geomaps.google_url','http://maps.google.com'),$this->_API['google']);
        $geoData = false;        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, sprintf($this->_API['google'],urlencode($address)));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = trim(curl_exec($curl));      
        // Process JSON		
		if(!empty($response)) {
			$data = json_decode($response);
			if($data->status == "OK" && is_array($data->results) && $result = $data->results[0]) {
				$status = 200;
				$elev = 0;
				$lat = $result->geometry->location->lat;
				$lon = $result->geometry->location->lng;
				if(!is_numeric($lat) || !is_numeric($lon)) $status = 'error';
				$geoData = array('status'=>$status,'lon'=>$lon,'lat'=>$lat,'elev'=>$elev);
			}
		}		
        curl_close($curl);
        return $geoData;        
    }
    
    function tiny_geocoder($address)
    {              
        $geoData = false;        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, sprintf($this->_API['tiny_geocoder'],urlencode($address)));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $response = trim(curl_exec($curl));
        // Process CSV
        if($response!='' && $response!=620)
        {
             // Split pieces of data by the comma that separates them
            list($lat, $lon) = explode(",", $response);
            $status = '200';
            $elev = '0';
            if(!is_numeric($lat) || !is_numeric($lon)) $status = 'error'; 
            $geoData = array('status'=>$status,'lon'=>$lon,'lat'=>$lat,'elev'=>$elev);           
        }
        
        // More complete data can be found via XML
        // Create SimpleXML object from XML Content
//                    $xmlObject = simplexml_load_string($xmlContent);
//                    $localObject = $xmlObject->Response;
//                    prx($localObject);                            
        curl_close($curl);
        return $geoData;        
       
    }
}
