<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_State extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'state';

	public static function getState($name,$country_id)
	{
		$stateModel = new self();
		$select = $stateModel->select();
		$select->from('state');
		$select->where('name="'.$name.'"');
		$select->where('country_id ='.$country_id);
		return $stateModel->fetchAll($select);	
	}
	
	//*****************************************************//
	// Called UserController - billingAction method //
	public function addState($country_id,$name)
	{
		$rowState = $this->createRow();
		if($rowState) {
			$rowState->country_id = $country_id;
			$rowState->name = $name;
			$rowState->save();			
		} else {
			throw new Zend_Exception("Could not add state");
		}	
		return $rowState;		
	}
	
}