<?php

class AdminQuizController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    }
    
    //*************************************************************//
    //************COURSE FUNCTIONS ********************************//
    //*************************************************************//
	// CALLED FROM ADMIN DASHBOARD /admin/index when admin clicks on Create new Course
    public function addQuizAction()
    {
    	if ($this->_request->isPost()) {	
			
    		try {    			
    			//first create new course
				$quizModel = new Model_Quiz();
					$rowQuiz = $quizModel->createQuiz(				
					$this->_getParam('quiz_name'),
					$this->_getParam('course_id')				
				);
					
    		} catch(Exception $e) {
    			Zend_Registry::get('logger')->err('Exception occured in addQuizAction course id='.$this->_getParam('course_id').' in QuizController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			throw new Exception($e);
    		}
    		return $this->_redirect('/admin/index');			
		}
		else
		{
			$course_list = Model_Course::getActiveCourses();
			$this->view->course = $course_list;
						
		}
    }
    
    public function listQuizzesAction()
    {	
    	try {	    		
    		$currentQuizzes = Model_Quiz::getQuizzes();    		 
			$this->view->quizzes = $currentQuizzes;				
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in listQuizzesAction in QuizController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    
    // CALLED in /course/admin-view to update course
	public function updateQuizAction()
    {
		$quizModel = new Model_Quiz();
		if ($this->_request->isPost()) {	

			try { 
    			//first update quiz	    			
				$quizModel->updateQuiz(
					$this->_getParam('course_id'),
					$this->_getParam('quiz_name')					
				);
				
    		} catch(Exception $e) {
    			throw new Exception($e);
    		}
			return $this->_redirect('/quiz/list-quizzes/course_id/'.$this->_getParam('course_id'));		
		}
    	else {
			$course_list = Model_Course::getActiveCourses();
			
			$quiz = Model_Quiz::loadQuiz($this->_getParam('quiz_id'));
			
			//get course
			$course_ids = Model_Course::getActiveCourses();
			$course_id_array=null;
			foreach($course_ids->toArray() as $id)
				$course_id_array[] = $id['course_id'];

				
			$this->view->course_id_array=$course_id_array;
			$this->view->course = $course_list;
			$this->view->quiz = $quiz; // you can use both course->title or course['title'] in the view			
		}		
    }
    
    // CALLED in /course/list-courses to delete course
	public function deleteCourseAction()
    {
		$course_id = $this->_request->getParam('course_id');
		$courseModel = new Model_Course();
		$courseModel->deleteCourse($course_id);
		return $this->_redirect('/user/list-courses');
    }
    
	// Admin clicks on view courses on /course/list-courses page
    public function viewQuizAction()
    {		
		$quiz = Model_Quiz::loadQuiz($this->_getParam('quiz_id'));
		$this->view->quiz = $quiz;
		//get coursefor this quiz
		$course = Model_Course::loadCourse($quiz->course_id);
		$this->view->course = $course;
		
		
    }
    
	public function addQuestionAction()
    {
    	if ($this->_request->isPost()) {	
			$dbAdapter = Zend_Db_Table::getDefaultAdapter();
    		$dbAdapter->beginTransaction();
    		try {    			
    			//first create new course
				$quizQuestionModel = new Model_QuizQuestion();
					$rowQuizQuestion = $quizQuestionModel->addQuestion(
					$this->_getParam('quiz_id'),									
					$this->_getParam('question_desc'),
					$this->_getParam('points')				
				);
				
				$quizAnswerModel = new Model_QuizQuestion();
					$rowQuizQuestion = $quizQuestionModel->addQuestion(
					$this->_getParam('quiz_id'),									
					$this->_getParam('question_desc'),
					$this->_getParam('points')				
				);
				
				
				
				
				$dbAdapter->commit();

    		} catch(Exception $e) {
    			$dbAdapter->rollBack();
    			Zend_Registry::get('logger')->err('Exception occured in addQuestionAction course id='.$this->_getParam('course_id').' in QuizController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			throw new Exception($e);
    		}
    		return $this->_redirect('/admin/index');			
		}
		else
		{
			$course_list = Model_Course::getActiveCourses();
			$this->view->course = $course_list;
						
		}
    }
    
    
   
}

