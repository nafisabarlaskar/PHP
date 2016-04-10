<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Topic extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'topic';

	//******************************************************//
	// Called AdminController - addTopicAction method //
	// Called AdminController - addChapterAction method //
	public function addTopic($course_id,$topic_name,$notes,$parent_topic_id=0)
	{
		//before inserting we need to find topic_order
		//first check if its chapter or topic		
		if($parent_topic_id==0) {			
			$topic_order = Model_Topic::getChapterOrder($course_id)->toArray();
			$topic_order = $topic_order['topicOrder'];
			if($topic_order==null)
				$topic_order = 1;
			else
				$topic_order = $topic_order + 1;
		}
		else {
			$topic_order = Model_Topic::getTopicOrder($course_id,$parent_topic_id)->toArray();
			$topic_order = $topic_order['topicOrder'];
			//if topic order is null means this is a first topic in this chapter - so we should get the topic
			// order by topic id itself
			if($topic_order==null) {
				$topic_order = 0;
			}
			//$topic_order = $topic_order + 0.1;
			$topic_order = $topic_order + 1;
		}
		
		// create a new row
		$rowTopic = $this->createRow();
		if($rowTopic) {
			// update the row values
			$rowTopic->course_id = $course_id;
			$rowTopic->topic_name = $topic_name;		
			$rowTopic->notes = $notes;			
			$rowTopic->parent_topic_id = $parent_topic_id;
			$rowTopic->topic_order = $topic_order;
			$rowTopic->save();	
			return $rowTopic;
		} else {
			throw new Zend_Exception("Could not add new Topic!");
		}
	}
	
	
	//******************************************************//
	// Called AdminController - editTopicAction method //
	public function editTopic($topic_id,$topic_name,$notes,$topic_order)
	{		
		$rowTopic = $this->find($topic_id)->current();
		if($rowTopic) {
			// update the row values
			$rowTopic->topic_name = $topic_name;		
			$rowTopic->notes = $notes;			
			$rowTopic->topic_order = $topic_order;			
			$rowTopic->save();	
			return $rowTopic;
		} else {
			throw new Zend_Exception("Could not update topic!");
		}
	}
	
	//******************************************************//
	// Called AdminController - editChapterAction method //
	public function editChapter($topic_id, $topic_name, $topic_order)
	{
		// fetch the user's row
		$rowTopic = $this->find($topic_id)->current();
		if($rowTopic) {
			// update the row values
			$rowTopic->topic_name = $topic_name;
			$rowTopic->topic_order = $topic_order;
			$rowTopic->save();
			//return the updated topic
			return $rowTopic;
		}else{
			throw new Zend_Exception("Chapter update failed. Chapter not found!");
		}
	}
	
	//******************************************************//
	// Called AdminController - viewCourseAction method //
	// Called CourseController - viewAction method //
	public static function getChapters($course_id,$batch_id=null)
	{		
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);				
		$select->from('topic','topic.*');
		$select->joinLeft('topic_video', 'topic.topic_id = topic_video.topic_id', array('is_sample'));
		$select->joinLeft('video', 'topic_video.video_id = video.video_id');
		$select->where('course_id = '.$course_id);
		if($batch_id!=null)
			$select->where('batch_id = '.$batch_id);
		$select->where('is_sample is null or is_sample="N"');
		$select->where('is_active="Y"');
		$select->order(array('parent_topic_id','topic_order'));		
		return $topicModel->fetchAll($select);				
	}
	

	public static function checkVideo($batch_id,$course_id)
	{
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);
		$select->from('topic','topic.*');
		$select->joinLeft('topic_video', 'topic.topic_id = topic_video.topic_id', array());
		$select->joinLeft('video', 'topic_video.video_id = video.video_id');
		$select->where('course_id = '.$course_id);
		$select->where('batch_id = '.$batch_id);
		return $topicModel->fetchAll($select);
	}
	
	public static function displayTopics($batch_id,$course_id)
	{
		$topic = new self();
		$select = $topic->select();
		$select->from('topic','topic.*');
		$select->where('course_id = '.$course_id);
		$select->where('batch_id = '.$batch_id);
		return $topic->fetchAll($select);
	}
	
	// Called CourseController - viewAction method //
	public static function getBatchTopics($course_id,$batch_id,$user_id)
	{
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);
		$select->from('topic',array('topic_id','topic_name','has_quiz'));
		$select->joinLeft('topic_video', 'topic.topic_id = topic_video.topic_id',array('topic_video_id'));
		$select->joinLeft('video', 'video.video_id = topic_video.video_id',array('video_id','video_url_hd'));
		$select->joinLeft('batch', 'batch.batch_id = topic.batch_id',array('batch_id','batch_name','class_days','class_time','date_format(start_date,\'%D \' \'%M \') as start_date'));
		$select->joinLeft('topic_supplement', 'topic.topic_id = topic_supplement.topic_id',array('order'));
		$select->joinLeft('supplement', 'topic_supplement.supplement_id = supplement.supplement_id and is_visible="Y"',array('supplement_id','supplement_name','filename','type'));
		
		$select->joinLeft(array('aqt' =>'adl_quiz_topic'), 'aqt.topic_id = topic.topic_id and aqt.quiz_number=1','aqt.quiz_id');
		$select->joinLeft(array('aqu' => 'adl_quiz_user'), 'aqu.quiz_id = aqt.quiz_id and aqu.user_id='.$user_id, array('aqu.user_id','aqu.is_complete','aqu.score'));
		
		$select->where('topic.course_id = '.$course_id);
		$select->where('topic.batch_id = '.$batch_id);
		$select->where('parent_topic_id != 0');
		$select->where('is_sample is null or is_sample="N"');
		$select->where('topic.is_active="Y"');
		$select->order(array('parent_topic_id','topic_order','topic_supplement.order'));
		return $topicModel->fetchAll($select);
		//return $select->__toString();
	}
	
	//******************************************************//	
	// Called ExamController - getTotalTopics method //
	public static function getTotalTopics($course_id)
	{		
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);				
		$select->from('topic',array('total_topics'=>'count(topic.topic_id)'));
		$select->joinLeft('topic_video', 'topic.topic_id = topic_video.topic_id');
		$select->where('course_id = '.$course_id);
		$select->where('parent_topic_id!=0');
		$select->where('is_active="Y"');
		$select->where('is_sample is null or is_sample="N"');
		return $topicModel->fetchRow($select);				
	}
	
	
	// Called CourseController - mviewAction method //
	public static function getMyChapters($course_id,$user_id,$quiz_number=null)
	{		
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);				
		$select->from('topic','topic.*');
		$select->joinLeft('topic_video', 'topic.topic_id = topic_video.topic_id', array('is_sample'));
		if($quiz_number!=null)
			$select->joinLeft(array('aqt' =>'adl_quiz_topic'), 'aqt.topic_id = topic.topic_id and aqt.quiz_number='.$quiz_number,'aqt.quiz_id');
		else 
			$select->joinLeft(array('aqt' =>'adl_quiz_topic'), 'aqt.topic_id = topic.topic_id and aqt.quiz_number=1','aqt.quiz_id');
		
		$select->joinLeft(array('aqu' => 'adl_quiz_user'), 'aqu.quiz_id = aqt.quiz_id and aqu.user_id='.$user_id, array('aqu.user_id','aqu.is_complete','aqu.score'));
		$select->where('course_id = '.$course_id);
		$select->where('is_sample is null or is_sample="N"');
		$select->where('is_active="Y"');
		$select->order(array('parent_topic_id','topic_order'));		
		return $topicModel->fetchAll($select);				
	}
	
	// Called CourseController - batchviewAction method //
	public static function getMyBatchChapters($batch_id,$course_id,$user_id,$quiz_number=null)
	{
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);
		$select->from('topic','topic.*');
		$select->joinLeft('batch_video', 'topic.topic_id = batch_video.topic_id and batch_video.batch_id='.$batch_id, array('is_sample'));
		if($quiz_number!=null)
			$select->joinLeft(array('aqt' =>'adl_quiz_topic'), 'aqt.topic_id = topic.topic_id and aqt.quiz_number='.$quiz_number,'aqt.quiz_id');
		else
			$select->joinLeft(array('aqt' =>'adl_quiz_topic'), 'aqt.topic_id = topic.topic_id and aqt.quiz_number=1','aqt.quiz_id');
	
		$select->joinLeft(array('aqu' => 'adl_quiz_user'), 'aqu.quiz_id = aqt.quiz_id and aqu.user_id='.$user_id, array('aqu.user_id','aqu.is_complete','aqu.score'));
		$select->where('course_id = '.$course_id);
		$select->where('is_sample is null or is_sample="N"');
		$select->where('is_active="Y"');
		$select->order(array('parent_topic_id','topic_order'));
		//return $select->__toString();
		return $topicModel->fetchAll($select);
	}
	
	//******************************************************//	
	// Called IndexController - indexAction method //
	public static function getSampleTopic($course_id,$topic_id=null)
	{		
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);				
		$select->from('topic','topic.*');
		$select->joinLeft('topic_video', 'topic.topic_id = topic_video.topic_id', array('is_sample'));
		$select->joinLeft('video', 'topic_video.video_id = video.video_id',array('video_url_hd'));
		$select->where('course_id = '.$course_id);
		$select->where('is_sample = "Y"');
		if($topic_id!=null)
			//$select->where('topic.topic_id not in (?)',$topic_id);
			$select->where('topic.topic_id not in ('.$topic_id.')');
		//$select->order("rand()");				
		//$select->order("topic.topic_id");
		$select->limit(1);
		//return $select->__toString();
		return $topicModel->fetchRow($select);				
	}
	
	
	
	
	
	//******************************************************//
	// Called AdminController - deleteTopicAction method //
	public function deleteTopic($topic_id)
	{
		// fetch the topic row
		$rowTopic = $this->find($topic_id)->current();
		if($rowTopic) {
			$rowTopic->delete();
		}else{
			throw new Zend_Exception("Could not delete topic. Topic not found!");
		}
	}
	
	//******************************************************//
	// Called AdminController - deleteChapterAction method //
	public function deleteChapter($topic_id)
	{
		//first delete all subtopics		
		$select = $this->getAdapter()->quoteInto('parent_topic_id = ?', (int)$topic_id);      
		$this->delete($select);
		// next delete the chapter itself
		$rowTopic = $this->find($topic_id)->current();
		if($rowTopic) {
			$rowTopic->delete();
		}else{
			throw new Zend_Exception("Could not delete topic. Topic not found!");
		}
	}
	
	//******************************************************//
	// Called AdminController - editTopicAction method //
	public static function loadTopic($topic_id)
	{	
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);				
		$select->from('topic','topic.*');
		//REMOVE THIS COURSE JOIN WHEN WE HAVE DIFFERENT IMAGES FOR EACH START QUIZ VIDEO
		$select->join('course', 'topic.course_id = course.course_id', array('title','is_hd'));
		$select->joinLeft('topic_video', 'topic.topic_id = topic_video.topic_id', array('topic_video_id','is_sample'));
		$select->joinLeft('video', 'topic_video.video_id = video.video_id');
		$select->where('topic.topic_id = '.$topic_id);				
		return $topicModel->fetchRow($select);
		//$stmt = $select->query();
        //return $stmt->fetchAll();;
	}
	
	//******************************************************//
	// Called in Model Topic - addTopic method //
	public static function getChapterOrder($course_id)
	{
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);
		$select->from('topic', array('topicOrder'=>'max(topic_order)'));		
		$select->where('course_id = '.$course_id);		
		$select->where('parent_topic_id = 0');
		return $topicModel->fetchRow($select);
		
	}
	
	//******************************************************//
	// Called in Model Topic - addTopic method //
	public static function getTopicOrder($course_id,$parent_topic_id=0)
	{
		$topicModel = new self();
		$select = $topicModel->select();
		$select->setIntegrityCheck(false);
		$select->from('topic', array('topicOrder'=>'max(topic_order)'));		
		$select->where('parent_topic_id = '.$parent_topic_id);
		$select->where('course_id = '.$course_id);		
		return $topicModel->fetchRow($select);		
	}		
}