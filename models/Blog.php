<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Blog extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'blog';

	//******************************************************//
	// Called ExamController - examScoreAction method //
	public function addBlog($title,$meta_title,$meta_description,$meta_keywords,$article,$preview,$user_id=null)
	{
		// create a new row
		$rowBlog = $this->createRow();
		if($rowBlog) {
			// update the row values
			$rowBlog->title = $title;
			$rowBlog->meta_title = $meta_title;
			$rowBlog->meta_description = $meta_description;
			$rowBlog->meta_keywords = $meta_keywords;
			$rowBlog->article = $article;
			$rowBlog->preview = $preview;
			$rowBlog->user_id = $user_id;
			$rowBlog->save();	
			return $rowBlog;
		} else {
			throw new Zend_Exception("Could not add new blog");
		}
	}
	
	public function editBlog($blog_id,$title,$meta_title,$meta_description,$meta_keywords,$article,$preview,$user_id=null)
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
			$rowBlog->user_id = $user_id;
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
		$select->from(array('bg' => 'blog'), 'bg.*');
		$select->joinleft(array('u' =>'user'), 'bg.user_id = u.user_id',array('u.first_name','u.last_name'));
		$select->joinleft(array('up' =>'user_picture'), 'up.user_id = u.user_id',array('picture_1'));
		$select->where('bg.blog_id = '.$blog_id);
		return $blogModel->fetchRow($select);		        
	}
	
	public static function getBlogs($count=null)
	{
		$blogModel = new self();
		$select = $blogModel->select();
		$select->setIntegrityCheck(false);
		$select->from(array('bg' => 'blog'), 'bg.*');
		$select->joinleft(array('u' =>'user'), 'bg.user_id = u.user_id',array('u.first_name','u.last_name','u.email'));
		$select->joinleft(array('up' =>'user_picture'), 'up.user_id = u.user_id',array('picture_1'));
		$select->order('date_created desc');
		if($count!=null)
			$select->limit($count);
		return $blogModel->fetchAll($select);
	}
	
	public static function getRecentBlogs($blog_id,$count)
	{
		$blogModel = new self();
		$select = $blogModel->select();
		$select->where('blog.blog_id != '.$blog_id);
		$select->order('date_created desc');
		if($count!=null)
			$select->limit($count);
		return $blogModel->fetchAll($select);
	}
	
	public static function getMediaBlogs($blog_ids)
	{
		$blogModel = new self();
		$select = $blogModel->select();
		$select->where('blog.blog_id in( '.$blog_ids.')');
		$select->order('date_created desc');
		return $blogModel->fetchAll($select);
	}
	
				
}