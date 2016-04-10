<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_Faculty extends Zend_Db_Table_Abstract {
	/**
	* The default table name
	*/
	protected $_name = 'faculty';

	//******************************************************//
	// Called AdminController - addFacultyAction method //
	public function createFaculty($user_id, $highlights, $bio, $video_id)
	{
		// create a new row
		$rowFaculty = $this->createRow();
		if($rowFaculty) {
			// update the row values
			$rowFaculty->user_id = $user_id;
			$rowFaculty->highlights = $highlights;
			$rowFaculty->bio = $bio;			
			$rowFaculty->video_id = $video_id;
			$rowFaculty->save();
			//return the new user
			return $rowFaculty;
		} else {
			throw new Zend_Exception("Could not create faculty");
		}
	}
	
	//******************************************************//
	// Called AdminController - editFacultyAction method //
	public function updateFaculty($faculty_id,$user_id, $highlights, $bio, $video_id)
	{
		// fetch the user's row
		$rowFaculty = $this->find($faculty_id)->current();
		if($rowFaculty) {
			// update the row values
			// update the row values
			$rowFaculty->user_id = $user_id;
			$rowFaculty->highlights = $highlights;
			$rowFaculty->bio = $bio;			
			$rowFaculty->video_id = $video_id;
			$rowFaculty->save();
			//return the new user
			return $rowFaculty;
		}else{
		throw new Zend_Exception("Faculty update failed. User not found!");
		}
	}	
}