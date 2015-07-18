<?php

require_once('ignition_auth.php') ;

/**
 * This class implements ignition_auth and provides an interface to web server provided
 * http authentication
 * To include into projects, set the following environment variable in the
 * applications apache config
 * SetEnv AUTHCLASS "ignition_auth_http"
 */
class ignition_auth_http extends ignition_auth
{
	
	/**
	 * This method does not need to take any of the parameters given and they should
	 * be left as null.  It will use the $_SERVER['PHP_AUTH_USER'] to determine the username
	 * 
	 * @param string $username Leave this as null
	 * @param string $token    Leave this as null
	 * 
	 * @return boolean    If the user has already been authenticated by web server, then this will return true.  If they haven't been authenticated by web server, this script can't run
	 */
	function isAuthenticated($username=null,$token=null)
	{
		return true ;
	}
	
	/**
	 * Authenticate user for accessing this system (it does not do access rights though)
	 * Any parameters passed to this method are ignored as the user has already been
	 * authenticated by the web server software using http auth
	 * 
	 * @param string $username Leave this as null it is ignored
	 * @param string $password Leave this as null it is ignored
	 * 
	 * @return boolean    True
	 */
	function authenticate($username=null,$password=null)
	{
		return true ;
	}
	
	/**
	 * This method will return the username for the authenticated user, otherwise null
	 *
	 * @return string    The username of the authenticated user or null
	 */
	function getUsername()
	{
		return $_SERVER['REMOTE_USER'] ;
	}
	
	/**
	 * This method will return null as the email address is not known with http authentication
	 *
	 * @return null    Null
	 */
	function getEmail()
	{
		return null ;
	}
	
	/**
	 * This method will return null as the firstname is not known with http authentication
	 *
	 * @return null    Null
	 */
	function getFirstname()
	{
		return null ;
	}
	
	/**
	 * This method will return null as the lastname is not known with http authentication
	 *
	 * @return null    Null
	 */
	function getLastname()
	{
		return null ;
	}
	
	/**
	 * This method will http_redirect to redirect to given url.  Cannot 'logout' with http auth
	 * 
	 * @return boolean    No return value - function exits script
	 */
	function logout($redirect)
	{
		header("Location: $redirect") ;
		exit(0) ;
	}
}


?>
