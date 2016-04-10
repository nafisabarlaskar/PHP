<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_BlogSubscribe extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'blog_subscribe';

	//******************************************************//
	// Called ExamController - examScoreAction method //
	public function addSubsriber($email)
	{
		// create a new row
		$rowBlog = $this->createRow();
		if($rowBlog) {
			// update the row values
			$rowBlog->email = $email;
			$rowBlog->save();	
			return $rowBlog;
		} else {
			throw new Zend_Exception("Could not add new subscriber");
		}
	}
	
	public function editBlog($blog_id,$title,$meta_title,$meta_description,$meta_keywords,$article,$preview)
	{
		// create a new row
		$rowBlog = $this->find($blog_id)->current();		
		if($rowBlog) {
			// update the row values
			$rowBlog->title = $title;
			$rowBlog->meta_title = $meta_title;
			$rowBlog->meta_description = $meta_description;
			$rowBlog->meta_keywords = $meta_keywords;
			$rowBlog->article = $article;
			$rowBlog->preview = $preview;
			$rowBlog->save();	
			return $rowBlog;
		} else {
			throw new Zend_Exception("Could not edit new blog");
		}
	}
	
	public static function loadBlog($blog_id)
	{	
		$blogModel = new self();
		$select = $blogModel->select();
		$select->setIntegrityCheck(false);	
		$select->from('blog', 'blog.*');
		$select->where('blog.blog_id = '.$blog_id);
		return $blogModel->fetchRow($select);		        
	}
	
	public static function getBlogs()
	{
		$blogModel = new self();
		$select = $blogModel->select();
		$select->order('date_created desc');
		return $blogModel->fetchAll($select);
	}
	
				
}