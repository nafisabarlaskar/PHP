<?php		
require_once 'Zend/Db/Table/Abstract.php';
class Model_ExamUser extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'exam_user';
	
	
	
	public static function loadExamUser($exam_id,$user_id)
	{	
		$examUserModel = new self();
		$select = $examUserModel->select();		
		$select->from('exam_user','exam_user.*');
		$select->where('exam_id = '.$exam_id);
		$select->where('user_id = '.$user_id);
		return $examUserModel->fetchRow($select);        
	}
	
	//******************************************************//
	// Called AdaptiveController - startQuizAction method //
	public function addExamUser($exam_id,$user_id)
	{
		// create a new row
		$rowExamUser = $this->createRow();
		if($rowExamUser) {
			// update the row values
			$rowExamUser->exam_id = $exam_id;
			$rowExamUser->user_id = $user_id;
			$rowExamUser->save();	
			return $rowExamUser;
		} else {
			throw new Zend_Exception("Could not add new exam user quiz_id=".$exam_id.'-user_id='.$user_id);
		}
	}
	
	// Called ExamController - examScore method //
	public function updateExamUser($exam_user_id,$score)
	{
		try {
		$row = $this->find($exam_user_id)->current();
		if($row) {
			// update the row values
			$row->score = $score;
			$row->is_complete = 'Y';
			$row->save();	
			return $row;	
		}else{
			throw new Zend_Exception("ExamUser updatExamUser failed. exam user id=".$exam_user_id);
		}
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	
	// Called ExamController - submitExam method //
	public function updateExamTaken($exam_user_id)
	{
		try {
		$row = $this->find($exam_user_id)->current();
		if($row) {
			// update the row values
			$row->is_complete = 'N';
			$row->save();	
			return $row;	
		}else{
			throw new Zend_Exception("ExamUser updatExamTaken failed. exam user id=".$exam_user_id);
		}
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	
	// Called ExamController - examScore method //
	public function getFinalScore($exam_user_id)
	{
		try {
			$examUserModel = new self();
			$select = $examUserModel->select();		
			$select->from('exam_user','exam_user.*');
			$select->where('exam_user_id = '.$exam_user_id);			
			return $examUserModel->fetchRow($select);				
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	
	// Called CourseController - myView method //
	public function getFinalScoreByExam($exam_id, $user_id)
	{
		try {
			$examUserModel = new self();
			$select = $examUserModel->select();		
			$select->from('exam_user','exam_user.*');
			$select->where('exam_id = '.$exam_id);			
			$select->where('user_id = '.$user_id);
			return $examUserModel->fetchRow($select);				
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	
	
			
}