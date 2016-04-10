<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Course extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'course';
	
	//******************************************************//
	// Called AdminController - addCourseAction method //	
	public function createCourse($title,$learning_mode,$placement_assistance,$duration,$fees,$job_types,$benefits,$faq,$testimonials)
	{
		// create a new row
		$rowCourse = $this->createRow();
		if($rowCourse) {
			// update the row values
			$rowCourse->title = $title;
			$rowCourse->learning_mode = $learning_mode;
			$rowCourse->placement_assistance = $placement_assistance;
			$rowCourse->duration = $duration;
			$rowCourse->fees = $fees;		
			$rowCourse->job_types = $job_types;
			$rowCourse->benefits = $benefits;
			$rowCourse->faq = $faq;
			$rowCourse->testimonials = $testimonials;
			
			$rowCourse->save();	
			return $rowCourse;
		} else {
			throw new Zend_Exception("Could not add course!");
		}
	}
	
	//******************************************************//
	// Called AdminController - updateCourseAction method //
	public function updateCourse($course_id,$title,$learning_mode,$placement_assistance,$duration,$fees,$job_types,$benefits,$faq,$testimonials)
	{
		$rowCourse = $this->find($course_id)->current();
		if($rowCourse) {
			// update the row values
			$rowCourse->title = $title;
			$rowCourse->learning_mode = $learning_mode;
			$rowCourse->placement_assistance = $placement_assistance;
			$rowCourse->duration = $duration;
			$rowCourse->fees = $fees;
			$rowCourse->job_types = $job_types;
			$rowCourse->benefits = $benefits;
			$rowCourse->faq = $faq;
			$rowCourse->testimonials = $testimonials;
			$rowCourse->save();	
			return $rowCourse;	
		}else{
			throw new Zend_Exception("Course update failed. Course not found!");
		}
	}
	
	//******************************************************//
	// Called AdminController - updateCourseAction method //
	public static function loadCourse($course_id)
	{	
		$courseModel = new self();
		$select = $courseModel->select();
		$select->setIntegrityCheck(false);	
		$select->from('course', 'course.*');
		$select->where('course.course_id = '.$course_id);
		return $courseModel->fetchRow($select);		        
	}
	
	public static function showCourses()
	{
		$courseModel = new self();
		$select = $courseModel->select();
		$select->setIntegrityCheck(false);
		$select->from('course', 'course.*');
		return $courseModel->fetchAll($select);
	}
	
	//******************************************************//
	// Called CourseController - listCoursesAction method //
	public static function getCourses()
	{
		$courseModel = new self();
		$select = $courseModel->select();
		$select->order(array('title'));
		return $courseModel->fetchAll($select);
	}
	
	//******************************************************//	
	// Called IndexController -  indexAction method //
	public static function getActiveCourses()
	{
		$courseModel = new self();
		$select = $courseModel->select();
		$select->setIntegrityCheck(false);
		$select->from('course', array('course_id','title','fees','benefits','og_description','seo_keyword2'));
		$select->joinLeft('category_course', 'course.course_id = category_course.course_id',null);
		$select->joinLeft('category', 'category_course.category_id=category.category_id',array('category_id','category_name'));
		//$select->joinLeft('topic', 'course.course_id = topic.course_id and parent_topic_id!=0',array('lectures'=>'count(topic.topic_id)'));		
		//$select->joinLeft('topic_video', 'topic.topic_id = topic_video.topic_id and is_sample="N"',array('lectures'=>'count(topic_video.topic_id)'));
		$select->where('course.is_active = "Y"');
		$select->order('category.order_number');
		$select->order('category_course.order_number');
		//$select->group('course.course_id');
		return $courseModel->fetchAll($select);
	}
	
	public static function getEnrolledCourses($user_id)
	{
		$courseModel = new self();
		$select = $courseModel->select();
		$select->setIntegrityCheck(false);
		$select->from('course', array('course_id','title'));
		$select->join('enrollment', 'course.course_id = enrollment.course_id',array('batch_id','course_id'));
		$select->joinLeft('batch', 'enrollment.batch_id=batch.batch_id',array('batch_id','start_date'));
		$select->where('user_id = '.$user_id);
		return $courseModel->fetchAll($select);
	}
	
	
	//******************************************************//	
	// Called IndexController -  referralAction method //
	public static function getCourseReferral()
	{
		$courseModel = new self();
		$select = $courseModel->select();
		$select->setIntegrityCheck(false);
		$select->from('course', array('course_id','title'));
		$select->join('referral', 'course.course_id = referral.course_id',array('short_name','cashback'));
		$select->order('cashback DESC');
		return $courseModel->fetchAll($select);
	}
	
	//******************************************************//
	// Called UserController - viewFacultyAction method //
	public static function getCoursesByFaculty($faculty_id)
	{
		$courseModel = new self();
		$select = $courseModel->select();
		$select->setIntegrityCheck(false);
		$select->from('course', 'course.*');
		$select->joinLeft('course_faculty', 'course.course_id = course_faculty.course_id');
		$select->order(array('title'));
		$select->where('faculty_id = '.$faculty_id);
		return $courseModel->fetchAll($select);
	}
	public function uploadCourse()
	{
		$newCourse = new self();
		$select = $newCourse->select();
		$select->from('course','course.*');
		$select->where('has_batch = "Y"');
		return $newCourse->fetchAll($select);
	}
	
	//******************************************************//
	// Called AdminController - deleteCourseAction method //
	public function deleteCourse($course_id)
	{
		$rowCourse = $this->find($course_id)->current();
		if($rowCourse) {
			$rowCourse->delete();
		}else{
			throw new Zend_Exception("Could not delete Course. Course not found!");
		}
	}
	
}