<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Discount extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'discount';
	
	//******************************************************//
	// Called CourseController - freeCourseAction method //
	public static function loadDiscount($discount_id)
	{	
		$discountModel = new self();
		$select = $discountModel->select();
		$select->from('discount','discount.*');
		$select->where('discount_id = '.$discount_id);				
		return $discountModel->fetchRow($select);
		//$stmt = $select->query();
        //return $stmt->fetchAll();;
	}	
	
	public static function showDiscount($course_id)
	{
		$discountModel = new self();
		$select = $discountModel->select();
		$select->from('discount','discount.*');
		$select->where('course_id = '.$course_id);
		return $discountModel->fetchAll($select);
	}	
	//******************************************************//
	// Called UserController - registerAction method //
	public function addDiscount($user_id, $discount_code, $discount_percentage)
	{
		// create a new row
		$rowDiscount = $this->createRow();
		if($rowDiscount) {
			// update the row values
			$rowDiscount->user_id = $user_id;
			$rowDiscount->discount_code = $discount_code;
			$rowDiscount->discount_percentage = $discount_percentage;
			$rowDiscount->save();
			//return the new user
			return $rowDiscount;
		} else {
			throw new Zend_Exception("Could not add discount");
		}
	}
	
	//******************************************************//
	// Called ExamController - registerAction method //
	public function addTimedDiscount($user_id, $course_id,$discount_code, $discount_percentage,$valid_until,$one_time)
	{
		// create a new row
		$rowDiscount = $this->createRow();
		if($rowDiscount) {
			// update the row values
			$rowDiscount->user_id = $user_id;
			$rowDiscount->course_id = $course_id;
			$rowDiscount->discount_code = $discount_code;
			$rowDiscount->discount_percentage = $discount_percentage;
			$rowDiscount->valid_until = $valid_until;
			$rowDiscount->one_time = $one_time;
			$rowDiscount->save();
			//return the new user
			return $rowDiscount;
		} else {
			throw new Zend_Exception("Could not add discount");
		}
	}
	
	//******************************************************//
	// Called CourseController - discountAction method //	
	public static function getDiscount($discount_code,$course_id)
	{
		$discountModel = new self();
		$select = $discountModel->select();
		$select->from('discount',array('discount_id','discount_percentage','course_id','one_time','discount_code'));
		$select->where('discount_code = "'.$discount_code.'"');
		$select->where('course_id = '.$course_id.' OR course_id is null');
		$select->where('is_active = "Y"');
		$select->where('NOW() <= valid_until');
		return $discountModel->fetchRow($select);		
	}
	
	//******************************************************//
	// Called ReportController - searchAction method //
	public static function getFreeStudents()
	{
		$discountModel = new self();
		$select = $discountModel->select()->distinct();		
		$select->setIntegrityCheck(false);
		$select->from(array('d'=>'discount'), array('discount_code'));
		$select->joinLeft(array('e'=>'enrollment'),'e.discount_id = d.discount_id',array('enrolled'=>'count(e.enrollment_id)'));						
		$select->where('d.course_id=4');
		$select->where('e.payment_received="Y"');
		$select->where('d.is_active="Y"');
		$select->group('d.discount_id');
		$select->order('enrolled desc');		
		return $discountModel->fetchAll($select);
		//return $select->__toString();
	}
	

	//******************************************************//
	// Called UserController - indexAction method //	
	public static function getDiscountCode($user_id)
	{
		$discountModel = new self();
		$select = $discountModel->select();
		$select->from('discount',array('discount_id','discount_code'));
		$select->where('user_id = '.$user_id);
		$select->where('is_active = "Y"');
		return $discountModel->fetchRow($select);		
	}
	public function createDiscountCode($user_id,$course_id,$discount_code,$discount_percentage,$one_time)
	{
		$newRow = $this->createRow();
		if($newRow)
		{
			$newRow->user_id = $user_id;
			$newRow->course_id = $course_id;
			$newRow->discount_code = $discount_code;
			$newRow->discount_percentage = $discount_percentage;
			$newRow->one_time = $one_time;
		
			$newRow->save();
			return $newRow;
		}
		else {
			throw new Zend_Exception("Failed to create discount code!");
		}
	}
	
	public function updateDiscountCode($discount_id,$user_id,$course_id,$discount_code,$discount_percentage,$one_time,$is_active)
	{
		try {
			$rowDiscount = $this->find($discount_id)->current();
			if($rowDiscount) {
				// update the row values
				$rowDiscount->user_id = $user_id;
				$rowDiscount->course_id = $course_id;
				$rowDiscount->discount_code = $discount_code;
				$rowDiscount->discount_percentage = $discount_percentage;
				$rowDiscount->one_time = $one_time;
				$rowDiscount->is_active = $is_active;
				$rowDiscount->save();
				return $rowDiscount;
			}else{
				throw new Zend_Exception("Discount updateDiscountCode failed. discount not found-".$discount_id);
			}
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	// Called CourseController - freeCourseAction method //
	public function updateActiveStatus($discount_id)
	{
		try {
		$rowDiscount = $this->find($discount_id)->current();
		if($rowDiscount) {
			// update the row values
			$rowDiscount->is_active = 'N';
			$rowDiscount->save();	
			return $rowDiscount;	
		}else{
			throw new Zend_Exception("Discount updateActiveStatus failed. discount not found-".$discount_id);
		}
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
}