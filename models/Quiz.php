<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Quiz extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'quiz';
	
	//******************************************************//
	// Called AdminController - addCourseAction method //	
	public function createQuiz($quiz_name,$course_id)
	{
		// create a new row
		$rowQuiz = $this->createRow();
		if($rowQuiz) {
			// update the row values
			$rowQuiz->quiz_name = $quiz_name;
			$rowQuiz->course_id = $course_id;
			$rowQuiz->save();	
			return $rowQuiz;
		} else {
			throw new Zend_Exception("Could not create Quiz!");
		}
	}
	
	//******************************************************//
	// Called QuizController - updateQuizAction method //
	public function updateCourse($quiz_id,$course_id,$quiz_name)
	{
		$rowQuiz = $this->find($quiz_id)->current();
		if($rowQuiz) {
			// update the row values
			$rowQuiz->quiz_name = $quiz_name;
			$rowQuiz->course_id = $course_id;
			$rowQuiz->save();	
			return $rowQuiz;	
		}else{
			throw new Zend_Exception("Quiz update failed. Quiz not found!");
		}
	}
	
	//******************************************************//
	// Called QuizController - updateQuizAction method //
	public static function loadQuiz($quiz_id)
	{	
		$quizModel = new self();
		$select = $quizModel->select();
		$select->from('quiz', 'quiz.*');
		$select->where('quiz.quiz_id = '.$quiz_id);
		return $quizModel->fetchRow($select);		        
	}
	
	// Called QuizController - listQuizzesAction
	public static function getQuizzes()
	{
		$quizModel = new self();
		$select = $quizModel->select();
		$select->order(array('quiz_name'));
		return $quizModel->fetchAll($select);
	}
	
	
		
}