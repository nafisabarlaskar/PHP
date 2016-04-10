<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Role extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'role';

	
	//******************************************************//
	// Called AdminController - addFacultyAction method//	
	public static function getRole($role_name)
	{
		$roleModel = new self();
		$select = $roleModel->select();
		$select->from('role',array('role_id'));
		$select->where('role_name = "' . $role_name .'"');		
		return $roleModel->fetchAll($select);		
	}
	
}