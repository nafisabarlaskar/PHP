<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_ExamResponse extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'exam_response';

	//******************************************************//
	// Called ExamController - examScoreAction method //
	public function addExamResponse($exam_user_id,$question_id,$answer_id)
	{
		// create a new row
		$rowExamResponse = $this->createRow();
		if($rowExamResponse) {
			// update the row values
			$rowExamResponse->exam_user_id = $exam_user_id;
			$rowExamResponse->question_id = $question_id;
			$rowExamResponse->answer_id = $answer_id;
			$rowExamResponse->save();	
			return $rowExamResponse;
		} else {
			throw new Zend_Exception("Could not add new exam response =".$exam_user_id);
		}
	}
	
//******************************************************//
	// Called AdaptiveController - viewScoreAction method //
	public static function viewScore($quiz_id,$user_id,$question_ids)
	{	
		$quizResponseModel = new self();
		$select = $quizResponseModel->select();
		$select->setIntegrityCheck(false);
		
		$select->from(array('aqq' =>'adl_quiz_question'),array('aqq.quiz_id','aqq.question_id'));
		$select->join(array('aqa' => 'adl_question_answer'), 'aqa.question_id = aqq.question_id', array('total_answers'=>'count(aqa.answer_id)'));
		$select->join(array('aqu' => 'adl_quiz_user'), 'aqu.quiz_id = aqq.quiz_id',null);
		$select->joinLeft(array('aqr' => 'adl_quiz_response'), 'aqr.quiz_user_id = aqu.quiz_user_id and aqr.question_id=aqq.question_id and aqr.answer_id=aqa.answer_id', array('user_answer_count'=>'count(aqr.answer_id)','points'=>'count(aqa.answer_id)-count(aqr.answer_id)','total_points'=>'count(aqa.answer_id)-1'));
		$select->where('aqq.quiz_id = '.$quiz_id);
		$select->where('aqu.user_id = '.$user_id);
		$select->where('aqq.question_id in ('.$question_ids.')');
		$select->group('aqq.question_id');
		return $quizResponseModel->fetchAll($select);
	        
	}
	
			
}