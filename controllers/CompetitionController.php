<?php

class CompetitionController extends Zend_Controller_Action
{
	private $_user_id;

    public function init()
    {
        /* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }	
    
    public function encryptAction()
    {
    	//echo 'hello';
    	$this->_helper->layout->disableLayout();
    	$salt='8211C57ACE34AC26560BE0B3FE181509826354A3BD83';
    	$token = strtolower('ccode=N-DZ-IB-1402&rollnum=123213&communityid=123456&sectioncode=FEB2013GCCRAA12&loginname=omairaasim@dezyre.com');
    	$checksum = sha1($token.$salt);
    	Zend_Registry::get('logger')->err('Inside competition controller encrypt = '.strtolower($checksum));
    	$host_name = $_SERVER['HTTP_HOST'];
    	$request = new Zend_Controller_Request_Http();
    	$referrer = $request->getHeader('referer');
    	Zend_Registry::get('logger')->err('Inside competition controller host = '.$host_name);
    	Zend_Registry::get('logger')->err('Inside competition controller referrer = '.$referrer);
    }
    
	public function decryptAction()
    {
    	$salt='8211C57ACE34AC26560BE0B3FE181509826354A3BD83';
    	//echo 'hello';
    	$this->_helper->layout->disableLayout();
    	$email = $this->_getParam('rollnum');
    	$course_code = $this->_getParam('ccode');
    	$community_id = $this->_getParam('communityid');
    	$section_code = $this->_getParam('sectioncode');
    	$csum = $this->_getParam('csum');
    	
    	
    	$token = strtolower('ccode='.$course_code.'&rollnum='.$email.'&communityid='.$community_id.'&sectioncode='.$section_code);
    	$checksum = sha1($token.$salt);
    	echo '<br/>checksum='.$checksum.'<br/>';
    	if($checksum==$csum)
    		echo 'valid';
    	else 
    		echo 'invalud';
    }
    
	function encrypt($decrypted, $password, $salt='!kQm*fF3pXe1Kbm%913') 
	{ 
		 // Build a 256-bit $key which is a SHA256 hash of $salt and $password.
		 $key = hash('SHA256', $salt . $password, true);
		 // Build $iv and $iv_base64.  We use a block size of 128 bits (AES compliant) and CBC mode.  (Note: ECB mode is inadequate as IV is not used.)
		 //srand(); 
		 $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
		 if (strlen($iv_base64 = rtrim(base64_encode($iv), '=')) != 22) return false;
		 // Encrypt $decrypted and an MD5 of $decrypted using $key.  MD5 is fine to use here because it's just to verify successful decryption.
		 $encrypted = base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $decrypted . md5($decrypted), MCRYPT_MODE_CBC, $iv));
		 // We're done!
		 return $iv_base64 . $encrypted;
 	} 

	function decrypt($encrypted, $password, $salt='!kQm*fF3pXe1Kbm%913') {
		 // Build a 256-bit $key which is a SHA256 hash of $salt and $password.
		 $key = hash('SHA256', $salt . $password, true);
		 // Retrieve $iv which is the first 22 characters plus ==, base64_decoded.
		 $iv = base64_decode(substr($encrypted, 0, 22) . '==');
		 // Remove $iv from $encrypted.
		 $encrypted = substr($encrypted, 22);
		 // Decrypt the data.  rtrim won't corrupt the data because the last 32 characters are the md5 hash; thus any \0 character has to be padding.
		 $decrypted = rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, base64_decode($encrypted), MCRYPT_MODE_CBC, $iv), "\0\4");
		 // Retrieve $hash which is the last 32 characters of $decrypted.
		 $hash = substr($decrypted, -32);
		 // Remove the last 32 characters from $decrypted.
		 $decrypted = substr($decrypted, 0, -32);
		 // Integrity check.  If this fails, either the data is corrupted, or the password/salt was incorrect.
		 if (md5($decrypted) != $hash) return false;
		 // Yay!
		 return $decrypted;
 	}
    /*
	public function excelChampionshipAction()
    {
    	try {
    		$this->_helper->layout->setLayout('excellayout');
    		$competition = Model_Competition::loadCompetition(1);
    		$this->view->competition=$competition;    		
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in excelChampionshipAction ' . $e->getMessage().'---------'. $e->getTraceAsString());    		
    	}        
    }
    */
    
	//called for register excel championship
    public function registerAction() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	
    	$is_existing_user=true;
    	
    	try {
    		$userModel = new Model_User();    		
    		$competitionUserModel = new Model_CompetitionUser();
    		$templateMessage = new Zend_View();
	    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    	
	    	$first_name = $this->_getParam('first_name');
	    	$last_name = $this->_getParam('last_name');
	    	$email = $this->_getParam('email');
	    	$phone = $this->_getParam('phone');
	    		  	    	
	    	// STEP 1: CHECK IF USER EXISTS IN DATABASE
	    	$currentUser = $userModel->loadUserByEmail($email);
    		if(count($currentUser)==1) {    			
    			$competitionUserRow = $competitionUserModel->checkUserRegistered(1,$currentUser->user_id);
    			if(count($competitionUserRow)!=1) {
    				$competitionUserModel->registerUser(1, $currentUser->user_id);
    				$userModel->updateUserName($currentUser->user_id, $first_name, $last_name);
    			}					    	
    		} else {
    			//User does not exist
    			$is_existing_user=false;
    			$role = Model_Role::getRole('student');
		    	$role = $role->toArray();
		    		    	
		    	//generate random password
				$password = $this->randomPassword();
	
				//GET REFERER FROM COOKIE
				$request = new Zend_Controller_Request_Http();
		    	$referrer = $request->getCookie('dezyre-referrer');
		    			
		    	$userModel = new Model_User();		
				$rowUser=$userModel->createUser(				
						$email,
						$password,
						$role[0]['role_id'],
						$first_name,
						$last_name,
						$phone,
						$referrer,
						'Y'
				);
				$currentUser = $userModel->loadUserProfile($rowUser->user_id);
			    
			    //IF NEW USER LOG THE USER IN
				$auth = Zend_Auth::getInstance();
				$storage = $auth->getStorage();			
		        $storage->write($currentUser);
		        
		        //and register the user in competition
		        $competitionUserModel->registerUser(1, $currentUser->user_id);
    		}
    		
    		//Send welcome email with password		
    		$templateMessage->to_user_name = $first_name;			
			$templateMessage->to_user_email = $email;
			if($is_existing_user==false)
		    	$templateMessage->is_new_user = 'yes';
		    
		    $templateMessage->password = $password;
		    $subject = 'Thank you for registering for World Excel Championships at DeZyre';
		    $this->_helper->SendEmailAction($subject,$templateMessage,'competition.phtml');
			    

    		
	    	$arr = array ('success'=>'ok');
    		echo json_encode($arr);
    		
	    		
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in joinFreeAction course_id='.$this->_getParam('course_id').' in CourseController: ' .$e->getMessage().'---------'. $e->getMessage().'---------'. $e->getTraceAsString());
    		$arr = array ('success'=>'fail');
    		echo json_encode($arr);
    }
    }
    
	public function randomPassword()
	{
		$strings = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		
		$password = '';
		for($i=0; $i<10;$i++){
			$password .= substr($strings, rand(0, strlen($strings)), 1);
		}
		return $password;
	}
}


