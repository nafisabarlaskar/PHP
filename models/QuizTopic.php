<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_QuizTopic extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'adl_quiz_topic';
	
	
	
	//******************************************************//
	// Called AdaptiveController - startQuizAction method //
	// NOT BEING USED NOW
	public static function loadQuiz($topic_id)
	{	
		$quizTopicModel = new self();		
		$select = $quizTopicModel->select();
		$select->setIntegrityCheck(false);
		$select->from(array('aqt' => 'adl_quiz_topic'), 'aqt.quiz_id');
		$select->join(array('aqq' =>'adl_quiz_question'), 'aqt.quiz_id = aqq.quiz_id','aqq.question_id');
		$select->join(array('aq' =>'adl_question'), 'aqq.question_id = aq.question_id','aq.question');
		$select->join(array('aqa' =>'adl_question_answer'), 'aq.question_id = aqa.question_id',array('aqa.answer','aqa.answer_id','aqa.is_correct','answer_explain'));
		$select->where('aqt.topic_id = '.$topic_id);
		return $quizTopicModel->fetchAll($select);		        
	}
	
	//******************************************************//
	// Called AdaptiveController - startQuizAction method //
	public static function getQuizId($topic_id,$quiz_number=null)
	{	
		$quizTopicModel = new self();		
		$select = $quizTopicModel->select();
		$select->setIntegrityCheck(false);
		$select->from(array('aqt' => 'adl_quiz_topic'), 'aqt.quiz_id');
		$select->where('aqt.topic_id = '.$topic_id);
		// by default initially every topic will have only one quiz - in future topics may have more than one quiz - so the quiz will be picked by quiz number
		if($quiz_number!=null)
			$select->where('aqt.quiz_number = '.$quiz_number);
		else 
			$select->where('aqt.quiz_number = 1');
		return $quizTopicModel->fetchRow($select);		        
	}
	
	// Called AdaptiveController - startQuizAction method //
	public static function loadQuizResponse($topic_id,$quiz_id,$user_id=null,$question_ids=null)
	{	
		$quizTopicModel = new self();		
		$select = $quizTopicModel->select();
		$select->setIntegrityCheck(false);
		$select->from(array('aqt' => 'adl_quiz_topic'), 'aqt.quiz_id');
		$select->join(array('aqq' =>'adl_quiz_question'), 'aqt.quiz_id = aqq.quiz_id','aqq.question_id');
		$select->join(array('aq' =>'adl_question'), 'aqq.question_id = aq.question_id','aq.question');
		$select->join(array('aqa' =>'adl_question_answer'), 'aq.question_id = aqa.question_id',array('aqa.answer','aqa.answer_id','aqa.is_correct','answer_explain'));
		if($user_id!=null) 
			$select->joinLeft(array('aqu' => 'adl_quiz_user'), 'aqu.quiz_id = aqt.quiz_id and aqu.user_id='.$user_id, array('aqu.quiz_user_id','aqu.user_id'));
		else 
			$select->joinLeft(array('aqu' => 'adl_quiz_user'), 'aqu.quiz_id = aqt.quiz_id and aqu.user_id is null', array('aqu.quiz_user_id','aqu.user_id'));
		
		$select->joinLeft(array('aqr' => 'adl_quiz_response'), 'aqr.quiz_user_id = aqu.quiz_user_id and aqr.question_id=aqq.question_id and aqr.answer_id=aqa.answer_id', array('user_question'=>'aqr.question_id','user_answer'=>'aqr.answer_id'));
		
		$select->where('aqt.topic_id = '.$topic_id);
		$select->where('aqt.quiz_id = '.$quiz_id);
		if($question_ids!=null)
			$select->where('aq.question_id in ('.$question_ids.')');
		return $quizTopicModel->fetchAll($select);		        
		//return $select->__toString();
	}
	
			
}