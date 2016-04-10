<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Testimonial extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'testimonial';

	//******************************************************//
	// Called ExamController - examScoreAction method //
	public function addTestimonial($user_id,$course_id,$testimonial,$rating)
	{
		// create a new row
		$rowTestimonial = $this->createRow();
		if($rowTestimonial) {
			// update the row values
			$rowTestimonial->user_id = $user_id;
			$rowTestimonial->course_id = $course_id;
			$rowTestimonial->testimonial = $testimonial;
			$rowTestimonial->rating = $rating;
			$rowTestimonial->save();	
			return $rowTestimonial;
		} else {
			throw new Zend_Exception("Could not add new testimonial =".$user_id);
		}
	}	
	
	public static function getTestimonialsByCourse($course_id,$count=null)
	{
		$testimonialModel = new self();
		$select = $testimonialModel->select();
		$select->setIntegrityCheck(false);
		$select->from('testimonial', 'testimonial.*');
		$select->joinLeft('user', 'user.user_id = testimonial.user_id',array('first_name','last_name','college'=>'college','designation'=>'designation','company'=>'company'));
		if($count==null)
			$select->joinLeft('user_picture', 'user.user_id = user_picture.user_id','user_picture_id');
		else
			$select->join('user_picture', 'user.user_id = user_picture.user_id','user_picture_id');
		$select->where('testimonial.is_visible = "Y"');
		$select->where('testimonial.course_id = '.$course_id);
		if($count!=null)
			$select->limit($count);
		$select->order('rand()');
		return $testimonialModel->fetchAll($select);
	}
	

	public static function getTestimonials($count=null)
	{
		$testimonialModel = new self();
		$select = $testimonialModel->select();
		$select->setIntegrityCheck(false);	
		$select->from('testimonial', 'testimonial.*');
		$select->joinLeft('user', 'user.user_id = testimonial.user_id',array('first_name','last_name','college'=>'college','designation'=>'designation','company'=>'company'));
		if($count==null)
			$select->joinLeft('user_picture', 'user.user_id = user_picture.user_id','user_picture_id');
		else 
			$select->join('user_picture', 'user.user_id = user_picture.user_id','user_picture_id');
		$select->where('testimonial.is_visible = "Y"');
		if($count!=null)
			$select->limit($count);
		$select->order('rand()');		
		return $testimonialModel->fetchAll($select);
	}
	
	public static function countTestimonial($course_id)
	{		
		$testimonialModel = new self();
		$select = $testimonialModel->select();
		$select->setIntegrityCheck(false);	
		$select->from('testimonial', array("total"=>"COUNT(*)","average"=>"round(avg(rating),2)"));
		$select->where('course_id ='.$course_id);
		return $testimonialModel->fetchRow($select);        
	}
}