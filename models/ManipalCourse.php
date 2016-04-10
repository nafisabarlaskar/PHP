<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_ManipalCourse extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'manipal_course';

	
	//******************************************************//
	// Called UserController - validateAction method//	
	public static function getCourseId($course_code)
	{
		$manipalCourseModel = new self();
		$select = $manipalCourseModel->select();
		$select->from('manipal_course',array('course_id','fees'));
		$select->where('code = "' . $course_code .'"');		
		return $manipalCourseModel->fetchRow($select);				
	}
	
}