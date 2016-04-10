<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Address extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'address';

	public static function getAddress($address_id)
	{
		$addressModel = new self();
		$select = $addressModel->select();
		$select->setIntegrityCheck(false);
		$select->from('address',array('full_name'=>'name','address_line_1'=>'address_line_1','address_line_2'=>'address_line_2','zip'=>'zip','phone'=>'phone','email'=>'email','zip_code'=>'zip_code'));
		$select->joinLeft('city', 'address.city_id = city.city_id',array('city_name'=>'name'));
		$select->joinLeft('state', 'city.state_id = state.state_id',array('state_name'=>'name'));
		$select->joinLeft('country', 'state.country_id = country.country_id',array('country_name'=>'name'));
		$select->where('address.address_id='.$address_id);
		return $addressModel->fetchRow($select);	
	}
	
	// Called UserController - billingAction method //
	public function createAddress($city_id,$full_name,$address_line_1,$address_line_2,$zip,$zip_code=null,$phone=null,$email=null)
	{
		// create a new row
		$rowAddress = $this->createRow();
		if($rowAddress) {
			// update the row values
			$rowAddress->city_id = $city_id;
			$rowAddress->name = $full_name;
			$rowAddress->address_line_1 = $address_line_1;
			$rowAddress->address_line_2 = $address_line_2;
			$rowAddress->zip = $zip;			
			$rowAddress->zip_code = $zip_code;
			$rowAddress->phone = $phone;
			$rowAddress->email = $email;
			$rowAddress->save();
			return $rowAddress;
		}else{
			throw new Zend_Exception("Could not add new address");
		}
	}
	
	// Called UserController - billingAction method //
	public function updateAddress($address_id,$city_id,$full_name,$address_line_1,$address_line_2,$zip,$zip_code,$phone,$email)
	{		
		$rowAddress = $this->find($address_id)->current();
		if($rowAddress) {
			// update the row values
			$rowAddress->city_id = $city_id;
			$rowAddress->name = $full_name;
			$rowAddress->address_line_1 = $address_line_1;
			$rowAddress->address_line_2 = $address_line_2;
			$rowAddress->zip = $zip;	
			$rowAddress->zip_code = $zip_code;
			$rowAddress->phone = $phone;
			$rowAddress->email = $email;		
			$rowAddress->save();
			return $rowAddress;
		}else{
			throw new Zend_Exception("Address update failed. Address not found!");
		}
	}
		
	
}