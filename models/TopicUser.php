<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_TopicUser extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'topic_user';

	//******************************************************//
	// Called AdminController - addTopicAction method //
	public function addTopicUser($topic_id,$user_id=null,$anonymous_id=null)
	{
		// create a new row
		$rowTopicUser = $this->createRow();
		if($rowTopicUser) {
			// update the row values
			$rowTopicUser->topic_id = $topic_id;
			$rowTopicUser->user_id = $user_id;
			$rowTopicUser->anonymous_id = $anonymous_id;
			$rowTopicUser->save();	
			return $rowTopicUser;
		} else {
			throw new Zend_Exception("Could not add new Topic User!");
		}
	}
	
	//******************************************************//
	// Called CourseController - closeVideoAction method //
	public function updateStopTime($topic_user_id)
	{		
		$rowTopic = $this->find($topic_user_id)->current();
		if($rowTopic) {
			// update the row values
			$rowTopic->date_stopped = new Zend_Db_Expr("NOW()");
			$rowTopic->save();	
			return $rowTopic;
		} else {
			throw new Zend_Exception("Could not update stop time for topic user!");
		}
	}
	
}