<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_QuizResponse extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'adl_quiz_response';

	//******************************************************//
	// Called AdaptiveController - checkAnswerAction method //
	public function addQuizResponse($quiz_user_id,$question_id,$answer_id)
	{
		// create a new row
		$rowQuizResponse = $this->createRow();
		if($rowQuizResponse) {
			// update the row values
			$rowQuizResponse->quiz_user_id = $quiz_user_id;
			$rowQuizResponse->question_id = $question_id;
			$rowQuizResponse->answer_id = $answer_id;
			$rowQuizResponse->save();	
			return $rowQuizResponse;
		} else {
			throw new Zend_Exception("Could not add new quiz response =".$quiz_user_id);
		}
	}
	
	//******************************************************//
	// Called AdaptiveController - viewScoreAction method //
	public static function viewScoreold($quiz_id,$user_id)
	{	
		$quizResponseModel = new self();
		$select = $quizResponseModel->select();
		$select->setIntegrityCheck(false);
		
		$select->from(array('aqq' =>'adl_quiz_question'),'aqq.question_id');
		$select->join(array('aqu' => 'adl_quiz_user'), 'aqu.quiz_id = aqq.quiz_id', 'aqu.user_id');
		$select->joinLeft(array('aqr' => 'adl_quiz_response'), 'aqr.quiz_user_id = aqu.quiz_user_id and aqr.question_id=aqq.question_id', '(4-count(aqr.answer_id)) as score');
		$select->where('aqq.quiz_id = '.$quiz_id);
		$select->where('aqu.user_id = '.$user_id);
		$select->group('aqr.question_id');
		//return $quizResponseModel->fetchAll($select);
		return $select->__toString();
		
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
		//return $select->__toString();
		/*
		 * 
		select aqq.quiz_id, aqq.question_id,count(aqa.answer_id),count(aqr.answer_id),(count(aqa.answer_id)-count(aqr.answer_id)) as 'points',count(aqa.answer_id)-1 as 'total_points'
from adl_quiz_question aqq 
join adl_question_answer aqa on `aqq`.question_id = aqa.question_id
INNER JOIN `adl_quiz_user` AS `aqu` ON aqu.quiz_id = aqq.quiz_id 
LEFT JOIN `adl_quiz_response` AS `aqr` ON aqr.quiz_user_id = aqu.quiz_user_id and aqr.question_id=aqq.question_id and aqr.answer_id=aqa.answer_id
where aqq.quiz_id=5
AND (aqu.user_id = 46) 
and aqq.question_id in (16,17)
group by aqq.question_id
		 * 
		 */        
	}
	
	//******************************************************//
	// Called AdaptiveController - viewScoreAction method //
	public static function getUserAttemptedQuestions($quiz_id,$user_id)
	{	
		$quizResponseModel = new self();
		$select = $quizResponseModel->select()->distinct();
		$select->setIntegrityCheck(false);
		
		$select->from(array('aqr' =>'adl_quiz_response'),'question_id');
		$select->join(array('aqu' => 'adl_quiz_user'), 'aqu.quiz_user_id = aqr.quiz_user_id',null);
		$select->where('aqu.quiz_id = '.$quiz_id);
		$select->where('aqu.user_id = '.$user_id);
		return $quizResponseModel->fetchAll($select);		        
	}		
}