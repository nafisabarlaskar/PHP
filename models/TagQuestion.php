<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_TagQuestion extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'tag_question';

	//******************************************************//
	// Called CourseController - askQuestionAction method //
	public function addTagQuestion($tag_id,$course_question_id)
	{
		// create a new row
		$rowTagQuestion = $this->createRow();
		if($rowTagQuestion) {
			// update the row values
			$rowTagQuestion->tag_id = $tag_id;
			$rowTagQuestion->course_question_id = $course_question_id;
			$rowTagQuestion->save();										
			return $rowTagQuestion;
		} else {
			throw new $rowTagQuestion("Could not add new tag question!");
		}
	}
	
	//******************************************************//	
	// Called CourseController - courseFeedAction method //
	public static function getTagsbyQuestion($course_question_id)
	{		
		$tagQuestionModel = new self();
		$select = $tagQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('tq'=>'tag_question'),array('tag_question_id','course_question_id','tag_id'));		
		$select->joinLeft(array('t'=>'tags'), 't.tag_id = tq.tag_id',array('tag_name'=>'tag_name'));
		$select->where('course_question_id = '.$course_question_id);
		return $tagQuestionModel->fetchAll($select);				
	}
	
	// Called CourseController - tagFeedAction method //
	public static function getPopularTags($tag_id)
	{		
		$tagQuestionModel = new self();
		$select = $tagQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('tq'=>'tag_question'));		
		$select->joinLeft(array('t'=>'tags'), 't.tag_id = tq.tag_id',array('tag_name'=>'tag_name','tag_id'=>'t.tag_id','questions'=>'count(t.tag_id)'));
		$select->where('t.tag_id !='.$tag_id);
		$select->order('questions DESC');
		$select->group('tq.tag_id');
		
		return $tagQuestionModel->fetchAll($select);				
	}
	
	// Called CourseController - questionFeedAction method //
	public static function getTagCount($tag_ids)
	{		
		$tagQuestionModel = new self();
		$select = $tagQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('tq'=>'tag_question'));		
		$select->joinLeft(array('t'=>'tags'), 't.tag_id = tq.tag_id',array('tag_name'=>'tag_name','tag_id'=>'t.tag_id','questions'=>'count(t.tag_id)'));
		$select->where('t.tag_id in('.$tag_ids.')');		
		$select->order('questions DESC');
		$select->group('tq.tag_id');
		
		return $tagQuestionModel->fetchAll($select);				
	}
	
	// Called CourseController - courseFeedAction method //
	public static function getQuestionByTag($tag_id,$number_of_records,$start_pos=0)
	{		
		$tagQuestionModel = new self();
		$select = $tagQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('tq'=>'tag_question'),array('tag_question_id','course_question_id','tag_id'));		
		$select->joinLeft(array('cq'=>'course_question'), 'cq.course_question_id = tq.course_question_id',array('question_title','question','user_id','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('questioner_name'=>'first_name','questioner_email'=>'email','college'=>'college','designation'=>'designation','company'=>'company'));
		$select->where('tag_id = '.$tag_id);
		if($start_pos == 0)
			$select->limit($number_of_records);
		else
			$select->limit($number_of_records,$start_pos);	
		return $tagQuestionModel->fetchAll($select);				
	}
	
	public static function getQuestionsByMultipleTags($course_question_id,$tag_id,$number_of_records)
	{		
		$tagQuestionModel = new self();
		$select = $tagQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('tq'=>'tag_question'),array('tag_question_id','course_question_id','tag_id'));		
		$select->joinLeft(array('cq'=>'course_question'), 'cq.course_question_id = tq.course_question_id',array('question_title','question','user_id','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('questioner_name'=>'first_name','questioner_email'=>'email'));
		$select->where('tag_id in ('.$tag_id.')');
		$select->where('cq.course_question_id != '.$course_question_id);
		/*
		if($start_pos == 0)
			$select->limit($number_of_records);
		else
			$select->limit($number_of_records,$start_pos);
		*/	
		return $tagQuestionModel->fetchAll($select);				
	}
	
	public function deleteTagQuestion($course_question_id)
	{
		$select = $this->getAdapter()->quoteInto('course_question_id = ?', (int)$course_question_id);      
		$this->delete($select);	
	}
		
}