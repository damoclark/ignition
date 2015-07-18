<?php

require_once('ignition_auth.php') ;

class ignition_auth_none extends ignition_auth
{

	protected $password = null ;
	protected $user = null ;
	protected $email = null ;
	protected $firstname = null ;
	protected $lastname = null ;
	
	function __construct(ignition $ignition)
	{
		parent::__construct($ignition) ;
		
		//To simulate the tracking of an authenticated user, and one that changes
		//as part of a debugging session, if you request any page that is authenticated
		//by this class, it will check for a 'username' parameter and if it finds one
		//will set a cookie storing this username.  If you need to change the username
		//then you just make another page request in the browser with the new
		//'usename' parameter.

		//If a username= parameter provided in the current request, then save it
		//as a cookie and we can use it in this class to know who the user is
		if(isset($_REQUEST['username']))
			$this->user = $_REQUEST['username'] ;
			
		if(isset($this->user))
			setcookie('ignition_auth_none_username',$_REQUEST['username']) ;
		elseif(isset($_COOKIE['ignition_auth_none_username']))
			$this->user = $_COOKIE['ignition_auth_none_username'] ;
	}
	
	/**
	 * This method checks to see if a username has been specified either via
	 * the cookie ignition_auth_none_username or whether this object instance
	 * has previously had the authenticate method called passing in a username
	 *
	 * If a username has been provided, then this method will return true.
	 *
	 * If a username has not been provided, then it will return false
	 * 
	 * THIS METHOD NEVER AUTHENTICATES!!!
	 * 
	 * @return boolean    Always returns true
	 */
	function isAuthenticated()
	{
		($this->user == null) ? false : true ;
	}
	
	/**
	 * This method takes the given username and stores it as the authenticated user
	 * and always returns true.  THIS METHOD NEVER AUTHENTICATES, IT ALWAYS RETURNS TRUE
	 * If username is null, it is not stored and whatever the parameter the script
	 * for 'username' holds will be what the authenticated username will be.  But
	 * passing a username to this method can override that
	 * 
	 * @param string $username The username that will always be authenticated
	 * @param string $password Saved password, but ignored
	 * 
	 * @return boolean    Always returns true
	 */
	function authenticate($username=null,$password=null)
	{
		if($username !== null)
			$this->user = $username ;
		return true ;
	}
	
	/**
	 * This method will return the username provided to the <code>isAuthenticated</code>
	 * or <code>authenticate</code> methods, or to the query variable 'username' for the
	 * request or the hardcoded value 'clarkd'
	 * 
	 * @return string    The username passed into the <code>isAuthenticated</code> or <code>authenticate</code>
	 */
	function getUsername()
	{
		if($this->user == null)
		{
			$this->user = 'fred' ;
		}
		return $this->user ;
	}
	
	/**
	 * This method will always return either a fake email address or whatever email address is passed as a query option for variable 'email'
	 * 
	 * @return string    Fake email address fred@domain.com
	 */
	function getEmail()
	{
		if($this->email == null)
		{
			if(isset($_GET['email']))
				$this->email = $_GET['email'] ;
			else
				$this->email = 'fred@domain.com' ;
		}
		return $this->email ;
	}
	
	/**
	 * This method will always return a fake firstname
	 * 
	 * @return string    Fake first name 'fred'
	 */
	function getFirstname()
	{
		return 'Fred' ;
	}
	
	/**
	 * This method will always return a fake lastname
	 * 
	 * @return string    Fake last name 'nerk'
	 */
	function getLastname()
	{
		return 'Nerk' ;
	}
	
	/**
	 * This method will pretend to logout
	 * 
	 * @param string $redirect URL to redirect to after logging out
	 * @return boolean    Will always return true
	 */
	function logout($redirect)
	{
		header('Location: main.php') ;
	}
}


?>
