<?php
class Helper_GetCountryAction extends Zend_Controller_Action_Helper_Abstract
{
	
	function process($ip=null)
	{
		$request = curl_init();
		$license='vdAw2Fd6YzFW';
		
		if($ip==null) {
			$ip= self::get_remote_ip_address();
		}
		//echo $ip;
		//Zend_Registry::get('logger')->err('inside overrise second ip='.$ip);
		$params = getopt('l:i:');
		if (!isset($params['l'])) $params['l'] = $license;
		//if (!isset($params['i'])) $params['i'] = '12.215.42.19';//US
		//if (!isset($params['i'])) $params['i'] = '122.162.54.46';//INDIA
		if (!isset($params['i'])) $params['i'] = $ip;
		$query = 'https://geoip.maxmind.com/a?' . http_build_query($params);
	

		// Call the web service using the configured URL.
		$curl = curl_init();
		curl_setopt_array(
		    $curl,
		    array(
		        CURLOPT_URL => $query,
		        CURLOPT_SSL_VERIFYPEER => false,
		        CURLOPT_RETURNTRANSFER => true
		    )
		);
		
		$resp = curl_exec($curl);
		return $resp;
			
		
	}
	
	function get_remote_ip_address()
	{
	   // Check to see if an HTTP_X_FORWARDED_FOR header is present.
	
	   if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	
	   {
	   
	      // If the header is present, use the last IP address.
	      $temp_array      = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	      $temp_ip_address = $temp_array[count($temp_array) - 1];  
	   }
	   else
	   {
	      // If the header is not present, use the 
	      // default server variable for remote address.
	      $temp_ip_address = $_SERVER['REMOTE_ADDR'];
	   }
	
	   return $temp_ip_address;
	}
    
        
    
    function direct($ip=null)
    {        
        return $this->process($ip); 
    }
    
        
}

?>