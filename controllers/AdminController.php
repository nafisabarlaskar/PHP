<?php

class AdminController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    }
    
    //*************************************************************//
    //************COURSE FUNCTIONS ********************************//
    //*************************************************************//
	// CALLED FROM ADMIN DASHBOARD /admin/index when admin clicks on Create new Course
    public function addCourseAction()
    {
    	if ($this->_request->isPost()) {	
			
			$dbAdapter = Zend_Db_Table::getDefaultAdapter();
    		$dbAdapter->beginTransaction();
    		try {    			
    			//first create new course
				$courseModel = new Model_Course();
				$rowCourse = $courseModel->createCourse(				
				$this->_getParam('title'),
				$this->_getParam('learning_mode'),
				$this->_getParam('placement_assistance'),				
				$this->_getParam('duration'),				
				$this->_getParam('fees'),
				$this->_getParam('job_types'),
				$this->_getParam('benefits'),
				$this->_getParam('faq'),
				$this->_getParam('testimonials')				
				);
					
				//then add faculty
				$faculty_ids = $this->_getParam('faculty_ids');				
				$courseFacultyModel = new Model_CourseFaculty();
				$courseFacultyModel->addCourseFaculty($rowCourse->course_id,$faculty_ids);
	    		
				$dbAdapter->commit();
    		} catch(Exception $e) {
    			$dbAdapter->rollBack();
    			throw new Exception($e);
    		}
    		return $this->_redirect('/course/list-courses');			
		}
		else
		{
			$faculty_list = Model_User::getFaculty();
			$this->view->faculty = $faculty_list;			
		}
    }
    
    
    // CALLED in /course/admin-view to update course
	public function updateCourseAction()
    {
    	try{
		$courseModel = new Model_Course();
		if ($this->_request->isPost()) {	

			$dbAdapter = Zend_Db_Table::getDefaultAdapter();
    		$dbAdapter->beginTransaction();
    		try { 
    			//first update course	    			
				$courseModel->updateCourse(
					$this->_getParam('course_id'),
					$this->_getParam('title'),
					$this->_getParam('learning_mode'),
					$this->_getParam('placement_assistance'),
					$this->_getParam('duration'),				
					$this->_getParam('fees'),
					$this->_getParam('job_types'),
					$this->_getParam('benefits'),
					$this->_getParam('faq'),
					$this->_getParam('testimonials')
				);
				
				//then delete faculty id in course_faculty and then add them fresh
				$courseFacultyModel = new Model_CourseFaculty();
				$courseFacultyModel->deleteCourseFaculty($this->_getParam('course_id'));
				
				//then add faculty
				$faculty_ids = $this->_getParam('faculty_ids');
				$courseFacultyModel->addCourseFaculty($this->_getParam('course_id'),$faculty_ids);
				
				
	    		$dbAdapter->commit();
    		} catch(Exception $e) {
    			$dbAdapter->rollBack();
    			throw new Exception($e);
    		}
			return $this->_redirect('/admin/view-course/course_id/'.$this->_getParam('course_id'));		
		}
    	else {
			$faculty_list = Model_User::getFaculty();
			
			$course = Model_Course::loadCourse($this->_getParam('course_id'));
			
			//get faculty
			$faculty_ids = Model_CourseFaculty::getFaculty($this->_getParam('course_id'));
			$faculty_id_array=null;
			foreach($faculty_ids->toArray() as $id)
				$faculty_id_array[] = $id['faculty_id'];

				
			$this->view->faculty_id_array=$faculty_id_array;
			$this->view->faculty = $faculty_list;
			$this->view->course = $course; // you can use both course->title or course['title'] in the view			
		}	
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in updateAction course id='.$this->_getParam('course_id').' in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}	
    }
    
    // CALLED in /course/list-courses to delete course
	public function deleteCourseAction()
    {
		$course_id = $this->_request->getParam('course_id');
		$courseModel = new Model_Course();
		$courseModel->deleteCourse($course_id);
		return $this->_redirect('/user/list-courses');
    }
    
	// Admin clicks on view courses on /course/list-courses page
    public function viewCourseAction()
    {		
		$course = Model_Course::loadCourse($this->_getParam('course_id'));
		
		//get faculty for this course
		$faculty = Model_CourseFaculty::getFacultyByCourseId($this->_getParam('course_id'));
		$this->view->faculty = $faculty;
		
		$chapters = Model_Topic::getChapters($this->_getParam('course_id'));		
	
		//now arrange chapters by topic/subtopic
		$chapter_array = null;
		$topic_array = null;
		foreach($chapters as $chapter) {
			if($chapter['parent_topic_id']==0) {
				//echo 'chapter name = '.$chapter->topic_name;
				$topic_array[$chapter['topic_id']] = array();
				$chapter_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order']);
			}
			else {
				$temp_array = $topic_array[$chapter['parent_topic_id']];
				$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order'],'video_url'=>$chapter['video_url'],'notes'=>$chapter['notes']);
				$topic_array[$chapter['parent_topic_id']] = $temp_array;
			}
		}
		
		$this->view->course = $course;
		//$this->view->chapters = $chapters;
		if($chapter_array==null || count($chapter_array>0))
			$this->view->chapters = $chapter_array;
		if($topic_array==null || count($topic_array>0))
			$this->view->topics = $topic_array;
    }
    
    
    
    //*************************************************************//
    //************END COURSE FUNCTIONS ********************************//
    //*************************************************************//
    
    
    //*************************************************************//
    //************CHAPTER AND TOPIC FUNCTIONS**********************//
    //*************************************************************//
    
    // CALLED in /admin/view-course to add chapter
    public function addChapterAction()
    {
    	if ($this->_request->isPost()) {			
				$topicModel = new Model_Topic();
				$topicModel->addTopic(				
					$this->_getParam('course_id'),
					$this->_getParam('chapter_name')				
				);							
		}
		return $this->_redirect('/admin/view-course/course_id/'.$this->_getParam('course_id'));
    }
    
    // CALLED in /admin/view-course to edit chapter
	public function editChapterAction()
    {
		if ($this->_request->isPost()) 
		{
			$topicModel = new Model_Topic();
			$topicModel->editChapter(
				$this->_getParam('topic_id'),				
				$this->_getParam('topic_name'),
				$this->_getParam('topic_order')
			);			
		}
		return $this->_redirect('/admin/view-course/course_id/'.$this->_getParam('course_id'));
    }
    
    // CALLED in /admin/add-topic to add a new topic to chapter
    // CALLED in /admin/view-course to add a new topic to chapter
	public function addTopicAction()
    {
    	if ($this->_request->isPost()) {

    		$dbAdapter = Zend_Db_Table::getDefaultAdapter();
    		$dbAdapter->beginTransaction();
    		try {
	    		//first insert video
	    		$videoModel = new Model_Video();
	    		$rowVideo = $videoModel->addVideo($this->_getParam('video_url'));
	    		
				$topicModel = new Model_Topic();
				$rowTopic = $topicModel->addTopic(				
					$this->_getParam('course_id'),				
					$this->_getParam('topic_name'),
					$this->_getParam('notes'),					
					$this->_getParam('chapter_id')				
				);					
				
				$videoTopicModel = new Model_TopicVideo();
	    		$videoTopicModel->addTopicVideo($rowTopic->topic_id,$rowVideo->video_id,$this->_getParam('is_sample'));
	    	    			
				$dbAdapter->commit();
    		} catch(Exception $e) {
    			$dbAdapter->rollBack();
    			throw new Exception($e);
    		}
    		return $this->_redirect('/admin/view-course/course_id/'.$this->_getParam('course_id'));
		}
		else
		{
			
			$this->view->course_id = $this->_getParam('course_id');
			$this->view->chapter_id = $this->_getParam('chapter_id');
		}				
    }
    
    // CALLED in /admin/view-course to edit topic
    // CALLED in /admin/edit-topic to edit topic
    public function editTopicAction()
    {
    	try {
    	if ($this->_request->isPost()) {

    		$dbAdapter = Zend_Db_Table::getDefaultAdapter();
    		$dbAdapter->beginTransaction();
    		try {
	    		//first update video
	    		$videoModel = new Model_Video();
	    		if($this->_getParam('video_id')!=null) {	    			
	    			$rowVideo = $videoModel->editVideo($this->_getParam('video_id'),$this->_getParam('video_url'));
	    		}
	    		else {
	    			//first insert video	    		
	    			$rowVideo = $videoModel->addVideo($this->_getParam('video_url'));	    			
	    			//then insert the video in topic_video table
	    			$videoTopicModel = new Model_TopicVideo();
	    			$videoTopicModel->addTopicVideo($this->_getParam('topic_id'),$rowVideo->video_id,$this->_getParam('is_sample'));
	    		}	    		
	    		
				$topicModel = new Model_Topic();
				$rowTopic = $topicModel->editTopic(				
					$this->_getParam('topic_id'),				
					$this->_getParam('topic_name'),
					$this->_getParam('notes'),							
					$this->_getParam('topic_order')					
				);					
				
				//before editing check if video exists - else add
				$topicVideoModel = new Model_TopicVideo();
				/*if($this->_getParam('topic_video_id') == null) {
					$rowTopicVideo = $topicVideoModel->addTopicVideo(
						$this->_getParam('topic_id'),	
						$rowVideo->video_id,
						$this->_getParam('is_sample')
					);
				}*/
				if($this->_getParam('topic_video_id') != null) {
				$rowTopicVideo = $topicVideoModel->editTopicVideo(				
					$this->_getParam('topic_video_id'),				
					$this->_getParam('is_sample')
				);
				}
				$dbAdapter->commit();
    		} catch(Exception $e) {
    			$dbAdapter->rollBack();
    			throw new Exception($e);
    		}
    		return $this->_redirect('/admin/view-course/course_id/'.$this->_getParam('course_id'));
		}
    	else
		{
			echo "ok";
			$topic = Model_Topic::loadTopic($this->_getParam('topic_id'));			
			//$this->view->topic = $topic[0];						
			$this->view->topic = $topic;
		}	
    	}  catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in editTopicAction method AdminController'.$e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}			
    }
    
    public function displayTopicAction()
    {
    	try {
    		$batch_id = $this->_getParam('batch_id');
    		$course_id = $this->_getParam('course_id');
    
    		$videos = Model_Topic::checkVideo($batch_id,$course_id);
    		
    		$batches = Model_Batch::getBatch($batch_id);
    		$start_date = $batches->starting_day;
    		$start_month = $batches->starting_month;
    		$this->view->batches = $batches;
    		
    		$this->view->videos = $videos;
    		$this->view->batch_id = $this->_getParam('batch_id');
    		$this->view->course_id = $this->_getParam('course_id');
    		$this->view->topic_array = $topic_array;
    		$this->view->start_date = $start_date;
    		$this->view->start_month = $start_month;
    		
    		$this->view->video = $videos->video_url_hd;
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in displayTopicAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function showTopicsAction()
    {
    	try {
    		$batch_id = $this->_getParam('batch_id');
    		$course_id = $this->_getParam('course_id');
    		$topics = Model_Topic::displayTopics($batch_id,$course_id);
    		$topic_array=array();
    		foreach($topics as $topic){
    			$topic_array[]=array('topic_name'=>$topic->topic_name,'topic_id'=>$topic->topic_id,'parent_topic_id'=>$topic->parent_topic_id);
    			
    		}
    		$batches = Model_Batch::getBatch($batch_id);
    		$start_date = $batches->starting_day;
    		$start_month = $batches->starting_month;
    		$this->view->batches = $batches;
    		 
    		$this->view->batch_id = $this->_getParam('batch_id');
    		$this->view->course_id = $this->_getParam('course_id');
    		$this->view->topic_array = $topic_array;
    		$this->view->start_date = $start_date;
    		$this->view->start_month = $start_month;
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in showTopicsAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function displayStudentsAction()
    {
    	try {
    		$batch_id = $this->_getParam('batch_id');
    		$course_id = $this->_getParam('course_id');
    		$students = Model_User::displayStudents($batch_id,$course_id); 
    		$this->view->students = $students;
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in displayStudentsAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    public function displayBatchesAction()
    {
    	try {
    		$course_id = $this->_getParam('course_id');
    		Zend_Registry::get('logger')->err('error125='.$course_id);
    		$batches = Model_Batch::getAdminBatches($course_id);
    		$this->view->batches = $batches;
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in displayBatchesAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function viewBatchesAction()
    {
    	try {
    		$course_id = $this->_getParam('course_id');
    		Zend_Registry::get('logger')->err('error125='.$course_id);
    		$batches = Model_Batch::getAdminBatches($course_id);
    		$this->view->batches = $batches;
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in viewBatchesAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function showBatchesAction()
    {
    	try {
    		$course_id = $this->_getParam('course_id');
    		Zend_Registry::get('logger')->err('error125='.$course_id);
    		$batches = Model_Batch::getAdminBatches($course_id);
    		$this->view->batches = $batches;
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in showBatchesAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function uploadVideoAction()
    {
    	try{
    		$video_id = $this->_getParam('video_id');
    		$topic_id = $this->_getParam('topic_id');
    		$Video = Model_TopicVideo::getVideo($topic_id,$video_id);
    		$this->view->video_url = $Video->video_url_hd;
    		
    		if($this->_request->isPost())
    		{
    			$video_id = $this->_getParam('video_id');
    			$topic_id = $this->_getParam('topic_id');
    			$batch_id = $this->_getParam('batch_id');
    			$course_id = $this->_getParam('course_id');
    			$video_url_hd = $this->_getParam('video_url_hd');
    			$videoUrl = new Model_Video();
    			 
    			$rowVideo = $videoUrl->uploadVideo($video_id,$this->_getParam('video_url_hd'));
    			return $this->_redirect('/admin/display-topic/course_id/'.$this->_getParam('course_id').'/batch_id/'.$this->_getParam('batch_id').'/');
    			
    		}
    		
    		$this->view->topic_name = $this->_getParam('topic_name');
    		$this->view->batch_id = $this->_getParam('batch_id');
    		$this->view->course_id = $this->_getParam('course_id');
    		$this->view->video_url_hd = $video_url_hd;
    		$this->view->topic_id = $this->_getParam('topic_id');
    		$this->view->video_id = $this->_getParam('video_id');
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in uploadVideoAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    	 
    }
    
    public function insertVideoAction()
    {
    	try{
    		if($this->_request->isPost())
    		{
    			$video_url_hd = $this->_getParam('video_url_hd');
    			$videoModel = new Model_Video();
    			$rowVideo = $videoModel->insertVideo($video_url_hd);
    			$topic_id=$this->_getParam('topic_id');
    			$topicVideoModel = new Model_TopicVideo();
    			$rowTopicVideo = $topicVideoModel->addVideoId(
    					$this->_getParam('topic_id'),
    					$rowVideo->video_id);
    			return $this->_redirect('/admin/show-topics/course_id/'.$this->_getParam('course_id').'/batch_id/'.$this->_getParam('batch_id').'/');
    		}
    		$this->view->video_url_hd = $video_url_hd;
    		$this->view->topic_id=$this->_getParam('topic_id');
    		$this->view->batch_id = $this->_getParam('batch_id');
    		$this->view->course_id = $this->_getParam('course_id');
    		$this->view->topic_name = $this->_getParam('topic_name');
    
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in insertVideoAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    
    }
    
    public function discountCodeAction()
    {
    	try{
    		if($this->_request->isPost())
    		{
		    	$discountModel = new Model_Discount();
		    	$rowUser=$discountModel->createDiscountCode(
		    			$this->_getParam('user_id'),
		    			$this->_getParam('course_id'),
		    			$this->_getParam('discount_code'),
		    			$this->_getParam('discount_percentage'),
		    			$this->_getParam('one_time')
    			);
	    		$this->view->message = "Discount Code created successfully!";
    		}
    	} catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in discountCodeAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function uploadCourseAction()
    {
    	try {
    		$courses = Model_Course::uploadCourse();
    		$this->view->courses = $courses;
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in uploadCoursesAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function showCourseAction()
    {
    	try {
    		$courses = Model_Course::uploadCourse();
    		$this->view->courses = $courses;
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in uploadCoursesAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function displayCourseAction()
    {
    	try {
    		$courses = Model_Course::uploadCourse();
    		$this->view->courses = $courses;
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in uploadCoursesAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    public function enrolledCoursesAction()
    {
    	try {
    		$email = $this->_getParam('email');
    		$courses = Model_Enrollment::getCoursesByStudentEmail($email);
    		foreach($courses as $course)
    		{
    			if ($course->batch_id != null){
    				$batches = Model_Batch::getAdminBatches($course->course_id);
    				$this->view->batches = $batches;
    			}
    			$referrers = Model_User::displayReferrer($course->course_id,$email);
    			$this->view->referrer = $referrers->referrer;
    		}
    		$this->view->courses = $courses;
    
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in enrolledCoursesAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function getCoursesAction()
    {
    	try {
    		$courses = Model_Course::showCourses();
    		$this->view->courses = $courses;
    
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in getCoursesAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function viewDiscountAction()
    {
    	try {
    		$course_id = $this->_getParam('course_id');
    		$discounts = Model_Discount::showDiscount($course_id);
    		$this->view->discounts = $discounts;
    		$this->view->course_id = $this->_getParam('course_id') ;
    		$this->view->title = $this->_getParam('title');
    	
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in viewDiscountAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    public function updateDiscountAction()
    {
    	try {
	    		$this->view->user_id = $this->_getParam('user_id');
	    		$this->view->course_id = $this->_getParam('course_id') ;
	    		$this->view->discount_code = $this->_getParam('discount_code');
	    		$this->view->discount_percentage = $this->_getParam('discount_percentage');
	    		$this->view->one_time = $this->_getParam('one_time');
	    		$this->view->discount_id = $this->_getParam('discount_id');
	    		$this->view->title = $this->_getParam('title');
	    		$this->view->is_active = $this->_getParam('is_active');
	    		
	    		if($this->_request->isPost())
	    		{
	    			$discountModel = new Model_Discount();
	    			$rowUser=$discountModel->updateDiscountCode(
	    					$this->_getParam('discount_id'),
	    					$this->_getParam('user_id'),
	    					$this->_getParam('course_id'),
	    					$this->_getParam('discount_code'),
	    					$this->_getParam('discount_percentage'),
	    					$this->_getParam('one_time'),
	    					$this->_getParam('is_active')
	    			);
	     			return $this->_redirect('/admin/get-courses/');
	    		}
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in updateDiscountAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    
    
    public function updateBatchAction()
    {
    	try{
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();
    
    		$updateEnrollment = new Model_Enrollment();
    		$newBatch = $updateEnrollment->updateBatch(
    				$this->_getParam('enrollment_id'),
    				$this->_getParam('batch_id'));
    		$arr = array ('success'=>'ok');
    		echo json_encode($arr);
    
    	}
    	catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in updateBatchAction in AdminController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    public function viewCoursesAction()
    {
    	 
    }
    
    
    // CALLED in /admin/view-course to delete a chapter
    public function deleteChapterAction()
    {
		$topic_id = $this->_request->getParam('topic_id');
		$topicModel = new Model_Topic();
		$topicModel->deleteChapter($topic_id);
		return $this->_redirect('/admin/view-course/course_id/'.$this->_getParam('course_id'));
    }
    
    // CALLED in /admin/view-course to delete a topic
	public function deleteTopicAction()
    {
		$topic_id = $this->_request->getParam('topic_id');
		$topicModel = new Model_Topic();
		$topicModel->deleteTopic($topic_id);
		return $this->_redirect('/admin/view-course/course_id/'.$this->_getParam('course_id'));
    }
    
    //*************************************************************//
    //************USER / FACULTY FUNCTIONS**********************//
    //*************************************************************//
    
    
	// THIS IS CALLED FROM THE ADMIN DASHBOARD WHEN ADMIN CLICKS ON ADD NEW FACULTY
	// CALLED in /admin/index to add a new faculty
	// CALLED in /admin/add-faculty to add a new faculty
    public function addFacultyAction()
    {    	
    	if ($this->_request->isPost()) {
    		$dbAdapter = Zend_Db_Table::getDefaultAdapter();
    		$dbAdapter->beginTransaction();
    		try {
    			
    			//first select role_id from role table where role=faculty
    			$role = Model_Role::getRole('faculty');
    			$role = $role->toArray();
    			
    			//first add the user				
	    		$userModel = new Model_User();				
				$userRow = $userModel->createUser(				
					$this->_getParam('email'),
					$this->_getParam('password'),
					$role[0]['role_id'],
					$this->_getParam('first_name'),
					$this->_getParam('last_name'),					
					$this->_getParam('phone')
				);
				
				//second add introduction video
	    		$videoModel = new Model_Video();
	    		$rowVideo = $videoModel->addVideo($this->_getParam('intro_video'));
	    		
				//then add the faculty details
				
				$facultyModel = new Model_Faculty();				
				$rowFaculty = $facultyModel->createFaculty(
					$userRow->user_id,				
					$this->_getParam('highlights'),
					$this->_getParam('bio'),
					$rowVideo->video_id									
				);
				
		    	// save the user image
				$upload = new Zend_File_Transfer_Adapter_Http();
				//$upload->addValidator('Extension', false, array('jpg', 'png', 'gif'));
				$fileInfo = $upload->getFileInfo();
				// if submitted a file
				if( $fileInfo['photo']['tmp_name'] ){
					if( $upload->isValid() ){
						$this->savePicture($fileInfo['photo']['tmp_name'],$userRow->user_id);
					}
				}
				$dbAdapter->commit();
    		} catch(Exception $e) {
    			$dbAdapter->rollBack();
    			//throw new Exception($e);
    			print_r($e);
    			echo 'EXCEPTION'.$e->getTraceAsString();
    			throw new Exception($e);
    		}
			//return $this->_redirect('/admin');
			return $this->_redirect('/user/list-faculty');			
		}		
    }
    
    // THIS IS CALLED FROM THE ADMIN DASHBOARD WHEN ADMIN CLICKS ON LIST FACULTY AND THEN EDIT NEXT TO FACULTY NAME
    public function editFacultyAction()
    {
    	if ($this->_request->isPost()) {
    		$dbAdapter = Zend_Db_Table::getDefaultAdapter();
    		$dbAdapter->beginTransaction();
    		try {
    			//first edit the user				
	    		$userModel = new Model_User();				
				$userRow = $userModel->updateUser(				
					$this->_getParam('user_id'),	
					$this->_getParam('email'),					
					$this->_getParam('first_name'),
					$this->_getParam('last_name'),
					$this->_getParam('phone')
				);
				
				//second modify introduction video
	    		$videoModel = new Model_Video();
	    		$rowVideo = $videoModel->editVideo($this->_getParam('video_id'),$this->_getParam('intro_video'));
	    		
				//then modify the faculty details				
				$facultyModel = new Model_Faculty();				
				$rowFaculty = $facultyModel->updateFaculty(
					$this->_getParam('faculty_id'),
					$this->_getParam('user_id'),				
					$this->_getParam('highlights'),
					$this->_getParam('bio'),
					$this->_getParam('video_id')									
				);
				
		    	// save the user image
				$upload = new Zend_File_Transfer_Adapter_Http();
				$upload->addValidator('Extension', false, array('jpg', 'png', 'gif'));
				$fileInfo = $upload->getFileInfo();
				// if submitted a file
				if( $fileInfo['photo']['tmp_name'] ){
					if( $upload->isValid() ){
						$this->savePicture($fileInfo['photo']['tmp_name'],$this->_getParam('user_id'));
					}
				}
				$dbAdapter->commit();
    		} catch(Exception $e) {
    			$dbAdapter->rollBack();
    			throw new Exception($e);
    		}
    		return $this->_redirect('/user/list-faculty');
		}
    	else
		{
			$userModel = new Model_User();
			$user = $userModel->loadFacultyProfile($this->_getParam('user_id'));			
			$this->view->user = $user[0];						
		}				
    }    

	// CURRENTLY BEING CALLED IN LIST FACULTY ACTION TO UPDATE FACULTYS PASSWORD
    // SHOULD BE USED FOR USER CHANGING HIS PASSWORD TOO
    public function passwordAction()
    {
		$userModel = new Model_User();
		if ($this->_request->isPost()) 
		{			
			$userModel->updatePassword(
				$this->_getParam('user_id'),
				$this->_getParam('password')
			);
			return $this->_forward('list-faculty');		
		} else {
			$id = $this->_request->getParam('user_id');
			$currentUser = $userModel->find($id)->current();			
			$this->view->user =  $currentUser;
		}
		
    }
    
// FOR SAVING FACULTY PICTURE - CALLED IN ADD-FACULTY And EDIT-FACULTY method
	public function savePicture($picture,$faculty_id)
    {
    	require 'ResizeImage.php';
    	$image1 = new ResizeImage();
    	$image1->source = $picture;

    	// get picture 1 (50px)
    	$image1->width = 60;
    	$image1->height = 60;    	
    	$imageString_1 = $image1->Resize();

    	// get picture 2 (100px)
    	$image2 = new ResizeImage();
    	$image2->source = $picture;
    	$image2->width = 200;
    	$image2->height = 200;
    	$imageString_2 = $image2->Resize();

		// check if user has a picture
		$userPictureModel = new Model_UserPicture();
		if( !$userPictureModel->find($faculty_id)->count() )
			$userPicture = $userPictureModel->createRow();
		else
			 $userPicture = $userPictureModel->find($faculty_id)->current();

		$userPicture->user_id = $faculty_id;
		$userPicture->picture_1 = $imageString_1;
		$userPicture->picture_2 = $imageString_2;
		$userPicture->save();
    }
    
    
    
    
    


}

