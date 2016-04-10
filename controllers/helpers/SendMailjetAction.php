<?php
class Helper_SendMailjetAction extends Zend_Controller_Action_Helper_Abstract
{
    function send_email($subject,$templateMessage,$template,$file)
    {
    	//Zend_Registry::get('logger')->err('Inside sent email' . $email);
    	try {
    		
    		$config = array('ssl' => 'ssl',
                    'port' => 465,
		    		'auth' => 'login',
		    		'username' => '5e6b052f8d280cb7a0a1531c6ad32ee1',
		    		'password' => 'b3152e8358b4825210e7a552334246a7');
 
		    $transport = new Zend_Mail_Transport_Smtp('in.mailjet.com', $config);
		 
		    //Zend_Registry::get('logger')->err('after transport');
		    
		    $mail = new Zend_Mail();
		 
		    $mail->setFrom(Zend_Registry::getInstance()->configuration->app->email, Zend_Registry::getInstance()->configuration->app->name);
		    $mail->addTo($templateMessage->to_user_email, $templateMessage->to_user_name);
    		$mail->addTo('contact@dezyre.com');
    		$mail->setSubject($subject);
		    $mail->setBodyHtml($templateMessage->render($template));
		    
		    if(!$mail->send($transport)){
				Zend_Registry::get('logger')->err('Unable to send email: ' . $email);    			
    		}  
		 
		    //Zend_Registry::get('logger')->err('done ');
    		
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