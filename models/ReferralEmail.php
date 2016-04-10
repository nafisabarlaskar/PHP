<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_ReferralEmail extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'referral_emails';

	//******************************************************//
	// Called CourseController - askQuestionAction method //
	public function addEmails($user_id,$email)
	{
		// create a new row
		$row = $this->createRow();
		if($row) {
			// update the row values
			$row->user_id = $user_id;
			$row->email = $email;
			$row->save();	
			return $row;
		} else {
			throw new Zend_Exception("Could not add new referral email");
		}
	}	
}