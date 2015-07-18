<?php

require_once('ignition_auth.php') ;
//Load the simplesamlphp autoloader - it takes care of the rest
require_once('lib/_autoload.php');

/**
 * This class implements ignition_auth and provides sso authentication to ignition services.
 * To include into ignition projects, set the following environment variable in the
 * applications apache config
 * SetEnv AUTHCLASS "ignition_auth_sso"
 */
class ignition_auth_sso extends ignition_auth
{
	//protected $simplesaml = null ;
	protected $authSource = null ;

	/**
	 * @var array SSO Attributes
	 */
	protected $attributes = null ;
	
	/**
	 * Constructor for the ignition_auth_sso class, using SSO for authentication of ignition services
	 * 
	 * @param string $authsource The name of the authentication source to use for SSO
	 * 
	 * @return ignition_auth_sso    Object
	 */
	function __construct(ignition $ignition=null,$authsource='default-sp')
	{
		parent::__construct($ignition) ;
		
		$this->authSource = $authsource ;
		//$this->simplesaml = new SimpleSAML_Auth_Simple('default-sp') ;
	}
	
	/**
	 * This method will use the IdProvider to ask the user for their username
	 * and password.
	 * 
	 * @return boolean    If the user has already been authenticated by the IdP, then this will return true, otherwise it will return false and <code>authenticate</code> should be called
	 */
	function isAuthenticated()
	{
		$simplesaml = new SimpleSAML_Auth_Simple($this->authSource) ;
		$authenticated = $simplesaml->isAuthenticated() ;
		unset($simplesaml) ;
		if(getenv('DEBUG')) error_log('Checking if user is authenticated',E_USER_NOTICE) ;
		if(getenv('DEBUG')) error_log('isAuthenticated: '.$authenticated,E_USER_NOTICE) ;
		return $authenticated ;
	}
	
	/**
	 * Authenticate user for accessing this system (it does not do access rights though)
	 * Any parameters passed to this method are ignored as the browser will be
	 * redirected to the IdP which will get the username and password for authentication
	 * This method does not return
	 * 
	 * @param string $username Leave this as null it is ignored
	 * @param string $password Leave this as null it is ignored
	 * 
	 * @return void    This method does not return
	 */
	function authenticate($username=null,$password=null)
	{
		$simplesaml = new SimpleSAML_Auth_Simple($this->authSource) ;
		$simplesaml->requireAuth() ;
	}
	
	/**
	 * This method will return the username for the authenticated user, otherwise null
	 *
	 * @abstract
	 * @return string    The username of the authenticated user or null
	 */
	function getUsername()
	{
		$simplesaml = new SimpleSAML_Auth_Simple($this->authSource) ;
		
		if($simplesaml->isAuthenticated() and $this->attributes == null)
			$this->attributes = $simplesaml->getAttributes() ;

		unset($simplesaml) ;
		
		if($this->attributes == null)
			return null ;
			
		return $this->attributes['UserID'][0] ;
	}
	
	/**
	 * This method will return the email address for the authenticated user, otherwise null
	 *
	 * @abstract
	 * @return string    Email address of authenticated user or null if not authenticated
	 */
	function getEmail()
	{
		$simplesaml = new SimpleSAML_Auth_Simple($this->authSource) ;
		if($simplesaml->isAuthenticated() and $this->attributes == null)
			$this->attributes = $simplesaml->getAttributes() ;

		unset($simplesaml) ;
		if($this->attributes == null)
			return null ;
			
		return $this->attributes['EmailAddress'][0] ;
	}
	
	/**
	 * This method will return the first name for the authenticated user, otherwise null
	 *
	 * @abstract
	 * @return string    Firstname of the authenticated user otherwise null
	 */
	function getFirstname()
	{
		$simplesaml = new SimpleSAML_Auth_Simple($this->authSource) ;
		if($simplesaml->isAuthenticated() and $this->attributes == null)
			$this->attributes = $simplesaml->getAttributes() ;

		unset($simplesaml) ;
		if($this->attributes == null)
			return null ;
			
		return $this->attributes['FirstName'][0] ;
	}
	
	/**
	 * This method returns the lastname of the authenticated user otherwise null
	 *
	 * @abstract
	 * @return string    Lastname of the authenticated user otherwise null
	 */
	function getLastname()
	{
		$simplesaml = new SimpleSAML_Auth_Simple($this->authSource) ;
		if($simplesaml->isAuthenticated() and $this->attributes == null)
			$this->attributes = $simplesaml->getAttributes() ;

		unset($simplesaml) ;
		if($this->attributes == null)
			return null ;
			
		return $this->attributes['LastName'][0] ;
	}

	/**
	 * This method will call on simplesamlPHP to do a logout from the SSO for
	 * all sites
	 * 
	 * @return boolean    Returns true if logout successful otherwise false
	 */
	function logout($redirect)
	{
		$simplesaml = new SimpleSAML_Auth_Simple($this->authSource) ;
		$simplesaml->logout($redirect) ;
	}
}

?>
