<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_City extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'city';

	public static function getCity($name,$state_id)
	{
		$cityModel = new self();
		$select = $cityModel->select();
		$select->from('city');
		$select->where('name="'.$name.'"');
		$select->where('state_id ='.$state_id);
		return $cityModel->fetchAll($select);	
	}
	
	//*****************************************************//
	// Called UserController - billingAction method //
	public function addCity($state_id,$name)
	{
		$rowCity = $this->createRow();
		if($rowCity) {
			$rowCity->state_id = $state_id;
			$rowCity->name = $name;
			$rowCity->save();			
		} else {
			throw new Zend_Exception("Could not add city");
		}	
		return $rowCity;		
	}
	
}