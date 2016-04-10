<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_ManipalUser extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'manipal_user';

	
	//******************************************************//
	// Called ManipalController - courseAction method //
	public function addUser($user_id,$rollnum,$email,$course_id,$course_code,$community_id,$section_code,$checksum)
	{
		// create a new row
		$rowManipalUser = $this->createRow();
		if($rowManipalUser) {
			// update the row values
			$rowManipalUser->user_id = $user_id;
			$rowManipalUser->rollnum = $rollnum;
			$rowManipalUser->email = $email;
			$rowManipalUser->course_id = $course_id;
			$rowManipalUser->course_code = $course_code;
			$rowManipalUser->community_id = $community_id;
			$rowManipalUser->section_code = $section_code;
			$rowManipalUser->checksum = $checksum;
			$rowManipalUser->save();	
			return $rowManipalUser;
		} else {
			throw new Zend_Exception("Could not register new user manipal");
		}
	}
	
}