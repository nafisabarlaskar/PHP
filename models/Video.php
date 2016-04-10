<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Video extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'video';

	//******************************************************//
	// Called AdminController - addTopicAction method //
	// Called AdminController - editTopicAction method //
	// Called AdminController - addFacultyAction method //
	public function addVideo($video_url)
	{
		// create a new row
		$rowVideo = $this->createRow();
		if($rowVideo) {
			// update the row values
			$rowVideo->video_url = $video_url;
			$rowVideo->save();	
			return $rowVideo;
		} else {
			throw new Zend_Exception("Could not add new Video!");
		}
	}			
	
	//******************************************************//
	// Called AdminController - editTopicAction method //
	// Called AdminController - editFacultyAction method //
	public function editVideo($video_id,$video_url)
	{
		$rowVideo = $this->find($video_id)->current();
				
		if($rowVideo) {
			// update the row values
			$rowVideo->video_url = $video_url;
			$rowVideo->save();	
			return $rowVideo;
		} else {
			throw new Zend_Exception("Could not edit Video!");
		}
	}
	public function uploadVideo($video_id,$video_url)
	{
		$rowVideo = $this->find($video_id)->current();
	
		if($rowVideo) {
			$rowVideo->video_url_hd = $video_url;
			$rowVideo->save();
			return $rowVideo;
		} else {
			throw new Zend_Exception("Could not update Video!");
		}
	}
	
	public function insertVideo($video_url_hd)
	{
		$rowVideo = $this->createRow();
		if($rowVideo) {
			// update the row values
			$rowVideo->video_url_hd = $video_url_hd;
			$rowVideo->save();
			return $rowVideo;
		} else {
			throw new Zend_Exception("Could not add new Video!");
		}
	}
	
	
	
	}
