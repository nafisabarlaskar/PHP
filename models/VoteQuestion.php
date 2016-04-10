<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_VoteQuestion extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'vote_question';

	//******************************************************//
	// Called CourseController - askQuestionAction method //
	public function vote($course_question_id,$user_id,$vote,$favorite)
	{
		// create a new row
		$rowVoteQuestion = $this->createRow();
		if($rowVoteQuestion) {
			// update the row values
			$rowVoteQuestion->course_question_id = $course_question_id;
			$rowVoteQuestion->user_id = $user_id;			
			$rowVoteQuestion->vote = $vote;
			$rowVoteQuestion->favorite = $favorite;
			$rowVoteQuestion->save();	
			return $rowVoteQuestion;
		} else {
			throw new $rowVoteQuestion("Could not add new vote!");
		}
	}		
	
	//******************************************************//
	// Called ForumController - voteAction method //
	public function updateVote($vote_question_id,$vote,$favorite)
	{
		$rowVoteQuestion = $this->find($vote_question_id)->current();		
		if($rowVoteQuestion) {
			// update the row values
			$rowVoteQuestion->vote = $vote;
			$rowVoteQuestion->favorite = $favorite;
			$rowVoteQuestion->save();	
			return $rowVoteQuestion;
		} else {
			throw new $rowVoteQuestion("Could not update vote!");
		}
	}

	//******************************************************//	
	// Called CourseController - questionFeedAction method //
	public static function getVotes($course_question_id)
	{		
		$voteQuestionModel = new self();
		$select = $voteQuestionModel->select();						
		//$select->from(array('v'=>'vote_question'),array('user_id'=>new Zend_Db_Expr("COUNT(IF(user_id = $user_id, 1, NULL))"),'votes'=>new Zend_Db_Expr("SUM(vote)")));		
		$select->from(array('v'=>'vote_question'),array('votes'=>new Zend_Db_Expr("IF(SUM(vote)>0,SUM(vote),0)")));
		$select->where('v.course_question_id = '.$course_question_id);
		return $voteQuestionModel->fetchRow($select);
		//return $select->__toString();				
	}
	
	//******************************************************//	
	// Called USerController - viewAction method //
	public static function getReputationScore($user_id)
	{		
		$voteQuestionModel = new self();
		$select = $voteQuestionModel->select();						
		$select->setIntegrityCheck(false);
		$select->from(array('vq'=>'vote_question'),array('reputation'=>new Zend_Db_Expr("SUM(IF(vote = 1, 5, -2))")));
		$select->join(array('cq'=>'course_question'), 'cq.course_question_id = vq.course_question_id',null);
		$select->where('cq.user_id = '.$user_id);
		return $voteQuestionModel->fetchRow($select);
		//return $select->__toString();				
	}
	
	//******************************************************//	
	// Called USerController - viewAction method //
	public static function getReputations($user_id)
	{		
		$voteQuestionModel = new self();
		$select1 = $voteQuestionModel->select();						
		$select1->setIntegrityCheck(false);
		$select1->from(array('vq'=>'vote_question'),array('reputation'=>new Zend_Db_Expr("SUM(IF(vote = 1, 5, -2))"),'date_created'=>'last_modified'));
		$select1->join(array('cq'=>'course_question'), 'cq.course_question_id = vq.course_question_id',array('course_question_id'=>'course_question_id','question_title'=>'question_title'));
		$select1->where('cq.user_id = '.$user_id);
		$select1->group('cq.course_question_id');
		
		//$voteAnswerModel = new Model_VoteAnswer();
		//$select2 = $voteAnswerModel->select();
		$select2 = $voteQuestionModel->select();						
		$select2->setIntegrityCheck(false);
		$select2->from(array('va'=>'vote_answer'),array('reputation'=>new Zend_Db_Expr("SUM(IF(vote = 1, 10, -2))"),'date_created'=>'last_modified'));
		$select2->join(array('ca'=>'course_answer'), 'ca.course_answer_id = va.course_answer_id',null);
		$select2->join(array('cq'=>'course_question'), 'cq.course_question_id = ca.course_question_id',array('cq.course_question_id'=>'course_question_id','cq.question_title'=>'question_title'));
		$select2->where('ca.user_id = '.$user_id);
		$select2->group('cq.course_question_id');
		
		$select = $voteQuestionModel->select()
     					->union(array($select1, $select2))
     					->order('date_created');
     
		return $voteQuestionModel->fetchAll($select);
		//return $select->__toString();				
	}
	
	//******************************************************//	
	// Called CourseController - viewAction method //
	public static function getFavorites($user_id)
	{		
		$voteQuestionModel = new self();
		$select = $voteQuestionModel->select();						
		$select->setIntegrityCheck(false);
		$select->from(array('vq'=>'vote_question'),null);
		$select->join(array('cq'=>'course_question'), 'cq.course_question_id = vq.course_question_id',array('course_question_id'=>'course_question_id','question_title'=>'question_title'));
		$select->where('vq.user_id = '.$user_id);
		$select->where('vq.favorite = 1');
		return $voteQuestionModel->fetchAll($select);
		//return $select->__toString();				
	}
	
	//******************************************************//	
	// Called VoteController - voteAction method //
	public static function checkUserVote($course_question_id,$user_id)
	{		
		$voteQuestionModel = new self();
		$select = $voteQuestionModel->select();						
		$select->from(array('v'=>'vote_question'),array('vote_question_id','vote','favorite'));		
		$select->where('course_question_id = '.$course_question_id);
		$select->where('user_id = '.$user_id);		
		return $voteQuestionModel->fetchRow($select);
		//return $select->__toString();				
	}
	
	public function deleteVote($user_id,$course_question_id)
	{
		try {
			$this->delete( array('user_id = ?' => $user_id,'course_question_id=?'=>$course_question_id));
		
		} catch (Exception $e) {
			throw new Zend_Exception("Could not delete vote");
		}
	}
}