<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_TopicVideo extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'topic_video';

	//******************************************************//
	// Called AdminController - addTopicAction method //
	public function addTopicVideo($topic_id,$video_id,$is_sample)
	{
		// create a new row
		$rowTopicVideo = $this->createRow();
		if($rowTopicVideo) {
			// update the row values
			$rowTopicVideo->topic_id = $topic_id;
			$rowTopicVideo->video_id = $video_id;
			$rowTopicVideo->is_sample = $is_sample;
			$rowTopicVideo->save();	
			return $rowTopicVideo;
		} else {
			throw new Zend_Exception("Could not add new Topic Video!");
		}
	}
	
	
	public function addVideoId($topic_id,$video_id)
	{
		$rowTopicVideo = $this->createRow();
		if($rowTopicVideo) {
			// update the row values
			$rowTopicVideo->topic_id = $topic_id;
			$rowTopicVideo->video_id = $video_id;
			$rowTopicVideo->save();
			return $rowTopicVideo;
		} else {
			throw new Zend_Exception("Could not add new Topic Video!");
		}
	}
	
	public static function getVideo($topic_id,$video_id)
	{
		$topicVideoModel = new self();
		$select = $topicVideoModel->select();
		$select->setIntegrityCheck(false);
		$select->from('topic_video','topic_video.*');
		$select->join('video', 'topic_video.video_id = video.video_id');
		$select->where('topic_video.topic_id = '.$topic_id);
		$select->where('topic_video.video_id = '.$video_id);
		return $topicVideoModel->fetchRow($select);
	}
	
	
	
	

	//******************************************************//
	// Called AdminController - editTopicAction method //
	public function editTopicVideo($topic_video_id,$is_sample)
	{		
		$rowTopic = $this->find($topic_video_id)->current();
		if($rowTopic) {
			// update the row values
			$rowTopic->is_sample = $is_sample;
			$rowTopic->save();	
			return $rowTopic;
		} else {
			throw new Zend_Exception("Could not update topic video!");
		}
	}	
	
}