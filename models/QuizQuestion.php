<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_QuizQuestion extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'quiz_question';
	
	//******************************************************//
	// Called AdminController - addCourseAction method //	
	public function addQuestion($quiz_id,$question_desc,$points)
	{
		// create a new row
		$rowQuizQuestion = $this->createRow();
		if($rowQuizQuestion) {
			// update the row values
			$rowQuizQuestion->quiz_id = $quiz_id;
			$rowQuizQuestion->question_desc = $question_desc;
			$rowQuizQuestion->points = $points;
			$rowQuizQuestion->save();	
			return $rowQuizQuestion;
		} else {
			throw new Zend_Exception("Could not create Question!");
		}
	}
			
}