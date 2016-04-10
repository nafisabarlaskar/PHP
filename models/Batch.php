<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Batch extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'batch';

	
	//******************************************************//
	// Called CourseController - viewAction method //
	public static function getBatches($course_id)
	{		
		$batchModel = new self();
		$select = $batchModel->select();
		$select->setIntegrityCheck(false);				
		//$select->from('batch', array('batch_id','class_days','duration','class_time','date_format(start_date,\'%D \' \'%M \') as starting_date','if(NOW()<=start_date,"open","close") as status'));
		//$select->from('batch', array('batch_id','class_days','duration','class_time','fees','fees_dollar','fees_project','fees_project_dollar','date_format(start_date,\'%D \') as starting_day','date_format(start_date,\'%M \') as starting_month','if(NOW()<=start_date,"open","close") as status'));
		$select->from('batch', array('batch_id','class_days','duration','class_time','fees','fees_dollar','fees_project','fees_project_dollar','date_format(start_date,\'%D \') as starting_day','date_format(start_date,\'%M \') as starting_month','if(DATE_SUB(NOW(), INTERVAL 12 HOUR)<=start_date,"open","close") AS status'));		
		$select->where('course_id = '.$course_id);
		$select->where('is_private = "N"');
		$select->where('is_active = "Y"');
		$select->where('NOW()<=start_date+Interval 7 Day');
		$select->order('start_date');		
		return $batchModel->fetchAll($select);
		//return $select->__toString();
	}
	
	public static function getBatch($batch_id)
	{
		$batchModel = new self();
		$select = $batchModel->select();
		$select->from('batch', array('class_days','duration','class_time','date_format(start_date,\'%D \') as starting_day','date_format(start_date,\'%M \') as starting_month','date_format(start_date,\'%Y \') as starting_year'));
		$select->where('batch_id = '.$batch_id);
		return $batchModel->fetchRow($select);
		//return $select->__toString();
	}
	
	//******************************************************//
	// Called CourseController - viewAction method //
	public static function getFees($batch_id)
	{
		$batchModel = new self();
		$select = $batchModel->select();
		$select->from('batch','batch.*');
		$select->where('batch_id = '.$batch_id);
		return $batchModel->fetchRow($select);		
	}
	
	public function getAdminBatches($course_id)
	{
		$newBatch = new self();
		$select = $newBatch->select();
		$select->from('batch', array('date_format(start_date,\'%D \' \'%M \') as starting_date','if(NOW()<=start_date,"open","close") as status','class_days','batch_id','course_id','class_time'));
		$select->where('course_id = '.$course_id);
		$select->order('start_date ASC');
		return $newBatch->fetchAll($select);
	}
	
	public static function getNextBatch($course_id)
	{
		$batchModel = new self();
		$select = $batchModel->select();
		$select->setIntegrityCheck(false);
		$select->from('batch', array('batch_id','class_days','is_private','is_active','duration','class_time','fees','fees_dollar','fees_project','fees_project_dollar','date_format(start_date,\'%D \') as starting_day','date_format(start_date,\'%M \') as starting_month'));
		$select->where('course_id = '.$course_id);
		$select->where('is_private = "N"');
		$select->where('is_active = "Y"');
		$select->where('NOW()< start_date');
		$select->order('start_date ASC');
		$select->limit(1);
		return $batchModel->fetchRow($select);
		//return $select->__toString();
	}
	
}