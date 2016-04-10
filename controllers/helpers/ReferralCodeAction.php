<?php
class Helper_ReferralCodeAction extends Zend_Controller_Action_Helper_Abstract
{
    function direct()
    {
    	try {
    		$strings = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
			$password = '';
			for($i=0; $i<4;$i++){
				$password .= substr($strings, rand(0, strlen($strings)), 1);
			}
			return $password;    		
    	}
    	catch (Exception $e){
			Zend_Registry::get('logger')->err('Unable to generate password: ' . $e->getMessage());
		}
    }
}

?>