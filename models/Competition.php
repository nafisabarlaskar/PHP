<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Competition extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
	protected $_name = 'competition';	
	public static function loadCompetition($competition_id)
	{	
		$competitionModel = new self();
		$select = $competitionModel->select();
		$select->from('competition', array('competition.title','competition.prize','competition.rules','competition.meta_title','competition.meta_description','competition.meta_keywords','date_format(competition.entries_deadline,\'%D \' \'%M \') as deadline'));
		$select->where('competition.competition_id = '.$competition_id);
		return $competitionModel->fetchRow($select);		        
	}				
}