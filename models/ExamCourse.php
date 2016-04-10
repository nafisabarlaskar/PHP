<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_ExamCourse extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'exam_course';
	
	//******************************************************//
	// Called ExamController - startExamAction method //
	public static function getExamId($course_id)
	{	
		$examCourseModel = new self();		
		$select = $examCourseModel->select();
		$select->setIntegrityCheck(false);
		$select->from(array('ec' => 'exam_course'), 'ec.exam_id');
		$select->join(array('ex' =>'exam'), 'ex.exam_id = ec.exam_id',array('ex.exam_time','ex.template','ex.questions'));
		$select->where('ec.course_id = '.$course_id);
		return $examCourseModel->fetchRow($select);		        
	}			
}