<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_CompetitionUser extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'competition_user';	
	public function checkUserRegistered($competition_id,$user_id)
	{	
		$competitionUserModel = new self();
		$select = $competitionUserModel->select();
		$select->from('competition_user');
		$select->where('competition_id = '.$competition_id);
		$select->where('user_id = '.$user_id);
		return $competitionUserModel->fetchRow($select);		        
	}				
	
	//******************************************************//
	// Called CompetitionController - registerAction method //
	public function registerUser($competition_id,$user_id)
	{
		// create a new row
		$rowCompetitionUser = $this->createRow();
		if($rowCompetitionUser) {
			// update the row values
			$rowCompetitionUser->competition_id = $competition_id;
			$rowCompetitionUser->user_id = $user_id;
			$rowCompetitionUser->save();	
			return $rowCompetitionUser;
		} else {
			throw new Zend_Exception("Could not register new user for competition");
		}
	}
}