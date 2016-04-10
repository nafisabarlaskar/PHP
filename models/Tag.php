<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Tag extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'tags';

	//******************************************************//
	// Called CourseController - askQuestionAction method //
	public function addTag($tag_name)
	{
		// create a new row
		$rowTag = $this->createRow();
		if($rowTag) {
			// update the row values
			$rowTag->tag_name = $tag_name;
			$rowTag->save();	
			return $rowTag;
		} else {
			throw new Zend_Exception("Could not add new tag");
		}
	}	

	public static function getTags($tagName)
	{
		$tagModel = new self();
		$select = $tagModel->select();
		$select->from('tags');
		$select->where('tag_name LIKE ?', $tagName.'%');		
		return $tagModel->fetchAll($select);	
	}
	
	public static function getTag($tagName)
	{
		$tagModel = new self();
		$select = $tagModel->select();
		$select->from('tags');
		$select->where('tag_name = "'.$tagName.'"');		
		return $tagModel->fetchAll($select);	
	}
	
	public static function loadTag($tag_id)
	{	
		$tagModel = new self();
		$select = $tagModel->select();
		$select->setIntegrityCheck(false);	
		$select->from('tags', 'tags.*');
		$select->where('tags.tag_id = '.$tag_id);
		return $tagModel->fetchRow($select);		        
	}
}