<?php

class UserController extends Zend_Controller_Action
{

	private $_user_id;	
	
    public function init()
    {
        /* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }
	
    public function indexAction() {
    	return $this->_redirect('/user/my-courses/');
    }
    
    public function reviewsAction() {
    	$testimonials = Model_Testimonial::getTestimonials();
    	$testimonials_pics=array();
    	$testimonials_nopics=array();
    	foreach($testimonials as $testimonial) {
    		if($testimonial->user_picture_id!=null)
    			$testimonials_pics[]=$testimonial;
    		else
    			$testimonials_nopics[]=$testimonial;
    	}
    	//print_r($testimonials);
    	$this->view->testimonials_pics=$testimonials_pics;
    	$this->view->testimonials_nopics=$testimonials_nopics;
    }
    
    //This should be the students dashboard - after login this page can be shown with what courses he has taken
    // CALLED when user logs in - this is users dashboard page
    public function myCoursesAction()
    {
    	if($this->_user_id==null)
    		return $this->_forward('no-page/','error');
    	// action body
    	try {
	    	if($this->_user_id!=null) {    			    		   
		    	$currentCourses = Model_Enrollment::getCoursesByStudent($this->_user_id);
		    	//Zend_Registry::get('logger')->err('Exception ===='.count($currentCourses));
		    	//echo $currentCourses;
		    	$this->view->courses = $currentCourses;				
	    	}
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in indexAction user id='.$this->_user_id.' in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    //This should be the students dashboard - after login this page can be shown with what courses he has taken
    // CALLED when user logs in - this is users dashboard page
    public function myAccountAction()
    {
    	if($this->_user_id==null)
    		return $this->_forward('no-page/','error');
    	// action body
    	try {
	    	if($this->_user_id!=null) {    		
	    		   
		    	//GET DISCOUNT CODE
		    	$discount_code = Model_Discount::getDiscountCode($this->_user_id);
		    	$this->view->discount_code = $discount_code['discount_code'];		    	
	    	}
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in indexAction user id='.$this->_user_id.' in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    
    
    
    // WHEN USER CLICKS ON LOGIN THIS ACTION IS CALLED //
	public function loginAction()
    {
    	$auth = Zend_Auth::getInstance();
    	if($auth->hasIdentity()) {
    		return $this->_redirect('/');
    	}   	
    	
    	try {
    		
    		
			$userForm = new Form_User();
			$userForm->setAction('/user/login');
			$email = $userForm->getElement('email');
			$email->clearValidators();
			$password = $userForm->getElement('password');
			$password->clearValidators();
			$userForm->removeElement('first_name');
			$userForm->removeElement('last_name');
			$userForm->removeElement('password2');
			$userForm->removeElement('phone');		
			$userForm->removeElement('role');
			$userForm->removeElement('phone');
			$userForm->removeElement('address_line_1');
	    	$userForm->removeElement('address_line_2');
	    	$userForm->removeElement('city');
	    	$userForm->removeElement('state');
	    	$userForm->removeElement('zip');
	    	$userForm->removeElement('country');
	    	$userForm->removeElement('full_name');
			
			$requestUri = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
			if ($this->_request->isGet()) {	    
				$this->view->last_url=$requestUri;			
	    	}
			
			if ($this->_request->isPost() && $userForm->isValid($_POST)) {
				$this->view->last_url=$requestUri;
				if($this->log_user()) {
					//return $this->_redirect('/user/my-courses/');
					$auth = Zend_Auth::getInstance();
					$storage = $auth->getStorage();
					$user=$storage->read();
					
					$userPictureModel = new Model_UserPicture();
					$profile_picture = $userPictureModel->find($this->_user_id)->current();		
			
    				if($user->first_name==null || $user->last_name==null || $user->linkedin==null || $user->college==null || $user->degree==null || count($profile_picture)==0)
						return $this->_redirect('/user/view/');
					else
						return $this->_redirect('/user/my-courses/');
				}
				else
					$this->view->loginMessage = "Sorry, your email or password was incorrect or your email has not been validated";
					 
							
			}
			$this->view->form = $userForm;
			if($this->_getParam('email')!=null) {
				$this->view->email = $this->_getParam('email');
			}
			//first clear any old session
    		Zend_Session::destroy(true);
			
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in loginAction email='.$email.' in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}		
    }
    
	public function aloginAction()
    {    	
    	try {
    		if ($this->_request->isPost()) {
    			$this->_helper->viewRenderer->setNoRender(true);
    			$this->_helper->layout->disableLayout();
    			$email=$this->_getParam('email');
    			$password = $this->_getParam('password');
    			
    			//FIRST CHECK IF EMAIL IS VALID
    			if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
    				$userModel = new Model_User();
    				$currentUser = $userModel->loadUserByEmail($email);
    				//THEN CHECK IF EMAIL EXIST IN DATABASE
    				if(count($currentUser)==1) {
    					//IF EXIST THEN LOGIN USER
    					if($this->log_user())
    						$arr = array ('status'=>'login_success','user_id'=>$currentUser->user_id);	    	    
    					else
    						$arr = array ('status'=>'login_fail');
    				}    	
    				else {
    					//WE HAVE TO REGISTER USER
    					$arr = $this->registerUser($email,$password);    					     					
    				}	    				
    			}
    			else {
    				$arr = array ('status'=>'invalid_email');    				
    			}    			
    			echo json_encode($arr);
    		}
    		else
    			return $this->_forward('no-page/','error'); 		
					
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in aloginAction in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}		
    }
    
	private function registerUser($email,$password)
	{
		$userModel = new Model_User();    	
    	$templateMessage = new Zend_View();
	    $templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    $is_existing_user=true;
		//FIRST CHECK IF EMAIL ALREADY EXISTS
    	$currentUser = $userModel->loadUserByEmail($email);
    	//IF EMAIL DOES NOT EXISTS REGISTER THE USER IN
    	if(count($currentUser)!=1) {    			
    		$role = Model_Role::getRole('student');
	    	$role = $role->toArray();
	    		    	
	    	//GET REFERER FROM COOKIE
			$request = new Zend_Controller_Request_Http();
	    	$referrer = $request->getCookie('dezyre-referrer');	    			
	    			
			$rowUser=$userModel->createUser(				
					$email,
					$password,
					$role[0]['role_id'],
					null,
					null,
					null,
					$referrer,
					'N'
			);
			$currentUser = $userModel->loadUserProfile($rowUser->user_id);
			//IF NEW USER LOG THE USER IN
			$auth = Zend_Auth::getInstance();
			$storage = $auth->getStorage();			
	        $storage->write($currentUser);
	        	
			$is_existing_user=false;
			//Send welcome email with password					
			$templateMessage->to_user_email = $email;
		    $templateMessage->is_new_user = 'yes';
		    $templateMessage->password = $password;
		    $subject = 'Welcome to DeZyre. Account Created';
		    $this->_helper->SendEmailAction($subject,$templateMessage,'welcome.phtml');
		    $user_id=$rowUser->user_id;					
    	}
    	//Zend_Registry::get('logger')->err('registeraction = enrollment_id====' .$enrollment_id);
    	$ret_array = array('status'=>'login_success','user_id'=>$currentUser->user_id);    				 
    	return $ret_array;
	}

    // WHEN USER CLICKS ON REGISTER THIS ACTION IS CALLED //
    
    public function registerAction()
    {
    	$auth = Zend_Auth::getInstance();
    	if($auth->hasIdentity()) {
    		return $this->_redirect('/');
    	}
    	
    	try {
	    	$userForm = new Form_User();
	    	$userForm->removeElement('address_line_1');
	    	$userForm->removeElement('address_line_2');
	    	$userForm->removeElement('city');
	    	$userForm->removeElement('state');
	    	$userForm->removeElement('zip');
	    	$userForm->removeElement('country');
	    	$userForm->removeElement('full_name');
	    	$userForm->removeElement('phone');
	    	
	    	$requestUri = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
			if ($this->_request->isGet()) {	    
				$this->view->last_url=$requestUri; 			
	    	}
	    	
			if ($this->_request->isPost()) {
				if ($userForm->isValid($_POST)) {
					$this->view->last_url=$requestUri;
					//first select role_id from role table where role=student
	    			$role = Model_Role::getRole('student');
	    			$role = $role->toArray();
	    			
	    			//generate verification code
					$verify_code = $this->randomPassword();
					
					//GET REFERER FROM COOKIE
					$request = new Zend_Controller_Request_Http();
	    			$referrer = $request->getCookie('dezyre-referrer');
	    			//Zend_Registry::get('logger')->err('Referer='.$referer);
	    			
					$userModel = new Model_User();
					$rowUser=$userModel->createUser(				
					$this->_getParam('email'),
					$this->_getParam('password'),
					$role[0]['role_id'],
					$this->_getParam('first_name'),
					$this->_getParam('last_name'),					
					$this->_getParam('phone'),
					$referrer,
					'N'
					);
					
					//Generate discount code
					$email=explode("@",$rowUser->email);
					//$discount_code = strtolower($email[0]).'-'.$this->_helper->ReferralCodeAction();
					$discount_code = strtolower($email[0]).'-dezyre';
					
					
					//Insert referral code in discount table
					$discountModel = new Model_Discount();
					$rowDiscount = $discountModel->addDiscount($rowUser->user_id,$discount_code,Zend_Registry::getInstance()->configuration->referral->discount);
					
					//Send Activation Email
					/*
					$templateMessage = new Zend_View();
			    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');		    	
			    	$templateMessage->to_user_name = $rowUser->first_name.' '.$rowUser->last_name;
			    	$templateMessage->to_user_email = $rowUser->email;
			    	$templateMessage->to_user_id = $rowUser->user_id;
			    	$templateMessage->verify_code = $verify_code;
			    	$subject = 'Activate your account on DeZyre.com';
			    	$this->_helper->SendEmailAction($subject,$templateMessage,'activate.phtml');
			    	*/
			    	
			    	
			    	//just to make sure no one simply enters the url and goes to that page
			    	//return $this->_redirect('/user/activate-email/email/'.$rowUser->email);
			    	//echo 'Please check your email and click on the link to activate your account';
					
					//Send Welcome Email
					$templateMessage = new Zend_View();
			    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');		    	
			    	$templateMessage->to_user_name = $rowUser->first_name.' '.$rowUser->last_name;
			    	$templateMessage->to_user_email = $rowUser->email;
			    	//$templateMessage->message = 'Welcome to DeZyre - ';
			    	$subject = 'Welcome to DeZyre '.$rowUser->first_name.' '.$rowUser->last_name;
			    	$this->_helper->SendEmailAction($subject,$templateMessage,'welcome.phtml');					
					//$this->log_user();
					
					if($this->log_user()) {
						return $this->_redirect('/user/my-courses/');
					}
					else
						$this->view->loginMessage = "Sorry, your email or password was incorrect or your email has not been validated";
					

					return $this->_redirect('/course/list-courses/');
				}
				
