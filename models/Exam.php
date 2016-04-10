<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Exam extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'exam';
	
	
	
	public static function loadExam($course_id)
	{	
		$examModel = new self();		
		$select = $examModel->select();
		$select->setIntegrityCheck(false);
		$select->from(array('ec' => 'exam_course'), 'ec.course_id');
		$select->join(array('eq' =>'exam_question'), 'eq.exam_id = ec.exam_id','eq.question_id');
		$select->join(array('aq' =>'adl_question'), 'eq.question_id = aq.question_id','aq.question');		
		$select->join(array('aqa' =>'adl_question_answer'), 'aq.question_id = aqa.question_id',array('aqa.answer','aqa.answer_id','aqa.is_correct'));
		$select->where('ec.course_id = '.$course_id);
		$select->where('eq.is_active = "Y"');
		$select->order("rand()");
		return $examModel->fetchAll($select);		        
	}
	
				
}