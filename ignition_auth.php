<?php

/**
 * This is an abstract class for authentication of ignition project apps
 * @abstract
 */
abstract class ignition_auth
{
	/**
	 * @var ignition An instance of the ignition class
	 */
	protected $ignition = null ;
	
	/**
	 * Constructor for abstract ignition authentication object
	 * 
	 * @param ignition $ignition Ignition object handling this authenticator
	 * 
	 * @return ignition_auth     An instance of this class
	 */
	public function __construct(ignition $ignition=null)
	{
		if(is_null($ignition))
		{
			error_log("No ignition object provided") ;
			$ex = new ignition_Exception(null,ignition_Exception::GENERIC_ERROR) ;
			$ex->handleException($ignition) ;
		}
		$this->ignition = $ignition ;
	}
	
	/**
	 * This method takes an optional username and token and validated whether this
	 * pair comprises an already authenticated user
	 *
	 * @abstract 
	 * 
	 * @return boolean    If previously authenticated and still valid return true otherwise false
	 */
	abstract function isAuthenticated() ;
	
	/**
	 * This method takes a username and password and authenticates the user against
	 * whatever data source is appropriate
	 *
	 * @abstract 
	 * @param string $username Username to be authenticated
	 * @param string $password The password to validate
	 * 
	 * @return boolean    Will return true if the user is authenticated with password or false otherwise
	 */
	abstract function authenticate($username=null,$password=null) ;
	
	/**
	 * This method will return the username for the authenticated user, otherwise null
	 *
	 * @abstract
	 * @return string    The username of the authenticated user or null
	 */
	abstract function getUsername() ;
	
	/**
	 * This method will return the email address for the authenticated user, otherwise null
	 *
	 * @abstract
	 * @return string    Email address of authenticated user or null if not authenticated
	 */
	abstract function getEmail() ;
	
	/**
	 * This method will return the first name for the authenticated user, otherwise null
	 *
	 * @abstract
	 * @return string    Firstname of the authenticated user otherwise null
	 */
	abstract function getFirstname() ;
	
	/**
	 * This method returns the lastname of the authenticated user otherwise null
	 *
	 * @abstract
	 * @return string    Lastname of the authenticated user otherwise null
	 */
	abstract function getLastname() ;
	
	/**
	 * This method will cause a logout from the system
	 *
	 * @param string $redirect URL to redirect to after logging out
	 * @return boolean    True if logout successful otherwise false
	 */
	abstract function logout($redirect) ;
	
	/**
	 * This method will return true if the given username (or username that is
	 * authenticated) is a staff member, by testing the username to see if it
	 * matches a student number format of ^[sSqQ]\d+$. If it matches that RE
	 * then it will return false, otherwise will return true
	 * 
	 * @param string $username Username to check or null if testing authenticated user
	 * 
	 * @return boolean    True if username is a staff member, otherwise false
	 */
	function isStaff($username=null)
	{
		if($username == null)
			$username = $this->getUsername() ;
			
		return (!preg_match('/^[sSqQcC]\d+$/',$username)) ;
	}
	
	/**
	 * This method will return true if the given username (or username that is
	 * authenticated) is a student, by testing the username to see if it matches
	 * a student number format of ^[sSqQ]\d+$.  If it matches that RE then it
	 * will return true, otherwise return false
	 * 
	 * @param string $username Username to check or null if testing authenticated user
	 * 
	 * @return boolean    True if username is a student, otherwise false
	 */
	function isStudent($username=null)
	{
		return !$this->isStaff() ;
	}
}

?>
