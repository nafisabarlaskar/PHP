<?php

class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        
        
        
       
        
        if (!$errors || !$errors instanceof ArrayObject) {
            $this->view->message = 'You have reached the error page';
            return;
        }
        
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $priority = Zend_Log::NOTICE;
                $this->view->message = 'Page not found';
                //Zend_Registry::get('logger')->err('Invalid controller or action was accessed='.$errors->exception);
                
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $this->view->message = 'Application error';
                break;
        }
        
        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log($this->view->message, $priority, $errors->exception);
            $log->log('Request Parameters', $priority, $errors->request->getParams());
        }
        
        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }
        
        $this->view->request   = $errors->request;
        $this->view->response = $this->getResponse();
        
        
        return $this->_forward('exception/','error');

       
        
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }

    public function noauthAction()
    {
        // action body
    }
    
	public function noPageAction()
    {
        // action body
    }
    
	public function exceptionAction()
    {
        // action body
        //$errors = $this->_getParam('error_handler');
        //$this->view->exception = $errors->exception;
        //print_r($errors);
    }
    
	public function manipalErrorAction()
    {
    	/* Error codes
    	 * 1 - Did not find course_id for manipal course code egs GCCARA1
    	 * 2 - 
    	 * 3 - course validity has expired
    	 * 4 - All parameters are not present
    	 * 5 - checksum does not validate
    	 * 6 - Iframe called from a different referrer
    	 * 7 - invalid email
    	 */
    	$error_message = 'Please contact the support staff for more information.'; 
    	$error_code = $this->_getParam('code');
    	Zend_Registry::get('logger')->err('Error message='.$error_code);
    	if($error_code==1)
    		$error_message = 'We are unable to load the requested course at this time. Please contact the support team for more information';
    	else if($error_code==2)
    		$error_message = 'We are unable to process the enrollment. Please contact the support team for more information';
    	else if($error_code==3)
    		$error_message = 'Your course validity has expired. Please contact the support team for more information';
    	else if($error_code==4)
    		$error_message = 'We are unable to load the requested course at this time. Please contact the support team for more information';
    	else if($error_code==5)
    		$error_message = 'We were not able to validate the request. Please contact the support team for more information';
    	else if($error_code==6)
    		$error_message = 'We were not able to validate the request. Please contact the support team for more information';
    	else if($error_code==7)
    		$error_message = 'We were unable to validate the student info. Please contact the support team for more information';
    	$this->view->error_message=$error_message;
    	Zend_Registry::get('logger')->err('Error message='.$error_message);    	    	
    }


}



