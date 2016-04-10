<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_CourseQuestion extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'course_question';

	//******************************************************//
	// Called CourseController - askQuestionAction method //
	public function addQuestion($course_id,$user_id,$faculty_id,$question,$question_title)
	{
		// create a new row
		$rowCourseQuestion = $this->createRow();
		if($rowCourseQuestion) {
			// update the row values
			$rowCourseQuestion->course_id = $course_id;
			$rowCourseQuestion->user_id = $user_id;
			$rowCourseQuestion->faculty_id = $faculty_id;
			$rowCourseQuestion->question = $question;
			$rowCourseQuestion->question_title = $question_title;
			$rowCourseQuestion->save();	
			return $rowCourseQuestion;
		} else {
			throw new Zend_Exception("Could not add new course question!");
		}
	}
	
	//******************************************************//	
	// Called CourseController - courseFeedAction method //	
	public static function getQuestions($course_id,$number_of_records,$start_pos=0,$student_ids=null)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('cq'=>'course_question'),array('cq.course_question_id','cq.user_id','cq.question','cq.question_title','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));				
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('questioner_name'=>'first_name','questioner_email'=>'email','college'=>'college','designation'=>'designation','company'=>'company'));		
		$select->where('cq.course_id = '.$course_id);
		$select->order(array('cq.date_created DESC '));
		if($start_pos == 0)
			$select->limit($number_of_records);
		else
			$select->limit($number_of_records,$start_pos);		
		return $courseQuestionModel->fetchAll($select);				
	}
	
	//******************************************************//
	// Called ForumController - classroomAction method //
	public static function getQuestionsByBatch($course_id,$student_ids,$number_of_records,$start_pos=0)
	{
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);
		$select->from(array('cq'=>'course_question'),array('cq.course_question_id','cq.user_id','cq.question','cq.question_title','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('questioner_name'=>'first_name','questioner_email'=>'email','college'=>'college','designation'=>'designation','company'=>'company'));
		$select->where('cq.course_id = '.$course_id);
		$select->where('cq.user_id in ('.$student_ids.')');
		$select->order(array('cq.date_created DESC '));
		if($start_pos == 0)
			$select->limit($number_of_records);
		else
			$select->limit($number_of_records,$start_pos);
		return $courseQuestionModel->fetchAll($select);
	}
	
	
	//******************************************************//	
	// Called CourseController - courseFeedAction method //	
	public static function getRecentQuestions($course_id,$course_question_id,$number_of_records)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('cq'=>'course_question'),array('cq.course_question_id','cq.user_id','cq.question','cq.question_title','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));				
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('questioner_name'=>'first_name','questioner_email'=>'email'));		
		$select->where('cq.course_id = '.$course_id);
		$select->where('cq.course_question_id != '.$course_question_id);
		$select->order(array('cq.date_created DESC '));
		$select->limit($number_of_records);				
		return $courseQuestionModel->fetchAll($select);				
	}
	
	
	//******************************************************//	
	// Called CourseController - myViewAction method //	
	public static function getOthersQuestions($course_id,$user_id,$number_of_records,$start_pos=0)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('cq'=>'course_question'),array('cq.course_question_id','cq.user_id','cq.question','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));				
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('questioner_name'=>'first_name'));		
		$select->where('cq.course_id = '.$course_id);
		$select->where('cq.user_id != '.$user_id);
		$select->order(array('cq.date_created DESC'));
		if($start_pos == 0)
			$select->limit($number_of_records);
		else
			$select->limit($number_of_records,$start_pos);		
		return $courseQuestionModel->fetchAll($select);				
	}
	
	
	//******************************************************//	
	// Called CourseController - myFeedAction method //
	public static function getMyQuestions($course_id,$user_id,$number_of_records,$start_pos=0)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('cq'=>'course_question'),array('cq.course_question_id','cq.user_id','cq.question','cq.question_title','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('questioner_name'=>'first_name','questioner_email'=>'email','college'=>'college','designation'=>'designation','company'=>'company'));
		$select->where('course_id = '.$course_id);
		$select->where('cq.user_id = '.$user_id);
		$select->order(array('cq.date_created DESC'));		
		if($start_pos == 0)
			$select->limit($number_of_records);
		else
			$select->limit($number_of_records,$start_pos);
		return $courseQuestionModel->fetchAll($select);				
	}
	
	//******************************************************//	
	// Called UserController - viewAction method //
	public static function getUserQuestions($user_id)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('cq'=>'course_question'),array('cq.course_question_id','cq.user_id','cq.question','cq.question_title','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('questioner_name'=>'first_name','questioner_email'=>'email'));
		$select->joinLeft(array('vq'=>'vote_question'), 'cq.course_question_id = vq.course_question_id',array('votes'=>new Zend_Db_Expr("IF(SUM(vote)>0,SUM(vote),0)")));
		$select->where('cq.user_id = '.$user_id);
		$select->group('cq.course_question_id');
		$select->order(array('cq.date_created DESC'));		
		return $courseQuestionModel->fetchAll($select);				
		//return $select->__toString();
	}
	
	//******************************************************//	
	// Called CourseController - mViewAction method //
	public static function getUserQuestionsByCourse($user_id,$course_id)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('cq'=>'course_question'),array('question_count'=>new Zend_Db_Expr("count(*)")));
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',null);
		$select->where('cq.user_id = '.$user_id);			
		$select->where('cq.course_id = '.$course_id);
		return $courseQuestionModel->fetchRow($select);				
		//return $select->__toString();
	}
	
