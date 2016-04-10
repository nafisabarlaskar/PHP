<?php

class BlogController extends Zend_Controller_Action
{
	private $_user_id;

    public function init()
    {
        /* Initialize action controller here */
    	/* Initialize action controller here */
    	$auth = Zend_Auth::getInstance();
		if($auth->hasIdentity())
    		$this->_user_id = Zend_Auth::getInstance()->getIdentity()->user_id;
    }
    
	public function rssAction1()
    {
    	try {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();

    	$feed = new Zend_Feed_Writer_Feed;
    	
    	$feed->setTitle('Articles on Finance careers | DeZyre.com');
    	$feed->setLink('http://www.dezyre.com');
    	$feed->setFeedLink('http://www.dezyre.com/blog/rss/', 'atom');
    	$feed->addAuthor(array(
   			'name'  => 'DeZyre',
    		'email' => 'contact@dezyre.com',
    		'uri'   => 'http://www.dezyre.com/',
		));
    	$feed->setDateModified(time());   	
    	
    	
    	$blogs = Model_Blog::getBlogs();
    	
    	foreach($blogs as $blog) {
    		$blog_title=strtolower(trim(preg_replace(array('~[^0-9a-z\'\?]+~i','/\?/','/\'/'), array('-','',''), html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '-', htmlentities($blog->title, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'))));
			$date = date_create($blog->date_created);
		
    		$entry = $feed->createEntry();
			$entry->setTitle($blog->title);
			$entry->setLink('http://www.dezyre.com/article/'.$blog_title.'/'.$blog->blog_id);
			
			if($blog->user_id!=null) {
				$entry->addAuthor(array(
	    			'name'  => $blog->first_name.' '.$blog->last_name,
	    			'email' => $blog->email	    						
				));
			}
			$entry->setDateCreated(time());
			$entry->setDateModified(time());
			$entry->setDescription($blog->preview);
			$entry->setContent($blog->article);
			$feed->addEntry($entry);    		
    	}
    	
		/**
		* Render the resulting feed to Atom 1.0 and assign to $out.
		* You can substitute "atom" with "rss" to generate an RSS 2.0 feed.
		*/
		$out = $feed->export('atom');
		echo $out;
    	} catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Blog Controller rssAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }

    }
    
	public function rssAction()
    {
    	try {
    	//$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();
		
    	
    	$out = 
    	"<?xml version='1.0' encoding='UTF-8' ?>".
				"<feed xml:lang='en-US' xmlns='http://www.w3.org/2005/Atom'>".
  					"<title>Articles on Finance Careers | DeZyre.com</title>".
  					"<subtitle>The latest articles from DeZyre.com</subtitle>".
  					"<link href='http://www.dezyre.com/blog/rss/' rel='self'/>".
  					"<updated>".date('c')."</updated>".
    				"<id>http://www.dezyre.com/</id>".
  					"<author>".
   						"<name>DeZyre</name>".
   						"<email>contact@dezyre.com</email>".
    					"<uri>http://www.dezyre.com/</uri>".
  					"</author>";
    	
    	$blogs = Model_Blog::getBlogs();    	
    	foreach($blogs as $blog) {
    		$blog_title=strtolower(trim(preg_replace(array('~[^0-9a-z\'\?]+~i','/\?/','/\'/'), array('-','',''), html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '-', htmlentities($blog->title, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'))));
			
			
    		$out .=    	
  					"<entry>".
   						"<title>".$blog->title."</title>".
   						"<link href='http://www.dezyre.com/article/".$blog_title."/".$blog->blog_id."'/>".    		
   						"<updated>".date("c", strtotime($blog->date_created))."</updated>";
    		if($blog->user_id!=null) {
    				$out .=
   						"<author>".
    						"<name>".$blog->first_name.' '.$blog->last_name."</name>".
    						"<email>".$blog->email."</email>".
   						"</author>";
    		}
    		$out .=
   						"<summary type='html'><![CDATA[".$blog->preview."<]]></summary>".
   						//"<summary type='html'>".$blog->preview."</summary>".
    					"<id>http://www.dezyre.com/article/".$blog_title."/".$blog->blog_id."/</id>".
    					"<content type='html'><![CDATA[".$blog->article."<]]></content>".
    					//"<content type='html'>".$blog->article."</content>".
  					"</entry>";
    	}
		$out .=	"</feed>";
    	
		//echo $out;
		$this->view->feed=$out;
    	} catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Blog Controller rssAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }

    }
    
    
	public function indexAction()
    {
    	$blogs = Model_Blog::getBlogs();
    	$this->view->blogs=$blogs;
    }
    
	public function subscribeAction()
    {
    	$this->_helper->viewRenderer->setNoRender(true);
    	$this->_helper->layout->disableLayout();  	
    	
    	try {
	    	$email = $this->_getParam('email');

	    	$blogSubscribeModel = new Model_BlogSubscribe();
	    	$blogSubscribeModel->addSubsriber($email);
	    	echo "ok";
	    		    	
    	} catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Blog Controller SubscribeAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }
	    
    	
    	
    	
    }
    
    public function addAction()
    {
    	
    }
    
	public function panelAction()
    {
    	
    }
    
    public function listAction()
    {
    	$blogs = Model_Blog::getBlogs();
    	$this->view->blogs=$blogs;
    	
    }
    
	public function viewAction()
    {
    	$blog_id = $this->_getParam('blog_id');
    	$title = $this->_getParam('title');
    	
    	$blog = Model_Blog::loadBlog($blog_id);
    	$blog_title=strtolower(trim(preg_replace(array('~[^0-9a-z\'\?]+~i','/\?/','/\'/'), array('-','',''), html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '-', htmlentities($blog->title, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8'))));
    	
    	/*
    	if($blog->user_id!=null) {
    		$userModel = new Model_User();
	    	$user = $userModel->loadUserProfile($blog->user_id);
	    	$this->view->user=$user;
    	}
    	*/
    	
    	//echo $blog_title;
    	
    	if(strcmp($this->_getParam('title'),$blog_title)!=0) 
  		  //	return $this->_redirect('/courseview/'.$course->course_id.'/'.preg_replace('/\s+/','-',$course->title));
  		$this->_helper->Redirector
        ->setCode(301) 
        ->gotoRouteAndExit(array('title' => $blog_title,
        						 'blog_id' => $blog->blog_id             					 
           						)
        					);
        	
    	$blogs = Model_Blog::getRecentBlogs($blog_id,10);
    	$this->view->blogs=$blogs;
        
    	$this->view->blog_title=$blog_title;
    	$this->view->blog=$blog;
    	
    }
    
	public function editAction()
    {
    	try {
	    	if ($this->_request->isPost()) 
			{
				$user_id = $this->_getParam('user_id');
    			if(strlen($user_id)==0)
    				$user_id=null;
				$blog_id = $this->_getParam('blog_id');
				$title = $this->_getParam('title');
		    	$meta_title = $this->_getParam('meta_title');
		    	$meta_description = $this->_getParam('meta_description');
		    	$meta_keywords = $this->_getParam('meta_keywords');
		    	$article = $this->_getParam('article');
		    	$preview = $this->_getParam('preview');
	
		    	$blogModel = new Model_Blog();
		    	$blogModel->editBlog($blog_id,$title,$meta_title,$meta_description,$meta_keywords,$article,$preview,$user_id);
		    	return $this->_redirect('/blog/list');				
			}
			else {
				$blog_id = $this->_getParam('blog_id');
	    		$blog = Model_Blog::loadBlog($blog_id);
	    		$this->view->blog=$blog;			
			}
    	} catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Blog Controller editBlogAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }
    	
    }
    
    public function submitBlogAction()
    {
    	//Zend_Registry::get('logger')->err('Inside blog');
    	try {
    		
    		$user_id = $this->_getParam('user_id');
    		if(strlen($user_id)==0)
    			$user_id=null;
	    	$title = $this->_getParam('title');
	    	$meta_title = $this->_getParam('meta_title');
	    	$meta_description = $this->_getParam('meta_description');
	    	$meta_keywords = $this->_getParam('meta_keywords');
	    	$preview = $this->_getParam('preview');
	    	$article = $this->_getParam('article');

	    	$blogModel = new Model_Blog();
	    	$blogModel->addBlog($title,$meta_title,$meta_description,$meta_keywords,$article,$preview,$user_id);
	    	return $this->_redirect('/blog/list');	    	
    	} catch(Exception $e) {
    		Zend_Registry::get('logger')->err('Exception occured in Blog Controller submitBlogAction'. $e->getMessage().'---------'. $e->getTraceAsString());
	    	return $this->_forward('exception/','error');
	    }
    	
    }
    
}



