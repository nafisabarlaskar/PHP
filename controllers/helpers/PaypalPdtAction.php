<?php
class Helper_PaypalPdtAction extends Zend_Controller_Action_Helper_Abstract
{
	
	function process_pdt($tx)
	{
        // Init cURL
        //Zend_Registry::get('logger')->err('Inside helper PDTACTION='.$tx);
        $request = curl_init();
		//$token='39CQfPqab131RiIt-5j4z-7Xypz1DyI0SaR1zCISsU0xh2iNgms7SGXsYd4';//sandbox
		$token='K1Hp2AGIggWoDMy9y4eSkSqcpt3B57snyQVwwiPiBXuMXflhWO0CkHSWRK4';//prod
		$paypal_url = 'https://www.paypal.com/cgi-bin/webscr';//prod
    	//$paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';//sandbox
        // Set request options
        curl_setopt_array($request, array
        (
                CURLOPT_URL => $paypal_url,
                CURLOPT_POST => TRUE,
                CURLOPT_POSTFIELDS => http_build_query(array
                (
                        'cmd' => '_notify-synch',
                        'tx' => $tx,
                        'at' => $token,
                )),
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_HEADER => FALSE,
                CURLOPT_SSL_VERIFYPEER => TRUE,
                CURLOPT_CAINFO => dirname(__FILE__) .'/cacert.pem',
        ));

        // Execute request and get response and status code
        $response = curl_exec($request);
        $status   = curl_getinfo($request, CURLINFO_HTTP_CODE);

        // Close connection
        curl_close($request);

        // Validate response
        if($status == 200 AND strpos($response, 'SUCCESS') === 0)
        {
                // Remove SUCCESS part (7 characters long)
                $response = substr($response, 7);

                // Urldecode it
                $response = urldecode($response);

                // Turn it into associative array
                preg_match_all('/^([^=\r\n]++)=(.*+)/m', $response, $m, PREG_PATTERN_ORDER);
                $response = array_combine($m[1], $m[2]);

                // Fix character encoding if needed
                if(isset($response['charset']) AND strtoupper($response['charset']) !== 'UTF-8')
                {
                        foreach($response as $key => &$value)
                        {
                                $value = mb_convert_encoding($value, 'UTF-8', $response['charset']);
                        }

                        $response['charset_original'] = $response['charset'];
                        $response['charset'] = 'UTF-8';
                }

                // Sort on keys
                ksort($response);

                // Done!
                return $response;
        }

        return FALSE;
	}
        
    
    function direct($tx)
    {        
        return $this->process_pdt($tx); 
    }
    
        
}

?>