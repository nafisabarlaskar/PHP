<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_CourseAnswer extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'course_answer';

	//******************************************************//
	// Called CourseController - askQuestionAction method //
	public function addAnswer($course_question_id,$user_id,$answer)
	{
		// create a new row
		$rowCourseAnswer = $this->createRow();
		if($rowCourseAnswer) {
			// update the row values
			$rowCourseAnswer->course_question_id = $course_question_id;
			$rowCourseAnswer->user_id = $user_id;			
			$rowCourseAnswer->answer = $answer;
			$rowCourseAnswer->save();	
			return $rowCourseAnswer;
		} else {
			throw new $rowCourseAnswer("Could not add new course answer!");
		}
	}
	
	//******************************************************//	
	// Called CourseController - courseFeedAction method //
	public static function getAnswers($course_question_id)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('ca'=>'course_answer'),array('course_answer_id','user_id','answer','date_format(ca.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as answer_date'));		
		//$select->joinLeft(array('u2'=>'user'), 'ca.user_id = u2.user_id',array('email'=>'email','replier_name'=>new Zend_Db_Expr("CONCAT(first_name, ' ', last_name)")));
		$select->joinLeft(array('u2'=>'user'), 'ca.user_id = u2.user_id',array('email'=>'email','replier_name'=>'first_name','college'=>'college','designation'=>'designation','company'=>'company'));
		$select->where('course_question_id = '.$course_question_id);
		$select->order(array('ca.date_created'));				
		return $courseQuestionModel->fetchAll($select);
		//return $select->__toString();				
	}
	
	//******************************************************//	
	// Called ForumController - voteAnswerAction method //
	public static function loadAnswer($course_answer_id)
	{		
		$courseAnswerModel = new self();
		$select = $courseAnswerModel->select();
		$select->from(array('ca'=>'course_answer'),array('course_question_id','user_id','course_answer_id'));		
		$select->where('course_answer_id = '.$course_answer_id);
		return $courseAnswerModel->fetchRow($select);
		//return $select->__toString();				
	}
	
	//******************************************************//	
	// Called UserController - viewAction method //
	public static function getUserAnswers($user_id)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);
		$select->from(array('ca'=>'course_answer'));				
		$select->join(array('cq'=>'course_question'),'ca.course_question_id = cq.course_question_id',array('cq.course_question_id','cq.question_title'));
		$select->joinLeft(array('va'=>'vote_answer'), 'ca.course_answer_id = va.course_answer_id',array('votes'=>new Zend_Db_Expr("IF(SUM(vote)>0,SUM(vote),0)")));
		$select->where('ca.user_id = '.$user_id);
		$select->group('cq.course_question_id');
		$select->order(array('cq.date_created DESC'));
		//$select->group('cq.question_title');
		//return $select->__toString();		
		return $courseQuestionModel->fetchAll($select);					
	}
	
	//******************************************************//	
	// Called CourseController - myViewAction method //
	public static function getUserAnswersByCourse($user_id,$course_id)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);
		$select->from(array('ca'=>'course_answer'),array('answer_count'=>new Zend_Db_Expr("count(*)")));				
		$select->join(array('cq'=>'course_question'),'ca.course_question_id = cq.course_question_id',null);		
		$select->where('ca.user_id = '.$user_id);
		$select->where('cq.course_id = '.$course_id);
		//return $select->__toString();		
		return $courseQuestionModel->fetchRow($select);					
	}
	
	public function deleteAnswer($course_answer_id)
	{
		// fetch the row
		$rowCourseAnswer = $this->find($course_answer_id)->current();
		if($rowCourseAnswer) {
			$rowCourseAnswer->delete();
		}else{
			throw new Zend_Exception("Could not delete answer. Answer not found!");
		}
	}
		
}