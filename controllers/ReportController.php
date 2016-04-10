<?php

class ReportController extends Zend_Controller_Action
{
	private $_user_id;

    public function init()
    {
        /* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }
    
	public function indexAction()
    {
    	        
    }
    
	public function freeStudentsAction()
    {
    	try {
    		$codes=Model_Discount::getFreeStudents();
    		//echo $codes;
    		$this->view->codes=$codes;
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in free students action in reportcontroller '.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}	    	        
    }
    
    public function downloadAction()
    {
    	//$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	
    	$enrollment_session = new Zend_Session_Namespace('enrollment_session');
    	$enrollments = $enrollment_session->report;
    	$this->view->enrollments=$enrollments;    	        
    }
    
    
	public function searchAction()
    {
    	$enrollments=null;
    	if ($this->_request->isPost()) {    		    	
	    	$email=$this->_getParam('email');
	    	
	    	$userModel = new Model_User();
    		$admin_courses = $userModel->adminCourses($this->_user_id);
    		$course_id_array=array();
    		foreach($admin_courses as $course)
    			array_push($course_id_array, $course->course_id);

    		$enrollments = Model_Enrollment::searchStudent($email, $course_id_array);
	    	$this->view->enrollments=$enrollments;
    		$this->view->email=$email;
    	}
    	        
    }
    
	public function reportAction()
    {    	
    	$start_date=null;
    	$end_date=null;
    	$course_id=null;
    	$course_title=null;
    	$custom_start_date=null;
    	$custom_end_date=null;
    	$this->view->custom_start_date=$start_date;
    	$this->view->custom_end_date=$end_date;
    	
    	$userModel = new Model_User();
    	$admin_courses = $userModel->adminCourses($this->_user_id);
    	
    	if ($this->_request->isPost()) {
    		    	
	    	$date=$this->_getParam('date_dropdown');
	    	$course_id=$this->_getParam('course_id');    	
	    	$course_title=$this->_getParam('course_title');
	    	
	    	if($date=='today') {
	    		$start_date = date('Y-m-d H:i:s',mktime(0,0,0,date('m', strtotime("this month")),date('d', strtotime("this month")),date('Y', strtotime("this month"))));
	    		$end_date= date('Y-m-d H:i:s',mktime(23,59,59,date('m', strtotime("this month")),date('d', strtotime("this month")),date('Y', strtotime("this month"))));	    		
	    	}
	    	else
	    	if($date=='yesterday') {
	    		$start_date = date('Y-m-d H:i:s',mktime(0,0,0,date('m', strtotime("this month")),date('d', strtotime("yesterday")),date('Y', strtotime("this month"))));
	    		$end_date= date('Y-m-d H:i:s',mktime(23,59,59,date('m', strtotime("this month")),date('d', strtotime("yesterday")),date('Y', strtotime("this month"))));
	    	}
	    	else
	    	if($date==null || $date=='thismonth') {
	    		$start_date = date('Y-m-d H:i:s',mktime(0,0,0,date('m', strtotime("this month")),1,date('Y', strtotime("this month"))));
	    		$end_date= date('Y-m-d H:i:s',mktime(23,59,59,date('m', strtotime("this month")),date('t', strtotime("this month")),date('Y', strtotime("this month"))));
	    	}
	    	else if($date=='lastmonth') {
	    		$start_date = date('Y-m-d H:i:s',mktime(0,0,0,date('m', strtotime("last month")),1,date('Y', strtotime("last month"))));
	    		$end_date= date('Y-m-d H:i:s',mktime(23,59,59,date('m', strtotime("last month")),date('t', strtotime("last month")),date('Y', strtotime("last month"))));
	    	}
	    	else if($date=='custom') {
	    		$start_date=$this->_getParam('startdate');
	    		$this->view->custom_start_date=$start_date;
	    		$start_date = date('Y-m-d H:i:s',mktime(0,0,0,date('m', strtotime($start_date)),date('d', strtotime($start_date)),date('Y', strtotime($start_date))));
	    		$end_date=$this->_getParam('enddate');
	    		$this->view->custom_end_date=$end_date;
	    		$end_date = date('Y-m-d H:i:s',mktime(23,59,59,date('m', strtotime($end_date)),date('d', strtotime($end_date)),date('Y', strtotime($end_date))));
	    	}	    	
	    	$this->view->course_title=$course_title;
    		$this->view->course_id=$course_id;
    		
    		if($course_id=='all') {
    			$course_id_array=array();
    			foreach($admin_courses as $course)
    				array_push($course_id_array, $course->course_id);
    			$enrollments = Model_Enrollment::getStudentsByCourse($course_id_array,$start_date,$end_date);
    		}
    		else 
    			$enrollments = Model_Enrollment::getStudentsByCourse($course_id,$start_date,$end_date);
    	}
    	else {    		
    		$course_id=$admin_courses[0]->course_id;
    		$course_title=$admin_courses[0]->title;
    		
	    	$this->view->course_title=$course_title;
	    	$this->view->course_id=$course_id;

	    	//default make it This month
	    	$start_date = date('Y-m-d H:i:s',mktime(0,0,0,date('m', strtotime("this month")),1,date('Y', strtotime("this month"))));
	    	$end_date= date('Y-m-d H:i:s',mktime(23,59,59,date('m', strtotime("this month")),date('t', strtotime("this month")),date('Y', strtotime("this month"))));
	    	$date='thismonth';
	    	$enrollments = Model_Enrollment::getStudentsByCourse($course_id,$start_date,$end_date);	    		
    	}
    	$revenue=0;
    	foreach($enrollments as $enrollment) { 
    		$revenue +=$enrollment->payment_amount;
		}				
    	
		$amount = $this->moneyFormatIndia($revenue);
		    	
    	$this->view->courses=$admin_courses;    		    	
    	$this->view->enrollments=$enrollments;
    	
    	$enrollment_session = new Zend_Session_Namespace('enrollment_session');
		$enrollment_session->report = $enrollments;
		
    	$this->view->date=$date;
    	$this->view->revenue=$amount;
    	
    	
    }
    


	function moneyFormatIndia($num){
	    $explrestunits = "" ;
	    if(strlen($num)>3){
	        $lastthree = substr($num, strlen($num)-3, strlen($num));
	        $restunits = substr($num, 0, strlen($num)-3); // extracts the last three digits
	        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping.
	        $expunit = str_split($restunits, 2);
	        for($i=0; $i<sizeof($expunit); $i++){
	            // creates each of the 2's group and adds a comma to the end
	            if($i==0)
	            {
	                $explrestunits .= (int)$expunit[$i].","; // if is first value , convert into integer
	            }else{
	                $explrestunits .= $expunit[$i].",";
	            }
	        }
	        $thecash = $explrestunits.$lastthree;
	    } else {
	        $thecash = $num;
	    }
	    return $thecash; // writes the final format where $currency is the currency symbol.
	}
}

