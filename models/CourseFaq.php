<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_CourseFaq extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'course_faq';

	
	//******************************************************//
	// Called CourseController - viewBatchAction method //
	public static function getFaq($course_id)
	{		
		$faqModel = new self();
		$select = $faqModel->select();
		$select->from('course_faq', array('question','answer','type','question_order'));		
		$select->where('course_id = '.$course_id);
		$select->order('question_order');
		return $faqModel->fetchAll($select);		
	}

				
}