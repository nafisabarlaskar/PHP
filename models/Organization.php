<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Organization extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'organization';

	
	//******************************************************//
	// Called UserController - validateAction method//	
	public static function getOrganization($org_name)
	{
		$orgModel = new self();
		$select = $orgModel->select();
		$select->from('organization',array('organization_id'));
		$select->where('name = "' . $org_name .'"');		
		return $orgModel->fetchRow($select);
				
	}
	
}