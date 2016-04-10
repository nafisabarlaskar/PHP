<?php

class ManipalController extends Zend_Controller_Action
{
	private $_user_id;
		 
    public function init()
    {
        /* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }
    
	//called by Manipal as a iframe call
	public function courseAction()
    {
    	//Zend_Registry::get('logger')->err('Inside manipal');
    	try {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();

    	$course_code = $this->_getParam('ccode');
    	$email = $this->_getParam('loginname');
    	$rollnum = $this->_getParam('rollnum');
    	$community_id = $this->_getParam('communityid');
    	$section_code = $this->_getParam('sectioncode');
    	$csum = $this->_getParam('csum');
    	
    	//calculate checksum
    	$token = strtolower('ccode='.$course_code.'&rollnum='.$rollnum.'&communityid='.$community_id.'&sectioncode='.$section_code.'&loginname='.$email);
    	$salt=Zend_Registry::getInstance()->configuration->manipal->key;    	    	    	
    	$checksum = sha1($token.$salt);
    	
    	//get Referrer
    	$request = new Zend_Controller_Request_Http();
		$referrer = $request->getHeader('referer');
		$requested_time = date("D, d M Y H:i:s", $_SERVER['REQUEST_TIME']);
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		
		
		//check if all parameters are present
		if($rollnum==null || $email==null || $course_code==null || $community_id==null || $section_code==null || $csum==null) {
			$this->sendErrorEmail($rollnum,$course_code, $email, $community_id, $section_code, $csum, $checksum, $referrer, "Manipal: All parameters are not present",$requested_time,$user_agent);
    		throw new Exception('All parameters are not present rollnum='.$rollnum.' - email='.$email.' - course_code='.$course_code.' -communityid='.$community_id.' - section code='.$section_code,4);
		}
    	
    	//check email
    	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		    $this->sendErrorEmail($rollnum,$course_code, $email, $community_id, $section_code, $csum, $checksum, $referrer, "Manipal: Invalid Email",$requested_time,$user_agent);
    		throw new Exception('Invalid Email='.$email,6);
		}
				
    	//first check referrer - if invalid - show error   	
		$manipal_url = Zend_Registry::getInstance()->configuration->manipal->url;
		$manipal_url_2 = Zend_Registry::getInstance()->configuration->manipal->url2;
		$manipal_url_3 = Zend_Registry::getInstance()->configuration->manipal->url3;
		//Zend_Registry::get('logger')->err('Inside manipal referrer2='.$manipal_url_2);
		//if (strpos($referrer,'localhost') === false && strpos($referrer,$manipal_url) === false  && strpos($referrer,$manipal_url_2) === false && strpos($referrer,$manipal_url_3) === false) {
		
		if (strpos($referrer,$manipal_url) === false  && strpos($referrer,$manipal_url_2) === false && strpos($referrer,$manipal_url_3) === false) {
			$this->sendErrorEmail($rollnum,$course_code, $email, $community_id, $section_code, $csum, $checksum, $referrer, "Manipal: Invalid Referrer",$requested_time,$user_agent);
	    	throw new Exception('Invalid Referrer='.$referrer,6);
		}
		
		//set referrer in session for redirect to manipal login page when sesson expires
		//$referrer_session = new Zend_Session_Namespace('referrer_session');
		//$referrer_session->referrer = $referrer;

		//$referrer_session1 = new Zend_Session_Namespace('referrer_session');
	    //$referrer = $referrer_session1->referrer;
	    
    	//Zend_Registry::get('logger')->err('Inside manpal referrer='.$referrer);
	    		
		//validate checksum    	
    	if($checksum!=$csum) {
    		$this->sendErrorEmail($rollnum,$course_code, $email, $community_id, $section_code, $csum, $checksum, $referrer, "Manipal: Invalid Checksum",$requested_time,$user_agent);				    
	    	throw new Exception('Invalid request - checksum does not validate - token ='.$token.' - csum='.$csum.' - checksum calculated based on token= '.$checksum,5);
    	}    	
    	
    	$already_enrolled=false;
    	    	    
    	$userModel = new Model_User();
    	$enrollmentModel = new Model_Enrollment();    	
   	
    	//get course_id    	
    	$manipal_course = Model_ManipalCourse::getCourseId($course_code);
    	if(count($manipal_course)!=0) {
    		$course_id = $manipal_course->course_id;
    		$fees = $manipal_course->fees;
    	}
    	else {
    		$this->sendErrorEmail($rollnum,$course_code, $email, $community_id, $section_code, $csum, $checksum, $referrer, "Manipal: Course code not valid",$requested_time,$user_agent); 
    		throw new Exception("Did not find course_id for manipal course code ".$course_code,1);
    	}
    	
    	$currentUser = $userModel->loadUserProfileByEmail($email);
    	
    	// STEP 1: CHECK IF USER EXISTS IN DATABASE
	    //$currentUser = $userModel->loadUserByEmail($email);
    	if(count($currentUser)==1) {
    		// User exist in database
    		// STEP 1a: Check if user has already enrolled    		
			$enrollmentRow = Model_Enrollment::isStudentEnrolled($course_id,$currentUser->user_id);						
			if(count($enrollmentRow)!=0) {
				if($enrollmentRow->payment_received=='N')
					$enrollmentModel->deleteEnrollment($enrollmentRow->enrollment_id);					
					//throw new Exception("Record already exist with payment=N for user ".$email,2);
				else {
					//check if validity has expired
					date_default_timezone_set("Asia/Kolkata");
					$now_date=date('Y-m-d H:i:s', time());
					$valid_until = date('Y-m-d H:i:s', strtotime($enrollmentRow->valid_until));
					if($now_date > $valid_until){
 						throw new Exception("course validity has expired for user ".$email.' - for course='.$course_id,3);
					} 
					$already_enrolled=true;
				}
			}    			    			
    	} else {
    			//User does not exist
    			$role = Model_Role::getRole('student');
		    	$role = $role->toArray();		    	
				$org = Model_Organization::getOrganization('manipal');		    				
		    	$userModel = new Model_User();		
				$rowUser=$userModel->addManipalUser($email, $role[0]['role_id'],$org->organization_id,'manipal.com', 'Y');
				$currentUser = $userModel->loadUserProfileByEmail($email);				
    		}
    		
    		//Log the user in    		
			$auth = Zend_Auth::getInstance();
			$storage = $auth->getStorage();			
		    $storage->write($currentUser);		    		    
    		
		    $courseModel = new Model_Course();
	    	$course = $courseModel->find($course_id)->current();	    		    	
			
    		// INSERT ROW IN ENROLLMENT TABLE
    		if(!$already_enrolled) {
    			$order_id = $course->course_code.'-'.date('dmy').'-'.rand(1111111,999999);
    			//add valid till for manipal users
    			$valid_until = date('Y-m-d H:i:s', strtotime('+'.$course->duration.' months'));
    			$enrollmentRow = $enrollmentModel->addManipal($course_id,$currentUser->user_id,null,$fees,'Y',$order_id,'manipal',$valid_until);
    			
    			//store in manipal_user table
				$manipalUserModel = new Model_ManipalUser();
				$manipalUserModel->addUser($currentUser->user_id,$rollnum ,$email,$course_id,$course_code,$community_id, $section_code,$csum);
				
    			//send email to Admin				    
		    	$templateMessage = new Zend_View();
	    		$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');		    	
		    	$templateMessage->course = $course;		    	
		    	$templateMessage->user = $currentUser;	    		
		    	$templateMessage->to_user_email = 'omair@dezyre.com';		    	
		    	$this->_helper->SendEmailAction('Manipal Enrollment: User: '.$currentUser->email.' - Course - '.$course->title,$templateMessage,'manipal.phtml');		    
		    	
	    	}
	    	//Zend_Registry::get('logger')->err('Inside manipal again');
	    	return $this->_redirect('/course/my-view/course_id/'.$course_id);	    	
	    
    	}  catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in courseAction in ManipalController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('manipal-error/','error',null,array("code"=>$e->getCode()));
    		//return $this->_forward('manipal-error/','error');
    	}
    }   

    private function sendErrorEmail($rollnum,$course_code,$email,$community_id,$section_code,$csum,$checksum,$referrer,$subject,$requested_time,$user_agent)
    {
    	//Zend_Registry::get('logger')->err('Inside send email');
    	//$cc_list=array("omair_aasim@yahoo.com","omairaasim@gmail.com");
    	//$cc_list=array("anand.varada@manipalglobal.com","basavaraj.kn@manipalglobal.com","santosh.kalure@manipalglobal.com","minal.kumari@manipalglobal.com");
    	$cc_list=array("edunxt.techsupport@manipalglobal.com");    	    	
    	$templateMessage = new Zend_View();
    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');		    	
	    $templateMessage->to_user_email = 'omair@dezyre.com';	    
	    $templateMessage->cc_list=$cc_list;
	    $templateMessage->course_code = $course_code;
	    $templateMessage->rollnum = $rollnum;
	    $templateMessage->email = $email;
	    $templateMessage->community_id = $community_id;
	    $templateMessage->section_code = $section_code;
	    $templateMessage->csum = $csum;
	    $templateMessage->checksum = $checksum;
	    $templateMessage->requested_time = $requested_time;
	    $templateMessage->user_agent = $user_agent;
	    $templateMessage->referrer = $referrer;	    
    	$this->_helper->SendEmailAction($subject,$templateMessage,'manipal-error.phtml');   	
    }
}


