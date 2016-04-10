<?php		
require_once 'Zend/Db/Table/Abstract.php';
class Model_QuizUser extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'adl_quiz_user';
	
	
	
	public static function loadQuizUser($quiz_id,$user_id)
	{	
		$quizUserModel = new self();
		$select = $quizUserModel->select();		
		$select->from('adl_quiz_user','adl_quiz_user.*');
		$select->where('quiz_id = '.$quiz_id);
		$select->where('user_id = '.$user_id);
		return $quizUserModel->fetchRow($select);        
	}
	
	
	public static function getAverageQuizScore($course_id,$user_id)
	{		
		$quizUserModel = new self();
		$select = $quizUserModel->select();
		$select->setIntegrityCheck(false);		
		$select->from(array('aqu' =>'adl_quiz_user'),array('avg_quiz_score'=>'avg(score)'));
		$select->joinLeft(array('aqt' =>'adl_quiz_topic'), 'aqt.quiz_id = aqu.quiz_id');
		$select->joinLeft(array('topic' =>'topic'), 'topic.topic_id = aqt.topic_id');
		$select->joinLeft(array('user' =>'user'), 'user.user_id = aqu.user_id');		
		$select->where('course_id = '.$course_id);
		$select->where('user.user_id = '.$user_id);
		return $quizUserModel->fetchRow($select);        
	}
	
	public static function getQuizzesCompleted($course_id,$user_id)
	{		
		$quizUserModel = new self();
		$select = $quizUserModel->select();
		$select->setIntegrityCheck(false);		
		$select->from(array('aqu' =>'adl_quiz_user'),array('quizzes_completed'=>'count(score)'));
		$select->joinLeft(array('aqt' =>'adl_quiz_topic'), 'aqt.quiz_id = aqu.quiz_id');
		$select->joinLeft(array('topic' =>'topic'), 'topic.topic_id = aqt.topic_id');
		$select->joinLeft(array('user' =>'user'), 'user.user_id = aqu.user_id');		
		$select->where('course_id = '.$course_id);
		$select->where('user.user_id = '.$user_id);
		return $quizUserModel->fetchRow($select);        
	}
	
	//******************************************************//
	// Called AdaptiveController - startQuizAction method //
	public function addQuizUser($quiz_id,$user_id)
	{
		// create a new row
		$rowQuizUser = $this->createRow();
		if($rowQuizUser) {
			// update the row values
			$rowQuizUser->quiz_id = $quiz_id;
			$rowQuizUser->user_id = $user_id;
			$rowQuizUser->save();	
			return $rowQuizUser;
		} else {
			throw new Zend_Exception("Could not add new quiz user quiz_id=".$quiz_id.'-user_id='.$user_id);
		}
	}
	
	// Called AdptiveController - nextQuestionAction method //
	public function updateQuizUser($quiz_user_id,$score)
	{
		try {
		$row = $this->find($quiz_user_id)->current();
		if($row) {
			// update the row values
			$row->score = $score;
			$row->is_complete = 'Y';
			$row->save();	
			return $row;	
		}else{
			throw new Zend_Exception("QuizUser updatQuizUser failed. quiz user id=".$quiz_user_id);
		}
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	
	
			
}