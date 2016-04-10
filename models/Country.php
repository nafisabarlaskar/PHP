<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Country extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'country';

	public static function getCountry()
	{
		$countryModel = new self();
		$select = $countryModel->select();
		$select->from('country');
		return $countryModel->fetchAll($select);	
	}
	
	public static function getCountryId($name)
	{
		$countryModel = new self();
		$select = $countryModel->select();
		$select->from('country');
		$select->where('name="'.$name.'"');		
		return $countryModel->fetchAll($select);	
	}
	
	//*****************************************************//
	// Called CourseController - updateBillingAddress method //
	public function addCountry($name)
	{
		$rowCountry = $this->createRow();
		if($rowCountry) {
			$rowCountry->name = $name;
			$rowCountry->save();			
		} else {
			throw new Zend_Exception("Could not add country");
		}	
		return $rowCountry;		
	}
	
}