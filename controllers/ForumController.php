<?php

class ForumController extends Zend_Controller_Action
{
	private $_user_id;
	
	public function index()
    {
        return $this->_redirect('/');
    }
    
    
    public function init()
    {
        /* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }
    
    //This is the course FEED action
    public function classroomAction() {
    	//first check if course id is provided
    	if($this->_getParam('course_id')==null  || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    
    	//check if manipal
    	$request = new Zend_Controller_Request_Http();
    	$referrer = $request->getHeader('referer');
    	Zend_Registry::get('logger')->err('Inside course referrer - '.$referrer);
    	$manipal_url = Zend_Registry::getInstance()->configuration->manipal->url;
    	//if (strpos($referrer,'localhost') !== false || strpos($referrer,$manipal_url) !== false || strpos($referrer,'manipal') !== false) {
    	if (strpos($referrer,$manipal_url) !== false  || strpos($referrer,'manipal') !== false) {
    		//Zend_Registry::get('logger')->err('Inside adaptive ifffffffff ');
    		$redirector = new Zend_Controller_Action_Helper_Redirector();
    		$redirector->gotoUrlAndExit($manipal_url);
    	}
    	
    	//CHECK IF THE USER IS STILL ENROLLED FOR THIS COURSE OTHERWISE REDIRECT HIM TO VIEW PAGE
    	$currentCourses = Model_Enrollment::isStudentEnrolledBatch($this->_getParam('course_id'),$this->_user_id,$this->_getParam('batch_id'));
    	if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
    		return $this->_redirect('/course/view/course_id/'.$this->_getParam('course_id'));
    	else if($currentCourses->batch_id != $this->_getParam('batch_id'))
    		return $this->_redirect('/forum/classroom/course_id/'.$this->_getParam('course_id').'/batch_id/'.$currentCourses->batch_id);
    	
    	
    	//get User
    	$userModel = new Model_User();
    	$currentUser = $userModel->find($this->_user_id)->current();
    	$this->view->current_user=$currentUser;
    
    	//get course
    	$course = Model_Course::loadCourse($this->_getParam('course_id'));
    	
    	$batch_mates = Model_User::getBatchMates($this->_getParam('course_id'), $this->_getParam('batch_id'),$this->_user_id );
    	$student_ids=array();
    	
    	foreach ($batch_mates as $mate) {
    		$student_ids[] = $mate->user_id;    		
    	}
    	$student_ids[]=$this->_user_id;
    	
    	$this->view->batch_mates=$batch_mates;
    	try {
        		 
    		//check if user is enrolled
    		if($this->_user_id !=null) {
    			$currentCourses = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);
    			if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
    				$this->view->enrolled='no';
    			else
    				$this->view->enrolled='yes';
    		}
    			
    			
    
    		//get faculty for this course
    		$faculty = Model_CourseFaculty::getFacultyByCourseId($this->_getParam('course_id'));
    		if($faculty!=null && count($faculty)>0)
    			$this->view->faculty = $faculty;
    		else
    			//assign faculty as binny
    			$this->view->faculty_id = 10;
    			
    			
    		//get number of questions to display
    		$number_of_records = Zend_Registry::getInstance()->configuration->number->records;
    		$this->view->number_of_records=$number_of_records;
    		//get questions and answers
    		$questions = Model_CourseQuestion::getQuestionsByBatch($this->_getParam('course_id'),implode(",",$student_ids),$number_of_records);
    			
    		//get tags
    		$unique_tags = array();
    		$tag_array = array();
    		foreach($questions as $question) {
    			$tags = Model_TagQuestion::getTagsbyQuestion($question->course_question_id);
    			foreach($tags as $t)
    				$unique_tags[]=$t->tag_name;
    			$tag_array[$question->course_question_id] = $tags;
    		}
    			
    		$unique_tags=array_unique($unique_tags);
    		$keywords=implode(",", $unique_tags);
    			
    		$start_pos = count($questions);
    		$this->view->start_pos=$start_pos;
    		$this->view->course = $course; // you can use both course->title or course['title'] in the view
    
    		$this->view->questions = $questions;
    		$this->view->keywords = $keywords;
    		$this->view->tag_array = $tag_array;
    		$this->view->batch_id=$this->_getParam('batch_id');
    			
    	} catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in courseFeedAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	public function voteQuestionAction()
    {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	try 
    	{
	    	$course_question_id = $this->_getParam('course_question_id');
	    	$upvote = (($this->_getParam('upvote') == 'true' || $this->_getParam('upvote') ==1 )? 1: 0);
	    	$downvote = (($this->_getParam('downvote') == 'true' || $this->_getParam('downvote') ==1 )? 1: 0);
	    	$fav = (($this->_getParam('fav') == 'true' || $this->_getParam('fav') ==1 )? 1: 0);
	    	$voteQuestionModel = new Model_VoteQuestion();
	    	
	    	$vote=0;
	    	if($upvote)
	    		$vote=1;
	    	if($downvote) 
	    		$vote=-1;
	    	
	    	if(!$fav & !$upvote & !$downvote) {
	    		$rowVoteQuestion = $voteQuestionModel->deleteVote($this->_user_id,$course_question_id);
	    	}
	    	else {
	    		//check if user has voted	    		
	    		$count_votes=$voteQuestionModel->checkUserVote($course_question_id,$this->_user_id);
	    		if(count($count_votes)==0) {    		
	    			$rowVoteQuestion = $voteQuestionModel->vote(
					$course_question_id,
					$this->_user_id,
					$vote,
					$fav
		    		);
	    		}
	    		else {
	    			$rowVoteQuestion = $voteQuestionModel->updateVote($count_votes->vote_question_id, $vote,$fav);
	    		}    		
	    	}
	    	
	    	if($vote==1 || $vote==-1) {
	    		//send email to User			
	    		$question = Model_CourseQuestion::getQuestion($course_question_id);
	    			
	    		$userModel = new Model_User();
	    		$user = $userModel->find($question->user_id)->current();
	    		//if manipal dont send email
	    		if($user->organization_id!=11) {	    		
	    			//Zend_Registry::get('logger')->err('Sending email');
		    		$templateMessage = new Zend_View();
			    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
			    	$templateMessage->course_question_id = $course_question_id;
			    	$templateMessage->user_id = $question->user_id;
			    	$templateMessage->to_user_name = $user->first_name.' '.$user->last_name;
			    	$templateMessage->to_user_email = $user->email;		    	    	
			    	$this->_helper->SendEmailAction('Your question was voted on DeZyre',$templateMessage,'vote_question.phtml');
		    	}		    	
	    	}
	    	
			$arr = array ('success'=>'ok');
	    	echo json_encode($arr);
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in voteQuestionAction in ForumController '.$this->_getParam('course_question_id').' '. $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	} 
    }
    
	public function voteAnswerAction()
    {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	try 
    	{
	    	$course_answer_id = $this->_getParam('course_answer_id');
	    	$upvote = (($this->_getParam('upvote') == 'true' || $this->_getParam('upvote') ==1 )? 1: 0);
	    	$downvote = (($this->_getParam('downvote') == 'true' || $this->_getParam('downvote') ==1 )? 1: 0);
	    		    	    	
	    	$voteAnswerModel = new Model_VoteAnswer();
	    	$vote=0;
	    	if($upvote)
	    		$vote=1;
	    	if($downvote) 
	    		$vote=-1;
	    		    	
	    	if(!$upvote & !$downvote) {
				$rowVoteAnswer = $voteAnswerModel->deleteVote($this->_user_id,$course_answer_id);
	    	}
	    	else {	    		//check if user has voted	    		
	    		$count_votes=$voteAnswerModel->checkUserVote($course_answer_id,$this->_user_id);
	    		if(count($count_votes)==0) {    		
	    			$rowVoteAnswer = $voteAnswerModel->vote(
					$course_answer_id,
					$this->_user_id,
					$vote
		    		);
	    		}
	    		else {
	    			$rowVoteAnswer = $voteAnswerModel->updateVote($count_votes->vote_answer_id, $vote);
	    		}    		
	    	}
	    	
    		if($vote==1 || $vote==-1) {
	    		//send email to User			
	    		$answer = Model_CourseAnswer::loadAnswer($course_answer_id);
	    		$userModel = new Model_User();
	    		$user = $userModel->find($answer->user_id)->current();
	    		
	    		//if manipal dont send email
		    	if($user->organization_id!=11) {
		    		//Zend_Registry::get('logger')->err('Sending email');
					$templateMessage = new Zend_View();
			    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
			       	$templateMessage->course_question_id = $answer->course_question_id;
			    	$templateMessage->course_answer_id = $answer->course_answer_id;
			    	$templateMessage->user_id = $answer->user_id;
			    	$templateMessage->to_user_name = $user->first_name.' '.$user->last_name;
			    	$templateMessage->to_user_email = $user->email;
			    	$this->_helper->SendEmailAction('Your answer was voted on DeZyre',$templateMessage,'vote_answer.phtml');
	    		}		    			    	
	    	}
	    	
			$arr = array ('success'=>'ok');
	    	echo json_encode($arr);
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in voteAnswerAction in ForumController '.$this->_getParam('course_answer_id').' '. $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	} 
    }   	
}

