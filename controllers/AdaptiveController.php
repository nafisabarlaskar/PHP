<?php


class AdaptiveController extends Zend_Controller_Action
{
	private $_user_id;

    public function init()
    {
        /* Initialize action controller here 1 */
    	/* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }

    public function indexAction()
    {
        // action body
    }
    
	
    public function startQuizAction()
    {	
    	try {	
    	$isStudentEnrolled='N';
    	$quizUser=null;
    	$is_quiz_completed='N';
    	
    	$topic_id = intval($this->_getParam('topic_id'));
    	
    	if($topic_id==null || !is_int($topic_id))
    		return $this->_redirect('/');
    	
    	//check if quiz exist for that topic    	
    	$quizTopic = Model_QuizTopic::getQuizId($topic_id);
    	$quiz_id=$quizTopic->quiz_id;
    	//if quiz does'nt exist for this course - redirect to course home page
    	if($quiz_id==null)
    		return $this->_redirect('/course-details/'.$topic->course_id);
    	
    		
    	//load the topic
    	$topic = Model_Topic::loadTopic($topic_id);
    	if($topic==null)
    		return $this->_redirect('/');

    	// 1 IS USER IS NULL
    	if($this->_user_id ==null) {
    		//check if manipal
    		$request = new Zend_Controller_Request_Http();
    		$referrer = $request->getHeader('referer');
	    	Zend_Registry::get('logger')->err('Inside adaptive referrer - '.$referrer);
			$manipal_url = Zend_Registry::getInstance()->configuration->manipal->url;
		    //if (strpos($referrer,'localhost') !== false || strpos($referrer,$manipal_url) !== false || strpos($referrer,'manipal') !== false) {
			if (strpos($referrer,$manipal_url) !== false  || strpos($referrer,'manipal') !== false) {
		    	//Zend_Registry::get('logger')->err('Inside adaptive ifffffffff ');
				$redirector = new Zend_Controller_Action_Helper_Redirector();
				$redirector->gotoUrlAndExit($manipal_url);
		    }
    		//CHECK IF ITS SAMPLE VIDEO - ELSE REDIRECT HIM
    		if($topic->is_sample=='N' || $topic->is_sample==null || $topic->is_active=='N') 
    			return $this->_redirect('/course-details/'.$topic->course_id);
    	}
    	
    	// 2 If USER IS REGISTERED BUT NOT ENROLLED
    	if($this->_user_id !=null) {
    		$currentCourses=Model_Enrollment::isStudentEnrolled($topic->course_id,$this->_user_id);
    		//if not registered send him to course page
    		if(count($currentCourses)==0 || $currentCourses->payment_received=='N') {
    			if($topic->is_sample=='N' || $topic->is_sample==null || $topic->is_active=='N')
    				return $this->_redirect('/course-details/'.$topic->course_id);
    		}
    	}
    	
    	// 3 If USER IS REGISTERED AND ENROLLED
    	if($this->_user_id !=null ) {
    		if($topic->is_active=='Y'){
    		$currentCourses=Model_Enrollment::isStudentEnrolled($topic->course_id,$this->_user_id);
    		//if not registered send him to course page
    		if(count($currentCourses)!=0 && $currentCourses->payment_received=='Y') {
    			$isStudentEnrolled='Y';
    			// See if this quiz is the one the user should be taking
    			/*
		    	$start_topic_id=null;
		    	$chapters = Model_Topic::getMyChapters($topic->course_id,$this->_user_id);
		    	foreach($chapters as $chapter) {
		    		if($chapter['parent_topic_id']!=0) {
		    			if($chapter['is_complete']=='Y' && $chapter['topic_id']==$topic_id) {
		    				$start_topic_id = $chapter['topic_id'];
		    				break;													
		    			}
		    			else if(($chapter['is_complete']=='N' || $chapter['is_complete']==null) && $start_topic_id==null) {
		    				$start_topic_id = $chapter['topic_id'];
		    				if($chapter['topic_id']!=$topic_id)
		    					return $this->_redirect('/adaptive/start-quiz/topic_id/'.$chapter['topic_id']);
		    				
		    				break;						
						}
		    		}
		    	}
		    	$topic_id=$start_topic_id;
		    	*/   
		 		$topic = Model_Topic::loadTopic($topic_id);   
		 		    	
		    	$quizUser = Model_QuizUser::loadQuizUser($quiz_id,$this->_user_id);
		    	//if user has not taken then insert a row in quiz user
		    	if($quizUser==null) {
		    		$quizUserModel = new Model_QuizUser();
		    		$quizUserModel->addQuizUser($quiz_id,$this->_user_id);
		    	}
		    	else 
		    		$is_quiz_completed=$quizUser->is_complete;		    	
    		}
    	} else
    		return $this->_redirect('/course-details/'.$topic->course_id);
    	}
    	
    	//load quiz responses
    	$question_answer_array = $this->loadQuizDetails($topic_id, $quiz_id);    	
    	$question_array = $question_answer_array[0];
    	$answer_array = $question_answer_array[1];
    	
    	if($quizUser!=null)
    		$question_array = $this->assignQuestionPoints($quiz_id, $question_array);
    		
    	
		//set the questions in the session
		$question_answer_session = new Zend_Session_Namespace('question_answer_session');
		$question_answer_session->questions = $question_array;
		$question_answer_session->answers = $answer_array;
		$question_answer_session->topic_id = $topic_id;
		$question_answer_session->quiz_id = $quiz_id;
		$question_answer_session->course_id = $topic->course_id;
		$question_answer_session->isStudentEnrolled=$isStudentEnrolled;
		
    	//get the first question to display
		reset($question_array);
		$first_question_id = key($question_array);
		$question = $question_array[$first_question_id];
		$answers = $answer_array[$first_question_id];
		$this->view->question = $question;		
		$this->view->answers = $answers;
		$this->view->is_quiz_completed = $is_quiz_completed;
		$this->view->isStudentEnrolled = $isStudentEnrolled;
		
		//load course
		$course = Model_Course::loadCourse($topic->course_id);
		
		//get video		
		$url = $topic->video_url;
		$interm = preg_replace('|(<*[^>]*width=)"[^>]+"([^>]*>)|Ui', '\1"365"\2', $url);
		$result = preg_replace('|(<*[^>]*height=)"[^>]+"([^>]*>)|Ui', '\1"240"\2', $interm);
		$this->view->topic=$topic;
		$this->view->course = $course;		
		$this->view->video_url = $result;
		
		//get next question id
		next($question_array);
		$next_question_id = key($question_array);
		$this->view->next_question = $next_question_id;
		
		//get faculty for this course to ask questions
		$faculty = Model_CourseFaculty::getFacultyByCourseId($topic->course_id);
		$this->view->faculty = $faculty;
    	
		
		
		//print_r($answer_array);
    	}catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Adaptive Controller startQuizAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }		
    }
    
    
	public function checkAnswerAction()
    {		
    	try {
	    	$this->_helper->viewRenderer->setNoRender(true);
	    	$this->_helper->layout->disableLayout();
	    	$arr = array();
	    	//get question from the session
	    	$question_answer_session = new Zend_Session_Namespace('question_answer_session');
	    	$question_array = $question_answer_session->questions;
	    	$question = $question_array[$this->_getParam('question_id')];
	    	//$next_question_id = $question_array[$this->_getParam('next_question_id')];
	    	$question['is_completed'] = 'Y';
	    	
	    	//get answers from the session	    	
	    	$answer_array = $question_answer_session->answers;
	    	$answers = $answer_array[$this->_getParam('question_id')];
	    		    	
	    	//pass the answer_id to get the array to check if its the correct answer
	    	$selected_answer = $answers[$this->_getParam('answer_id')];
	    	if($selected_answer['user_answer_id']!=$this->_getParam('answer_id')) {
		    	if($selected_answer['is_correct']=='Y')
		    	{
		    		$selected_answer['user_answer_id']=$this->_getParam('answer_id');
		    		//$arr = array ('success'=>'true');
		    		$arr = array ('success'=>'true','answer'=>$selected_answer['answer'],'explain'=>nl2br(htmlspecialchars($selected_answer['answer_explain'])));
		    	}
		    	else 
		    	{
		    		$points = intval($question['points']);
		    		$question['points'] = $points-1;    		
		    	
		    		$selected_answer['user_answer_id']=$this->_getParam('answer_id');
		    		$arr = array ('success'=>'false','answer'=>$selected_answer['answer'],'explain'=>nl2br(htmlspecialchars($selected_answer['answer_explain'])));	    		
		    	}
		    	$answers[$this->_getParam('answer_id')] = $selected_answer;
		    	$answer_array[$this->_getParam('question_id')] = $answers;
		    	$question_answer_session->answers = $answer_array;
		    	
		    	$question_array[$this->_getParam('question_id')] = $question;
		    	$question_answer_session->questions = $question_array;
		    	
		    	//if user is enrolled for the course
		    	//insert user response into adl_quiz_response table
		    	$isStudentEnrolled=$question_answer_session->isStudentEnrolled;
		    	if($this->_user_id !=null && $isStudentEnrolled=='Y') {
		    		//$enrollmentRow = Model_Enrollment::isStudentEnrolled($question_answer_session->course_id,$this->_user_id);						
					//if(count($enrollmentRow)!=0 && $enrollmentRow->payment_received=='Y') {
		    			$quizResponseModel = new Model_QuizResponse();
	    				$quizResponseModel->addQuizResponse($question['quiz_user_id'], $this->_getParam('question_id'), $this->_getParam('answer_id'));
					//}					    	
		    	}	    	
	    	}
	    	echo json_encode($arr);
    	}catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Adaptive Controller checkAnswerAction user_id='.$this->_user_id.' - '. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }    					
    }
    
	public function nextQuestionAction()
    {		
    	try {
	    	$this->_helper->viewRenderer->setNoRender(true);
	    	$this->_helper->layout->disableLayout();
	    	
	    	$next_question_id = intval($this->_getParam('next_question_id'));
	    	
	    	//get question from the session
	    	$question_answer_session = new Zend_Session_Namespace('question_answer_session');
	    	$question_array = $question_answer_session->questions;
	    	$question = $question_array[$next_question_id];
	    	
	    	//get answers from the session
	    	$answer_array = $question_answer_session->answers;
	    	$answers = $answer_array[$next_question_id];
	    	
	    	$answer_list='';
	    	$answer_explain_div='';
	    	
			foreach ($answers as $key => $value) { 
				if($question['is_completed']=='Y')  
					$answer_list .= "<br/><div><input disabled='disabled' type='radio' name='answer' id='answer_id_".$value['answer_id']."' value='".$value['answer_id']."'/> ".$value['answer'];					
				else
					$answer_list .= "<br/><div><input type='radio' name='answer' id='answer_id_".$value['answer_id']."' value='".$value['answer_id']."'/> ".$value['answer']; 
				
				if($value['user_answer_id']==$value['answer_id']) {  
					if($value['is_correct']=='Y') {					
						$answer_list .= "&nbsp; - <span style='color:#CC0000'>Correct</span>";
						$answer_explain_div .=
						"<div class='explain_div' id='explain_div_".$value['answer_id']."'>".
							"<span class='incorrect_answer'>".$value['answer']."</span>&nbsp; - <span class='incorrect' style='font-weight:bold;color:green'>Correct</span><br/>".
							"<span class='answer_explain'>".nl2br(htmlspecialchars($value['answer_explain']))."</span>".
						"</div>";
					} else if($value['is_correct']=='N') {				
						$answer_list .= "&nbsp; - <span style='color:#CC0000'>Incorrect</span>";
						$answer_explain_div .= 						
						"<div class='explain_div' id='explain_div_".$value['answer_id']."'>".
							"<span class='incorrect_answer'>".$value['answer']."</span>&nbsp; - <span class='incorrect'>Incorrect</span><br/>".
							"<span class='answer_explain'>".nl2br(htmlspecialchars($value['answer_explain']))."</span>".
						"</div>";
					}				
				}
				$answer_list .="</div>";				 
			}
				    	
	    	//get next_question_id
	    	if($question_array!=null) {
		    	while(key($question_array) !== $next_question_id){
					next($question_array);
				} 
				next($question_array);	
				$next_question_id = key($question_array);
	    	}
	    	
			$arr = array ('success'=>'true','next_question_id'=>$next_question_id);
	    	echo json_encode(array($arr,$question,$answer_list,$answer_explain_div));
	    		    	
    	}catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Adaptive Controller checkAnswerAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }    					
    }
    
	public function updateScoreAction()
    {
        // action body
        try {
	    	$this->_helper->viewRenderer->setNoRender(true);
	    	$this->_helper->layout->disableLayout();
	    	$question_answer_session = new Zend_Session_Namespace('question_answer_session');
	    	$isStudentEnrolled=$question_answer_session->isStudentEnrolled;
	    	$topic_id= $question_answer_session->topic_id;
	    	$course_id= $question_answer_session->course_id;
	    	$total_score = 0;
	    	//if(1!=2) {
        	if($this->_user_id !=null && $isStudentEnrolled=='Y') {	
	    		//$enrollmentRow = Model_Enrollment::isStudentEnrolled($topic->course_id,$this->_user_id);						
				//if(count($enrollmentRow)!=0 && $enrollmentRow->payment_received=='Y') {
					//STUDENT IS ENROLLED
					//check if user has completed the quiz or not
					//if not - then update is_complete to Y and update score
					$score=0;
					$quiz_id = $question_answer_session->quiz_id;
					
									
					$question_array = $question_answer_session->questions;
					foreach($question_array as $question) {
						$score = $score + intval($question['points']);	    				
		    			$total_score = $total_score + intval($question['answer_choices']);
					}
										
					$quizUser = Model_QuizUser::loadQuizUser($quiz_id,$this->_user_id);
					$final_score = round($score/$total_score *100);
					if($quizUser->is_complete==null || $quizUser->is_complete=='N' || $quizUser->score != $final_score) {
						$quizUserModel = new Model_QuizUser();
						$quizUserRow = $quizUserModel->updateQuizUser($quizUser->quiz_user_id,$final_score);
						return $this->_redirect('/adaptive/view-score/topic_id/'.$topic_id.'/rewatch/N');		    			
					}
				//}
				
    		}
    		//else
    			return $this->_redirect('/adaptive/view-score/topic_id/'.$topic_id);
    		//else 
    			//return $this->_redirect('/course-details/'.$course_id);
    		
        }catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Adaptive Controller scoreAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }
    }
    
	public function viewScoreAction()
    {		
    	try {
    		//$this->_helper->layout()->setLayout('adaptive_layout');
    		$score = 0;
    		$total_score = 0;
    		
    		$topic_id = intval($this->_getParam('topic_id'));
    		
    		if($topic_id==null || !is_int($topic_id))
    			return $this->_redirect('/');
    			
    		$rewatch = $this->_getParam('rewatch');
    		if($rewatch==null)
    			$this->view->rewatch='Y';
    		else 
    			$this->view->rewatch='N';
    		//echo 'rewatch='.$rewatch;
    		
   			//load the topic
	    	$topic = Model_Topic::loadTopic($topic_id);
	    	if($topic==null)
    			return $this->_redirect('/');
    		
    		$course = Model_Course::loadCourse($topic->course_id);
    			
    		//check if quiz exist for that topic    	
	    	$quizTopic = Model_QuizTopic::getQuizId($topic_id);
	    	$quiz_id=$quizTopic->quiz_id;
	    	//if quiz does'nt exist for this course - redirect to course home page
	    	if($quiz_id==null)
	    		return $this->_redirect('/course-details/'.$topic->course_id);
	    		
    	
	    		
    		$is_user_enrolled='N';	
	    	if($this->_user_id !=null) {	    		
	    		
	    		$enrollmentRow = Model_Enrollment::isStudentEnrolled($topic->course_id,$this->_user_id);
		    	if(count($enrollmentRow)==0 || $enrollmentRow->payment_received=='N') { 
		    		if($topic->is_sample=='N' || $topic->is_sample==null)
						return $this->_redirect('/course-details/'.$topic->course_id);
		    	}						
	    		else {	    			
		    		//STUDENT IS ENROLLED - check if user has completed the quiz or not
					//if not - then update is_complete to Y and update score
					$quizUser = Model_QuizUser::loadQuizUser($quiz_id,$this->_user_id);
					if($quizUser->is_complete==null || $quizUser->is_complete=='N') {
						return $this->_redirect('/course/my-view/course_id/'.$topic->course_id);		    			
					}
					else {
						//load quiz responses
						//get questions attempted by user - this is cos if later we add new questions to the quiz - the user might not have taken that
						$questions_attempted_array = Model_QuizResponse::getUserAttemptedQuestions($quiz_id,$this->_user_id);
						$question_id_array= array();
						
						foreach($questions_attempted_array as $t)
							$question_id_array[]=$t['question_id'];
							
						
						
						$question_answer_array = $this->loadQuizDetails($topic_id, $quiz_id,implode(",",$question_id_array));
				    	$question_array = $question_answer_array[0];
						$answer_array = $question_answer_array[1];
						$score = $quizUser->score;
						
						$quizscore = Model_QuizResponse::viewScore($quiz_id,$this->_user_id,implode(",",$question_id_array));
						//print_r($quizscore->toArray());
						foreach($quizscore as $qs) {
							$question_array[$qs->question_id]['points']=$qs->points;
							//$score = $score + intval($qs->points);
							$total_score = $total_score + intval($qs->total_points);
						}
					}	
					$is_user_enrolled='Y';
					
					//get NEXT MODULE
		    		$chapters = Model_Topic::getMyChapters($course->course_id,$this->_user_id);
		    		$chapters = $chapters->toArray();
		    		reset($chapters);
		    		//print_r($chapters);
		    		
		    		$index=0;
		    		
					foreach($chapters as $chapter) {
						if($chapter['parent_topic_id']!=0) {
							if($chapter['topic_id']==$topic_id)
								break;																		
						}
						
					}
					$next_chapter=current($chapters);
					$this->view->next_chapter=$next_chapter;
					
					$this->view->next_quiz_complete='Y';
					
					//get quiz associated with next chapter
					if($next_chapter['topic_id']!=null){
						$nextQuizTopic = Model_QuizTopic::getQuizId($next_chapter['topic_id']);
		    			$next_quiz_id=$nextQuizTopic->quiz_id;
		    			if($next_quiz_id!=null){
		    			$this->view->next_quiz='Y';
		    			$quizUser = Model_QuizUser::loadQuizUser($next_quiz_id,$this->_user_id);
		    			if(count($quizUser)==0)
		    				$this->view->next_quiz_complete='N';
		    			else
		    				$this->view->next_quiz_complete='Y';
		    			}		    			
					}

					
	    		}
													
	    	}
	    	else {
	    		if($topic->is_sample=='N') 
	    			return $this->_redirect('/course-details/'.$topic->course_id);
	    	}
	    	
	    	//if user is not logged in or user is logged in but not enrolled for the course
	    	if($this->_user_id ==null || $is_user_enrolled=='N') {
	    		$question_answer_session = new Zend_Session_Namespace('question_answer_session');
		    	//if user has come directly to this page without starting the quiz - we have no questions stored in the session
		    	//send him to start-quiz page 
		    	if(!Zend_Session::namespaceIsset('question_answer_session'))
		    		return $this->_redirect('/adaptive/start-quiz/topic_id/'.$topic_id);
		    	
		    	//if user has changed the topic id in url- start the quiz for the new topic
		    	$topic_id_session = $question_answer_session->topic_id;
		    	if($topic_id_session != $topic_id)
		    		return $this->_redirect('/adaptive/start-quiz/topic_id/'.$topic_id);
		    	
		    	$question_array = $question_answer_session->questions;
		    			    		    	
		    	//storing topic_id as array so we can show the user the next sample video to take
				//if all samples are over - then no sample video will be shown
				
				$quiz_session = new Zend_Session_Namespace('quiz_session');
				if($quiz_session->topic_id_array == null) 			{ 
					$quiz_session->topic_id_array = array($topic_id);
				}
				else {				
					$temp = $quiz_session->topic_id_array;
					if(!in_array($topic_id,$temp)) {
						$temp[] = $topic_id;
						$quiz_session->topic_id_array = $temp;
					}
				}	    	
		    	$topic_id_array = $quiz_session->topic_id_array;
		    	//echo ('implode='.implode(",",$topic_id_array));
		    	 
		    	
		    		
		    	foreach($question_array as $question) {
		    		if($question['is_completed']=='N') {
		    			$this->view->is_complete ='N';
		    			break;
		    		}
		    		else {
		    			$score = $score + intval($question['points']);	    				
		    			$total_score = $total_score + intval($question['answer_choices']);
		    		}	    		
		    	}				
				// for anonymous users - get next sample video
				$sample_topic = Model_Topic::getSampleTopic($topic->course_id,implode(",",$topic_id_array));
				//$sample_topic = Model_Topic::getSampleTopic($topic->course_id,$topic_id);
				
				//echo $topic;
				$this->view->sample_topic = $sample_topic;			
				
				
				$answer_array = $question_answer_session->answers;    	
		    	//for anonymous users - delete from the session
		    	//Zend_Session:: namespaceUnset('question_answer_session');			
	    	}    		
	    	$is_quiz_completed=$quizUser->is_complete;
	    	
	    	//if manipal user dont send reminder emails
	    	if($this->_user_id!=null) {
	    		$userModel = new Model_User();
	    		$user = $userModel->find($this->_user_id)->current();
	    		$this->view->org_id=$user->organization_id;
	    	}
	    	
	    	$this->view->topic = $topic;
	    	$this->view->course = $course;	    	
	    	$this->view->answers = $answer_array;		    	
		    $this->view->questions = $question_array;	    	
		    $this->view->score = $score;
		    $this->view->total_score = $total_score;
		    $this->view->is_user_enrolled = $is_user_enrolled;
		    $this->view->is_quiz_completed = $is_quiz_completed;
		    	    	
    	}catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Adaptive Controller viewScoreAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }    					
    }
    
    private function loadQuizDetails($topic_id,$quiz_id,$question_ids=null)
    {
	    //load quiz responses
    	$quiz = Model_QuizTopic::loadQuizResponse($topic_id,$quiz_id,$this->_user_id,$question_ids);	
		//echo $quiz;
		
		$question_array = array();
		$answer_array = array();
		foreach($quiz as $q) {
			if(!array_key_exists($q->question_id, $question_array)) 
				$question_array[$q->question_id]=array('question_id'=>$q->question_id,'question'=>$q->question,'is_completed'=>'N','quiz_user_id'=>$q->quiz_user_id,'points'=>0,'answer_choices'=>0);
			
			if($q->user_answer!=null && $q->is_correct=='Y' && $question_array[$q->question_id]['is_completed']=='N') {
				$question_array[$q->question_id]['is_completed']='Y';		
			}
			
			if(array_key_exists($q->question_id,$answer_array)) {
				$temp = $answer_array[$q->question_id];
				$temp[$q->answer_id] = array('answer_id'=>$q->answer_id,'answer'=>$q->answer,'is_correct'=>$q->is_correct,'answer_explain'=>$q->answer_explain,'user_answer_id'=>$q->user_answer);									
				$answer_array[$q->question_id]=$temp;				
			}
			else
				$answer_array[$q->question_id]=array($q->answer_id=>array('answer_id'=>$q->answer_id,'answer'=>$q->answer,'is_correct'=>$q->is_correct,'answer_explain'=>$q->answer_explain,'user_answer_id'=>$q->user_answer));
		}
		//loop thru again to set the question points -
		//question points depend on the number of answer choices
		foreach(array_keys($question_array) as $key) {
			//update points for each question
			$question_array[$key]['points']=count($answer_array[$key])-1; //We subtract 1 cos for last option selected he should not get 1 point
			$question_array[$key]['answer_choices']=count($answer_array[$key])-1;//This is to calculate how much the quiz score is out of
		}	
		
		return array($question_array,$answer_array);
    }
    
    private function assignQuestionPoints($quiz_id,$question_array)
    {
    	$questions_attempted_array = Model_QuizResponse::getUserAttemptedQuestions($quiz_id,$this->_user_id);
		$question_id_array= array();
		
		if(count($questions_attempted_array)>0) {							
			foreach($questions_attempted_array as $t)
				$question_id_array[]=$t['question_id'];
	    	$quizscore = Model_QuizResponse::viewScore($quiz_id,$this->_user_id,implode(",",$question_id_array));
			//print_r($quizscore->toArray());
			foreach($quizscore as $qs) {
				$question_array[$qs->question_id]['points']=$qs->points;
				$question_array[$qs->question_id]['answer_choices']=$qs->total_points;
			}
		}
		return $question_array;
    }
    
}



