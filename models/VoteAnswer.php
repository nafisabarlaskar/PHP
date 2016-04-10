<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_VoteAnswer extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'vote_answer';

	//******************************************************//
	// Called CourseController - askQuestionAction method //
	public function vote($course_answer_id,$user_id,$vote)
	{
		// create a new row
		$rowVoteAnswer = $this->createRow();
		if($rowVoteAnswer) {
			// update the row values
			$rowVoteAnswer->course_answer_id = $course_answer_id;
			$rowVoteAnswer->user_id = $user_id;			
			$rowVoteAnswer->vote = $vote;
			$rowVoteAnswer->save();	
			return $rowVoteAnswer;
		} else {
			throw new $rowVoteAnswer("Could not add new vote answer!");
		}
	}		
	
	//******************************************************//
	// Called ForumController - voteAction method //
	public function updateVote($vote_answer_id,$vote)
	{
		$rowVoteAnswer = $this->find($vote_answer_id)->current();		
		if($rowVoteAnswer) {
			// update the row values
			$rowVoteAnswer->vote = $vote;
			$rowVoteAnswer->save();	
			return $rowVoteAnswer;
		} else {
			throw new $rowVoteAnswer("Could not update vote answer!");
		}
	}

	//******************************************************//	
	// Called CourseController - questionFeedAction method //
	public static function getVotes($course_answer_id)
	{		
		$voteAnswerModel = new self();
		$select = $voteAnswerModel->select();						
		//$select->from(array('v'=>'vote_question'),array('user_id'=>new Zend_Db_Expr("COUNT(IF(user_id = $user_id, 1, NULL))"),'votes'=>new Zend_Db_Expr("SUM(vote)")));		
		$select->from(array('v'=>'vote_answer'),array('votes'=>new Zend_Db_Expr("IF(SUM(vote)>0,SUM(vote),0)")));
		$select->where('v.course_answer_id = '.$course_answer_id);
		return $voteAnswerModel->fetchRow($select);
		//return $select->__toString();				
	}
	
	// Called USerController - viewAction method //
	public static function getReputationScore($user_id)
	{		
		$voteAnswerModel = new self();
		$select = $voteAnswerModel->select();						
		$select->setIntegrityCheck(false);
		$select->from(array('va'=>'vote_answer'),array('reputation'=>new Zend_Db_Expr("SUM(IF(vote = 1, 10, -2))")));
		$select->join(array('ca'=>'course_answer'), 'ca.course_answer_id = va.course_answer_id',null);
		$select->where('ca.user_id = '.$user_id);
		return $voteAnswerModel->fetchRow($select);
		//return $select->__toString();				
	}
	
	
	//******************************************************//	
	// Called VoteController - voteAction method //
	public static function checkUserVote($course_answer_id,$user_id)
	{		
		$voteAnswerModel = new self();
		$select = $voteAnswerModel->select();						
		$select->from(array('v'=>'vote_answer'),array('vote_answer_id','vote'));		
		$select->where('course_answer_id = '.$course_answer_id);
		$select->where('user_id = '.$user_id);		
		return $voteAnswerModel->fetchRow($select);
		//return $select->__toString();				
	}
	
	public function deleteVote($user_id,$course_answer_id)
	{
		try {
			$this->delete( array('user_id = ?' => $user_id,'course_answer_id=?'=>$course_answer_id));
		
		} catch (Exception $e) {
			throw new Zend_Exception("Could not delete vote answer");
		}
	}
}