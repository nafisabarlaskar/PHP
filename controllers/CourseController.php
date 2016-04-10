<?php

class CourseController extends Zend_Controller_Action
{
	private $_user_id;
	
	public function index()
    {
        return $this->_redirect('/');
    }
    
    public function downloadAction()
    {
    	try {
    		Zend_Registry::get('logger')->err('Inside download action');
    		$this->_helper->layout->disableLayout();
    		$filename = $this->_getParam('filename');
    		$this->view->filename=$filename;
    		Zend_Registry::get('logger')->err('done Inside download action');
    	} catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in downloadAction filename='.$filename.' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    	 
    }
    
    public function init()
    {
        /* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }
    
    public function courseNotesAction()
    {
	    //CHECK IF THE USER IS STILL ENROLLED FOR THIS COURSE OTHERWISE REDIRECT HIM TO VIEW PAGE
		$currentCourses = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);
		//CHECK IF THE USER IS FACULTY FOR THIS COURSE OTHERWISE REDIRECT HIM TO VIEW PAGE		
		$isFaculty = Model_CourseFaculty::isCourseFaculty($this->_getParam('course_id'),$this->_user_id);
		//echo '*************************is faculty===='.$currentCourses->payment_received;
		if(count($isFaculty)==0) {
			if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
				return $this->_redirect('/course/view/course_id/'.$this->_getParam('course_id'));
		}
    	if($this->_getParam('course_id')==1){
    		$course = Model_Course::loadCourse($this->_getParam('course_id'));
    		$this->view->course = $course;    		
    	}
    	else
    		$this->_redirect('/');			
    }
    
    public function playVideoAction()
    {
    	//$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	
    	$topic_id = $this->_getParam('topic_id');
    	$topic = Model_Topic::loadTopic($topic_id);
    	
    	$topicUserModel = new Model_TopicUser();	    
    	//check if user is logged in
    	if($this->_user_id !=null) {
    		if($topic->is_active=='Y') {
    		//check if its sample video
    		if($topic->is_sample=='N') {
    			//check if user has registered for the course
    			$currentCourses=Model_Enrollment::isStudentEnrolled($topic->course_id,$this->_user_id);
    			if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
    				return $this->_redirect('/course-details/'.$topic->course_id);
    		}
    		$row_topicUser = $topicUserModel->addTopicUser($topic_id,$this->_user_id);
    		} else
    			return $this->_redirect('/course-details/'.$topic->course_id);
    		
    	}
    	//check if cookie is set
    	else {
    		if($topic->is_sample=='Y' && $topic->is_active=='Y') {
	    		if (isset($_COOKIE['dezyre-anon'])) {
	    			$request = new Zend_Controller_Request_Http();
	    			$anonymous_id = $request->getCookie('dezyre-anon');
	    			$row_topicUser = $topicUserModel->addTopicUser($topic_id,null,$anonymous_id);    			
	    		}
	    		else {    			
	    			$anonymous_id = 'Anon-'.date('dmy').'-'.rand(100,999);
	    			setcookie('dezyre-anon',$anonymous_id, mktime()+(60*60*24*365*10), "/") or die("Could not set anonymous cookie");
	    			$row_topicUser = $topicUserModel->addTopicUser($topic_id,null,$anonymous_id);    			
	    		}     		
    		}
    		else 
    			return $this->_redirect('/course-details/'.$topic->course_id);
    	}
    	
    	
    	$this->view->topic_user_id = $row_topicUser->topic_user_id; 
    	$this->view->topic = $topic;
    }
    
	public function closeVideoAction()
    {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	//Zend_Registry::get('logger')->err('Inside close action = '.$this->_getParam('topic_user_id'));
    	$topicUserModel = new Model_TopicUser();
    	$topicUserModel->updateStopTime($this->_getParam('topic_user_id'));    	
    }

    public function indexAction()
    {
        // action body
        return $this->_redirect('/');
    }
    
    public function adAction() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	
    	$course_id = $this->_getParam('course_id');
    	$ref = $this->_getParam('ref');
	    if($ref=='adwords') {
	    	if (!isset($_COOKIE['adwords'])) {
				// if a cookie does not exist set it
				setcookie("adwords", "adwords", mktime()+86400, "/") or die("Could not set cookie");				
			}			
		}
		return $this->_redirect('/course/view/course_id/'.$course_id);
    }
    
    public function viewBatchAction()
    {
    	$this->_helper->layout->disableLayout();
    	if($this->_getParam('course_id')==null || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    	 
    	if($this->_user_id !=null) {
    		$currentCourses=Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);
    		//if registered send him to my course page
    		if(count($currentCourses)!=0 && $currentCourses->payment_received=='Y') {
    			return $this->_forward('my-view/');
    		}
    	}
    	
    	//$this->_helper->layout()->setLayout('adaptive_layout');
    	$course = Model_Course::loadCourse($this->_getParam('course_id'));
    	$seo_keyword2=$course->seo_keyword2;
    	if($seo_keyword2==null) {
    		$seo_keyword2=$course->title;
    	}
    	$seo_keyword2=preg_replace(array('/\s+/','/\(/','/\)/',"/\-+/i"),array('-','','','-'),$seo_keyword2);
    	
    	
    	if($course->has_batch!='Y') {   		
    		//return $this->_redirect('/course/view/course_id/'.$course->course_id);
    		return $this->_redirect('/online-course/'.$seo_keyword2.'/'.$course->course_id);
    	}
    	 
    		
    	
    	if(strcmp($this->_getParam('title'),$seo_keyword2)!=0)
    		//	return $this->_redirect('/courseview/'.$course->course_id.'/'.preg_replace('/\s+/','-',$course->title));
    		$this->_helper->Redirector
    		->setCode(301)
    		->gotoRouteAndExit(array('title' => $seo_keyword2,
    				'course_id' => $course->course_id
    		)
    		);
    		//unset quiz session so if user has taken the two sample quizzes - if he comes back to course page
    		// he can take the quizzes again
    		Zend_Session:: namespaceUnset('quiz_session');
    		try {
    	
    				
    			//if($course!=null && count($course)>0 && $course->is_active=='Y') {
    			if($course!=null && count($course)>0) {
    					
    				//$this->view->is_google=$is_google;
    				$total_fees = $course->fees;
    				$this->view->total_fees = $total_fees;
    					    					
    				//GET BATCHES
    				$batches = Model_Batch::getBatches($this->_getParam('course_id'));
    				//echo $batches;
    				$this->view->batches=$batches;
    				
    				//GET FAQ
    				$faqs = Model_CourseFaq::getFaq($this->_getParam('course_id'));
    				//echo count($faq);
    				$this->view->faqs=$faqs;
    	
    				//if course has batch - get topics from batch
    				if($course->has_batch=='Y') {
    					//find the next open batch
    					$next_batch_id=$batches[0]->batch_id;
    					foreach($batches as $batch) {
    						if($batch->status=='open') {
    							$next_batch_id=$batch->batch_id;
    							break;
    						}
    					}
    					$chapters = Model_Topic::getChapters($this->_getParam('course_id'),$next_batch_id);
    				}
    				else
    					$chapters = Model_Topic::getChapters($this->_getParam('course_id'));
    	
    				//print_r($chapters->toArray());
    					
    				//now arrange chapters by topic/subtopic
    				$chapter_array = null;
    				$topic_array = null;
    				foreach($chapters as $chapter) {
    					if($chapter['parent_topic_id']==0) {
    						//echo 'chapter name = '.$chapter->topic_name;
    						$topic_array[$chapter['topic_id']] = array();
    						$chapter_array[] = array('topic_name'=>$chapter['topic_name'],'topic_description'=>$chapter['topic_description'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order'],'transcript'=>$chapter['transcript']);
    					}
    					else {
    						$temp_array = $topic_array[$chapter['parent_topic_id']];
    						$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_description'=>$chapter['topic_description'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order'],'video_url'=>$chapter['video_url'],'notes'=>$chapter['notes'],'is_sample'=>$chapter['is_sample'],'transcript'=>$chapter['transcript']);
    						$topic_array[$chapter['parent_topic_id']] = $temp_array;
    					}
    				}
    					
    				//check if user is enrolled
    				if($this->_user_id !=null) {
    					$currentCourses = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);
    					if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
    						$this->view->enrolled='no';
    					else
    						$this->view->enrolled='yes';
    				}
    					
    					
    				$this->view->course = $course;
    				if($chapter_array!=null && count($chapter_array>0))
    					$this->view->chapters = $chapter_array;
    				if($topic_array!=null && count($topic_array>0))
    					$this->view->topics = $topic_array;
    					
    				$topic = Model_Topic::getSampleTopic($course->course_id);
    				$this->view->sample_topic = $topic;
    					
    			}
    			else
    				return $this->_redirect('/course/list-courses');
    	
    			
    			$this->view->doctype('XHTML1_RDFA');
    	
    			$testimonials = Model_Testimonial::getTestimonialsByCourse($course->course_id,20);
    			$test_count_avg = Model_Testimonial::countTestimonial($course->course_id);
    	
    	
    			$this->view->testimonials=$testimonials;
    			$this->view->test_count_avg=$test_count_avg;
    	
    	
    				
    			//get country
    			$country=$this->_helper->GetCountryAction();
    			$callback_country = $country;
    			$this->view->callback_country=$callback_country;
    			//echo $country;
    			if(strcasecmp($country,'IN')!=0)
    				$country='NA';
    				
    			$this->view->country=$country;
    	
    				
    			//get referrer
    			$referer_session = new Zend_Session_Namespace('referer');
    			$referer = $referer_session->referer;
    			if($referer==null || strlen($referer)==0) {
    				$request = new Zend_Controller_Request_Http();
    				$referer = $request->getHeader('referer');
    				$referer_session->referer = $referer;
    			}
    			$this->view->referer = $referer;
    				
    				
    	
    		} catch (Exception $e) {
    			Zend_Registry::get('logger')->err('Exception occured in viewBatchAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			return $this->_forward('exception/','error');
    		}
    
    }
    
	// User and Guest  view course
	// CALLED in /course/list-courses to view a particular course
	// CALLED in /index/index where we list all courses in the box
	// CALLED in /user/view-faculty where we list what courses the faculty teaches
    public function viewAction()
    {
    	
    	    	
    	if($this->_getParam('course_id')==null || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    	
    	if($this->_user_id !=null) {
    		$currentCourses=Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);
    		//if registered send him to my course page
    		if(count($currentCourses)!=0 && $currentCourses->payment_received=='Y') {
    			return $this->_forward('my-view/');
    		}
    	}
    		
    	//$this->_helper->layout()->setLayout('adaptive_layout');	
    	$course = Model_Course::loadCourse($this->_getParam('course_id'));
    	
    	$seo_keyword2=$course->seo_keyword2;
		if($seo_keyword2==null) {
			$seo_keyword2=$course->title;	
		}
					
		$seo_keyword2=preg_replace(array('/\s+/','/\(/','/\)/',"/\-+/i"),array('-','','','-'),$seo_keyword2);
		
		
		if($course->has_batch=='Y') {
			//return $this->_redirect('/course/view/course_id/'.$course->course_id);
			return $this->_redirect('/'.$seo_keyword2.'/'.$course->course_id);
		}
		
    	if(strcmp($this->_getParam('title'),$seo_keyword2)!=0) 
  		  //	return $this->_redirect('/courseview/'.$course->course_id.'/'.preg_replace('/\s+/','-',$course->title));
  		$this->_helper->Redirector
        ->setCode(301) 
        ->gotoRouteAndExit(array('title' => $seo_keyword2,
        						 'course_id' => $course->course_id             					 
           						)
        					);
        //unset quiz session so if user has taken the two sample quizzes - if he comes back to course page
        // he can take the quizzes again					
    	Zend_Session:: namespaceUnset('quiz_session');    		
    	try {    		
	    	
			
			//if($course!=null && count($course)>0 && $course->is_active=='Y') {
    		if($course!=null && count($course)>0) {
			
			//$this->view->is_google=$is_google;
		  	$total_fees = $course->fees;
		  	$this->view->total_fees = $total_fees;
			
			//get faculty for this course
			$faculty = Model_CourseFaculty::getFacultyByCourseId($this->_getParam('course_id'));
			$this->view->faculty = $faculty;
			
			//GET BATCHES
			$batches = Model_Batch::getBatches($this->_getParam('course_id'));
			//echo $batches;
			$this->view->batches=$batches;
				
			//if course has batch - get topics from batch
			if($course->has_batch=='Y') {
				//find the next open batch
				$next_batch_id=$batches[0]->batch_id;
				foreach($batches as $batch) {					
					if($batch->status=='open') {
						$next_batch_id=$batch->batch_id;
						break;
					}
				}
				$chapters = Model_Topic::getChapters($this->_getParam('course_id'),$next_batch_id);
			}
			else
				$chapters = Model_Topic::getChapters($this->_getParam('course_id'));
						
			//print_r($chapters->toArray());
			
			//now arrange chapters by topic/subtopic
			$chapter_array = null;
			$topic_array = null;
			foreach($chapters as $chapter) {
				if($chapter['parent_topic_id']==0) {
					//echo 'chapter name = '.$chapter->topic_name;
					$topic_array[$chapter['topic_id']] = array();
					$chapter_array[] = array('topic_name'=>$chapter['topic_name'],'topic_description'=>$chapter['topic_description'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order'],'transcript'=>$chapter['transcript']);
				}
				else {
					$temp_array = $topic_array[$chapter['parent_topic_id']];
					$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_description'=>$chapter['topic_description'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order'],'video_url'=>$chapter['video_url'],'notes'=>$chapter['notes'],'is_sample'=>$chapter['is_sample'],'transcript'=>$chapter['transcript']);
					$topic_array[$chapter['parent_topic_id']] = $temp_array;
				}
			}
			
			//check if user is enrolled
			if($this->_user_id !=null) {
				$currentCourses = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);		
				if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
					$this->view->enrolled='no';
				else
					$this->view->enrolled='yes';
			}
			
			
			$this->view->course = $course;
			if($chapter_array!=null && count($chapter_array>0))
				$this->view->chapters = $chapter_array;
			if($topic_array!=null && count($topic_array>0))
				$this->view->topics = $topic_array;
							
			$topic = Model_Topic::getSampleTopic($course->course_id);
			$this->view->sample_topic = $topic;
			
			}
			else 
				return $this->_redirect('/course/list-courses');
				
					
			//$blogs = Model_Blog::getBlogs(10);
    		//$this->view->blogs=$blogs;
    		
    		//$media_blogs = Model_Blog::getMediaBlogs('4,20');
    		//$this->view->media_blogs=$media_blogs;
	    	
			$this->view->doctype('XHTML1_RDFA');
		
    		$testimonials = Model_Testimonial::getTestimonialsByCourse($course->course_id,20);
    		$test_count_avg = Model_Testimonial::countTestimonial($course->course_id);
    		
    		
    		$this->view->testimonials=$testimonials;
    		$this->view->test_count_avg=$test_count_avg;
    		
    		
    		//Question Feed
    		//get number of questions to display
			$number_of_records = Zend_Registry::getInstance()->configuration->number->records;		
	    	$this->view->number_of_records=$number_of_records;
			//get questions and answers
			$questions = Model_CourseQuestion::getQuestions($this->_getParam('course_id'),$number_of_records);
			
			//get tags			
			$unique_tags = array();
    		$tag_array = array();
			foreach($questions as $question) {				
				$tags = Model_TagQuestion::getTagsbyQuestion($question->course_question_id);
				foreach($tags as $t)
					$unique_tags[]=$t->tag_name; 
				$tag_array[$question->course_question_id] = $tags; 
			}
			
			$unique_tags=array_unique($unique_tags);
			$keywords=implode(",", $unique_tags);
							
			$start_pos = count($questions);
			$this->view->start_pos=$start_pos;
			$this->view->questions = $questions;
			$this->view->keywords = $keywords;
			$this->view->tag_array = $tag_array;
			
			//get country
			$country=$this->_helper->GetCountryAction();
			$callback_country = $country;
			$this->view->callback_country=$callback_country;
			//echo $country;
			if(strcasecmp($country,'IN')!=0)
				$country='NA';
			
			$this->view->country=$country;
    
			
			//get referrer
			$referer_session = new Zend_Session_Namespace('referer');
			$referer = $referer_session->referer;
			if($referer==null || strlen($referer)==0) {			
				$request = new Zend_Controller_Request_Http();
				$referer = $request->getHeader('referer');
				$referer_session->referer = $referer;
			}
			$this->view->referer = $referer;
			
			
						
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in viewAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	public function fbViewAction()
    {
    	if($this->_getParam('course_id')==null || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    	
    	if($this->_user_id !=null) {
    		$currentCourses=Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);
    		//if registered send him to my course page
    		if(count($currentCourses)!=0 && $currentCourses->payment_received=='Y') {
    			return $this->_forward('my-view/');
    		}
    	}
    		
    	$this->_helper->layout()->setLayout('fb_layout');
    	//$this->_helper->layout()->setLayout('adaptive_layout');	
    	$course = Model_Course::loadCourse($this->_getParam('course_id'));
    	
    	if(!$this->_getParam('title')) 
  		  //	return $this->_redirect('/courseview/'.$course->course_id.'/'.preg_replace('/\s+/','-',$course->title));
  		$this->_helper->Redirector
        ->setCode(301) 
        ->gotoRouteAndExit(array('course_id' => $course->course_id,
             					 'title' => preg_replace(array('/\s+/','/\(/','/\)/',"/\-+/i"),array('-','','','-'),$course->title)
           						)
        					);
        //unset quiz session so if user has taken the two sample quizzes - if he comes back to course page
        // he can take the quizzes again					
    	Zend_Session:: namespaceUnset('quiz_session');    		
    	try {    		
	    	$is_google='n';
    		$request = new Zend_Controller_Request_Http();
			$adword_cookie = $request->getCookie('adwords');  		
		  	if($adword_cookie=='adwords' )
		  	{
		  		$is_google='y';
		  	}
		  	$this->view->is_google=$is_google;
    		
			
			if($course!=null && count($course)>0 && $course->is_active=='Y') {
			
			//$this->view->is_google=$is_google;
		  	$total_fees = $course->fees;
		  	$this->view->total_fees = $total_fees;
			
			//get faculty for this course
			$faculty = Model_CourseFaculty::getFacultyByCourseId($this->_getParam('course_id'));
			$this->view->faculty = $faculty;
			
			$chapters = Model_Topic::getChapters($this->_getParam('course_id'));
			//print_r($chapters->toArray());
			
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
					$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order'],'video_url'=>$chapter['video_url'],'notes'=>$chapter['notes'],'is_sample'=>$chapter['is_sample']);
					$topic_array[$chapter['parent_topic_id']] = $temp_array;
				}
			}
			
			//check if user is enrolled
			if($this->_user_id !=null) {
				$currentCourses = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);		
				if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
					$this->view->enrolled='no';
				else
					$this->view->enrolled='yes';
			}
			
			
			$this->view->course = $course;
			if($chapter_array!=null && count($chapter_array>0))
				$this->view->chapters = $chapter_array;
			if($topic_array!=null && count($topic_array>0))
				$this->view->topics = $topic_array;
				
				
			$topic = Model_Topic::getSampleTopic($course->course_id);
			$this->view->sample_topic = $topic;
			
			}
			else 
				return $this->_redirect('/course/list-courses');
				
					
			$this->view->doctype('XHTML1_RDFA');
		
    	
    
			
			
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in viewAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    //This is shown when user clicks on a particular course on My COURSES page
    public function myViewAction()
    {
    	if($this->_getParam('course_id')==null  || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    	
    	try {
    		
    		//$this->_helper->layout()->setLayout('adaptive_layout');
	    	//CHECK IF THE USER IS STILL ENROLLED FOR THIS COURSE OTHERWISE REDIRECT HIM TO VIEW PAGE
			$currentCourses = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);			
			if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
				return $this->_redirect('/course/view/course_id/'.$this->_getParam('course_id'));
			else if($currentCourses->batch_id!=null)
				return $this->_redirect('/course/batch-view/course_id/'.$this->_getParam('course_id').'/batch_id/'.$currentCourses->batch_id);
							
				
			
			$course = Model_Course::loadCourse($this->_getParam('course_id'));
			
			
			
	    	$chapters = Model_Topic::getMyChapters($course->course_id,$this->_user_id);
	    	//$chapters = Model_Topic::getChapters($this->_getParam('course_id'));
	    	//print_r($chapters->toArray());
	    	//echo 'count='.count($chapters);
	    	 
	    	
			
			//now arrange chapters by topic/subtopic
			$chapter_array = null;
			$topic_array = null;			
			$is_complete_count=0;
			$total_lectures=0;
			$start_topic=null;
			$start_topic_id=null;
			$next_topic_id=null;
			$next_id_index = null;
			$index=0;
			foreach($chapters as $chapter) {
				if($chapter['parent_topic_id']==0) {
					//echo 'chapter name = '.$chapter->topic_name;
					$topic_array[$chapter['topic_id']] = array();
					$chapter_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order']);
				}
				else {
					if(($chapter['is_complete']=='N' || $chapter['is_complete']==null) && $start_topic_id==null) {
						$start_topic=$chapter['topic_name'];
						$start_topic_id = $chapter['topic_id'];
						$next_id_index=$index+1;						
					}					
					$temp_array = $topic_array[$chapter['parent_topic_id']];
					$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order'],'is_complete'=>$chapter['is_complete'],'score'=>$chapter['score'],'has_quiz'=>$chapter['has_quiz']);
					//$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order']);
					$topic_array[$chapter['parent_topic_id']] = $temp_array;
					if($chapter['is_complete']=='Y')
						$is_complete_count++;
					$total_lectures++;
				}
				$index++;
			}
			//get next topic id to show in view score page
			//Zend_Registry::get('logger')->err('Exception occured '.$next_id_index);
			//$next_topic_id = $chapters[$next_id_index]['topic_id'];
			
			
			//get users posted questions
			//get number of questions to display
			/*
			$number_of_records = Zend_Registry::getInstance()->configuration->number->records;
			$my_questions = Model_CourseQuestion::getMyQuestions($course->course_id,$this->_user_id,$number_of_records);
			$my_answer_array = array();
			foreach($my_questions as $question) {
				$answers = Model_CourseAnswer::getAnswers($question->course_question_id); 
				$my_answer_array[$question->course_question_id] = $answers; 
			}
			$this->view->my_questions = $my_questions;
			$this->view->my_answer_array = $my_answer_array;
			
			//Get Course Discussions
			$questions = Model_CourseQuestion::getOthersQuestions($course->course_id,$this->_user_id,$number_of_records);			
			
			$answer_array = array();
			foreach($questions as $question) {
				$answers = Model_CourseAnswer::getAnswers($question->course_question_id); 
				$answer_array[$question->course_question_id] = $answers; 
			}
			$this->view->questions = $questions;
			$this->view->answer_array = $answer_array;
			*/
			$percentage_complete= round(($is_complete_count*100)/$total_lectures,0);
			
			$examCourse = Model_ExamCourse::getExamId($course->course_id);
			
			$quizUserModel = new Model_QuizUser();
			$quizUserRow = $quizUserModel->getAverageQuizScore($course->course_id,$this->_user_id);
	    	$avg_quiz_score = number_format((float)($quizUserRow->avg_quiz_score), 2, '.', '');
	    	$this->view->avg_quiz_score = $avg_quiz_score;
	    	
			
			//echo ('00000000==='.count($examCourse));
			if($examCourse!=null && count($examCourse)!=0){
		    	$exam_id=$examCourse->exam_id;
		    	$examUser = Model_ExamUser::loadExamUser($exam_id,$this->_user_id);
				$this->view->examUser=$examUser;
			
		    	//$examCourse = Model_ExamCourse::getExamId($course->course_id);
			    //$exam_id=$examCourse->exam_id;
			    $examUserModel = new Model_ExamUser();
				$examUserRow = $examUserModel->getFinalScoreByExam($exam_id, $this->_user_id);			
				$final_exam_score='Not Taken';
				$final_grade='NA';
				if($examUserRow!=null) {	
					$final_exam_score=$examUserRow->score;
					if($final_exam_score!=null) {
						$final_score = ($final_exam_score+$avg_quiz_score)/2;
				    	$final_grade='';
				    	if($final_score>=90)
				    		$final_grade='A';
				    	else if($final_score>=80)
				    		$final_grade='B';
				    	else if($final_score>=70)
				    		$final_grade='C';
				    	else if($final_score>=60)
				    		$final_grade='D';
				    	else if($final_score<60)
				    		$final_grade='F';		    	
				    	$final_exam_score.='%';
					}		
				}
				$this->view->exam_score = $final_exam_score;
				$this->view->final_grade = $final_grade;
			}
			
		    
			$this->view->percentage_complete=$percentage_complete;
			$this->view->start_topic=$start_topic;
			$this->view->start_topic_id=$start_topic_id;
			//$this->view->next_topic_id=$next_topic_id;
			
			//get users questions
			$questions = Model_CourseQuestion::getUserQuestionsByCourse($this->_user_id,$course->course_id);
			$this->view->question_count=$questions->question_count;
			
			//get users answers
			$answers = Model_CourseAnswer::getUserAnswersByCourse($this->_user_id,$course->course_id);
			$this->view->answer_count=$answers->answer_count;
			
			
			
			$this->view->course = $course;
			$this->view->chapters = $chapter_array;
			$this->view->topics = $topic_array;
			$this->view->is_complete = $currentCourses->is_complete;
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in myViewAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    //This is shown when user clicks on a particular course with batches on My COURSES page
    public function batchViewAction()
    {
    	if($this->_getParam('course_id')==null  || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    	 
    	try {
    
    		//CHECK IF THE USER IS STILL ENROLLED FOR THIS COURSE OTHERWISE REDIRECT HIM TO VIEW PAGE
    		$currentCourses = Model_Enrollment::isStudentEnrolledBatch($this->_getParam('course_id'),$this->_user_id,$this->_getParam('batch_id'));
    		if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
    			return $this->_redirect('/course/view/course_id/'.$this->_getParam('course_id'));    		
    		else if($currentCourses->batch_id != $this->_getParam('batch_id'))
    			return $this->_redirect('/course/batch-view/course_id/'.$this->_getParam('course_id').'/batch_id/'.$currentCourses->batch_id);
    		 
    		 
    		
    
    		$course = Model_Course::loadCourse($this->_getParam('course_id'));
    		
    		$batch_topics = Model_Topic::getBatchTopics($course->course_id,$this->_getParam('batch_id'),$this->_user_id);
    		//echo $batch_topics;
    		$supplement_array=null;
    		$video_array=null;
    		$topics_array=null;
    		$batch_array=null;
    		foreach($batch_topics as $topic)
    		{
    			if (!array_key_exists($topic->batch_id, $batch_array))
    				$batch_array[$topic->batch_id] = array('batch_id'=>$topic->batch_id,'batch_name'=>$topic->batch_name,'class_days'=>$topic->class_days,'class_time'=>$topic->class_time,'start_date'=>$topic->start_date);
    			
    			//add topics to topic_array
    			if (!array_key_exists($topic->topic_id, $topics_array))    				
    				$topics_array[$topic->topic_id] = array('topic_id'=>$topic->topic_id,'topic_name'=>$topic->topic_name,'is_complete'=>$topic->is_complete,'score'=>$topic->score,'has_quiz'=>$topic->has_quiz,'quiz_id'=>$topic->quiz_id);
    			
    			if($topic->supplement_id !=null)
    				$supplement_array[$topic->topic_id][$topic->supplement_id] = array('supplement_id'=>$topic->supplement_id,'supplement_name'=>$topic->supplement_name,'filename'=>$topic->filename,'type'=>$topic->type);
    			if($topic->topic_video_id !=null)
    				$video_array[$topic->topic_id][$topic->topic_video_id] = array('batch_name'=>$topic->batch_name,'start_date'=>$topic->start_date,'video_id'=>$topic->video_id,'video_url'=>$topic->video_url_hd);    			
    			
    		}
    		$this->view->batch=$batch_array;
    		$this->view->videos=$video_array;
    		$this->view->topics=$topics_array;
    		$this->view->supplements=$supplement_array;
    		$this->view->course = $course;
    		$this->view->batch_id=$this->_getParam('batch_id');
    			    		
        	//$batch_id = $currentCourses->batch_id;		
    		//$chapters = Model_Topic::getMyBatchChapters($batch_id,$course->course_id,$this->_user_id);
    		
        	/*		
    		//now arrange chapters by topic/subtopic
    		$chapter_array = null;
    		$topic_array = null;
    		$is_complete_count=0;
    		$total_lectures=0;
    		$start_topic=null;
    		$start_topic_id=null;
    		$next_topic_id=null;
    		$next_id_index = null;
    		$index=0;
    		foreach($chapters as $chapter) {
    			if($chapter['parent_topic_id']==0) {
    				$topic_array[$chapter['topic_id']] = array();
    				$chapter_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order']);
    			}
    			else {
    				if(($chapter['is_complete']=='N' || $chapter['is_complete']==null) && $start_topic_id==null) {
    					$start_topic=$chapter['topic_name'];
    					$start_topic_id = $chapter['topic_id'];
    					$next_id_index=$index+1;
    				}
    				$temp_array = $topic_array[$chapter['parent_topic_id']];
    				$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order'],'is_complete'=>$chapter['is_complete'],'score'=>$chapter['score'],'has_quiz'=>$chapter['has_quiz']);
    				//$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order']);
    				$topic_array[$chapter['parent_topic_id']] = $temp_array;
    				if($chapter['is_complete']=='Y')
    					$is_complete_count++;
    				$total_lectures++;
    			}
    			$index++;
    		}
    		
    		$percentage_complete= round(($is_complete_count*100)/$total_lectures,0);
    			
    			
    		$quizUserModel = new Model_QuizUser();
    		$quizUserRow = $quizUserModel->getAverageQuizScore($course->course_id,$this->_user_id);
    		$avg_quiz_score = number_format((float)($quizUserRow->avg_quiz_score), 2, '.', '');
    		$this->view->avg_quiz_score = $avg_quiz_score;
        		
    		$this->view->percentage_complete=$percentage_complete;
    		$this->view->start_topic=$start_topic;
    		$this->view->start_topic_id=$start_topic_id;
        			
    		$this->view->course = $course;
    		$this->view->chapters = $chapter_array;
    		$this->view->topics = $topic_array;
    		$this->view->is_complete = $currentCourses->is_complete;
    		*/
    		
    	} catch (Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in batchViewAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	//This is shown when user clicks on a quizzes My COURSES page
    public function quizAction()
    {
    	if($this->_getParam('course_id')==null  || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    	
    	try {
    		//$this->_helper->layout()->setLayout('adaptive_layout');
	    	//CHECK IF THE USER IS STILL ENROLLED FOR THIS COURSE OTHERWISE REDIRECT HIM TO VIEW PAGE
			$currentCourses = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);
			//CHECK IF THE USER IS FACULTY FOR THIS COURSE OTHERWISE REDIRECT HIM TO VIEW PAGE		
			$isFaculty = Model_CourseFaculty::isCourseFaculty($this->_getParam('course_id'),$this->_user_id);
			//echo '*************************is faculty===='.$currentCourses->payment_received;
			if(count($isFaculty)==0) {
				if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
					return $this->_redirect('/course/view/course_id/'.$this->_getParam('course_id'));
			}
			
			$auth = Zend_Auth::getInstance();
			if($auth->hasIdentity()) {
				$this->view->identity = $auth->getIdentity();
			}		
			
			$course = Model_Course::loadCourse($this->_getParam('course_id'));
			
			
			//get faculty for this course
			$faculty = Model_CourseFaculty::getFacultyByCourseId($course->course_id);
			$this->view->faculty = $faculty;
			
	    	$chapters = Model_Topic::getMyChapters($course->course_id,$this->_user_id);
	    	//$chapters = Model_Topic::getChapters($this->_getParam('course_id'));
	    	//print_r($chapters->toArray());
	    	//echo 'count='.count($chapters);
	    	 
	    	
			
			//now arrange chapters by topic/subtopic
			$chapter_array = null;
			$topic_array = null;			
			$is_complete_count=0;
			$total_lectures=0;
			$start_topic=null;
			$start_topic_id=null;
			$next_topic_id=null;
			$next_id_index = null;
			$index=0;
			foreach($chapters as $chapter) {
				if($chapter['parent_topic_id']==0) {
					//echo 'chapter name = '.$chapter->topic_name;
					$topic_array[$chapter['topic_id']] = array();
					$chapter_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order']);
				}
				else {
					if(($chapter['is_complete']=='N' || $chapter['is_complete']==null) && $start_topic_id==null) {
						$start_topic=$chapter['topic_name'];
						$start_topic_id = $chapter['topic_id'];
						$next_id_index=$index+1;						
					}					
					$temp_array = $topic_array[$chapter['parent_topic_id']];
					$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order'],'is_complete'=>$chapter['is_complete'],'score'=>$chapter['score']);
					//$temp_array[] = array('topic_name'=>$chapter['topic_name'],'topic_id'=>$chapter['topic_id'],'topic_order'=>$chapter['topic_order']);
					$topic_array[$chapter['parent_topic_id']] = $temp_array;
					if($chapter['is_complete']=='Y')
						$is_complete_count++;
					$total_lectures++;
				}
				$index++;
			}
			//get next topic id to show in view score page
			//Zend_Registry::get('logger')->err('Exception occured '.$next_id_index);
			//$next_topic_id = $chapters[$next_id_index]['topic_id'];
			
			
			$percentage_complete= round(($is_complete_count*100)/$total_lectures,0);
			
			$this->view->percentage_complete=$percentage_complete;
			$this->view->start_topic=$start_topic;
			$this->view->start_topic_id=$start_topic_id;
			//$this->view->next_topic_id=$next_topic_id;
			
			
			
			$this->view->course = $course;
			$this->view->chapters = $chapter_array;
			$this->view->topics = $topic_array;
			$this->view->is_complete = $currentCourses->is_complete;
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in quizAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    

    // CALLED in /layout in the menu
    // CALLED in /admin/index in the main menu
    public function listCoursesAction()
    {	
    	try {	
    		
			$courses = Model_Course::getActiveCourses();
        	$category_array=array();
        	
        	foreach($courses as $course){
        		$category_array[$course->category_name][]=array('title'=>$course->title,'fees'=>$course->fees,'course_id'=>$course->course_id,'benefits'=>$course->benefits,'seo_keyword2'=>$course->seo_keyword2);        		
        	}
        	
        	
        	$this->view->category_array = $category_array;
        			
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in listCoursesAction in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
		
    // CALLED in /admin/index in the main menu
    public function fbListCoursesAction()
    {	
    	try {	
    		$this->_helper->layout()->setLayout('fb_layout');
			$courses = Model_Course::getActiveCourses();
        	$category_array=array();
        	
        	foreach($courses as $course){
        		$category_array[$course->category_name][]=array('title'=>$course->title,'fees'=>$course->fees,'course_id'=>$course->course_id,'benefits'=>$course->benefits);        		
        	}
        	
        	
        	$this->view->category_array = $category_array;
        			
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in listCoursesAction in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    
	//called when user clicks on "Enroll" button on course details page
    // called when user clicks on make payment button on payment page
    public function enrollAction() {
    	if($this->_getParam('course_id')==null  || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    	    	
    	$this->_helper->layout()->setLayout('layout_payment');	
    	try 
    	{   
    		
    		$request = new Zend_Controller_Request_Http();
    		
    		//LOAD THE COURSE
			$courseModel = new Model_Course();
			$userModel = new Model_User();
	    	$course = $courseModel->find($this->_getParam('course_id'))->current();	    	
	    	$enrollmentRow = null;
	    	$enroll_id_cookie = null;
	    	$order_id = null;
	    	
	    	$batch_id = $this->_getParam('batch_id');
	    	$project = 'N';
	    	if($batch_id!=null) {
	    		//GET FEES
	    		$batch = Model_Batch::getFees($batch_id);
	    		$project = $this->_getParam('project');
	    	}     		
    		//IF USER IS ALREADY ENROLLED FOR THIS COURSE send him to dashboard
    		//check if user is enrolled    		
			if($this->_user_id !=null) {
				$enrollmentRow = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);
						
				if(count($enrollmentRow)!=0 && $enrollmentRow->payment_received=='Y')
					return $this->_redirect('/course/my-view/course_id/'.$this->_getParam('course_id'));
			}
			else {
				if (isset($_COOKIE[$course->course_code])) {
					$enroll_id_cookie = $request->getCookie($course->course_code);
					if($enroll_id_cookie!=null) {					
						$enrollmentRow_no_login = Model_Enrollment::isUserEnrolled($enroll_id_cookie);
						//Zend_Registry::get('logger')->err('ENROLLMENT '.$enrollmentRow_no_login->user_id);
						if($enrollmentRow_no_login->user_id !=null)
							$enroll_id_cookie=null;
					}
				}				
			}
			
			$ip=null;
			$country = $this->_helper->GetCountryAction();
			
			//FOR REGULAR COURSE
			$total_fees = $course->fees;
			
			//FOR BATCH COURSE
			if($batch_id!=null) {
				$total_fees = $batch->fees;				
				if(strcmp($project,'Y')==0)
					$total_fees = $batch->fees_project;
			}
			
			if(strcmp($country,'IN')!=0) {
				$country='NA';
				$total_fees = $course->fees_dollar;
				if($batch_id!=null) {
					if(strcmp($project,'Y')==0)
						$total_fees = $batch->fees_project_dollar;
					else 
						$total_fees = $batch->fees_dollar;
				}					
			}
				
    		
			
			
			$or=$this->_getParam('or');
    		if(strcmp($or, 'Y')==0) {
    			//Zend_Registry::get('logger')->err('inside overrise=y');
				//get country
				$ip=$this->_getParam('ip');
				//Zend_Registry::get('logger')->err('inside overrise=ip='.$ip);
				//$country=$this->_helper->GetCountryAction($ip);
				$country='IN';
				$total_fees = $course->fees;
				if($batch_id!=null) {
					if(strcmp($project,'Y')==0)
						$total_fees = $batch->fees_project;
					else
						$total_fees = $batch->fees;
				}														
			}
			
			$this->view->country=$country;
			//$this->view->country='IN';
						
		  	
	    	// INSERT ROW IN ENROLLMENT TABLE
	    	$enrollmentModel = new Model_Enrollment();
	    	//if(count($enrollmentRow)==0 || $enroll_id_cookie==null) {
	    	//Zend_Registry::get('logger')->err('count($enrollmentRow)==== '.count($enrollmentRow).'-----------$enroll_id_cookie===='.$enroll_id_cookie);
	    	if(($enrollmentRow==null || count($enrollmentRow)==0) && $enroll_id_cookie==null) {
	    		$enrollmentRow = $enrollmentModel->add(				
						$course->course_id,
						$this->_user_id,
						null,
						$total_fees,
						'N',
						$order_id,
		    			null,
		    			$batch_id
					);
				//IF USER IS NOT LOGGED IN - SET THE COOKIE
				if($this->_user_id ==null)
					setcookie($course->course_code, $enrollmentRow->enrollment_id, mktime()+(86400*30), "/") or die("Could not set enroll cookie");
					
				$enroll_id =  $enrollmentRow->enrollment_id;
	    	}
	    	else {
	    		if($this->_user_id !=null)
	    			$enroll_id =  $enrollmentRow->enrollment_id;
	    		else
	    			$enroll_id = $enroll_id_cookie;	    		
	    			    						 
	    		$enrollmentModel = new Model_Enrollment();
	    		$enrollmentModel->updateEnrollment(				
						$enroll_id,
						null,
						$total_fees,
						$order_id,
	    				$batch_id
					);
	    	}
	    	
	    	//instead of using enrollment id lets use user_id for generating order_id
	    	//with new checkout flow back to using enrollment id for generating orderid since user may not be logged in
	    	$order_id = $course->course_code.'-'.date('dmy').'-'.$enroll_id.rand(1000,99999);
	    	//$order_id = $course->course_code.'-'.date('dmy').'-'.$this->_user_id.rand(1000,99999);
	    	
	    	$enrollmentModel->updateOrderId($enroll_id, $order_id);
	    	require 'libfuncs.php3';	    	    	
	    	$merchant_id = Zend_Registry::getInstance()->configuration->ccavenue->merchantid;	    	
	    	$redirect_url = 'http://www.dezyre.com/course/payment-details';
	    	
	    	//$fees=$course->fees;
	    	//if($is_google=='y')
	    		//$fees=$course->ad_offer;
    		
    		//echo '------------='.$checksum;
			//echo '------------='.$order_id;			
			//echo '<br/>enrollment------------='.$enrollmentRow->enrollment_id;
			
			if($this->_user_id !=null) {
				$user = $userModel->loadUserProfile($this->_user_id);
	        	//check if user address exists so we can pass that on to ccavenue    	
				if($user->address_id!=null) {
					//get Address from address table
					$rowAddress = Model_Address::getAddress($user->address_id);				
					$this->view->user_address=$rowAddress;
					$this->view->full_name = $rowAddress->full_name;
					$this->view->phone = $rowAddress->phone;
	        		$this->view->email = $rowAddress->email;
				}
				else {
					$this->view->user=$user;
					$this->view->full_name = $user->first_name.' '.$user->last_name;
	        		$this->view->phone = $user->phone;
	        		$this->view->email = $user->email;	
				}
			}
			
	    	//coupon
			if($this->_getParam('couponCode')!=null) {
				$coupon = Model_Discount::getDiscount($this->_getParam('couponCode'),$this->_getParam('course_id'));
				$this->view->coupon=$coupon;		

				$discount = floor($total_fees * $coupon->discount_percentage/100);
				$total_fees = $total_fees - $discount;
			}
			$this->view->total_fees = $total_fees;
			
			$checksum = getCheckSum($merchant_id,$total_fees,$order_id ,$redirect_url,Zend_Registry::getInstance()->configuration->ccavenue->key);
						
    		$this->view->merchant_id = $merchant_id;
    		$this->view->order_id = $order_id;
    		$this->view->redirect_url = $redirect_url;
    		$this->view->checksum = $checksum;
    		$this->view->enrollment_id = $enroll_id;
	    	$this->view->course=$course;
	    	$this->view->user_id=$this->_user_id;
	    	
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in enrollAction course_id='.$this->_getParam('course_id').' in CourseController: ' .$e->getMessage().'---------'. $e->getMessage().'---------'. $e->getTraceAsString());
    		
    		return $this->_forward('exception/','error');
    	}
    }
    
	
    //called for cod courses in enroll.phtml
    public function codCourseAction() {
    	//$this->_helper->viewRenderer->setNoRender(true);
    	//$this->_helper->layout->disableLayout();
    	
    	try {
	    	//update enrollment and set payment_recieved to N
			$enrollmentModel = new Model_Enrollment();
	    	//*******************************************$enrollmentRow = $enrollmentModel->updatePaymentReceived($this->_getParam('enrollment_id'),$this->_getParam('payment_received'),$this->_getParam('payment_form'));
	    	
	    	$courseModel = new Model_Course();
	    	$course = $courseModel->find($enrollmentRow->course_id)->current();
	    	
	    	$userModel = new Model_User();
	    	//update phone number
	    	$userModel->updateUserPhone($this->_user_id,$this->_getParam('cod_phone'));
        	$currentUser = $userModel->find($this->_user_id)->current();
	    	
	    	//send email to User				
			$templateMessage = new Zend_View();
	    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    		    	
	    	$templateMessage->course = $course;
	    	$templateMessage->user = $currentUser;
	    		    	
   			$templateMessage->to_user_name = $currentUser->first_name.' '.$currentUser->last_name;
	    	$templateMessage->to_user_email = $currentUser->email;    	
	    	$this->_helper->SendEmailAction('Thankyou for enrolling on DeZyre',$templateMessage,'cod_enrollment.phtml');
	    	
	    	$this->view->course = $course;
	    	//return $this->_redirect('/user/index/');
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in codCourseAction course_id='.$this->_getParam('course_id').' in CourseController: ' .$e->getMessage().'---------'. $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	//called for free courses in enroll.phtml
    public function freeCourseAction() {
    	//$this->_helper->viewRenderer->setNoRender(true);
    	//$this->_helper->layout->disableLayout();
    	//Zend_Registry::get('logger')->err('email in freeeeee='.$this->_getParam('acct_email'));
    	//Zend_Registry::get('logger')->err('email in freeeeee='.$this->_getParam('free_phone'));
    	
    	$is_existing_user=true;
    	try {
    		$userModel = new Model_User();
    		$enrollmentModel = new Model_Enrollment();
    		$templateMessage = new Zend_View();
	    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    	$user_id=null;
	    	$email = null;
	    	$enrollment_id = $this->_getParam('enrollment_id');
	    	
	    	//load enrollment
	    	$enrollment_row = Model_Enrollment::loadEnrollment($enrollment_id);
	    	$discount_id = $enrollment_row->discount_id;
	    	
	    	//load discount
	    	if($discount_id!=null) {
		    	$discount_row = Model_Discount::loadDiscount($discount_id);
			    $one_time = $discount_row->one_time;
			    
			    //if one_time=='Y' - then deactivate the code
			    $discountModel = new Model_Discount();
			    if($one_time=='Y') {
			    	$discountModel->updateActiveStatus($discount_id);
			    }
	    	}
	    	
	    	$courseModel = new Model_Course();
	    	$course = $courseModel->find($this->_getParam('course_id'))->current();
    		
    		//FIRST CHECK IF USER IS LOGGED IN
    		if($this->_user_id !=null) {    			
        		$currentUser = $userModel->find($this->_user_id)->current();
        		$user_id=$this->_user_id;
        		$email = $currentUser->email;    			
    		}
    		else 
    		{
    			$return_array = $this->registerUser($this->_getParam('acct_email'),$course->course_id,$enrollment_id,$course->course_code,$this->_getParam('free_phone'),'N');
    			$currentUser = $return_array["currentUser"];
    			$user_id=$currentUser->user_id;
    			$is_existing_user = $return_array["is_existing_user"];
    			$enrollment_id= $return_array["enrollment_id"];
    			//Zend_Registry::get('logger')->err('freeaction = enrollment_id====' .$enrollment_id); 
    			$email = $this->_getParam('acct_email');   			    			
    		}
    		
	    	//update enrollment
			//$enrollmentModel = new Model_Enrollment();
	    	$enrollmentRow = $enrollmentModel->updatePaymentReceived
	    	($enrollment_id,$this->_getParam('payment_received'),$this->_getParam('payment_form'),$user_id);
	    	
    		//Check if user already has referral code					
			//GET DISCOUNT CODE
	    	$discount_code_row = Model_Discount::getDiscountCode($user_id);
	    	$discount_code = $discount_code_row['discount_code'];
	    	if($discount_code==null || strlen($discount_code)==0) 
	    	{
				//Generate unique referral code
				$email=explode("@",$currentUser->email);
				//$discount_code = strtolower($email[0]).'-'.$this->_helper->ReferralCodeAction();					
				$discount_code = strtolower($email[0]).'-dezyre';
				//Insert referral code in discount table
				$discountModel = new Model_Discount();
				$rowDiscount = $discountModel->addDiscount($currentUser->user_id,$discount_code,Zend_Registry::getInstance()->configuration->referral->discount);
	    	}
	    	
	    	//if CASH ON DELIVERY UPDATE PHONE
	    	$email_template='enrollment.phtml';
	    	//Zend_Registry::get('logger')->err('$currentUser->email===' .$currentUser->email);
	    	//Zend_Registry::get('logger')->err('payment form====' .$this->_getParam('payment_form'));
	    	if($this->_getParam('payment_form')=='cashdd')
	    	{
	    		$userModel->updateUserPhone($user_id,$this->_getParam('cod_phone'));
	    		$email_template='cod_enrollment.phtml';
	    		//Zend_Registry::get('logger')->err('inside if');
	    	}        	
	    	
	    	//send email to User				    		    	
	    	$templateMessage->course = $course;
	    	$templateMessage->phone = $this->_getParam('cod_phone');
	    	if($is_existing_user==true)
	    		$templateMessage->user = $currentUser;	    		
	    	$templateMessage->to_user_email = $currentUser->email;    	
	    	//Zend_Registry::get('logger')->err('$templateMessage->to_user_email==' .$templateMessage->to_user_email);
	    	//Zend_Registry::get('logger')->err('$$course->title==' .$course->title);
	    	$this->_helper->SendEmailAction('Thankyou for enrolling on DeZyre for '.$course->title,$templateMessage,$email_template);
	    	
	    	
	    	$this->view->course = $course;
	    	$this->view->is_existing_user = $is_existing_user;
	    	$this->view->payment_form = $this->_getParam('payment_form');
	    	$this->view->email = $email;
	    	
	    		
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in freeCourseAction course_id='.$this->_getParam('course_id').' in CourseController: ' .$e->getMessage().'---------'. $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    }
    }
    
	//called for free courses in view.phtml
    public function joinFreeAction() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	$is_existing_user=true;
    	$already_enrolled=false;
    	try {
    		$userModel = new Model_User();
    		$enrollmentModel = new Model_Enrollment();
    		$templateMessage = new Zend_View();
	    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    	$user_id=null;
	    	
	    	$email = $this->_getParam('email');
	    	$phone = $this->_getParam('phone');
	    	$coupon_code = $this->_getParam('coupon_code');
	    	
	    	// STEP 1: CHECK IF USER EXISTS IN DATABASE
	    	$currentUser = $userModel->loadUserByEmail($email);
    		if(count($currentUser)==1) {
    			// User exist in database
    			// STEP 1a: Check if user has already enrolled
    			$enrollmentModel = new Model_Enrollment();
				$enrollmentRow = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$currentUser->user_id);
						
				if(count($enrollmentRow)!=0) {
					if($enrollmentRow->payment_received=='N')
						$enrollmentModel->deleteEnrollment($enrollmentRow->enrollment_id);
					else
						$already_enrolled=true;
				}
    			
    			// STEP 1a: CHECK IF USER IS LOGGED IN
	    		if($this->_user_id !=null) {    			
	        		$user_id=$this->_user_id;	        		    			
	    		} else {
	    			$user_id=$currentUser->user_id;
	    		}	    	
    		} else {
    			//User does not exist
    			$role = Model_Role::getRole('student');
		    	$role = $role->toArray();
		    		    	
		    	//generate random password
				$password = $this->randomPassword();
	
				//GET REFERER FROM COOKIE
				$request = new Zend_Controller_Request_Http();
		    	$referrer = $request->getCookie('dezyre-referrer');
		    			
		    	$userModel = new Model_User();		
				$rowUser=$userModel->createUser(				
						$email,
						$password,
						$role[0]['role_id'],
						null,
						null,
						$phone,
						$referrer,
						'Y'
				);
				$currentUser = $userModel->loadUserProfile($rowUser->user_id);

				//Send welcome email with password					
				$templateMessage->to_user_email = $email;
			    $templateMessage->is_new_user = 'yes';
			    $templateMessage->password = $password;
			    $subject = 'Welcome to DeZyre. Account Created';
			    $this->_helper->SendEmailAction($subject,$templateMessage,'welcome.phtml');
			    $user_id=$rowUser->user_id;
			    
			    //IF NEW USER LOG THE USER IN
				$auth = Zend_Auth::getInstance();
				$storage = $auth->getStorage();			
		        $storage->write($currentUser);
		        $is_existing_user=false;
    		}

    		
    		$courseModel = new Model_Course();
	    	$course = $courseModel->find($this->_getParam('course_id'))->current();
	    		    	
			
    		// INSERT ROW IN ENROLLMENT TABLE
    		if(!$already_enrolled) {
    			$discount_row = Model_Discount::getDiscount($coupon_code,$this->_getParam('course_id'));
    			
    			$order_id = $course->course_code.'-'.date('dmy').'-'.rand(1000,99999);	    	    		
	    		$enrollmentRow = $enrollmentModel->add($this->_getParam('course_id'),$user_id,$discount_row['discount_id'],0,'Y',$order_id,'free');
				$enroll_id =  $enrollmentRow->enrollment_id;
    	    	 	
	    		//Check if user already has referral code					
				//GET DISCOUNT CODE
		    	$discount_code_row = Model_Discount::getDiscountCode($user_id);
		    	$discount_code = $discount_code_row['discount_code'];
		    	if($discount_code==null || strlen($discount_code)==0) 
		    	{
					//Generate unique referral code
					$email=explode("@",$currentUser->email);
					//$discount_code = strtolower($email[0]).'-'.$this->_helper->ReferralCodeAction();					
					$discount_code = strtolower($email[0]).'-dezyre';
					//Insert referral code in discount table
					$discountModel = new Model_Discount();
					$rowDiscount = $discountModel->addDiscount($currentUser->user_id,$discount_code,Zend_Registry::getInstance()->configuration->referral->discount);
		    	}
    			
		    	$email_template='enrollment.phtml';	    		    	
		    	//send email to User				    		    	
		    	$templateMessage->course = $course;
		    	$templateMessage->phone = $this->_getParam('cod_phone');
		    	if($is_existing_user==true)
		    		$templateMessage->user = $currentUser;	    		
		    	$templateMessage->to_user_email = $currentUser->email;
		    	$templateMessage->referral_code = $discount_code;    	
		    	$this->_helper->SendEmailAction('Thankyou for enrolling on DeZyre for '.$course->title,$templateMessage,$email_template);
	    	}
	    	$arr = array ('success'=>'ok','is_existing_user'=>$is_existing_user);
    		echo json_encode($arr);
    		
	    		
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in joinFreeAction course_id='.$this->_getParam('course_id').' in CourseController: ' .$e->getMessage().'---------'. $e->getMessage().'---------'. $e->getTraceAsString());
    		$arr = array ('success'=>'fail','is_existing_user'=>$is_existing_user);
    		echo json_encode($arr);
    }
    }
    
    // redirect URL called by CC AVENUE
    public function paymentDetailsAction() {
    
    	try {     		
    	
    	require 'libfuncs.php3';
    	$enrollment_id = $this->_getParam('Merchant_Param');
    	
    	$output = 	'Enrollment_id='.$enrollment_id.'-'.
    				$this->_getParam('Merchant_Id').'-'.
    				$this->_getParam('Amount').'-'.
    				$this->_getParam('Order_Id').'-'.
    				$this->_getParam('Redirect_Url').'-'.
    				$this->_getParam('Checksum').'-'.
    				$this->_getParam('billing_cust_name').'-'.
    				$this->_getParam('billing_cust_address').'-'.
    				$this->_getParam('billing_cust_country').'-'.
    				$this->_getParam('billing_cust_state').'-'.
    				$this->_getParam('billing_zip').'-'.
    				$this->_getParam('billing_cust_tel').'-'.
    				$this->_getParam('billing_cust_email').'-'.
    				$this->_getParam('billing_cust_city').'-'.
    				$this->_getParam('billing_zip_code');
    	
	    $workingKey = Zend_Registry::getInstance()->configuration->ccavenue->key ; //put in the 32 bit working key in the quotes provided here
		$merchant_id = $this->_getParam('Merchant_Id');
		$amount = $this->_getParam('Amount');
		$order_id = $this->_getParam('Order_Id');		
		$checksum = $this->_getParam('Checksum');
		$authDesc = $this->_getParam('AuthDesc');
		
		 			
	    $Checksum = verifyChecksum($merchant_id, $order_id , $amount,$authDesc,$checksum,$workingKey);
	    
	    $message = '';
	    $user_id=null;
	    $is_existing_user=true;
	    $userModel = new Model_User();
	    	   	    
	    //FOR LOCAL TESTING
	    //http://localhost/course/payment-details/billing_cust_email/paymentdezyre4@gmail.com
	    /*
	    $Checksum="true";
	    $authDesc="Y";
	    $enrollment_id = '15558';
		$order_id ='EXBI-160413-1555845835';
		$amount = '390';
		*/
		
		//LOAD ENROLLMENT
		$enrollmentModel = new Model_Enrollment();
		$enrollment = $enrollmentModel->find($enrollment_id)->current();
		
		$courseModel = new Model_Course();
	    $course = $courseModel->find($enrollment->course_id)->current();
		
	    // THIS METHOD WILL CHECK IF NEW USER OR EXISTING USER AND PERFORM APPROPRIATE ACTION
	   	//FIRST CHECK IF USER IS LOGGED IN
    	if($this->_user_id !=null) {    			
        	$currentUser = $userModel->find($this->_user_id)->current();
        	$user_id=$this->_user_id;    			
    	}
    	else {
	   		$return_array = $this->registerUser($this->_getParam('billing_cust_email'),$enrollment->course_id,$enrollment_id,$course->course_code,$this->_getParam('billing_cust_tel'),'Y');	   		
    		$currentUser = $return_array["currentUser"];
    		$user_id=$currentUser->user_id;
    		$is_existing_user = $return_array["is_existing_user"];
    		$enrollment_id= $return_array["enrollment_id"];    		
    	}
    	
		if($Checksum=="true" && $authDesc=="Y")
		{
			$this->afterPayment($enrollment, $enrollment_id, $order_id, $amount, $authDesc, 'ccdcnb', $user_id, $currentUser, $course, 
			$this->_getParam('billing_cust_country'),$this->_getParam('billing_cust_state'),$this->_getParam('billing_cust_city'),$this->_getParam('billing_cust_name'),$this->_getParam('billing_cust_address'),$this->_getParam('billing_zip'),$this->_getParam('billing_zip_code'),$this->_getParam('billing_cust_tel'),$this->_getParam('billing_cust_email'));
			
			
		}
		
		$this->view->message = $message;
    	$this->view->Checksum = $Checksum;
    	$this->view->authDesc = $authDesc;		
    	$this->view->course = $course;	
    	$this->view->amount = $amount;
    	$this->view->is_existing_user = $is_existing_user;
    	Zend_Registry::get('logger')->err('Called back from CCAvenue '.$output);
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in paymentDetailsAction in CourseController: ' .$e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
    
    // redirect URL called by paypal
    public function paypalPdtAction() {
    	Zend_Registry::get('logger')->err('Inside paypal PDTACTION='.$_GET['tx']);
    	try 
    	{
    		
    		$user_id=null;
    		$is_existing_user=true;
    		$userModel = new Model_User();
    		$payment_data = isset($_GET['tx'])? $this->_helper->PaypalPdtAction($_GET['tx']): FALSE;
    		if($payment_data){
    			
    			$enrollment_id = $payment_data['custom'];
    			$enrollmentModel = new Model_Enrollment();
				$enrollment = $enrollmentModel->find($enrollment_id)->current();
    			$courseModel = new Model_Course();
				$course = $courseModel->find($enrollment->course_id)->current();
				$amount = $payment_data['payment_gross'];
				    
    			
    			/*
    			foreach($payment_data as $key => $value) {
     				echo $key." = ". $value."<br>";
     				Zend_Registry::get('logger')->err('key='.$key.' value='.$value);
    			}
    			*/
    			
    			Zend_Registry::get('logger')->err('In PDT enrollment id ='.$enrollment_id);
    			Zend_Registry::get('logger')->err('In PDT order id ='.$payment_data['invoice']);
    			//LOAD ENROLLMENT
				
				Zend_Registry::get('logger')->err('enrollment payment received ='.$enrollment->payment_received);
				if($enrollment->payment_received!='Y') {
					
				    // THIS METHOD WILL CHECK IF NEW USER OR EXISTING USER AND PERFORM APPROPRIATE ACTION
				   	//FIRST CHECK IF USER IS LOGGED IN
			    	if($this->_user_id !=null) {    			
			        	$currentUser = $userModel->find($this->_user_id)->current();
			        	$user_id=$this->_user_id;    			
			    	}
			    	
			    	else {
			    		
				   		$return_array = $this->registerUser($payment_data['payer_email'],$enrollment->course_id,$enrollment_id,$course->course_code,$payment_data['contact_phone'],'Y');	   		
			    		$currentUser = $return_array["currentUser"];
			    		$user_id=$currentUser->user_id;
			    		$is_existing_user = $return_array["is_existing_user"];
			    		$enrollment_id= $return_array["enrollment_id"];    		
			    	}
			    	
			    	$this->afterPayment($enrollment, $enrollment_id, $payment_data['invoice'],$payment_data['payment_gross'], null, 'paypal', $user_id, $currentUser, $course, 
					$payment_data['address_country'],$payment_data['address_state'],$payment_data['address_city'],$payment_data['address_name'],$payment_data['address_street'],$payment_data['address_zip'],$payment_data['address_zip'],$payment_data['contact_phone'],$payment_data['payer_email']);
			    				    	
					Zend_Registry::get('logger')->err('done updating enrollment PDT');
				}
    		}
    		else {
    			Zend_Registry::get('logger')->err('Inside payment_data FALSE');
    		}
    		$this->view->amount = $amount;
    		Zend_Registry::get('logger')->err('course title='.$course->title);
    		$this->view->course = $course;
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in paypalpdtAction in CourseController: ' .$e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	// redirect URL called by paypal
    public function paypalIpnAction() {
    	//https://developer.paypal.com/webapps/developer/docs/classic/ipn/ht_ipn/
    	//https://developer.paypal.com/webapps/developer/applications/ipn_simulator
    	//http://www.geekality.net/2010/10/19/php-tutorial-paypal-payment-data-transfers-pdt/
    	//http://www.geekality.net/2011/05/28/php-tutorial-paypal-instant-payment-notification-ipn/
    	Zend_Registry::get('logger')->err('Inside paypal IPN action');
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	try 
    	{   
    		$user_id=null;
    		$is_existing_user=true;
    		$userModel = new Model_User();

    		$raw_post_data = file_get_contents('php://input');
			$raw_post_array = explode('&', $raw_post_data);
			$myPost = array();
			foreach ($raw_post_array as $keyval) {
			  $keyval = explode ('=', $keyval);
			  if (count($keyval) == 2)
			     $myPost[$keyval[0]] = urldecode($keyval[1]);
			}
			//$data=$this->_helper->PaypalIpnAction($_POST);
    		$payment_data=$this->_helper->PaypalIpnAction($myPost);
    		if($data === FALSE)
            {
               	Zend_Registry::get('logger')->err('data is false');
                header('HTTP/1.0 400 Bad Request', true, 400);
                exit;
            }
            $output = implode("\t", array(time(), json_encode($payment_data)));
            Zend_Registry::get('logger')->err('In IPN enrollment id ='.$payment_data['custom']);
    		Zend_Registry::get('logger')->err('In IPN order id ='.$payment_data['invoice']);
            //Zend_Registry::get('logger')->err('ouput='.$output);
            //LOAD ENROLLMENT
            $enrollment_id = $payment_data['custom'];
			$enrollmentModel = new Model_Enrollment();
			$enrollment = $enrollmentModel->find($enrollment_id)->current();
			Zend_Registry::get('logger')->err('enrollment payment received='.$enrollment->payment_received);
			if($enrollment->payment_received!='Y') {			
				$courseModel = new Model_Course();
			    $course = $courseModel->find($enrollment->course_id)->current();
			    $user_id=$enrollment->user_id;			    
			    // THIS METHOD WILL CHECK IF NEW USER OR EXISTING USER AND PERFORM APPROPRIATE ACTION
			   	//FIRST CHECK IF USER IS LOGGED IN
		    	if($user_id !=null) {    
		    		//Zend_Registry::get('logger')->err('inside user_id is not null');
		        	$currentUser = $userModel->find($user_id)->current();		        	    			
		    	}		    	
		    	else {		    		
			   		$return_array = $this->registerUser($payment_data['payer_email'],$enrollment->course_id,$enrollment_id,$course->course_code,$payment_data['contact_phone'],'Y');	   		
		    		$currentUser = $return_array["currentUser"];
		    		$user_id=$currentUser->user_id;
		    		$is_existing_user = $return_array["is_existing_user"];
		    		$enrollment_id= $return_array["enrollment_id"];    		
		    	}		    	
		    	//Zend_Registry::get('logger')->err('before after payment - '.$enrollment_id);
		    	$this->afterPayment($enrollment, $enrollment_id, $payment_data['invoice'],$payment_data['payment_gross'], null, 'paypal', $user_id, $currentUser, $course, 
				$payment_data['address_country'],$payment_data['address_state'],$payment_data['address_city'],$payment_data['address_name'],$payment_data['address_street'],$payment_data['address_zip'],$payment_data['address_zip'],$payment_data['contact_phone'],$payment_data['payer_email']);
		    	Zend_Registry::get('logger')->err('done updating enrollment IPN');			
			}    
    	
        	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in paymentIpnAction in CourseController: ' .$e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	private function afterPayment($enrollment,$enrollment_id,$order_id,$amount,$authDesc,$payment_type,$user_id,$currentUser,$course,
    $billing_cust_country,$billing_cust_state,$billing_cust_city,$billing_cust_name,$billing_cust_address,$billing_zip,$billing_zip_code,
    $billing_cust_tel,$billing_cust_email)
    {
    	try 
    	{
    		$enrollmentModel = new Model_Enrollment();
    		$discount_id = $enrollment->discount_id;
			//load discount
	    	if($discount_id!=null) {	    	
		    	//load discount
		    	$discount_row = Model_Discount::loadDiscount($discount_id);
			    $one_time = $discount_row->one_time;
			    
			    //if one_time=='Y' - then deactivate the code
			    $discountModel = new Model_Discount();
			    if($one_time=='Y') {
			    	$discountModel->updateActiveStatus($discount_id);
			    }
	    	}
			//update enrollment and set payment_recieved to Y			
    		$enrollmentRow = $enrollmentModel->updatePayment(				
				$enrollment_id,
				$order_id,
				$amount,
				$authDesc,
				$payment_type,
				$user_id					
			);
    		
    		//if has batch = get batch id
    		if($course->has_batch=='Y'){
    			$enrollment=Model_Enrollment::loadEnrollment($enrollment_id);
    			$batch_id = $enrollment->batch_id;
    			$batch = Model_Batch::getBatch($batch_id);
    		}
			
        	//$currentUser = $userModel->find($user_id)->current();

			//REFERRAL CODE is given only on paid courses
			if($amount !=0) {
				//Check if user already has referral code					
				//GET DISCOUNT CODE
		    	$discount_code_row = Model_Discount::getDiscountCode($user_id);
		    	$discount_code = $discount_code_row['discount_code'];
		    	if($discount_code==null || strlen($discount_code)==0) 
		    	{
		    		//Generate unique referral code
					$email=explode("@",$currentUser->email);
					//$discount_code = strtolower($email[0]).'-'.$this->_helper->ReferralCodeAction();					
					$discount_code = strtolower($email[0]).'-dezyre';
					//Insert referral code in discount table
					$discountModel = new Model_Discount();
					$rowDiscount = $discountModel->addDiscount($currentUser->user_id,$discount_code,Zend_Registry::getInstance()->configuration->referral->discount);
		    	}
			}		
	    	
			//send email to User				
			$templateMessage = new Zend_View();
	    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    	    
	    	$templateMessage->course = $course;
	    	$templateMessage->batch = $batch;
	    	$templateMessage->fees = $amount;
	    	$templateMessage->phone = $billing_cust_tel;
	    	
	    	$templateMessage->user = $currentUser;
	    	$templateMessage->referral_code = $discount_code;
	    	
   			$templateMessage->to_user_name = $currentUser->first_name.' '.$currentUser->last_name;
	    	$templateMessage->to_user_email = $currentUser->email;    	
	    	$this->_helper->SendEmailAction('Thankyou for enrolling on DeZyre for '.$course->title,$templateMessage,'enrollment.phtml');
        	        	
        	//UPDATE ADDRESS       
        	$this->updateBillingAddress(	$user_id,
        									$billing_cust_country,
        									$billing_cust_state,
        									$billing_cust_city,
        									$billing_cust_name,
        									$billing_cust_address,
        									null,
        									$billing_zip,
        									$billing_zip_code,
        									$billing_cust_tel,
        									$billing_cust_email,
        									$currentUser->address_id        									
        								);
        	return true;
        				
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in afterpayment in CourseController: ' .$e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    }
    
	//called when user clicks on "Apply" discount on payment page
    public function discountAction() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	//Does'nt work if the below line is commented
    	$this->_helper->layout->disableLayout();
    	$enrollment_id = $this->_getParam('enrollment_id');
    	$order_id = $this->_getParam('order_id');
    	$course_id = $this->_getParam('course_id');
    	$discount = $this->_getParam('discount');
    	$fees = $this->_getParam('fees');
    	if($discount==null || $fees==null)
    	{
    		$arr = array ('success'=>'fail');
    		echo json_encode($arr);
    	}
    	else 
    	{
	    	try {	
	    		$enrollmentModel = new Model_Enrollment();
		    	$discount_row = Model_Discount::getDiscount($discount,$course_id);
		    	
		    	if($discount_row==null) {
		    		$arr = array ('success'=>'fail');		    		
	    			$enrollmentRow = $enrollmentModel->updateEnrollment(				
						$enrollment_id,
						null,
						$fees					
					);	    	
		    	}
		    	else {
		    		$discount_id = $discount_row['discount_id'];
		    		$course_id_discount = $discount_row['course_id'];
		    		//Zend_Registry::get('logger')->err('course_id_discount==='.$course_id_discount);
		    		//Zend_Registry::get('logger')->err('course_id==='.$course_id);
		    		if($course_id_discount!=0 && $course_id_discount!=null && $course_id_discount!=$course_id) {
		    			$arr = array ('success'=>'fail');
		    			$enrollmentRow = $enrollmentModel->updateEnrollment(				
						$enrollment_id,
						$discount_id,
						$fees					
						);
		    		}
		    		else {	    		
		    			$discount_percentage = $discount_row['discount_percentage'];
			    		$discount = floor($fees * $discount_percentage/100);
			    		$total = $fees - $discount;
			    		
			    		$enrollmentModel = new Model_Enrollment();
		    			$enrollmentRow = $enrollmentModel->updateEnrollment(				
							$enrollment_id,
							$discount_row['discount_id'],
							$total					
						);
			    		
						//Zend_Registry::get('logger')->err('after update enrollment');
						
						require 'libfuncs.php3';	    		    	
		    			$merchant_id = Zend_Registry::getInstance()->configuration->ccavenue->merchantid;	    	
		    			$redirect_url = 'http://www.dezyre.com/course/payment-details';
	    				$checksum = getCheckSum($merchant_id,$total,$order_id ,$redirect_url,Zend_Registry::getInstance()->configuration->ccavenue->key);
	    		
						
			    		$arr = array ('success'=>'ok','total'=>$total,'discount'=>$discount,'discount_id'=>$discount_id,'checksum'=>$checksum);
			    	}    				
	    	}
	    	echo json_encode($arr);
	    	}catch(Exception $e) {
	    		Zend_Registry::get('logger')->err('Exception occured in discountAction discount code='.$discount.' fees='.$fees.' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
	    		return $this->_forward('exception/','error');
	    	}
    	}
    	
    }
    
	    
    //This is the course FEED action
    public function courseFeedAction() {      	
    	//first check if course id is provided
    	if($this->_getParam('course_id')==null  || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');

	    //check if manipal
    	$request = new Zend_Controller_Request_Http();
    	$referrer = $request->getHeader('referer');
	   	Zend_Registry::get('logger')->err('Inside course referrer - '.$referrer);
		$manipal_url = Zend_Registry::getInstance()->configuration->manipal->url;
	    //if (strpos($referrer,'localhost') !== false || strpos($referrer,$manipal_url) !== false || strpos($referrer,'manipal') !== false) {
		if (strpos($referrer,$manipal_url) !== false  || strpos($referrer,'manipal') !== false) {
	    	//Zend_Registry::get('logger')->err('Inside adaptive ifffffffff ');
			$redirector = new Zend_Controller_Action_Helper_Redirector();
			$redirector->gotoUrlAndExit($manipal_url);
	    }	
    		
    	//get course
		$course = Model_Course::loadCourse($this->_getParam('course_id'));
		//print_r($course->toArray());	
    	//If course is not active send user back to course view page
    	/*
    	if($course->is_active=='N')
    	//echo 'okokok';
    		//return $this->_redirect('/course/view/course_id/'.$this->_getParam('course_id'));
    		$this->_helper->Redirector
        ->setCode(301) 
        ->gotoRouteAndExit(array('course_id' => $course->course_id,
             					 'title' => preg_replace(array('/\s+/','/\(/','/\)/',"/\-+/i"),array('-','','','-'),$course->title)
           						),'viewcourse'
        					);
    	*/	
    	try {    	  	
    		
    		
    			
			//check if user is enrolled
			if($this->_user_id !=null) {
				$currentCourses = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);		
				if(count($currentCourses)==0 || $currentCourses->payment_received=='N')
					$this->view->enrolled='no';
				else
					$this->view->enrolled='yes';
			}
			
			
		
			//get faculty for this course
			$faculty = Model_CourseFaculty::getFacultyByCourseId($this->_getParam('course_id'));
			if($faculty!=null && count($faculty)>0) 
				$this->view->faculty = $faculty;			
			else 
				//assign faculty as binny
				$this->view->faculty_id = 10;
			
			
			//get number of questions to display
			$number_of_records = Zend_Registry::getInstance()->configuration->number->records;		
	    	$this->view->number_of_records=$number_of_records;
			//get questions and answers
			$questions = Model_CourseQuestion::getQuestions($this->_getParam('course_id'),$number_of_records);
			
			//get tags			
			$unique_tags = array();
    		$tag_array = array();
			foreach($questions as $question) {				
				$tags = Model_TagQuestion::getTagsbyQuestion($question->course_question_id);
				foreach($tags as $t)
					$unique_tags[]=$t->tag_name; 
				$tag_array[$question->course_question_id] = $tags; 
			}
			
			$unique_tags=array_unique($unique_tags);
			$keywords=implode(",", $unique_tags);
							
			$start_pos = count($questions);
			$this->view->start_pos=$start_pos;
			$this->view->course = $course; // you can use both course->title or course['title'] in the view
						
			$this->view->questions = $questions;
			$this->view->keywords = $keywords;
			$this->view->tag_array = $tag_array;
			
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in courseFeedAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}	
   	}
   	
   	
	//This is the tag FEED action
    public function tagFeedAction() {      	
    	//first check if course id is provided
    	if($this->_getParam('tag_id')==null  || !is_numeric($this->_getParam('tag_id')))
    		return $this->_forward('no-page/','error');
    		
    	//get Tag
		$tag = Model_Tag::loadTag($this->_getParam('tag_id'));
		$start_pos = $this->_getParam('start_pos');
		if($start_pos==null || $start_pos=='')
			$start_pos=0;
		
		try {    	  	
    			
			//get number of questions to display
			$number_of_records = Zend_Registry::getInstance()->configuration->number->records;		
	    	$this->view->number_of_records=$number_of_records;
			//get questions
			$questions = Model_TagQuestion::getQuestionByTag($this->_getParam('tag_id'), $number_of_records,$start_pos);
			//get tags			
    		$tag_array = array();
    		$unique_tags = array();
			foreach($questions as $question) {
				$tags = Model_TagQuestion::getTagsbyQuestion($question->course_question_id);
				foreach($tags as $t)
					$unique_tags[]=$t->tag_name; 
				$tag_array[$question->course_question_id] = $tags; 
			}
			$unique_tags=array_unique($unique_tags);
			$keywords=implode(",", $unique_tags);
			
			
			$popular_tags = Model_TagQuestion::getPopularTags($this->_getParam('tag_id'));
			
			
			$start_pos = count($questions) + $start_pos;
			$this->view->start_pos=$start_pos;
			$this->view->tag=$tag;
			$this->view->questions = $questions;
			$this->view->tag_array = $tag_array;
			$this->view->popular_tags = $popular_tags;
			$this->view->keywords = $keywords;
			
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in tagFeedAction tag id='.$this->_getParam('tag_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}	
   	}
   	
   	//Pagination with no javascript for crawlers
	public function nextQuestionsAction() {
   		
    	//first check if course id is provided
    	if($this->_getParam('course_id')==null  || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    		
    	
    		
    		//get course
			$course = Model_Course::loadCourse($this->_getParam('course_id'));
			//get faculty for this course
			$faculty = Model_CourseFaculty::getFacultyByCourseId($this->_getParam('course_id'));
		
    		try {
	    	$feed = $this->_getParam('feed');
	    	$number_of_records = Zend_Registry::getInstance()->configuration->number->records;    	
	    	$start_pos = $this->_getParam('start_pos');
	    	//get questions and answers
    		if($feed=='faculty') {			
				$questions = Model_CourseQuestion::getFacultyQuestions($this->_getParam('course_id'),$this->_user_id,$number_of_records,$start_pos);
	    	}
	    	else if($feed=='my') {			
				$questions = Model_CourseQuestion::getMyQuestions($this->_getParam('course_id'),$this->_user_id,$number_of_records,$start_pos);
				
	    	}
	    	else {
	    		$questions = Model_CourseQuestion::getQuestions($this->_getParam('course_id'),$number_of_records,$start_pos);
	    	}
	    	
	    	//get tags			
			$unique_tags = array();
    		$tag_array = array();
			foreach($questions as $question) {				
				$tags = Model_TagQuestion::getTagsbyQuestion($question->course_question_id);
				foreach($tags as $t)
					$unique_tags[]=$t->tag_name; 
				$tag_array[$question->course_question_id] = $tags; 
			}
			
			$unique_tags=array_unique($unique_tags);
			$keywords=implode(",", $unique_tags);
			
	    	
			
			$is_more='yes';
			if(count($questions)<$number_of_records)
				$is_more='no';
				
			
			
			$start_pos = count($questions) + $start_pos;
			$this->view->start_pos=$start_pos;
			$this->view->course = $course; // you can use both course->title or course['title'] in the view
			$this->view->faculty = $faculty;			
			$this->view->questions = $questions;
			$this->view->keywords = $keywords;
			$this->view->tag_array = $tag_array;
			$this->view->is_more=$is_more;
			
				
			
			
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in moreQuestionsAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    	
   	}
   	
	public function moreQuestionsAction() {
   		
    	//first check if course id is provided
    	if($this->_getParam('course_id')==null  || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
    		
    	if ($this->_request->isPost()) {
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();
    		try {
	    	$feed = $this->_getParam('feed');
	    	$number_of_records = Zend_Registry::getInstance()->configuration->number->records;    	
	    	$start_pos = $this->_getParam('start_pos');
	    	//get questions and answers
    		if($feed=='faculty') {			
				$questions = Model_CourseQuestion::getFacultyQuestions($this->_getParam('course_id'),$this->_user_id,$number_of_records,$start_pos);
	    	}
	    	else if($feed=='my') {			
				$questions = Model_CourseQuestion::getMyQuestions($this->_getParam('course_id'),$this->_user_id,$number_of_records,$start_pos);
				
	    	}
	    	else {
	    		$questions = Model_CourseQuestion::getQuestions($this->_getParam('course_id'),$number_of_records,$start_pos);
	    	}
	    	
			
			//$answer_array = array();
			//foreach($questions as $question) {
			//	$answers = Model_CourseAnswer::getAnswers($question->course_question_id); 
			//	$answer_array[$question->course_question_id] = $answers; 
			//}
			
    		//get tags			
    		//$tag_array = array();
    		
			
			
			
			$start_pos = count($questions) + $start_pos;
			
			$is_more='yes';
			if(count($questions)<$number_of_records)
				$is_more='no';
			
				
			$more_questions ='';
			foreach($questions as $question) {
				
				
			$tag_string='';
			$tags = Model_TagQuestion::getTagsbyQuestion($question->course_question_id);
			if(count($tags)>0) {
				$tag_string .= "<div style='padding-top:10px;'><!--Start tag div-->";
				foreach($tags as $t) {
					$tag_name_url = strtolower(trim(preg_replace(array('~[^0-9a-z\'\?]+~i','/\?/','/\'/'), array('-','',''), html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '-', htmlentities($t->tag_name, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'))));
					$tag_string .= "<a class='tag_button' href='/tags/".$t->tag_id."/".$tag_name_url."'>". 
									$t->tag_name."</a>&nbsp;&nbsp";
				}
				$tag_string .= "</div><!-- end tag div-->"; 
			}	
				
			$url='';
			if($question->company!=null)
				$url=$question->company.' ';
			if($question->designation!=null)
				$url.=$question->designation.' ';
			if($question->college!=null)
				$url.=$question->college.' ';
			
			$name = $question->questioner_name;
			if($name==null) {
				$email_front= explode("@",$question->questioner_email);
				$name=$email_front[0];	
			}
			
			$url .=$name;
			$url = str_replace(" ", "-", trim($url));			
	    	
	    	$more_questions .=
	    	 		"<div id='question_div_".$question->course_question_id."' class='question_div'>".
	    				"<div style='width:12%;float:left'> <!-- start photo div -->".
							"<img width='50' height='50' src='/user/view-image/user_id/".$question->user_id."/n/2'/>".
						"</div> <!-- end photo div -->".
						"<div style='width:88%;float:left;border:0px solid red;'>";

	    	$more_questions .=		
	    			"<b><a href='/users/".$question->user_id."/".$url."'>".$name."</a></b>";
	    	

			$question_title_url = strtolower(trim(preg_replace(array('~[^0-9a-z\'\?]+~i','/\?/','/\'/'), array('-','',''), html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '-', htmlentities($question->question_title, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'))));

			$question_detail='';
			if($question->question!=null && strlen($question->question)>0) { 					
				$question_detail=substr(str_replace('<br />', ' ', $question->question),0,200).'...';
			}
			
			$more_questions .=
					"<div class='question_title'>".
					"<h2>".					
					"<a href='/questions/".$question->course_question_id."/".$question_title_url."'>".
						$question->question_title.
					"</a>".					
					"</h2>".	
						$question_detail.				
						$tag_string.											
					"</div>";
							
			$more_questions .=						
						"<div>".
							"<div style='float:left'><span class='grey_tiny_text'>".$question->question_date."</span></div>";
							if(Zend_Auth::getInstance()->hasIdentity()) {
								$more_questions .= "<div style='float:right'>"; 
								if($this->_user_id == $question->user_id) { 
									$more_questions .= "<a class='delete_question blue_tiny_text' id='".$question->course_question_id."' href=''>Delete</a> &nbsp;&nbsp;&nbsp; ";
								}
								$more_questions .=
								"</div>";
							}
						$more_questions .=
						"</div>";
						
						
						$more_questions .=
						
						"</div>".
					"</div> <!-- end question div -->";
			}
			
			$more_questions_link="<div class='more_questions_link'><a style='float:left;width:98%' class='more_questions whitesmoke_button' href='/course/next-questions/course_id/".$this->_getParam('course_id')."/start_pos/".$start_pos."/feed/".$feed."' id='".$start_pos."'>More Questions</a></div>";
			
			$arr = array ('success'=>'ok','more_questions'=>$more_questions,'more_questions_link'=>$more_questions_link,'is_more'=>$is_more);
		   	echo json_encode($arr);
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in moreQuestionsAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    	}else
    		return $this->_forward('no-page/','error');
   	}
   	
   	
   	
	public function moreTagsAction() {
   		
    	//first check if course id is provided
    	if($this->_getParam('tag_id')==null  || !is_numeric($this->_getParam('tag_id')))
    		return $this->_forward('no-page/','error');
    		
    	if ($this->_request->isPost()) {
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();
    		try {
	    	$number_of_records = Zend_Registry::getInstance()->configuration->number->records;    	
	    	$start_pos = $this->_getParam('start_pos');
	    	
	    	//get questions
			$questions = Model_TagQuestion::getQuestionByTag($this->_getParam('tag_id'), $number_of_records,$start_pos);
			
			$start_pos = count($questions) + $start_pos;
			
			$this->view->start_pos=$start_pos;
			$this->view->questions = $questions;
			
			$is_more='yes';
			if(count($questions)<$number_of_records)
				$is_more='no';
			
				
			$more_questions ='';
			foreach($questions as $question) {
			
			$tag_string='';
			$tags = Model_TagQuestion::getTagsbyQuestion($question->course_question_id);
			if(count($tags)>0) {
				$tag_string .= "<div style='padding-top:10px;'><!--Start tag div-->";
				foreach($tags as $t) {
					$tag_name_url = strtolower(trim(preg_replace(array('~[^0-9a-z\'\?]+~i','/\?/','/\'/'), array('-','',''), html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '-', htmlentities($t->tag_name, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'))));
					$tag_string .= "<a class='tag_button' href='/tags/".$t->tag_id."/".$tag_name_url."'>". 
									$t->tag_name."</a>&nbsp;&nbsp";
				}
				$tag_string .= "</div><!-- end tag div-->"; 
			}
			
			$url='';
			if($question->company!=null)
				$url=$question->company.' ';
			if($question->designation!=null)
				$url.=$question->designation.' ';
			if($question->college!=null)
				$url.=$question->college.' ';
						
			$name = $question->questioner_name;
			if($name==null) {
				$email_front= explode("@",$question->questioner_email);
				$name=$email_front[0];	
			}						 
			$url .=$name;
			$url = str_replace(" ", "-", trim($url));
			
	    	
	    	$more_questions .=
	    	 		"<div id='question_div_".$question->course_question_id."' class='question_div'>".
	    				"<div style='width:12%;float:left'> <!-- start photo div -->".
							"<img width='50' height='50' src='/user/view-image/user_id/".$question->user_id."/n/1'/>".
						"</div> <!-- end photo div -->".
						"<div style='width:88%;float:left;border:0px solid red;'>";

	    	$more_questions .=		
	    			"<b><a href='/users/".$question->user_id."/".$url."'>".$name."</a></b>";
	    	

			$question_title_url = strtolower(trim(preg_replace(array('~[^0-9a-z\'\?]+~i','/\?/','/\'/'), array('-','',''), html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '-', htmlentities($question->question_title, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'))));	
			$more_questions .=
					"<div class='question_title'>".
					"<h2>".					
					"<a href='/questions/".$question->course_question_id."/".$question_title_url."'>".
						$question->question_title.
					"</a>".					
					"</h2>".					
						$tag_string.											
					"</div>";
							
			$more_questions .=						
						"<div>".
							"<div style='float:left'><span class='grey_tiny_text'>".$question->question_date."</span></div>";
							if(Zend_Auth::getInstance()->hasIdentity()) {
								$more_questions .= "<div style='float:right'>"; 
								if($this->_user_id == $question->user_id) { 
									$more_questions .= "<a class='delete_question blue_tiny_text' id='".$question->course_question_id."' href=''>Delete</a> &nbsp;&nbsp;&nbsp; ";
								}
								$more_questions .=
								"</div>";
							}
						$more_questions .=
						"</div>";
				
						
						$more_questions .=						
						"</div>".
					"</div> <!-- end question div -->";
			}
			
			$more_questions_link="<div class='more_questions_link'><a style='float:left;width:98%' class='more_questions whitesmoke_button' href='' id='".$start_pos."'>More Questions</a></div>";
			
			$arr = array ('success'=>'ok','more_questions'=>$more_questions,'more_questions_link'=>$more_questions_link,'is_more'=>$is_more);
		   	echo json_encode($arr);
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in moreQuestionsAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
    	}else
    		return $this->_forward('no-page/','error');
   	}
   	
   	
   	
   	
	public function myFeedAction() {
		//first check if course id is provided
    	if($this->_getParam('course_id')==null || $this->_user_id==null  || !is_numeric($this->_getParam('course_id')))
    		return $this->_forward('no-page/','error');
		try {
			//check if user is enrolled
			//CHECK IF THE USER IS STILL ENROLLED FOR THIS COURSE OTHERWISE REDIRECT HIM TO COURSE-FEED PAGE
			if($this->_user_id !=null) {
				$currentCourses = Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);				
				//CHECK IF THE USER IS FACULTY FOR THIS COURSE OTHERWISE REDIRECT HIM TO VIEW PAGE		
				$isFaculty = Model_CourseFaculty::isCourseFaculty($this->_getParam('course_id'),$this->_user_id);
				if($currentCourses->payment_received=='N' && count($isFaculty)==0)			
					return $this->_redirect('/course/course-feed/course_id/'.$this->_getParam('course_id'));			
			}
			
			//get course
			$course = Model_Course::loadCourse($this->_getParam('course_id'));
			//get faculty for this course
			$faculty = Model_CourseFaculty::getFacultyByCourseId($this->_getParam('course_id'));
			
			//get number of questions to display
			$number_of_records = Zend_Registry::getInstance()->configuration->number->records;		
	    	$this->view->number_of_records=$number_of_records;
			//get questions and answers
			if(count($isFaculty)>0) {
				$questions = Model_CourseQuestion::getFacultyQuestions($this->_getParam('course_id'),$this->_user_id,$number_of_records);
				$feed = 'faculty';
			}
			else if(count($currentCourses)>0) {
				$questions = Model_CourseQuestion::getMyQuestions($this->_getParam('course_id'),$this->_user_id,$number_of_records);
				$feed = 'my';
			}
			 
			/*
			$answer_array = array();
			foreach($questions as $question) {
				$answers = Model_CourseAnswer::getAnswers($question->course_question_id); 
				$answer_array[$question->course_question_id] = $answers; 
			}
			*/
			
			//get tags			
    		$tag_array = array();
    		$unique_tags = array();
			foreach($questions as $question) {
				$tags = Model_TagQuestion::getTagsbyQuestion($question->course_question_id);
				foreach($tags as $t)
					$unique_tags[]=$t->tag_name; 
				$tag_array[$question->course_question_id] = $tags; 
			}
			
			 
			$unique_tags=array_unique($unique_tags);
			$keywords=implode(",", $unique_tags);
			
			
			$start_pos = count($questions);
			$this->view->start_pos=$start_pos;
			
			$this->view->course = $course; // you can use both course->title or course['title'] in the view
			$this->view->faculty = $faculty;			
			$this->view->questions = $questions;
			$this->view->keywords = $keywords;
			//$this->view->answer_array = $answer_array;
			$this->view->tag_array = $tag_array;
			$this->view->feed = $feed;			
		} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in myFeedAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
   	}
   	
	public function questionFeedAction() {    	
		
		if($this->_getParam('course_question_id')==null  || !is_numeric($this->_getParam('course_question_id')))
    		return $this->_forward('no-page/','error');
    	try {
    		
    		
			//get questions and answers
			$questions = Model_CourseQuestion::getQuestion($this->_getParam('course_question_id'));
						
			if(count($questions)!=0) {

			
			$top_questions = Model_CourseQuestion::getRecentQuestions($questions->course_id,$this->_getParam('course_question_id'),10);
			$this->view->top_questions=$top_questions;
			
			//get course
			$course = Model_Course::loadCourse($questions->course_id);
			//get faculty for this course
			$faculty = Model_CourseFaculty::getFacultyByCourseId($questions->course_id);
			
			$voteAnswerModel = new Model_VoteAnswer();
			$answer_array = array();
			$answers = Model_CourseAnswer::getAnswers($questions->course_question_id);
			$vote_answer_array = array();
			foreach($answers as $answer) {
				//$vote_array = array();
				$votes=$voteAnswerModel->getVotes($answer->course_answer_id);
				//echo '<br/>course_answer_id==='.$answer->course_answer_id;		
				//echo '<br/>votes==='.$votes->votes;		
				$user_vote=null;
				
    			if($this->_user_id!=null) {
    				$user_vote=$voteAnswerModel->checkUserVote($answer->course_answer_id,$this->_user_id);    				
    			}
    			$user_vote_vote=null;
    			if($user_vote!=null)
    				$user_vote_vote=$user_vote->vote;
    				
    			$vote_array=array('votes'=>$votes->votes,'user_vote'=>$user_vote_vote);
    			$vote_answer_array[$answer->course_answer_id]=$vote_array;
    			//echo '<br/>';
    			//print_r($vote_array);
			}				
			$answer_array[$questions->course_question_id] = $answers; 
			//print_r($vote_answer_array);
			//$temp = $vote_answer_array[1220];
			//echo '<br/>ok='.$temp['votes'];
			$this->view->vote_answer_array=$vote_answer_array;
			//print_r($temp);
			
			//get tags			
    		$tag_array = array();
    		$unique_tags = array();
    		$unique_tag_ids = array();
			$tags = Model_TagQuestion::getTagsbyQuestion($questions->course_question_id);
			foreach($tags as $t)
			{
				$unique_tags[]=$t->tag_name;
				$unique_tag_ids[]=$t->tag_id;
			}			 
			$unique_tags=array_unique($unique_tags);
			$unique_tag_ids=array_unique($unique_tag_ids);
			
			$keywords=implode(",", $unique_tags);
			$tag_ids=implode(",", $unique_tag_ids);
			if($tag_ids!=null) {			
				$tag_questions = Model_TagQuestion::getQuestionsByMultipleTags($questions->course_question_id,$tag_ids,10);
				$this->view->tag_questions=$tag_questions;
				$tag_counts = Model_TagQuestion::getTagCount($tag_ids);
				$this->view->tag_counts=$tag_counts;
			}

			
			
			$tag_array[$questions->course_question_id] = $tags;

			$voteQuestionModel = new Model_VoteQuestion();
    		$votes=$voteQuestionModel->getVotes($questions->course_question_id);
    		if($this->_user_id!=null) {
    			$user_vote=$voteQuestionModel->checkUserVote($questions->course_question_id,$this->_user_id);
    			$this->view->user_vote=$user_vote;
    		}

    		if($this->_user_id !=null) {
    			$userModel = new Model_User();
    			$user = $userModel->loadUserProfile($this->_user_id);
    			$this->view->user = $user;
    			//check if student is enrolled
    			$batch=Model_Enrollment::isStudentEnrolled($course->course_id,$this->_user_id);
    			$this->view->batch=$batch;
    		}
    		
    		
    		$this->view->votes=$votes;   		
			
			$this->view->course = $course; // you can use both course->title or course['title'] in the view
			$this->view->faculty = $faculty;			
			$this->view->questions = $questions;
			$this->view->answer_array = $answer_array;			
			$this->view->tag_array = $tag_array;
			$this->view->keywords = $keywords;
			}
			else
				$this->view->message = 'This question was deleted';
    	} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in questionFeedAction course question id='.$this->_getParam('course_question_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
   	}
   	
   	//This is the course ask question action
    public function guestAskQuestionAction() {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
    	return $this->_redirect('/course/course-feed/course_id/'.$this->_getParam('course_id'));
    }
   	//This is the course ask question action
    public function askQuestionAction() {
    	
    	if ($this->_request->isPost()) {
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();    	    
    		try {
    			$question_title = $this->_getParam('question_title');
    			$question_text = nl2br($this->_getParam('question'));    			
    			
    			/*
    			ob_start();
				print_r($tags);
				$var = ob_get_contents();
				ob_end_clean();
				$fp=fopen('error.txt','a');
				fputs($fp,$var);
				fclose($fp);
				*/
    			
	    		$courseQuestionModel = new Model_CourseQuestion();	    		
				$rowCourseQuestion = $courseQuestionModel->addQuestion(
				$this->_getParam('course_id'),
				$this->_user_id,
				$this->_getParam('faculty_id'),
	    		$question_text,
	    		$question_title
	    		);
	    		
	    		//insert the tags
	    		$tagQuestionModel = new Model_TagQuestion();
	    		$tagModel = new Model_Tag();
    			$tags = json_decode($this->_getParam('tags'));
    			$tag_string ='';
    			foreach($tags as $t)
    			{
    				$tag_id = $t->tag_value;
    				//if both are same means new tag - first insert in tag table and get tag_id    				
    				if($t->tag_name==$t->tag_value) {
    					//now before inserting check if that tag exist
    					$rowTagId = Model_Tag::getTag(ucwords($t->tag_name));
    					if(count($rowTagId)==0) {    						
    						$rowTag = $tagModel->addTag(ucwords($t->tag_name));    					
    						$tag_id = $rowTag->tag_id;
    					}
    					else {
    						//Zend_Registry::get('logger')->err('tag tag tag='.$rowTagId[0]->tag_id);
    						$tag_id = $rowTagId[0]->tag_id;
    					}
    						
    				}
    				$rowTagQuestion = $tagQuestionModel->addTagQuestion($tag_id,$rowCourseQuestion->course_question_id);
    				$tag_string .= "<a href='' class='tag_button'>".$t->tag_name."</a>&nbsp;&nbsp";    				    				
    			}
	    		
	    		
	    		
    			$name = Zend_Auth::getInstance()->getIdentity()->first_name;
				if($name==null) {
					$email_front= explode("@",Zend_Auth::getInstance()->getIdentity()->email);
					$name=$email_front[0];	
				}
				
				$question_title_url = strtolower(trim(preg_replace(array('~[^0-9a-z\'\?]+~i','/\?/','/\'/'), array('-','',''), html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '-', htmlentities($question_title, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'))));
														
				$display_question='';										
				if($question_text !=null && strlen($question_text)>0 )
					$display_question = substr(str_replace('<br />', ' ', $question_text),0,200);
				Zend_Registry::get('logger')->err('display question='.$display_question);
				
				$question = 
				"<div id='question_div_".$rowCourseQuestion->course_question_id."' style='display:none' class='question_div'>".
					"<div style='width:12%;float:left'> <!-- start photo div -->".
						"<img width='50' height='50' src='/user/view-image/user_id/".$rowCourseQuestion->user_id."/n/1'/>".
					"</div> <!-- end photo div -->".
					"<div style='width:88%;float:left;border:0px solid red'> <!-- start non photo div -->".
						"<a href='/user/view/user_id/".$this->_user_id."'><b>".$name."</b></a><br/>".
						"<div class='question_title'>".
					"<h2>".					
				    "<a href='/questions/".$rowCourseQuestion->course_question_id."/".$question_title_url."'>".
						$question_title.
					"</a>".					
					"</h2>".
					$display_question.					
					"<div style='padding-top:10px;'>".
						$tag_string.	
					"</div>".	
					"</div>".							    	
						
						"<div>".
							"<div style='float:left'><span class='grey_tiny_text'>".date("M d Y h:i A", strtotime($rowCourseQuestion->date_created))."</span></div>".
							"<div style='float:right'>".
								"<a class='delete_question blue_tiny_text' id='".$rowCourseQuestion->course_question_id."' href=''>Delete</a> &nbsp;&nbsp;&nbsp; ". 
								//"<a class='answer_link blue_tiny_text'  id='".$rowCourseQuestion->course_question_id."' href=''>Answer</a>".
							"</div>".
						"</div>".													
					"</div>".
				"</div>";
	    	    $arr = array ('success'=>'ok','question'=>$question);
	    	    echo json_encode($arr);
	    	    
	    	    $userModel = new Model_User();
        		$faculty = $userModel->loadUserProfile($this->_getParam('faculty_id'));
        		
        		$course = Model_Course::loadCourse($this->_getParam('course_id'));
        	
        		$batch=Model_Enrollment::isStudentEnrolled($this->_getParam('course_id'),$this->_user_id);
        		//check if batch_id exists
        		$batch_id=$this->_getParam('batch_id');
        		if($batch_id==null)
        			$batch_id=$batch->batch_id;
        		if($batch_id!=null && $batch->payment_received='Y'){
        			Zend_Registry::get('logger')->err('batch id ='.$batch_id);
        			$batch_mates = Model_User::getBatchMates($this->_getParam('course_id'), $batch_id,$this->_user_id );
        			foreach($batch_mates as $mate) {
        				Zend_Registry::get('logger')->err('batch email ='.$mate->email);
        				$user = $userModel->loadUserProfile($mate->user_id);
        				$this->sendEmailToFaculty($rowCourseQuestion->course_question_id,$question_title,$question_text,$user,$course);
        			}
        		}
	    	    
	    	    $this->sendEmailToFaculty($rowCourseQuestion->course_question_id,$question_title,$question_text,$faculty,$course);
    		} catch (Exception $e) {    		
    			Zend_Registry::get('logger')->err('Exception occured in askQuestionAction course id='.$this->_getParam('course_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			return $this->_forward('exception/','error');
    		} 
    	}
    	else
    		return $this->_forward('no-page/','error');
    		  
   	}
   	
	//This is the action that is called when an answer is submitted
    public function answerAction() {
    	
    	if ($this->_request->isPost()) {
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();    	    
    		try {
    			$answer_text = nl2br($this->_getParam('answer'));
	    		$courseAnswerModel = new Model_CourseAnswer();
				$rowCourseAnswer = $courseAnswerModel->addAnswer(
				$this->_getParam('course_question_id'),
				$this->_user_id,
				$answer_text
	    		);
	    		
	    		$name = Zend_Auth::getInstance()->getIdentity()->first_name;
				if($name==null) {
					$email_front= explode("@",Zend_Auth::getInstance()->getIdentity()->email);
					$name=$email_front[0];	
				}
				
	    		
				$answer =				
				"<div id='answer_div_".$rowCourseAnswer->course_answer_id."' class='answer_div' style='padding:10px;border-bottom:1px solid #d9d9d9;margin-bottom:20px'>".
					"<div style='width:12%;float:left'>".
						"<img width='50' height='50' src='/user/view-image/user_id/".$rowCourseAnswer->user_id."/n/2'/>".
						"<br/>".
						"<div id='answers100' class='upvote' data-userid='".$this->_user_id."' data-answerid='".$rowCourseAnswer->course_answer_id."' style='float:left'>".
							"<a class='upvote' title='You cannot vote your own answer'></a>".
	    					"<span class='count'>0</span>".
							"<a class='downvote' title='You cannot vote your own answer'></a>".
	    				"</div>".							
					"</div>".
					"<div style='width:88%;float:left'> <!-- start non photo div -->".
						"<a href='/user/view/user_id/".$this->_user_id."'><b>".$name."</b></a><br/>".$answer_text."<br/>".
						"<span class='grey_tiny_text'>".date("M d Y h:i A", strtotime($rowCourseAnswer->date_created))."</span>".
						" &nbsp;&nbsp;&nbsp;<a class='delete_answer blue_tiny_text' id='".$rowCourseAnswer->course_answer_id."' href=''>Delete</a>".
					"</div>".				
				"</div>";
										
	    	    $arr = array ('success'=>'ok','answer'=>$answer);
	    	    echo json_encode($arr);
	    	    $this->sendEmailOnAnswer($this->_getParam('course_question_id'),$answer_text);
    		} catch (Exception $e) {    		
    			Zend_Registry::get('logger')->err('Exception occured in answerAction course question id='.$this->_getParam('course_question_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			return $this->_forward('exception/','error');
    		} 
    	}    	
    	else
    		return $this->_forward('no-page/','error');   	
   	}
   	
	//This is the action that is called when a delete question link is clicked
    public function editQuestionAction() {
    	
    	if ($this->_request->isPost()) {
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();    	    
    		try {
    			$userModel = new Model_User();
    			$user = $userModel->loadUserProfile($this->_user_id);
    			$courseQuestionModel = new Model_CourseQuestion();
	    		
	    		if($user->role_id==3) {
					$rowCourseQuestion = $courseQuestionModel->editQuestion($this->_getParam('course_question_id'),$this->_getParam('question_title'));
					

				$tagQuestionModel = new Model_TagQuestion();
				//first delete all tags for that question
				$tagQuestionModel->deleteTagQuestion($this->_getParam('course_question_id'));
	    		$tagModel = new Model_Tag();
				
    			$tags = json_decode($this->_getParam('tags'));
    			$tag_string ='';
    			foreach($tags as $t)
    			{
    				$tag_id = $t->tag_value;
    				//if both are same means new tag - first insert in tag table and get tag_id    				
    				if($t->tag_name==$t->tag_value) {
    					$rowTag = $tagModel->addTag(ucwords($t->tag_name));    					
    					$tag_id = $rowTag->tag_id;
    				}
    				$rowTagQuestion = $tagQuestionModel->addTagQuestion($tag_id,$this->_getParam('course_question_id'));    				    				    				
    			}
	    		$arr = array ('success'=>'ok');
	    	    echo json_encode($arr);
	    		}	    	    
    		} catch (Exception $e) {    		
    			Zend_Registry::get('logger')->err('Exception occured in editQuestionAction course question id='.$this->_getParam('course_question_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			return $this->_forward('exception/','error');
    		} 
    	}
    	else    		
    		return $this->_forward('no-page/','error');   	
   	}
   	
   	
	//This is the action that is called when a delete question link is clicked
    public function getTagAction() {
    	
    	
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();    	    
    		try {
    			$tag_array = array();
    			$tagRow = Model_Tag::getTags(trim($this->_getParam('term')));
    			//Zend_Registry::get('logger')->err('size='.count($tagRow));
    			foreach($tagRow as $tag) {
    				$tag_array[$tag->tag_id] = $tag->tag_name;	    			
    			}
    			
    			ob_start();
				print_r($tag_array);
				$var = ob_get_contents();
				ob_end_clean();
				$fp=fopen('error.txt','a');
				fputs($fp,$var);
				fclose($fp);
	    		
    			$arr = array ('1'=>'Balance Sheet','2'=>'Balance Statement','3'=>'Income Statement','4'=>'Cash Flow Statement');
	    	    echo json_encode($tag_array);
	    			    	    
    		} catch (Exception $e) {    		
    			Zend_Registry::get('logger')->err('Exception occured in getTagAction'. $e->getTraceAsString());
    			return $this->_forward('exception/','error');
    		} 
    	   	
   	}
   	
	//This is the action that is called when a delete question link is clicked
    public function deleteQuestionAction() {
    	
    	if ($this->_request->isPost()) {
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();    	    
    		try {
	    		$courseQuestionModel = new Model_CourseQuestion();
	    		$rowCourseQuestion=$courseQuestionModel->find($this->_getParam('course_question_id'))->current();
	    		if($rowCourseQuestion->user_id==$this->_user_id) {
					$rowCourseQuestion = $courseQuestionModel->deleteQuestion($this->_getParam('course_question_id'));
	    			$arr = array ('success'=>'ok');
	    	    	echo json_encode($arr);
	    		}	    	    
    		} catch (Exception $e) {    		
    			Zend_Registry::get('logger')->err('Exception occured in deleteQuestionAction course question id='.$this->_getParam('course_question_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			return $this->_forward('exception/','error');
    		} 
    	}
    	else    		
    		return $this->_forward('no-page/','error');   	
   	}

   	
   	//This is the action that is called when a delete answer link is clicked
    public function deleteAnswerAction() {    	
    	if ($this->_request->isPost()) {  
    		$this->_helper->viewRenderer->setNoRender(true);
    		$this->_helper->layout->disableLayout();  	    
    		try {
	    		$courseAnswerModel = new Model_CourseAnswer();
	    		$rowCourseAnswer=$courseAnswerModel->find($this->_getParam('course_answer_id'))->current();
	    		if($rowCourseAnswer->user_id==$this->_user_id) {
					$rowCourseAnswer = $courseAnswerModel->deleteAnswer($this->_getParam('course_answer_id'));
	    			$arr = array ('success'=>'ok');
	    	    	echo json_encode($arr);
	    		}	    	    
    		} catch (Exception $e) {    		
    			Zend_Registry::get('logger')->err('Exception occured in deleteAnswerAction course answer id='.$this->_getParam('course_answer_id').' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    			return $this->_forward('exception/','error');
    	} 
    	}
    	else
    		return $this->_forward('no-page/','error');   	
   	}
   	
   	
   	
   	//When a new question is posted - all faculty should get an email
   	private function sendEmailToFaculty($course_question_id,$question_title,$question,$faculty,$course) {
   		try {
	   		$questioner = Model_CourseQuestion::getQuestion($course_question_id);
	   		//get faculty for this course
	   		
	   		//UNCOMMENT IF YOU WANT TO SEND THE QUESTION TO ALL FACULTY
			//$faculty = Model_CourseFaculty::getFacultyByCourseId($questioner[0]->course_id);
			
			$templateMessage = new Zend_View();
	    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    	
	    	$templateMessage->course_question_id = $course_question_id;
	    	$templateMessage->questioner = $questioner->questioner_name;
	    	$templateMessage->question_title = $question_title;
	    	$templateMessage->question = $question;
	    	$subject = 'DeZyre.com: '.$questioner->questioner_name.' has posted a new question ';    	
	    	
	    	$templateMessage->course = $course;
   			$templateMessage->to_user_name = $faculty->first_name.' '.$faculty->last_name;
	    	$templateMessage->to_user_email = $faculty->email;    	
	    	$this->_helper->SendEmailAction($subject,$templateMessage,'question.phtml');
			
	    	//UNCOMMENT IF YOU WANT TO SEND THE QUESTION TO ALL FACULTY
			/*
	    	foreach($faculty as $faculty) {
				$templateMessage->to_user_name = $faculty->first_name.' '.$faculty->last_name;
	    		$templateMessage->to_user_email = $faculty->email;    	
	    		$this->_helper->SendEmailAction($subject,$templateMessage,'question.phtml');
			}
			*/
   		} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in sendEmailToFaculty method course question id='.$course_question_id.' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
   	}
   	
   	private function sendEmailOnAnswer($course_question_id,$answer)
   	{
   		try {
	 		$questioner = Model_CourseQuestion::getQuestion($course_question_id);
	 		$answers = Model_CourseAnswer::getAnswers($course_question_id);
	 		
	 		$templateMessage = new Zend_View();
	    	$templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    	
	    	$templateMessage->course_question_id = $course_question_id;
	    	$templateMessage->commentor = Zend_Auth::getInstance()->getIdentity()->first_name.' '.Zend_Auth::getInstance()->getIdentity()->last_name;
	    	$templateMessage->answer = $answer;
	    	//$subject = Zend_Auth::getInstance()->getIdentity()->first_name.' has posted an answer ';
	    	$subject = 'Your question has been answered on Dezyre.com';
	    	
	    	//Send email to person who posted the question provided he's not the one posted the answer
	    	//dont send email to manipal org id 11
	    	if ($this->_user_id!=$questioner->user_id && $questioner->organization_id!=11) {
	    		//Zend_Registry::get('logger')->err('Sending email');
	    		$templateMessage->to_user_name = $questioner->questioner_name;
	    		$templateMessage->to_user_email = $questioner->email;    	
	    		$this->_helper->SendEmailAction($subject,$templateMessage,'answer.phtml');
	    	}
	    	
	    	$user_id_array = array();
	    	$user_id_array[] = $questioner->user_id;
	    	foreach($answers as $answer)
	    	{
	    		if (!in_array($answer->user_id, $user_id_array) && $this->_user_id!=$answer->user_id) {
	    			$templateMessage->to_user_name = $answer->replier_name;
	    			$templateMessage->to_user_email = $answer->email;   		
	    			$subject = Zend_Auth::getInstance()->getIdentity()->first_name.' has posted an answer ';
	    		   	$this->_helper->SendEmailAction($subject,$templateMessage,'answer.phtml');
	    			$user_id_array[] = $answer->user_id;
	    		}
	    	}   	
   		} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in sendEmailOnAnswer method course question id='.$course_question_id.' in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());
    		return $this->_forward('exception/','error');
    	}
   	}
   	
   	
	private function updateBillingAddress($user_id,$country,$state,$city,$full_name,$address_line_1,$address_line_2,$zip,$zip_code,$phone,$email,$address_id)
	{
		try {
		//first get country
		$country_id = '';
		$countryRow = Model_Country::getCountryId($country);		
		if(count($countryRow)==1) {
			//then get country_id
			$country_id = $countryRow[0]->country_id;
		}
		else {
			//we have more than 2 rows with the same name - so go ahead and insert - we will manually update later
			$countryModel = new Model_Country();
			$rowCountry = $countryModel->addCountry($country);
			$country_id = $rowCountry->country_id;
		}
		
		//check if state exists - if it does - then select that state_id else insert new one
		$stateRow = Model_State::getState($state,$country_id);		
		$state_id='';
		if(count($stateRow)==1) {
			//then get state_id 
			$state_id = $stateRow[0]->state_id;
			//echo 'STATE ID='.$stateRow[0]->state_id;
		}
		else {
			//we have more than 2 rows with the same name - so go ahead and insert
			$stateModel = new Model_State();
			$rowState = $stateModel->addState($country_id,$state);
			$state_id = $rowState->state_id;
		}
		//check if city exists - if it does then select city_id else insert new one
		$cityRow = Model_City::getCity($city,$state_id);
		$city_id='';
		if(count($cityRow)==1) {
			//then get city_id 
			$city_id = $cityRow[0]->city_id;
		}
		else {
			//we have more than 2 rows with the same name - so go ahead and insert
			$cityModel = new Model_City();
			$rowCity = $cityModel->addCity($state_id,$city);
			$city_id = $rowCity->city_id;
		}				
		
		
		if($address_id == null) //means new address 
		{
			//add new address
			$addressModel = new Model_Address();
			$rowAddress = $addressModel->createAddress($city_id,$full_name,$address_line_1,$address_line_2,$zip,$zip_code,$phone,$email);
			//now add this address_id to user
			$userModel = new Model_User();
			$userModel->updateUserAddress($user_id,$rowAddress->address_id);						
		}
		else {
			//update existing address					
			$addressModel = new Model_Address();
			$rowAddress = $addressModel->updateAddress($address_id,$city_id,$full_name,$address_line_1,$address_line_2,$zip,$zip_code,$phone,$email);	
		}
		} catch (Exception $e) {    		
    		Zend_Registry::get('logger')->err('Exception occured in updateBillingAddress in CourseController: ' . $e->getMessage().'---------'. $e->getTraceAsString());    		
    	}
	}
	
	
	private function registerUser($email,$course_id,$enrollment_id,$course_code,$phone,$is_active)
	{
		//Zend_Registry::get('logger')->err('email='.$email);
		$userModel = new Model_User();
    	$enrollmentModel = new Model_Enrollment();
    	$templateMessage = new Zend_View();
	    $templateMessage->setScriptPath(APPLICATION_PATH . '/views/scripts/emails');
	    $is_existing_user=true;
		//FIRST CHECK IF EMAIL ALREADY EXISTS
    	$currentUser = $userModel->loadUserByEmail($email);
    	if(count($currentUser)==1) {
    		//Zend_Registry::get('logger')->err('inside if currentUser=1 courseid='.$course_id);    				
    		//Zend_Registry::get('logger')->err('userid='.$currentUser->user_id);
	    	//THEN CHECK IF USER IS ALREADY ENROLLED FOR THIS COURSE
    		$enrollmentRow = Model_Enrollment::isStudentEnrolled($course_id,$currentUser->user_id);
			if(count($enrollmentRow)!=0)
			{
				//delete the enrollment id and redirect the user to the course
				try {						
					$enrollmentModel->deleteEnrollment($enrollment_id);
				} catch (Exception $e) {
					Zend_Registry::get('logger')->err('Exception occured while deleting enrollment id: Possible refresh by user on thankyou page ' . $e->getMessage());	
				}							
				//delete cookie
				setcookie($course_code, $enrollment_id, mktime()-(86400*30), "/") or die("Could not delete enroll cookie");
				if($enrollmentRow->payment_received=='Y') {
					Zend_Registry::get('logger')->err('inside payment_received=Y');
					return $this->_redirect('/course/my-view/course_id/'.$course_id.'/email/'.$email);
				}
				else
					$enrollment_id = $enrollmentRow->enrollment_id;				
			}    				
			$user_id=$currentUser->user_id;
			
    	}
    	//IF EMAIL DOES NOT EXISTS REGISTER THE USER IN
    	else if(count($currentUser)!=1) {    			
    		$role = Model_Role::getRole('student');
	    	$role = $role->toArray();
	    		    	
	    	//generate random password
			$password = $this->randomPassword();

			//GET REFERER FROM COOKIE
			$request = new Zend_Controller_Request_Http();
	    	$referrer = $request->getCookie('dezyre-referrer');
	    			
	    			
			$rowUser=$userModel->createUser(				
					$email,
					$password,
					$role[0]['role_id'],
					null,
					null,
					$phone,
					$referrer,
					$is_active
			);
			$currentUser = $userModel->loadUserProfile($rowUser->user_id);
			//IF NEW USER LOG THE USER IN
			$auth = Zend_Auth::getInstance();
			$storage = $auth->getStorage();			
	        $storage->write($currentUser);
	        	
			$is_existing_user=false;
			//Send welcome email with password					
			$templateMessage->to_user_email = $email;
		    $templateMessage->is_new_user = 'yes';
		    $templateMessage->password = $password;
		    $subject = 'Welcome to DeZyre. Account Created';
		    $this->_helper->SendEmailAction($subject,$templateMessage,'welcome.phtml');
		    $user_id=$rowUser->user_id;					
    	}
    	//Zend_Registry::get('logger')->err('registeraction = enrollment_id====' .$enrollment_id);
    	$ret_array = array("currentUser"=>$currentUser,"is_existing_user"=>$is_existing_user,"enrollment_id"=>$enrollment_id);
    	return $ret_array;
	}
	
	public function randomPassword()
	{
		$strings = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		
		$password = '';
		for($i=0; $i<10;$i++){
			$password .= substr($strings, rand(0, strlen($strings)), 1);
		}
		return $password;
	}
	
	/*
	public function randomPassword()
	{
		$strings = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		
		$password = '';
		for($i=0; $i<6;$i++){
			$password .= substr($strings, rand(0, strlen($strings)), 1);
		}
		return $password;
	}
	*/
    
    
	
}

