<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_UserPicture extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'user_picture';
	protected $_primary = 'user_id';

	
}