//******************************************************//	
	// Called CourseController - myFeedAction method //
	public static function getFacultyQuestions($course_id,$faculty_id,$number_of_records,$start_pos=0)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('cq'=>'course_question'),array('cq.course_question_id','cq.user_id','cq.question','cq.question_title','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('questioner_name'=>'first_name','questioner_email'=>'email','college'=>'college','designation'=>'designation','company'=>'company'));
		$select->where('course_id = '.$course_id);
		$select->where('cq.faculty_id = '.$faculty_id);
		$select->order(array('cq.date_created DESC'));		
		if($start_pos == 0)
			$select->limit($number_of_records);
		else
			$select->limit($number_of_records,$start_pos);
		return $courseQuestionModel->fetchAll($select);				
	}
	
	//******************************************************//	
	// Called CourseController - questionFeedAction method //
	public static function getQuestion($course_question_id)
	{		
		$courseQuestionModel = new self();
		$select = $courseQuestionModel->select();
		$select->setIntegrityCheck(false);				
		$select->from(array('cq'=>'course_question'),array('cq.course_question_id','cq.course_id','cq.user_id','cq.question','cq.question_title','date_format(cq.date_created,\'%b \' \'%d \' \'%Y \' \'%h:\' \'%i \' \'%p\') as question_date'));
		//$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('email'=>'email','questioner_name'=>new Zend_Db_Expr("CONCAT(first_name, ' ', last_name)"),'college'=>'college','designation'=>'designation','company'=>'company','organization_id'=>'organization_id'));
		$select->joinLeft(array('u1'=>'user'), 'cq.user_id = u1.user_id',array('email'=>'email','questioner_name'=>'first_name','college'=>'college','designation'=>'designation','company'=>'company','organization_id'=>'organization_id'));
		$select->where('cq.course_question_id = '.$course_question_id);		
		$select->order(array('cq.date_created DESC'));
		//return $courseQuestionModel->fetchAll($select);				
		return $courseQuestionModel->fetchRow($select);
	}
	
	public function editQuestion($id,$question_title)
	{
		$rowQuestion = $this->find($id)->current();
		if($rowQuestion) {
			$rowQuestion->question_title = $question_title;
		$rowQuestion->save();
		}else{
		throw new Zend_Exception("editQuestion update failed. User not found!");
		}
	}
	
	public function deleteQuestion($course_question_id)
	{
		// fetch the row
		$rowCourseQuestion = $this->find($course_question_id)->current();
		if($rowCourseQuestion) {
			$rowCourseQuestion->delete();
		}else{
			throw new Zend_Exception("Could not delete question. Question not found!");
		}
	}
	
}