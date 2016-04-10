<?php
require_once 'Zend/Db/Table/Abstract.php';
class Model_User extends Zend_Db_Table_Abstract {
/**
* The default table name
*/
protected $_name = 'user';


// Called UserController - registerAction method //
public function addManipalUser($email,$role,$organization_id,$referrer=null,$is_active)
{
	// create a new row
	$rowUser = $this->createRow();
	if($rowUser) {
		// update the row values
		$rowUser->email = $email;
		$rowUser->role_id = $role;
		$rowUser->organization_id = $organization_id;		
		$rowUser->referrer = $referrer;		
		$rowUser->is_active = $is_active;
		$rowUser->save();
		//return the new user
		return $rowUser;
	} else {
		throw new Zend_Exception("Could not create user!");
	}
}


// Called AdminController - addFacultyAction method //
// Called UserController - registerAction method //
public function createUser($email, $password, $role,$firstName=null, $lastName=null,$phone=null,$referrer=null,$is_active)
{
	// create a new row
	$rowUser = $this->createRow();
	if($rowUser) {
		// update the row values
		$rowUser->email = $email;
		$rowUser->referrer = $referrer;
		$rowUser->password = md5($password);
		$rowUser->role_id = $role;
		$rowUser->first_name = $firstName;
		$rowUser->last_name = $lastName;		
		$rowUser->phone = $phone;
		//$rowUser->verification_code = $verify_code;
		$rowUser->is_active = $is_active;
		$rowUser->save();
		//return the new user
		return $rowUser;
	} else {
		throw new Zend_Exception("Could not create user!");
	}
}
// Called AdminController - editFacultyAction method //
public function updateUser($id, $email, $firstName, $lastName, $phone,$degree,$college,$company,$designation,$linkedin)
{
	// fetch the user's row
	$rowUser = $this->find($id)->current();
	if($rowUser) {
		// update the row values
		$rowUser->email = $email;
		$rowUser->first_name = $firstName;
		$rowUser->last_name = $lastName;
		$rowUser->phone = $phone;
		$rowUser->degree = $degree;
		$rowUser->college = $college;
		$rowUser->company = $company;
		$rowUser->designation = $designation;
		$rowUser->linkedin = $linkedin;
		$rowUser->save();
		//return the updated user
		return $rowUser;
	}else{
	throw new Zend_Exception("User update failed. User not found!");
	}
}

// Called CompetitionController - registerAction method //
public function updateUserName($id, $firstName, $lastName)
{
	// fetch the user's row
	$rowUser = $this->find($id)->current();
	if($rowUser) {
		// update the row values
		$rowUser->first_name = $firstName;
		$rowUser->last_name = $lastName;
		$rowUser->save();
		//return the updated user
		return $rowUser;
	}else{
	throw new Zend_Exception("User name update failed. User not found!");
	}
}


// Called CourseController - codCourseAction method //
public function updateUserPhone($id, $phone)
{
	// fetch the user's row
	$rowUser = $this->find($id)->current();
	if($rowUser) {
		// update the row values
		$rowUser->phone = $phone;
		$rowUser->save();
		//return the updated user
		return $rowUser;
	}else{
	throw new Zend_Exception("User phone failed. User not found!");
	}
}

// Called UserController - sendActivationAction method //
public function updateVerificationCode($user_id, $verification_code)
{
	// fetch the user's row
	$rowUser = $this->find($user_id)->current();
	if($rowUser) {
		// update the row values
		$rowUser->verification_code = $verification_code;
		$rowUser->save();
		//return the updated user
		return $rowUser;
	}else{
	throw new Zend_Exception("User phone failed. User not found!");
	}
}

// Called UserController - loginAction method //
public function updateLastLogin($id)
{
	// fetch the user's row
	$rowUser = $this->find($id)->current();
	if($rowUser) {
		// update the row values
		$rowUser->last_login = new Zend_Db_Expr("NOW()");
		$rowUser->save();
		//return the updated user
		return $rowUser;
	}else{
	throw new Zend_Exception("User last_login failed. User not found!");
	}
}

// Called CourseController freeCourseAction method
public function loadUserByEmail($email)
{	
	$userModel = new self();
	$select = $userModel->select();
	$select->from('user', 'user.*');
	$select->where('user.email = "'.$email.'"');
	return $userModel->fetchRow($select);        
}


// Called CourseController freeCourseAction method
// Called UserController - log_user method //
public function loadUserProfileByEmail($email)
{	
	$userModel = new self();
	$select = $userModel->select();
	$select->setIntegrityCheck(false);
	$select->from('user', 'user.*');
	$select->joinLeft('role', 'user.role_id = role.role_id', array('role_name'=>'role_name'));
	$select->where('user.email = "'.$email.'"');
	return $userModel->fetchRow($select);        
}

public function displayStudents($batch_id,$course_id)
{
	$userModel = new self();
	$select = $userModel->select();
	$select->setIntegrityCheck(false);
	$select->from(array('u'=>'user'),array('referrer','first_name','last_name','email'));
	$select->join(array('e'=>'enrollment'), 'e.user_id = u.user_id',null);
	$select->where('e.course_id = '.$course_id);
	$select->where('e.batch_id = '.$batch_id);
	$select->where('e.payment_received = "Y"');
	$select->where('e.payment_amount > 0 ');
	return $userModel->fetchAll($select);
}

public function displayReferrer($course_id,$email)
{
	$userModel = new self();
	$select = $userModel->select();
	$select->setIntegrityCheck(false);
	$select->from(array('u'=>'user'),array('referrer'));
	$select->join(array('e'=>'enrollment'), 'e.user_id = u.user_id',null);
	$select->where('e.course_id = '.$course_id);
	$select->where('u.email = "'.$email.'"');
	$select->where('e.payment_received = "Y"');
	$select->where('e.payment_amount > 0 ');
	return $userModel->fetchRow($select);
}



// Called UserController - billingAction method //
public function updateUserAddress($id, $address_id)
{
	// fetch the user's row
	$rowUser = $this->find($id)->current();
	if($rowUser) {
		// update the row values
		$rowUser->address_id = $address_id;
		$rowUser->save();
		//return the updated user
		return $rowUser;
	}else{
	throw new Zend_Exception("User update failed. Could not find user to update address_id!");
	}
}

// Called UserController - activateAction method //
public function activateAccount($id)
{
	// fetch the user's row
	$rowUser = $this->find($id)->current();
	if($rowUser) {
		// update the row values
		$rowUser->is_active = 'Y';
		$rowUser->verification_code = null;
		$rowUser->save();
		//return the updated user
		return $rowUser;
	}else{
	throw new Zend_Exception("User activation failed. Could not find user to activate the account!");
	}
}


// Called AdminController - addCourseAction method //
// Called AdminController - updateCourseAction method //
// Called UserController - listFacultyAction method //
public static function getFaculty()
{
	$userModel = new self();
	$select = $userModel->select();
	$select->setIntegrityCheck(false);
	$select->from('user',array('user_id','first_name','last_name'));
	$select->join('faculty', 'user.user_id = faculty.user_id');
	//$select->joinLeft('video', 'faculty.video_id = video.video_id');
	$select->where('role_id = 3');
	$select->order('user.user_id desc');
	return $userModel->fetchAll($select);	
}

// Called UserController - log_user method //
public function loadUserProfile($user_id)
{	
	$userModel = new self();
	$select = $userModel->select();
	$select->setIntegrityCheck(false);
	$select->from('user', 'user.*');
	$select->joinLeft('role', 'user.role_id = role.role_id', array('role_name'=>'role_name'));
	$select->where('user.user_id = '.$user_id);
	return $userModel->fetchRow($select);        
}

// Called ReportController - report method //
public function adminCourses($user_id)
{	
	$userModel = new self();
	$select = $userModel->select();
	$select->setIntegrityCheck(false);
	$select->from('user', 'user.*');
	$select->joinLeft('admin_course', 'user.user_id = admin_course.admin_id',array('course_id'));
	$select->joinLeft('course', 'admin_course.course_id = course.course_id',array('title'));
	$select->where('user.user_id = '.$user_id);
	return $userModel->fetchAll($select);        
}

// Called AdminController - editFacultyAction method //
// Called UserController - viewFacultyAction method //
public function loadFacultyProfile($user_id)
{		
	$userModel = new self();
	$select = $userModel->select();
	$select->setIntegrityCheck(false);
	$select->from('user','user.*');
	$select->joinLeft('faculty', 'faculty.user_id = user.user_id');
	//$select->joinLeft('video', 'faculty.video_id = video.video_id');
	$select->where('user.user_id = '.$user_id);
	return $userModel->fetchAll($select);		        
}

// Called AdminController - passwordAction method //
// Called in UserController - changePasswordAction
public function updatePassword($id, $password)
{
	// fetch the user's row
	$rowUser = $this->find($id)->current();
	if($rowUser) {
	//update the password
	$rowUser->password = md5($password);
	$rowUser->save();
	}else{
	throw new Zend_Exception("Password update failed. User not found!");
	}
}


public static function getUsers()
{
	$userModel = new self();
	$select = $userModel->select();
	$select->order(array('last_name', 'first_name'));
	return $userModel->fetchAll($select);
}

public static function getBatchMates($course_id,$batch_id,$user_id)
{
	$userModel = new self();
	$select = $userModel->select();
	$select->setIntegrityCheck(false);
	$select->from(array('u'=>'user'),array('user_id','first_name','last_name','email'));
	$select->join(array('e'=>'enrollment'), 'e.user_id = u.user_id',null);
	$select->where('e.course_id = '.$course_id);
	$select->where('e.batch_id = '.$batch_id);
	$select->where('e.payment_received = "Y"');
	$select->where('e.user_id != '.$user_id);
	return $userModel->fetchAll($select);
	//return $select->__toString();
}

public function deleteUser($id)
{
	// fetch the user's row
	$rowUser = $this->find($id)->current();
	if($rowUser) {
	$rowUser->delete();
	}else{
	throw new Zend_Exception("Could not delete user. User not found!");
	}
}



}