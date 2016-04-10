<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_QuizAnswer extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'quiz_answer';
	
	//******************************************************//
	// Called AdminController - addCourseAction method //	
	public function addAnswer($question_id,$answer_desc,$is_correct)
	{
		// create a new row
		$rowQuizAnswer = $this->createRow();
		if($rowQuizQuestion) {
			// update the row values
			$rowQuizAnswer->question_id = $question_id;
			$rowQuizAnswer->answer_desc = $answer_desc;
			$rowQuizAnswer->is_correct = $is_correct;
			$rowQuizAnswer->save();	
			return $rowQuizAnswer;
		} else {
			throw new Zend_Exception("Could not create Answer!");
		}
	}
			
}