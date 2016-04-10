<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_CourseFaculty extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'course_faculty';

	//******************************************************//
	// Called AdminController - addCourseAction method //
	public function addCourseFaculty($course_id,$faculty_ids)
	{
		foreach($faculty_ids as $faculty_id) {
			$rowCourseFaculty = $this->createRow();
			if($rowCourseFaculty) {
				$rowCourseFaculty->course_id = $course_id;
				$rowCourseFaculty->faculty_id = $faculty_id;
				$rowCourseFaculty->save();			
			} else {
				throw new Zend_Exception("Could not add new Course Faculty");
			}
		}
		return $rowCourseFaculty;		
	}
	
	//******************************************************//
	// Called AdminController - updateCourseAction method to select which faculty are teaching this course//	
	public static function getFaculty($course_id)
	{
		$courseFacultyModel = new self();
		$select = $courseFacultyModel->select();
		$select->from('course_faculty',array('faculty_id'));
		$select->where('course_id = '.$course_id);
		return $courseFacultyModel->fetchAll($select);		
	}

	//******************************************************//
	// Called AdminController - viewCourseAction method //
	// Called CourseController - viewAction method //
	// Called CourseController - listCoursesAction method //
	public static function getFacultyByCourseId($course_id)
	{
		$courseFacultyModel = new self();
		$select = $courseFacultyModel->select();
		$select->setIntegrityCheck(false);
		$select->from('course_faculty',array('course_id','faculty_id'));
		$select->joinLeft('user', 'user.user_id = course_faculty.faculty_id');
		$select->joinLeft('faculty', 'user.user_id = faculty.user_id');
		$select->where('course_id in(?)',$course_id);
		$select->order('user.first_name');
		return $courseFacultyModel->fetchAll($select);		
	}
	
	//******************************************************//
	// Called CourseController - myViewAction method //
	public static function isCourseFaculty($course_id,$faculty_id)
	{
		$courseFacultyModel = new self();
		$select = $courseFacultyModel->select();
		$select->from('course_faculty',array('course_id','faculty_id'));		
		$select->where('course_id = '.$course_id);
		$select->where('faculty_id = '.$faculty_id);		
		return $courseFacultyModel->fetchAll($select);		
	}
	
	
	
	//******************************************************//
	// Called AdminController - updateCourseAction method //
	public function deleteCourseFaculty($course_id)
	{
		$select = $this->getAdapter()->quoteInto('course_id = ?', (int)$course_id);      
		$this->delete($select);		
	}

		
	
	
	
	
}