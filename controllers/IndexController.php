<?php

class IndexController extends Zend_Controller_Action
{
	private $_user_id;

    public function init()
    {
        /* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }
    
	public function downloadAction()
    {
    	//$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	    	        
    }
           
	public function newsletterAction()
    {
        
    }
    
	public function videoAction()
    {
        
    }
    
	public function callbackAction()
    {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	
    	$name = $this->_getParam('name');
    	$email = $this->_getParam('email');
    	$mobile = $this->_getParam('mobile');
    	$course_title = $this->_getParam('course');
    	$course_id = $this->_getParam('course_id');
    	$referer = $this->_getParam('referer');
    	$country = $this->_getParam('country');
    	
    	$templateMessage = new Zend_View();
    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
    	
    	$templateMessage->callback_name = $name;
    	$templateMessage->callback_mobile = $mobile;    	    	
    	$templateMessage->callback_email = $email;
    	$templateMessage->callback_course = $course_title;
    	$templateMessage->callback_referer = $referer;
    	$templateMessage->callback_country = $country;
    	$templateMessage->to_user_email = Zend_Registry::getInstance()->configuration->callback->email;
    	$templateMessage->to_user_name = "Admin";
    	$this->_helper->SendEmailAction("Request Callback: ".$name.'-'.$mobile ,$templateMessage,'callback.phtml');
    	
    	//send mail to user
    	
    	$course = Model_Course::loadCourse($course_id);
    	
    	if($course->has_batch == 'Y')
    	{
    		$batches = Model_Batch::getNextBatch($course_id);
	    	$emailTemplate = strtolower(str_replace(" ","_",$course_title));
	    	
	    	$templateMessage->to_user_email = $email;
	    	$templateMessage->to_user_name = $name;
	    	$templateMessage->to_user_days = $batches->class_days;
	    	$templateMessage->to_user_time = $batches->class_time;
	    	$templateMessage->to_user_date = $batches->starting_day;
	    	$templateMessage->to_user_month = $batches->starting_month;
	    	$this->_helper->SendEmailAction("While we call you from DeZyre - here is some information about ".$course_title." ",$templateMessage,$emailTemplate.".phtml");
	    	$this->mailChimpAction($email,$name);
	    }
    	
    	echo "ok";
    }
    
   public function mailChimpAction($email,$name)
    {
    	try{
    	
    		if($this->_request->isPost()){
    		$apiKey = 'ee7156587f1bd38c25f113ddb0b9ea81-us4';
    		$listId = 'a85e34d341';
    		$double_optin=false;
    		$send_welcome=false;
    		$email_type = 'html';
    		//$email = $_POST['email'];
    		//$name = $_POST['name'];
    		Zend_Registry::get('logger')->err('email = '.$email);
    		Zend_Registry::get('logger')->err('name = '.$name);
    		
    		$merges = array('FNAME'=>$name);
    		$submit_url = "http://us4.api.mailchimp.com/1.3/?method=listSubscribe";
    		$data = array(
    				'email_address'=>$email,
    				'merge_vars' => $merges,
    				'apikey'=>$apiKey,
    				'id' => $listId,
    				'double_optin' => $double_optin,
    				'send_welcome' => $send_welcome
    		);
    		$payload = json_encode($data);
    		
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $submit_url);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($ch, CURLOPT_POST, true);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, urlencode($payload));
    		
    		$result = curl_exec($ch);
    		curl_close ($ch);
    		$data = json_decode($result);
    		if ($data->error){
    			echo $data->error;
    		} else {
    			echo "Thank you for your email. We'll keep you up to date..";
    		}
    		}
    	Zend_Registry::get('logger')->err('Inside test action for dudamobile');
    	}catch(Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in mailChimpAction in IndexController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function testAction()
    {
    	try {
	    	$this->_helper->viewRenderer->setNoRender(true);
	   		$this->_helper->layout->disableLayout();
	    	echo "ok";
    	}catch(Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in testAction in IndexController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    	
    }

    public function indexAction()
    {
    	try {
    		if($this->_user_id !=null) 
    			return $this->_forward('my-courses','user');
        	$courses = Model_Course::getActiveCourses();
        	$category_array=array();
        	
        	foreach($courses as $course){
        		$category_array[$course->category_name][]=array('title'=>$course->title,'fees'=>$course->fees,'course_id'=>$course->course_id,'og_description'=>$course->og_description,'seo_keyword2'=>$course->seo_keyword2);        		
        	}
        	
        	
        	$this->view->category_array = $category_array;
        	
        	
        	
        } catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in indexAction in IndexController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}        
    }
    
	public function privacyAction()
    {
        // action body
        
                        
    }
    /*
    public function pdfAction()
    {
    	try{
    	
    	//$this->_helper->viewRenderer->setNoRender(true);
    	//$this->_helper->layout->disableLayout();
    	
    	require_once('fpdf.php');
		require_once('fpdi.php');
		require_once 'Zend/Mail.php' ;
		require_once 'Zend/Mime/Part.php' ;
		require_once 'Zend/Mime.php' ;

		$pdf = new FPDI('L');
		$pdf->AddPage();
		$pdf->setSourceFile('./template.pdf');
		$tplIdx = $pdf->importPage(1);
		$size=$pdf->getTemplateSize($tplIdx);
		$pdf->useTemplate($tplIdx,0,0,297.02,210);
		
		$pdf->SetFont('times');
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFontSize(29);
		$pdf->SetXY(10, 92);
		$pdf->SetMargins(10,10,10);
		$pdf->Cell(0,10,'Mr. Mohamed Omair Aasim',0,1,'C');

		$pdf->SetFont('times','B');
		$pdf->SetFontSize(20);
		$pdf->SetXY(10, 117);
		$pdf->Cell(0,10,'GRADE A',0,1,'C');

		$pdf->SetFont('times');
		$pdf->SetXY(10, 127);
		$pdf->Cell(0,10,'AWARDED THIS MONTH OF '.strtoupper(date('F, Y')),0,1,'C');

		$name="Omair Aasim";
		$cert_name = str_replace(' ', '', $name);
		$file=$pdf->Output('certificates/'.$cert_name.'.pdf', 'F');
    	
    	$phone=addslashes("9900057870");
    	$address=addslashes("4G Temple Tree");
    	$course=addslashes("Investment Banking");
    	
    	//$file = 'people.txt';
		//$current = $name."\n".$phone."\n".$address."\n".$course;
		//file_put_contents($file, $current);
    	
	    exec("php ".APPLICATION_PATH."/write.php \"$name\" $phone \"$address\" \"$course\" > update.log 2>&1 &");
	    
	   }catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Index Controller examscoreAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }
	    
    }
    */
    
	public function disclaimerAction()
    {
        // action body                
    }
    
	public function aboutusAction()
    {    	            
    }
    
	public function contactusAction()
    {
        // action body                
    }
    
	public function investorAction()
    {
        // action body                
    }
    
	public function isbAction()
    {
        // action body 
        $this->_helper->layout()->setLayout('payment_layout');               
    }
    
	public function libaAction()
    {
        // action body 
        $this->_helper->layout()->setLayout('payment_layout');               
    }
    
	public function faqAction()
    {
        // action body                
    }
    
	public function amazonAction()
    {
    	$this->_helper->layout->disableLayout();
        // action body                
    }
    
	public function speedAction()
    {
    	//$this->_helper->layout->disableLayout();
        // action body                
    }
    
	public function sampleAction()
    {
    	//$this->_helper->layout->disableLayout();
        // action body                
    }
    
	public function studentNetworkAction()
    {
    	//$this->_helper->layout->disableLayout();
        // action body                
    }
    
	public function jobsAction()
    {
    	//if ($this->_request->isPost()) {
    		$keywords=$this->_getParam('keywords');
    		if($keywords==null)
    			$keywords="Financial Analyst";
    		$keywords1=str_replace(" ","+",$keywords);
    		$location=	$this->_getParam('location');
    		
    		
     
    		$xml_url="http://api.simplyhired.co.in/a/jobs-api/xml-v2/q-".$keywords1."/l-".$location."/ws-100/sb-dd/pn-1?pshid=50429&ssty=3&cflg=r&clip=122.172.202.96";
    		$xml = simplexml_load_file($xml_url);
    		$this->view->xml=$xml;    		
    		$this->view->keywords=$keywords;
    		$this->view->location=$location;
    		
    					
		
    	//}  
    	
                      
    }
    
    
	public function referralAction()
    {
    	if($this->_user_id !=null) {
    		$userModel = new Model_User();	    	
    		$user = $userModel->loadUserProfile($this->_user_id);
    		$this->view->user=$user;
    		
    		$discount_code_row = Model_Discount::getDiscountCode($user->user_id);
	    	$discount_code = $discount_code_row['discount_code'];
	    	$this->view->discount_code = $discount_code;
    	}
    	$courses = Model_Course::getCourseReferral();
        $this->view->courses=$courses;	
    }
    
	public function referralEmailsAction()
    {
    	if ($this->_request->isPost()) {
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();    	    
    		try {
    			if($this->_user_id !=null) {
    			$referralEmailModel = new Model_ReferralEmail();
    			$first_name = $this->_getParam('first_name');
    			$email_list = explode(',', $this->_getParam('emails'));
    			
    			//get discount code
    			$discount_code_row = Model_Discount::getDiscountCode($this->_user_id);
	    		$discount_code = $discount_code_row['discount_code'];
    			
    			$templateMessage = new Zend_View();
	    		$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    		    	
	    		$templateMessage->first_name = $first_name;
	    		$templateMessage->discount_code = $discount_code;	    		    	
   				
    			
    			foreach($email_list as $email) {
    				$referralEmailModel->addEmails($this->_user_id, $email);
    				$templateMessage->to_user_email = $email;    	
	    			//$this->_helper->SendEmailAction($first_name.' has invited you to check out DeZyre Online Academy',$templateMessage,'referral.phtml');
	    			
    				$this->_helper->SendMailjetAction($first_name.' has invited you to check out DeZyre Online Academy',$templateMessage,'referral.phtml');
    			}
    			  			
    			    			
    			$arr = array ('success'=>'ok');
	    	    echo json_encode($arr);   	
    			}		
    		}
    		catch (Exception $e) {    		
    			Zend_Registry::get('logger')->err('Exception occured in referralEmailsAction in IndexController ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			return $this->_forward('exception/','error');
    		} 
    	}    		
    }
    
	


}