				else {
	        		$this->view->errors = $userForm->getMessages();        		
				}
			}
			$this->view->form = $userForm;
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in registerAction in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    // Called from registerAction after user validates email    
    public function activateEmailAction() {
    	//echo 'jjjjjjjjjj==='.$_SERVER['HTTP_REFERER'];    	
    	$pos = strpos($_SERVER['HTTP_REFERER'], 'user/register');
    	if($pos === false)
    		return $this->_forward('no-page/','error');
    	
    	$this->view->email = $this->_getParam('email');
    }
    
	public function sendActivationAction()
    {
    	$auth = Zend_Auth::getInstance();
    	if(!$auth->hasIdentity()) {
    		return $this->_redirect('/');
    	}
    	
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	
    	try {
	    	
			if ($this->_request->isPost()) {				
					//generate verification code
					$verify_code = $this->randomPassword();
					$userModel = new Model_User();
			    	//update verification code
	    			$userModel->updateVerificationCode($this->_user_id,$verify_code);
	    			$rowUser= $userModel->loadUserProfile($this->_user_id);
					//Send Activation Email
					
					$templateMessage = new Zend_View();
			    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');		    	
			    	$templateMessage->to_user_name = $rowUser->first_name.' '.$rowUser->last_name;
			    	$templateMessage->to_user_email = $rowUser->email;
			    	$templateMessage->to_user_id = $rowUser->user_id;
			    	$templateMessage->verify_code = $verify_code;
			    	$subject = 'Activate your account on DeZyre.com';
			    	$this->_helper->SendEmailAction($subject,$templateMessage,'activate.phtml');
			    	$arr = array ('success'=>'ok');
	    	    	echo json_encode($arr);			    	
			}
			
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in sendActivation in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    
    
	// WHEN USER CLICKS ON REGISTER THIS ACTION IS CALLED //    
    public function activateAction()
    {
    	$auth = Zend_Auth::getInstance();
    	if(!$auth->hasIdentity()) {
    		return $this->_redirect('/');
    	}
    	
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	
    	try {
	    	$userModel = new Model_User();
			$verify_code = $this->_getParam('code');
			//check if verification code is correct
			//$user = $userModel->find($id)->current();
			$userModel = new Model_User();
        	$user = $userModel->loadUserProfile($this->_user_id);
        	if($user->verification_code == $verify_code) {
				$rowUser = $userModel->activateAccount($this->_user_id);
				$arr = array ('success'=>'ok');	    	    				
				//Send Welcome Email
				/*
				$templateMessage = new Zend_View();
		    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');		    	
		    	$templateMessage->to_user_name = ' '.$rowUser->first_name.' '.$rowUser->last_name;
		    	$templateMessage->to_user_email = $rowUser->email;
		    	//$templateMessage->message = 'Welcome to DeZyre - ';
		    	$subject = 'Welcome to DeZyre '.$rowUser->first_name.' '.$rowUser->last_name;
		    	$this->_helper->SendEmailAction($subject,$templateMessage,'welcome.phtml');
	
		    	
		    	$auth = Zend_Auth::getInstance();
				$storage = $auth->getStorage();			
	        	$storage->write($user);
	        	*/
        	}
        	else {
        		$arr = array ('success'=>'no');        		
        	}        									 
			echo json_encode($arr);
			
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in activateAction in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	// WHEN USER CLICKS ON Edit Profile//
    
    public function editProfileAction()
    {
    	
    	$auth = Zend_Auth::getInstance();
    	if(!$auth->hasIdentity()) {
    		return $this->_redirect('/');
    	}
    	try {
	    	$userForm = new Form_User();
			$userForm->setAction('/user/edit-profile');
			$email = $userForm->getElement('email');
			$email->clearValidators();
			$email->addValidator('EmailAddress',  TRUE  );
			$db_lookup_validator = new Zend_Validate_Db_NoRecordExists('user', 'email', array('field' => 'user_id', 'value' => $this->_user_id));
        	$email->addValidator($db_lookup_validator);
			
			$userForm->removeElement('password');
			$userForm->removeElement('password2');
			$userForm->removeElement('address_line_1');
	    	$userForm->removeElement('address_line_2');
	    	$userForm->removeElement('city');
	    	$userForm->removeElement('state');
	    	$userForm->removeElement('zip');
	    	$userForm->removeElement('country');
	    	$userForm->removeElement('full_name');
	    	
	    	
	    	$userForm->setEnctype(Zend_Form::ENCTYPE_MULTIPART);
			$image = new Zend_Form_Element_File('image');
			$image->setLabel('Upload an image:')
			      ->setMaxFileSize(10240000) // limits the filesize on the client side
			      ->setDescription('Click Browse and click on the image file you would like to upload');
			      
			$image->addValidator('Count', false, 1);                // ensure only 1 file
			$image->addValidator('Size', false, 10240000);            // limit to 10 meg
			$image->addValidator('Extension', false, 'jpg,jpeg,png,gif');// only JPEG, PNG, and GIFs
			$userForm->addElement($image);
	    	
	    	
	    	
			$userModel = new Model_User();
			if ($this->_request->isPost()) 
			{				
				if ($userForm->isValid($_POST)) 
				{
					/*
					$linkedin=trim($userForm->getValue('linkedin'));
					if($linkedin!=null) {
						if('http://' === "" || strpos($linkedin, 'http://') === 0)
							$linkedin=$userForm->getValue('linkedin');
						else 
							$linkedin='http://'.$userForm->getValue('linkedin');
					}
					*/
						
					$userModel->updateUser(
					$this->_user_id,
					trim($userForm->getValue('email')),
					trim($userForm->getValue('first_name')),
					trim($userForm->getValue('last_name')),
					trim($userForm->getValue('phone')),
					trim($userForm->getValue('degree')),
					trim($userForm->getValue('college')),
					trim($userForm->getValue('company')),
					trim($userForm->getValue('designation')),
					trim($userForm->getValue('linkedin'))
					);
					
					
				if($userForm->image->isUploaded())
			    {
			        $values = $userForm->getValues();
			        $source = $userForm->image->getFileName();
			        $t_source = $userForm->image->getFileInfo();
			        
			    	$fileInfo = $userForm->image->getFileInfo();
					// if submitted a file
					
					if( $fileInfo['image']['tmp_name'] ){						
							$this->savePicture($fileInfo['image']['tmp_name']);						
					}
			
			        
			    }
					
					
					
					
					return $this->_redirect('/user/view/user_id/'.$this->_user_id); 
				}
			else {
					$this->view->errors = $userForm->getMessages();        		
				}
			}
			else 
			{			
				$currentUser = $userModel->find($this->_user_id)->current();
				$userForm->populate($currentUser->toArray());
				
				$userPictureModel = new Model_UserPicture();
				$profile_picture = $userPictureModel->find($this->_user_id)->current();
				$this->view->profile_picture = $profile_picture;
		
			}
			$this->view->form = $userForm;
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in editProfileAction user id='.$this->_user_id.' in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	
	// WHEN USER CLICKS ON Change Password//    
    public function changePasswordAction()
    {
    	$auth = Zend_Auth::getInstance();
    	if(!$auth->hasIdentity()) {
    		return $this->_redirect('/');
    	}
    	try {
	    	$userForm = new Form_User();
			$userForm->setAction('/user/change-password');
			$userForm->removeElement('email');
			$userForm->removeElement('first_name');
			$userForm->removeElement('last_name');
			$userForm->removeElement('phone');
			$userForm->removeElement('address_line_1');
	    	$userForm->removeElement('address_line_2');
	    	$userForm->removeElement('city');
	    	$userForm->removeElement('state');
	    	$userForm->removeElement('zip');
	    	$userForm->removeElement('country');
	    	$userForm->removeElement('full_name');
			$userModel = new Model_User();
			if ($this->_request->isPost()) 
			{
				if ($userForm->isValid($_POST)) 
				{
					$userModel->updatePassword($this->_user_id,$userForm->getValue('password'));
					return $this->_forward('/my-account'); 
				}
			else {
	        		$this->view->errors = $userForm->getMessages();        		
				}
			}			
			$this->view->form = $userForm;
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in changePasswordAction user id='.$this->_user_id.' in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    	
    }
    
    // WHEN USER CLICKS ON Add Edit Billing//    
    public function billingAction()
    {
    	$auth = Zend_Auth::getInstance();
    	if(!$auth->hasIdentity()) {
    		return $this->_redirect('/');
    	}
    	try {
	    	$userForm = new Form_User();
			$userForm->setAction('/user/billing');
			$userForm->removeElement('email');
			$userForm->removeElement('first_name');
			$userForm->removeElement('last_name');
			$userForm->removeElement('phone');
			$userForm->removeElement('password');
			$userForm->removeElement('password2');
			$userModel = new Model_User();
			if ($this->_request->isPost()) 
			{
				if ($userForm->isValid($_POST)) {
					//first get country
					$country_id = $userForm->getValue('country');
					//check if state exists - if it does - then select that state_id else insert new one
					$stateRow = Model_State::getState($userForm->getValue('state'),$userForm->getValue('country'));
					//echo '<br/><br/>YAHOOOOOOOOOOOOO<br/><br/>=='.count($stateRow);
					$state_id='';
					if(count($stateRow)==1) {
						//then get state_id 
						$state_id = $stateRow[0]->state_id;
						//echo 'STATE ID='.$stateRow[0]->state_id;
					}
					else {
						//we have more than 2 rows with the same name - so go ahead and insert
						$stateModel = new Model_State();
						$rowState = $stateModel->addState($country_id,$userForm->getValue('state'));
						$state_id = $rowState->state_id;
					}
					//check if city exists - if it does then select city_id else insert new one
					$cityRow = Model_City::getCity($userForm->getValue('city'),$state_id);
					$city_id='';
					if(count($cityRow)==1) {
						//then get city_id 
						$city_id = $cityRow[0]->city_id;
					}
					else {
						//we have more than 2 rows with the same name - so go ahead and insert
						$cityModel = new Model_City();
						$rowCity = $cityModel->addCity($state_id,$userForm->getValue('city'));
						$city_id = $rowCity->city_id;
					}				
					$address_id = $userForm->getValue('address_id');
					if($address_id == null) //means new address 
					{
						//add new address
						$addressModel = new Model_Address();
						$rowAddress = $addressModel->createAddress($city_id,$userForm->getValue('full_name'),$userForm->getValue('address_line_1'),$userForm->getValue('address_line_2'),$userForm->getValue('zip'));
						//now add this address_id to user
						$userModel->updateUserAddress($this->_user_id,$rowAddress->address_id);						
					}
					else {
						//update existing address					
						$addressModel = new Model_Address();
						$rowAddress = $addressModel->updateAddress($address_id,$city_id,$userForm->getValue('full_name'),$userForm->getValue('address_line_1'),$userForm->getValue('address_line_2'),$userForm->getValue('zip'));				
					}
					
					return $this->_redirect('/user/my-account'); 
				}
				else {
	        		$this->view->errors = $userForm->getMessages();
	        		$country_list = Model_Country::getCountry();
					$this->view->country = $country_list;        		
				}
			}
			else {
				//first check if billing address already exists
				$userModel = new Model_User();
				$user = $userModel->find($this->_user_id)->current();
				//print_r($user->toArray());
				if($user->address_id!=null) {
					//get Address from address table
					$rowAddress = Model_Address::getAddress($user->address_id);
					//print_r($rowAddress->toArray());
					//echo '<br/><br/>full name='.$rowAddress->full_name;
					//assign to form
					$full_name = $userForm->getElement('full_name')->setValue($rowAddress->full_name);
					$userForm->getElement('address_line_1')->setValue($rowAddress->address_line_1);
					$userForm->getElement('address_line_2')->setValue($rowAddress->address_line_2);
					$userForm->getElement('city')->setValue($rowAddress->city_name);
					$userForm->getElement('state')->setValue($rowAddress->state_name);
					$userForm->getElement('country')->setValue($rowAddress->country_name);
					$userForm->getElement('zip')->setValue($rowAddress->zip_code);
					$userForm->getElement('address_id')->setValue($user->address_id);
				}
				$country_list = Model_Country::getCountry();
				$this->view->country = $country_list;
			}			
			$this->view->form = $userForm;
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in billingAction user id='.$this->_user_id.' in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	private function log_user()
    {
    	$db = Zend_Db_Table::getDefaultAdapter();
		//create the auth adapter
		$authAdapter = new Zend_Auth_Adapter_DbTable($db, 'user','email', 'password');
		//set the username and password
		$authAdapter->setIdentity($this->_getParam('email'));
		$authAdapter->setCredential(md5(trim($this->_getParam('password'))));
		//$authAdapter->setCredentialTreatment('? and is_active="Y"');
		
		//authenticate
		$result = $authAdapter->authenticate();
		
		if ($result->isValid()) {			
			$auth = Zend_Auth::getInstance();
			$storage = $auth->getStorage();
			
			$row = $authAdapter->getResultRowObject(array('user_id'));
			$userModel = new Model_User();
        	$user = $userModel->loadUserProfile($row->user_id);
        	$storage->write($user);        	        	
        	$userModel->updateLastLogin($row->user_id);
			return true;        	
		} 
		else {
			return false;
		}
    }
    
    
    
    
    // CALLED TO LOGOUT USER
	public function logoutAction()
    {
        // action body
        $authAdapter = Zend_Auth::getInstance();
		$authAdapter->clearIdentity();
		Zend_Session::destroy(true);
		return $this->_redirect('/');
    }
    
	public function forgotPasswordAction(){
		    	
		if( $this->getRequest()->isPost() ) 
		{
			try {
				$this->_helper->viewRenderer->setNoRender(true);
	    		$this->_helper->layout->disableLayout();
				// search for the user
				$userModel = new Model_User();
				$result = $userModel->fetchAll(array('email = ?' => $this->_getParam('email')));
	
				if($result->count()){
					// change the user password
					$user = $result->current();
					$newPassword = $this->randomPassword();
					$user->password = new Zend_Db_Expr('MD5(\''.$newPassword.'\')');
					$user->save();
	
					// prepare the email to send to user
					// set the view message and set the variables
			    	$templateMessage = new Zend_View();
			    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
			    	$templateMessage->to_user_name = $user->first_name;
			    	$templateMessage->to_user_email = $user->email;
			    	$templateMessage->to_user_password = $newPassword;
			    	
			    	$this->_helper->SendEmailAction('Dezyre Password Reset',$templateMessage,'reset-message.phtml');
			    	$arr = array ('success'=>'ok');
					echo json_encode($arr);	    	
				}
				else {
					$arr = array ('success'=>'no');
					echo json_encode($arr);
				}		
			} catch (Exception $e) {    		
    			Zend_Registry::get('logger')->err('Exception occured in forgotPasswordAction  in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			return $this->_forward('exception/','error');
    	}
		}
		else
    		return $this->_forward('no-page/','error');    	
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
    

        
    
	
    
	
    
    // THIS IS CALLED FROM THE MAIN MENU WHEN USER CLICKS ON "OUR FACULTY
    // also in admin/index
    public function listFacultyAction()
    {
    	try {
    		//$this->_helper->layout()->setLayout('adaptive_layout');
    		$faculty = Model_User::getFaculty();
    		
    		$this->view->faculty = $faculty;
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in listFacultyAction in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    // on list-courses page when we click on faculty name
    // on course main page where we click on faculty name
    public function viewFacultyAction()
    {
    	if($this->_getParam('user_id')==null || !is_numeric($this->_getParam('user_id')))
    		return $this->_forward('no-page/','error');
    	try {
	    	$userModel = new Model_User();
			$user = $userModel->loadFacultyProfile($this->_getParam('user_id'));
			$this->view->user = $user[0];
			
			$courses = Model_Course::getCoursesByFaculty($user[0]['user_id']);			
			$this->view->courses = $courses;	
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in viewFacultyAction in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }    
    
    
	public function viewAction()
    {
    	if($this->_user_id==null && ($this->_getParam('user_id')==null || !is_numeric($this->_getParam('user_id'))))
    		return $this->_forward('no-page/','error');
    	
    	if($this->_getParam('user_id')!=null)
    		$user_id=$this->_getParam('user_id');
    	else 
    		$user_id=$this->_user_id;
    		
    				    	
    	
    						
    	//for time being only users can see their profile
    	//if($this->_getParam('user_id')!=$this->_user_id)
    		//return $this->_redirect('/');
    		
    	try {
	    	$userModel = new Model_User();
	    	$currentUser = $userModel->find($user_id)->current();
	    	$this->view->currentUser = $currentUser;
	    	
	    	$url='';
	    	
			if($currentUser->company!=null)
				$url=$currentUser->company.' ';
			if($currentUser->designation!=null)
				$url.=$currentUser->designation.' ';
			if($currentUser->college!=null)
				$url.=$currentUser->college.' ';
			
			$name = $currentUser->first_name.' '.$currentUser->last_name;
			if($name==null) {
				$email_front= explode("@",$currentUser->email);
				$name=$email_front[0];	
			}
			
			$url .=$name;
			$title_url=$url;
			$this->view->title_url=$title_url;
			$url = str_replace(" ", "-", trim($url));
					
    		if(strcmp($this->_getParam('user_title'),$url)!=0) 
				$this->_helper->Redirector
        			 ->setCode(301) 
				     ->gotoRouteAndExit(array('user_title' => $url,
        						 'user_id' => $currentUser->user_id             					 
           						)
        					);
        
				
			$userPictureModel = new Model_UserPicture();
			$profile_picture = $userPictureModel->find($user_id)->current();
			$this->view->profile_picture = $profile_picture;
			
			//get users questions
			$questions = Model_CourseQuestion::getUserQuestions($user_id);
			//echo $questions;
			$this->view->questions=$questions;
			
			//get users answers
			$answers = Model_CourseAnswer::getUserAnswers($user_id);
			$this->view->answers=$answers;
			
			//echo $url;
			//get seo URL
			$url='';
			if($currentUser->first_name!=null || $currentUser->last_name!=null) 
				$url=$currentUser->first_name.'-'.$currentUser->last_name;			
			else {
				$email_front= explode("@",$currentUser->email);
				$url=$email_front[0];
			}
			
			//get reputation question
			$reputation=0;
			$voteQuestionModel = new Model_VoteQuestion();
    		$votes=$voteQuestionModel->getReputationScore($user_id);
    		if($votes->reputation!=0 && $votes->reputation !=null)
    			$reputation+=$votes->reputation;    		
    		
    		//get reputation answer
			$voteAnswerModel = new Model_VoteAnswer();
    		$votes=$voteAnswerModel->getReputationScore($user_id);
    		if($votes->reputation!=0 && $votes->reputation !=null)
    			$reputation+=$votes->reputation;
    		
    		$this->view->reputation_score=$reputation;
    		
    		$reputations = $voteQuestionModel->getReputations($user_id);
    		$this->view->reputations = $reputations;
    		//echo $reputation_questions;
    		//echo ('ppppppppp='.count($reputation_questions));
    		//foreach ($reputation_questions as $question) {
    			//echo 'reputation='.$question->reputation.'-question='.$question->question_title;
    		//}
    		
    		//get user favorites
    		$favorites=$voteQuestionModel->getFavorites($user_id);
    		$this->view->favorites=$favorites;

    		$incomplete='N';
    		if($currentUser->first_name==null || $currentUser->last_name==null || $currentUser->linkedin==null || $currentUser->college==null || $currentUser->degree==null || count($profile_picture)==0)
				$incomplete='Y';
    		$this->view->incomplete=$incomplete;
    		
    		
    		
			
					
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in viewAction in UserController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	private function savePicture($picture)
    {
    	require 'ResizeImage.php';
    	$image1 = new ResizeImage();
    	$image1->source = $picture;

    	// get picture 1 (50px)
    	$image1->width = 60;
    	$image1->height = 60;    	
    	$imageString_1 = $image1->Resize();

    	// get picture 2 (100px)
    	$image2 = new ResizeImage();
    	$image2->source = $picture;
    	$image2->width = 200;
    	$image2->height = 200;
    	$imageString_2 = $image2->Resize();

		// check if user has a picture
		$userPictureModel = new Model_UserPicture();
		if( !$userPictureModel->find($this->_user_id)->count() )
			$userPicture = $userPictureModel->createRow();
		else
			 $userPicture = $userPictureModel->find($this->_user_id)->current();

		$userPicture->user_id = $this->_user_id;
		$userPicture->picture_1 = $imageString_1;
		$userPicture->picture_2 = $imageString_2;
		$userPicture->save();
    }
   
	public function viewImageAction()
    {
		// disable layout and render script
    	$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

		// load user picture
		$userPictureModel = new Model_UserPicture();
		$userPicture = $userPictureModel->find($this->_getParam('user_id'))->current();
		$pictureNumber = $this->_getParam('n');
		//echo 'heeeee'.$pictureNumber;
		// if not found any image, i have to take default image to the user
		if($userPicture==null || !$userPicture->user_id ){
			//$picture = file_get_contents('../public/images/user/default.png');
			$picture = file_get_contents(Zend_Registry::get('configuration')->path->public.'/images/user/default.png');
			
		}
		else {
			if( $pictureNumber == 1 ){
				$picture = $userPicture->picture_1;
			}
			else {
				$picture = $userPicture->picture_2;
			}
		}
		
		// set the headers and show image
		$this->_response->setHeader('Content-Type', 'image/jpeg');
		$this->_response->setBody($picture);
		$this->_response->sendResponse();
    }
    
    // NOT TESTED
    /*
    public function deleteAction()
    {
		$id = $this->_request->getParam('id');
		$userModel = new Model_User();
		$userModel->deleteUser($id);
		return $this->_forward('list');
    }

	// NOT COMPLETED - DOES NOT WORK
    public function updateAction()
    {
		$userForm = new Form_User();
		$userForm->setAction('/user/update');
		$userForm->removeElement('password');
		$userModel = new Model_User();
		if ($this->_request->isPost()) 
		{
			if ($userForm->isValid($_POST)) 
			{
				$userModel->updateUser(
				$userForm->getValue('id'),
				$userForm->getValue('username'),
				$userForm->getValue('first_name'),
				$userForm->getValue('last_name'),
				$userForm->getValue('role')
				);
				return $this->_forward('list'); 
			}
		}
		else 
		{
			$id = $this->_request->getParam('id');
			$currentUser = $userModel->find($id)->current();
			$userForm->populate($currentUser->toArray());
		}
		$this->view->form = $userForm;
    }
    
	// NOT COMPLETED - IT WILL LIST ALL THE USERS IN THE DATABASE
    public function listAction()
    {
		
		$currentUsers = Model_User::getUsers();
		if ($currentUsers->count() > 0) {
		$this->view->users = $currentUsers;
		} else {
		$this->view->users = null;
		}
    }
    
    */
}





