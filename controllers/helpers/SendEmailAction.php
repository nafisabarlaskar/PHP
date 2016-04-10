<?php
class Helper_SendEmailAction extends Zend_Controller_Action_Helper_Abstract
{
    function send_email($subject,$templateMessage,$template)
    {
    	try {
    	Zend_Mail::setDefaultTransport( new Mail_Transport_Postmark( Zend_Registry::getInstance()->configuration->postmark->key) );
    	// send the email
    	$mail = new Zend_Mail('UTF-8');
    	//$mail->setSubject($subject .'- '. Zend_Registry::getInstance()->configuration->app->name);
    	$mail->setSubject($subject);
    	$mail->setFrom(Zend_Registry::getInstance()->configuration->app->email, Zend_Registry::getInstance()->configuration->app->name);
    	//$mail->addTo($templateMessage->to_user->email, $templateMessage->to_user->first_name);
    	$mail->addTo($templateMessage->to_user_email, $templateMessage->to_user_name);
    	$mail->addTo('contact@dezyre.com');
    	$cc_list=$templateMessage->cc_list;
    	//Zend_Registry::get('logger')->err('cclist='.count($cc_list));
    	if($cc_list!=null && count($cc_list)>0){
    		//Zend_Registry::get('logger')->err('cclist is not empty='.count($cc_list));
    		foreach($cc_list as $email)
    			$mail->addTo($email);
    	}
       $mail->setBodyHtml($templateMessage->render($template));
		
    	if(!$mail->send()){
			Zend_Registry::get('logger')->err('Unable to send email: ' . $email);    			
    	}    		
    	}
    	catch (Exception $e){
			Zend_Registry::get('logger')->err('Unable to send email: ' . $e->getMessage());
		}
    }
    
	function send_email_admin($subject,$templateMessage,$template)
    {
    	try {
    	//Zend_Mail::setDefaultTransport( new Mail_Transport_Postmark( Zend_Registry::getInstance()->configuration->postmark->key) );
    	// send the email
    	$mail = new Zend_Mail('UTF-8');
    	$mail->setSubject($subject);
    	$mail->setFrom(Zend_Registry::getInstance()->configuration->app->email, Zend_Registry::getInstance()->configuration->app->name);
    	$mail->addTo(Zend_Registry::getInstance()->configuration->app->email);
    	$mail->setBodyHtml($templateMessage->render($template));
		
    	if(!$mail->send()){
			Zend_Registry::get('logger')->err('Unable to send email: ' . $email);    			
    	}    		
    	}
    	catch (Exception $e){
			Zend_Registry::get('logger')->err('Unable to send email: ' . $e->getMessage());
		}
    }
	
    
    function direct($subject,$templateMessage,$template)
    {        
        return $this->send_email($subject,$templateMessage,$template); 
    }
    
    
}

?>