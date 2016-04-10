<?php

class ExamController extends Zend_Controller_Action
{
	private $_user_id;

    public function init()
    {
        /* Initialize action controller here */
    	/* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }

    public function indexAction()
    {
        // action body
        $course_id = intval($this->_getParam('course_id'));
        $exam = Model_ExamCourse::getExamId($course_id);
        $this->view->course_id = $course_id;
        $this->view->exam = $exam;
    }
    
	public function errorAction()
    {
        // action body        
        $question_answer_session = new Zend_Session_Namespace('question_answer_session');
	    $exam_user_id = $question_answer_session->exam_user_id;
	    
        $examUserModel = new Model_ExamUser();
		$examUserRow = $examUserModel->updateExamTaken($exam_user_id);
    }
    
    public function examScoreAction()
    {
    	try 
    	{
    	    		
    		$question_answer_session = new Zend_Session_Namespace('question_answer_session');
	    	$exam_user_id = $question_answer_session->exam_user_id;
	    	$course_id = $question_answer_session->course_id;
	    	$course = Model_Course::loadCourse($course_id);
	    	
	    	
	    	$examUserModel = new Model_ExamUser();
			$examUserRow = $examUserModel->getFinalScore($exam_user_id);			
			$final_exam_score=$examUserRow->score;
	    		
			$quizUserModel = new Model_QuizUser();
			$quizUserRow = $quizUserModel->getAverageQuizScore($course_id,$this->_user_id);
	    	$avg_quiz_score = number_format((float)($quizUserRow->avg_quiz_score), 2, '.', '');
	    	$final_score = ($final_exam_score+$avg_quiz_score)/2;
	    	$final_grade='';
	    	if($final_score>=90)
	    		$final_grade='A';
	    	else if($final_score>=80)
	    		$final_grade='B';
	    	else if($final_score>=70)
	    		$final_grade='C';
	    	else if($final_score>=60)
	    		$final_grade='D';
	    	else if($final_score<60)
	    		$final_grade='F';
    	
	    //to check if user is from manipal
	    $userModel = new Model_User();
	    $currentUser = $userModel->find($this->_user_id)->current();
    	$this->view->currentUser = $currentUser;
		$this->view->exam_score = $final_exam_score;
		$this->view->avg_quiz_score = $avg_quiz_score;
		$this->view->final_grade = $final_grade;
		$this->view->course=$course;
    	
		}catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Exam Controller examscoreAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }	    
    }
    
    private function makeCertificate($salutation,$name,$grade,$template)
    {
    	require_once('fpdf.php');
		require_once('fpdi.php');
		require_once 'Zend/Mail.php' ;
		require_once 'Zend/Mime/Part.php' ;
		require_once 'Zend/Mime.php' ;

		$pdf = new FPDI('L');
		$pdf->AddPage();
		$pdf->setSourceFile('./'.$template);
		$tplIdx = $pdf->importPage(1);
		$size=$pdf->getTemplateSize($tplIdx);
		$pdf->useTemplate($tplIdx,0,0,297.02,210);
		
		$pdf->SetFont('times');
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFontSize(29);
		$pdf->SetXY(10, 92);
		$pdf->SetMargins(10,10,10);
		$pdf->Cell(0,10,$salutation.' '.$name,0,1,'C');
		$pdf->SetFontSize(20);
		if(strcmp($template,'template_tally.pdf')!=0) {
			$pdf->SetFont('times','B');
			$pdf->SetXY(10, 117);
			$pdf->Cell(0,10,'GRADE '.$grade,0,1,'C');
		}

		$pdf->SetFont('times');
		$pdf->SetXY(10, 127);
		$pdf->Cell(0,10,'AWARDED THIS MONTH OF '.strtoupper(date('F, Y')),0,1,'C');

		$cert_name = str_replace(' ', '', $name);
		$file=$pdf->Output('certificates/'.$cert_name.'.pdf', 'F');    	
    }
    
    public function thankyouAction()
    {    	
    	$question_answer_session = new Zend_Session_Namespace('question_answer_session');
	    $course_id = $question_answer_session->course_id;
	    $discount_code = $question_answer_session->discount_code;
	    $course = Model_Course::loadCourse($course_id);
	    $this->view->course=$course;    	
	    $this->view->discount_code=$discount_code;
    }
    
	public function testimonialAction()
    {
    	
    }
    
	public function submitTestimonialAction()
    {
    	try {
	    	$this->_helper->viewRenderer->setNoRender(true);
	    	$this->_helper->layout->disableLayout();
	    	
	    	$testimonial = $this->_getParam('testimonial');
	    	$rating = $this->_getParam('rating');
	    	if($rating==null || strlen(trim($rating))==0)
	    		$rating=0;
	    	if($testimonial!=null && strlen(trim($testimonial))>0) 
	    	{    		
	    		//Zend_Registry::get('logger')->err('inside if');
	    		$question_answer_session = new Zend_Session_Namespace('question_answer_session');
		    	$course_id = $question_answer_session->course_id;
	    		$testimonialModel = new Model_Testimonial();
	 			$testimonialModel->addTestimonial($this->_user_id, $course_id, $testimonial,$rating);
	 			if($_FILES['image']['tmp_name']) {    	
	 				$this->savePicture($_FILES['image']['tmp_name']);
	 			}
	    	}
    	}catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in submitTestimonial eXamController '. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }
    	
    	return $this->_redirect('/exam/exam-score');
    }
    
    public function submitCertAction()
    {
    	try {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	$salutation = $this->_getParam('salutation');
    	$grade = $this->_getParam('grade');
    	$email = $this->_getParam('email');
    	$email = addslashes(preg_replace("/[\n\r]/"," ",$email));
    	
    	$name = addslashes($this->_getParam('full_name'));
    	$name = addslashes(preg_replace("/[\n\r]/"," ",$name));
    	
    	$phone = addslashes($this->_getParam('phone'));
    	$phone = preg_replace("/[\n\r]/"," ",$phone);
    	$phone = str_replace(" ", "", $phone);
    	
    	
    	$address = $this->_getParam('address');
    	$address = addslashes(preg_replace("/[\n\r]/"," ",$address)); 
    	
    	$question_answer_session = new Zend_Session_Namespace('question_answer_session');
	    $course_id = $question_answer_session->course_id;
	    $course = Model_Course::loadCourse($course_id);
	    $course_title = $course->title;
	    $course_fees = $course->fees;
	    
	    $exam = Model_ExamCourse::getExamId($course_id);
	    $discount_code='';
	    //Insert discount code in discount table
	    if($course_fees==0){
		    $email_new=explode("@",$this->_getParam('email'));
		    
			
						
			date_default_timezone_set("Asia/Kolkata");
			$date = date('Y-m-d H:i:s', strtotime('+12 hours')); 			
			$discountModel = new Model_Discount();
			if($course_id==4) {
				$discount_code = strtolower($email_new[0]).'-bbf1500off';
				$rowDiscount = $discountModel->addTimedDiscount(9,1,$discount_code,21.74,$date,'Y');
			}
	    	else if($course_id==22) {
				$discount_code = strtolower($email_new[0]).'-btally1500off';
				$rowDiscount = $discountModel->addTimedDiscount(9,16,$discount_code,30.62,$date,'Y');
			}
			 else if($course_id==20) {
				$discount_code = strtolower($email_new[0]).'-bcm1500off';
				$rowDiscount = $discountModel->addTimedDiscount(9,13,$discount_code,25.42,$date,'Y');
			}
			//set discount_code in session
			$question_answer_session->discount_code = $discount_code;
			$discount_code = addslashes(preg_replace("/[\n\r]/"," ",$discount_code));
		}
    	//make certificate
    	//$name = $salutation.' '.$name;
    	$this->makeCertificate($salutation,$name, $grade,$exam->template);
    	//now send email
    	//Zend_Registry::get('logger')->err('Before certiifcate');
    	exec("php ".APPLICATION_PATH."/write.php \"$name\" $phone \"$address\" \"$course_title\" \"$email\" $course_fees \"$discount_code\" $course_id > update.log 2>&1 &");
    	//Zend_Registry::get('logger')->err('after certiifcate');
    	return $this->_redirect('/exam/thankyou');
    	}catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Exam Controller submitcertAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }
    }
    
	public function submitExamAction()
    {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	
    	try {
    	$course_id = intval($this->_getParam('course_id'));
    	$exam_id = intval($this->_getParam('exam_id'));
    	$exam_user_id = intval($this->_getParam('exam_user_id'));
    	
    	//get question from the session
	    $question_answer_session = new Zend_Session_Namespace('question_answer_session');
	    $question_array = $question_answer_session->questions;
	    
	    $answer_array = $question_answer_session->answers;
    	
    	$examResponseModel = new Model_ExamResponse();
    	$total_questions = count($question_array);
    	$correct_answers=0;
    	foreach ($_POST['questions'] as $key => $value) {
 			//echo "<br/>Field ".htmlspecialchars($key)." is ".htmlspecialchars($value)."<br>";
 			$question_id = htmlspecialchars($key);
 			$answer_id = htmlspecialchars($value); 			
 			$examResponseModel->addExamResponse($exam_user_id, $question_id, $answer_id);

 			//check if answer is correct or wrong
 			$answers = $answer_array[$question_id];
 			$selected_answer = $answers[$answer_id];
 			//echo 'selected answer is '.$selected_answer['answer'].'<br/>';
 			//echo 'Is correct '.$selected_answer['is_correct'].'<hr>';
 			if($selected_answer['is_correct']=='Y')
 				$correct_answers++;			
    	}
    	//echo '<br/>Total questions='.$total_questions;
    	//echo '<br/>Correct Answers='.$correct_answers;
    	//update score
    	$final_exam_score = number_format((float)($correct_answers/$total_questions *100), 2, '.', '');
    	$examUserModel = new Model_ExamUser();
		$examUserRow = $examUserModel->updateExamUser($exam_user_id,$final_exam_score);
		
		//return $this->_redirect('/exam/exam-score');
		return $this->_redirect('/exam/testimonial');
		
		}catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Exam Controller examscoreAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }	

 		
	    
    }
    
	
    public function startExamAction()
    {	
    try {	
    	
    	$course_id = intval($this->_getParam('course_id'));
    	
    	if($course_id==null || !is_int($course_id))
    		return $this->_redirect('/');
    		
    	
    	
    	// 2 If USER IS REGISTERED BUT NOT ENROLLED
    	if($this->_user_id !=null) {
    		$currentCourses=Model_Enrollment::isStudentEnrolled($course_id,$this->_user_id);
    		//if not registered send him to course page
    		if(count($currentCourses)==0 || $currentCourses->payment_received=='N') {    			
    				return $this->_redirect('/course-details/'.$course_id);
    		}
    	}
    	
    	// 3 If USER IS REGISTERED AND ENROLLED
    	if($this->_user_id !=null) {
    		$currentCourses=Model_Enrollment::isStudentEnrolled($course_id,$this->_user_id);
    		//if not registered send him to course page
    		if(count($currentCourses)!=0 && $currentCourses->payment_received=='Y') {
    			
    			//check if exam exist for that course    	
		    	$examCourse = Model_ExamCourse::getExamId($course_id);
		    	$exam_id=$examCourse->exam_id;
		    	//if exam does'nt exist for this course - redirect to course home page
		    	if($exam_id==null)
		    		return $this->_redirect('/course-details/'.$course_id);
		    	
		    	//enter record in exam_user
    			$examUser = Model_ExamUser::loadExamUser($exam_id,$this->_user_id);
		    	//if user has not taken then insert a row in exam user
		    	if($examUser==null) {
		    		$examUserModel = new Model_ExamUser();
		    		$examUser=$examUserModel->addExamUser($exam_id,$this->_user_id);
		    		//echo 'exam_user_id==='.$examUser->exam_user_id;
		    	} else if($examUser->is_complete=='Y') 		    	
		    		//else user has already taken the exam - redirect
		    		return $this->_redirect('/course-details/'.$course_id);
		    	
		    	//Check if user has completed all quizzes before taking the exam
		    	$quizUserModel = new Model_QuizUser();
				$quizUserRow = $quizUserModel->getQuizzesCompleted($course_id,$this->_user_id);
				$completed_quizzes=$quizUserRow->quizzes_completed;
				
				//get total topics for the course
				$topicModel = new Model_Topic();
	    		$topicRow = $topicModel->getTotalTopics($course_id);
	    		$topicRow->total_topics;
	    		//if he has not completed all topics - redirect
	    		//echo 'completed='.$completed_quizzes;
	    		//echo '<br/>total='.$topicRow->total_topics;
				if($completed_quizzes<$topicRow->total_topics)
	    			return $this->_redirect('/course-details/'.$course_id);
		    		
    			$exam = Model_Exam::loadExam($course_id);
    			//print_r($exam);
    			
    		$question_array = array();
			$answer_array = array();
			foreach($exam as $q) {
				if(!array_key_exists($q->question_id, $question_array)) 
					$question_array[$q->question_id]=array('question_id'=>$q->question_id,'question'=>$q->question,'is_completed'=>'N');
				
				
				if(array_key_exists($q->question_id,$answer_array)) {
					$temp = $answer_array[$q->question_id];
					$temp[$q->answer_id] = array('answer_id'=>$q->answer_id,'answer'=>$q->answer,'is_correct'=>$q->is_correct);									
					$answer_array[$q->question_id]=$temp;				
				}
				else
					$answer_array[$q->question_id]=array($q->answer_id=>array('answer_id'=>$q->answer_id,'answer'=>$q->answer,'is_correct'=>$q->is_correct));
			}
						    	
    		}
    		
    		$this->view->exam_time=$examCourse->exam_time;
    		$this->view->question_array = $question_array;		
			$this->view->answer_array = $answer_array;
			$this->view->course_id=$course_id;
			$this->view->exam_id=$exam_id;
			$this->view->exam_user_id=$examUser->exam_user_id;
			
			$question_answer_session = new Zend_Session_Namespace('question_answer_session');
			$question_answer_session->questions = $question_array;
			$question_answer_session->answers = $answer_array;
			$question_answer_session->exam_user_id = $examUser->exam_user_id;
			$question_answer_session->course_id = $course_id;
			
    	}
    	
    	}catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Exam Controller startExamAction'. $e->getMessage().'---------'. $e->getTraceAsString());
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
	
}



