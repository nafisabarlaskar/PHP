<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Enrollment extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'enrollment';
	
	//******************************************************//
	// Called CourseController - freeCourseAction method //
	public static function loadEnrollment($enrollment_id)
	{	
		$enrollmentModel = new self();
		$select = $enrollmentModel->select();
		$select->from('enrollment','enrollment.*');
		$select->where('enrollment_id = '.$enrollment_id);				
		return $enrollmentModel->fetchRow($select);
		//$stmt = $select->query();
        //return $stmt->fetchAll();;
	}

	//******************************************************//
	// Called ManipalController - courseAction method //
	public function addManipal($course_id,$user_id,$discount_id=null,$payment_amount,$payment_received='N',$order_id,$payment_form=null,$valid_until)
	{
		// create a new row
		$rowEnrollment = $this->createRow();
		if($rowEnrollment) {
			// update the row values
			$rowEnrollment->course_id = $course_id;
			$rowEnrollment->user_id = $user_id;
			$rowEnrollment->discount_id = $discount_id;
			$rowEnrollment->payment_amount = $payment_amount;
			$rowEnrollment->payment_received = $payment_received;
			$rowEnrollment->order_id = $order_id;
			$rowEnrollment->payment_form = $payment_form;
			$rowEnrollment->valid_until = $valid_until;
			$rowEnrollment->save();	
			return $rowEnrollment;
		} else {
			throw new Zend_Exception("Could not add new manipal enrollment!");
		}
	}

	//******************************************************//
	// Called CourseController - paymentAction method //
	public function add($course_id,$user_id,$discount_id=null,$payment_amount,$payment_received='N',$order_id,$payment_form=null,$batch_id=null)
	{
		// create a new row
		$rowEnrollment = $this->createRow();
		if($rowEnrollment) {
			// update the row values
			$rowEnrollment->course_id = $course_id;
			$rowEnrollment->user_id = $user_id;
			$rowEnrollment->discount_id = $discount_id;
			$rowEnrollment->payment_amount = $payment_amount;
			$rowEnrollment->payment_received = $payment_received;
			$rowEnrollment->order_id = $order_id;
			$rowEnrollment->payment_form = $payment_form;
			$rowEnrollment->batch_id = $batch_id;
			$rowEnrollment->save();	
			return $rowEnrollment;
		} else {
			throw new Zend_Exception("Could not add new enrollment!");
		}
	}	
	
	// Called CourseController - discountAction method //
	public function updateEnrollment($enrollment_id,$discount_id,$payment_amount,$order_id=null,$batch_id=null)
	{
		try {
		$rowEnroll = $this->find($enrollment_id)->current();
		if($rowEnroll) {
			// update the row values
			$rowEnroll->discount_id = $discount_id;
			$rowEnroll->payment_amount = $payment_amount;
			if($order_id!=null)
				$rowEnroll->order_id = $order_id;
			if($batch_id!=null)
				$rowEnroll->batch_id = $batch_id;
			$rowEnroll->save();	
			return $rowEnroll;	
		}else{
			throw new Zend_Exception("Enrollment update failed. enrollment not found-".$enrollment_id);
		}
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	
	// Called CourseController - enrollAction method //
	public function updateOrderId($enrollment_id,$order_id)
	{
		try {
		$rowEnroll = $this->find($enrollment_id)->current();
		if($rowEnroll) {
			$rowEnroll->order_id = $order_id;
			$rowEnroll->save();	
			return $rowEnroll;	
		}else{
			throw new Zend_Exception("Enrollment order id failed. enrollment id not found-".$enrollment_id);
		}
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	
	
	// Called CourseController - paymentAction method //
	public function updatePayment($enrollment_id,$order_id,$payment_amount,$authdesc,$payment_form,$user_id)
	{
		try {
		$rowEnroll = $this->find($enrollment_id)->current();
		if($rowEnroll) {
			// update the row values
			$rowEnroll->order_id = $order_id;
			$rowEnroll->payment_amount = $payment_amount;
			$rowEnroll->authdesc = $authdesc;
			$rowEnroll->payment_received = 'Y';
			$rowEnroll->payment_form = $payment_form;
			$rowEnroll->user_id = $user_id;
			$rowEnroll->save();	
			return $rowEnroll;	
		}else{
			throw new Zend_Exception("Enrollment updatepayment failed. enrollment not found-".$enrollment_id);
		}
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	
	// Called CourseController - paymentAction method //
	public function updatePaymentReceived($enrollment_id,$payment_received,$payment_form,$user_id)
	{
		try {
		$rowEnroll = $this->find($enrollment_id)->current();
		if($rowEnroll) {
			// update the row values
			$rowEnroll->payment_received = $payment_received;
			$rowEnroll->payment_form = $payment_form;
			$rowEnroll->user_id = $user_id;
			$rowEnroll->save();	
			return $rowEnroll;	
		}else{
			throw new Zend_Exception("Enrollment updatepayment received failed. enrollment not found-".$enrollment_id);
		}
		} catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	}
	
	
	//******************************************************//
	// Called CourseController - myCoursesAction method //
	public static function getCoursesByStudent($user_id)
	{
		$enrollmentModel = new self();
		$select = $enrollmentModel->select();		
		$select->setIntegrityCheck(false);
		$select->from('enrollment',array('course_id','payment_received','batch_id'));
		$select->joinLeft('course', 'course.course_id = enrollment.course_id');
		$select->joinLeft('batch', 'batch.batch_id = enrollment.batch_id',array('batch_name','class_days','class_time','date_format(start_date,\'%D \' \'%M \') as start_date'));
		//$select->joinLeft('topic', 'course.course_id = topic.course_id');
		//$select->joinLeft('topic_video', 'topic.topic_id = topic_video.topic_id and is_sample="N"',array('lectures'=>'count(topic_video.topic_id)'));		
		$select->where('enrollment.user_id = '.$user_id);
		$select->order('enrollment.last_modified');
		//$select->group('course.course_id');
		//$select->where('enrollment.payment_received = "Y"');
		return $enrollmentModel->fetchAll($select);
		//return $select->__toString();
	}
	
	public static function getCoursesByStudentEmail($email)
	{
		$enrollmentModel = new self();
		$select = $enrollmentModel->select();
		$select->setIntegrityCheck(false);
		$select->from('enrollment',array('course_id','payment_received','enrollment_id','batch_id'));
		$select->joinLeft('course', 'course.course_id = enrollment.course_id');
		$select->joinLeft('batch', 'batch.batch_id = enrollment.batch_id',array('batch_name','batch_id','class_days','class_time','date_format(start_date,\'%D \' \'%M \') as start_date'));
		$select->joinLeft('user', 'enrollment.user_id = user.user_id');
		$select->where('user.email = "'.$email.'"');
		return $enrollmentModel->fetchAll($select);
		//return $select->__toString();
	}
	
	public function updateBatch($enrollment_id,$batch_id)
	{
		try{
			$rowEnroll = $this->find($enrollment_id)->current();
			if ($rowEnroll)
			{
				$rowEnroll->batch_id = $batch_id;
				$rowEnroll->save();
				return $rowEnroll;
			}
			else{
				throw new Zend_Exception("Enrollment updatebatch received failed. enrollment not found-".$enrollment_id);
			}
		}
		catch (Exception $e) {Zend_Registry::get('logger')->err($e->getMessage().'---------'. $e->getTraceAsString());}
	
	}
	
	//******************************************************//
	// Called ReportController - reportAction method //
	public static function getStudentsByCourse($course_id,$start_date,$end_date)
	{
		$enrollmentModel = new self();
		$select = $enrollmentModel->select()->distinct();		
		$select->setIntegrityCheck(false);
		$select->from(array('e'=>'enrollment'),array('e.course_id','e.payment_amount','date_format(e.last_modified,\'%b \' \'%d \' \'%Y \') as date_joined'));
		$select->joinLeft('user', 'e.user_id = user.user_id',array('first_name','last_name','email','phone'));				
		//$select->where('e.course_id = '.$course_id);
		$select->where('e.course_id IN(?) ',$course_id);
		$select->where('e.payment_received ="Y"');
		if($course_id==4) 
			$select->where('e.payment_amount>=0');
		else
			$select->where('e.payment_amount>0');
		if($start_date!=null) {
			$select->where('e.last_modified>="'.$start_date.'"');
			$select->where('e.last_modified<="'.$end_date.'"');
		}
		$select->order('e.last_modified');
		
		return $enrollmentModel->fetchAll($select);
	}
	
	//******************************************************//
	// Called ReportController - searchAction method //
	public static function searchStudent($email,$course_id_array)
	{
		$enrollmentModel = new self();
		$select = $enrollmentModel->select()->distinct();		
		$select->setIntegrityCheck(false);
		$select->from(array('e'=>'enrollment'),array('e.course_id','e.payment_amount','date_format(e.last_modified,\'%b \' \'%d \' \'%Y \') as date_joined'));
		$select->joinLeft('user', 'e.user_id = user.user_id',array('first_name','last_name','email','phone'));				
		$select->joinLeft('course', 'e.course_id = course.course_id',array('title'));
		$select->where('user.email="'.$email.'"');
		$select->where('e.course_id IN(?) ',$course_id_array);
		$select->where('e.payment_received ="Y"');
		//$select->where('e.payment_amount>0');
		$select->order('e.last_modified');
		
		return $enrollmentModel->fetchAll($select);
	}
	
	//******************************************************//
	// Called CourseController - myViewAction method //
	public static function isStudentEnrolled($course_id,$user_id)
	{
		$enrollmentModel = new self();
		$select = $enrollmentModel->select()->distinct();		
		$select->from('enrollment',array('enrollment_id','course_id','payment_received','is_complete','valid_until','batch_id'));	
		$select->where('enrollment.user_id = '.$user_id);
		$select->where('enrollment.course_id = '.$course_id);
		//$select->where('enrollment.payment_received = "Y"');
		return $enrollmentModel->fetchRow($select);
	}
	
	//******************************************************//
	// Called CourseController - myViewAction method //
	public static function isStudentEnrolledBatch($course_id,$user_id,$batch_id)
	{
		$enrollmentModel = new self();
		$select = $enrollmentModel->select()->distinct();
		$select->from('enrollment',array('enrollment_id','course_id','payment_received','is_complete','valid_until','batch_id'));
		$select->where('enrollment.user_id = '.$user_id);
		$select->where('enrollment.course_id = '.$course_id);
		$select->where('enrollment.batch_id = '.$batch_id);
		//$select->where('enrollment.payment_received = "Y"');
		return $enrollmentModel->fetchRow($select);
	}
	
	
	//******************************************************//
	// Called CourseController - enrollAction method //
	public static function isUserEnrolled($enrollment_id)
	{
		$enrollmentModel = new self();
		$select = $enrollmentModel->select()->distinct();		
		$select->from('enrollment',array('enrollment_id','user_id','course_id','payment_received','is_complete'));	
		$select->where('enrollment.enrollment_id = '.$enrollment_id);		
		return $enrollmentModel->fetchRow($select);
	}
	
	public function deleteEnrollment($enrollment_id)
	{
		$rowEnroll = $this->find($enrollment_id)->current();
		if($rowEnroll) {
			$rowEnroll->delete();
		}else{
			throw new Zend_Exception("Could not delete enrollment. Enrollment not found!");
		}
	}
}