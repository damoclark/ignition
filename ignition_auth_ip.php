<?php

require_once('ignition_auth.php') ;

/**
 * This class implements ignition_auth and provides an interface to authentication via ip address of the source
 * To include into ignition projects, set the following environment variable in the
 * applications apache config
 * SetEnv AUTHCLASS "ignition_auth_ip"
 *
 * Or it can be used along-side other authentication schemes by being instantiated manually before ignition
 */
class ignition_auth_ip extends ignition_auth
{
	
	/**
	 * @var array Configuration information for ip authentication loaded from ini
	 */
	protected $config = null ;
	
	/**
	 * @var boolean The client has already been authenticated
	 */
	protected $authenticated = false ;
	
	function __construct(ignition $ignition=null)
	{
		parent::__construct($ignition) ;
		
		//Check to see if AUTHCONF
		//Load configuration from ini file into instance
		try
		{
			$ini_filename = $this->ignition->getEnvPath('AUTHCONF') ;
		}
		catch(ignition_Exception $e)
		{
			$ex = new ignition_Exception(null,ignition_Exception::GENERIC_ERROR,$e) ;
			$ex->handleException($ignition) ;
			exit(1) ;
		}

		$this->config = parse_ini_file($ini_filename,true) ;
		if($this->config === false)
		{
			error_log("Error parsing auth_ip configuration file $ini_filename") ;
			$ex = new ignition_Exception(null,ignition_Exception::GENERIC_ERROR) ;
			$ex->handleException($ignition) ;
			exit(1) ;
		}
	}
	
	/**
	 * This method does not need to take any of the parameters given and they should
	 * be left as null.  Username can be specified to web server script via $_REQUEST['username']
	 * 
	 * @param string $username Leave this as null
	 * @param string $token    Leave this as null
	 * 
	 * @return boolean    If the user has already been authenticated, then return true, otherwise false
	 */
	function isAuthenticated($username=null,$token=null)
	{
		return $this->authenticated ;
	}
	
	/**
	 * Authenticate user for accessing this system (it does not do access rights though)
	 * Any parameters passed to this method are ignored as the client is being authenticated by ip address
	 * 
	 * @param string $username Leave this as null it is ignored
	 * @param string $password Leave this as null it is ignored
	 * 
	 * @return boolean    True if the ip address is permitted otherwise false
	 */
	function authenticate($username=null,$password=null)
	{
		//If already authenticated, dont check again
		if($this->authenticated)
			return $this->authenticated ;
		
		//use parse_ini_file based on env variable pointing to an ini file with the ip address filters
		$network_list = array() ;
		//If ip_filter config all on one line (not multiple ip_filter[] entries), convert
		//into an array
		if(!is_array($this->config['rules']['ip_filter']))
			$this->config['rules']['ip_filter'] = array($this->config['rules']['ip_filter']) ;
		foreach($this->config['rules']['ip_filter'] as $filter)
		{
			$network_list = array_merge($network_list,preg_split("/\s*,\s*/",$filter)) ; 
		}

		//use netmatch method to match the ip address (from $_SERVER['REMOTE_ADDR'])
		foreach($network_list as $filter)
		{
			//echo "filter=$filter\n" ;
			//If this is a 'not' rule (so denied if it matches this network)
			if(preg_match("/\s*!/",$filter))
			{
				//remove the '!' from filter
				$filter = preg_replace("/\s*!/",'',$filter) ;
				//See if this IP matches our 'not' rule
				$match = $this->netMatch($filter,$_SERVER['REMOTE_ADDR']) ;
				//If it matches not rule, then this IP is denied
				if($match)
					return false ;
			}
			else //Otherwise, match as normal
				$this->authenticated = $this->netMatch($filter,$_SERVER['REMOTE_ADDR']) ;
				
			//Once we have an allowed match, then no need to continue checking, so break
			if($this->authenticated)
				break ;
		}
		
		return $this->authenticated ;
	}
	
	/**
	 * This method will return the $_REQUEST['username'] as the authenticated user, otherwise null
	 *
	 * @return string    The username of the authenticated user or null
	 */
	function getUsername()
	{
		return (isset($_REQUEST['username'])) ? $_REQUEST['username'] : null ;
	}
	
	/**
	 * This method will return null as the email address is not known with ip authentication
	 *
	 * @return null    Null
	 */
	function getEmail()
	{
		return null ;
	}
	
	/**
	 * This method will return null as the firstname is not known with ip authentication
	 *
	 * @return null    Null
	 */
	function getFirstname()
	{
		return null ;
	}
	
	/**
	 * This method will return null as the lastname is not known with ip authentication
	 *
	 * @return null    Null
	 */
	function getLastname()
	{
		return null ;
	}
	
	/**
	 * This method will redirect to given url.  Cannot 'logout' with ip auth
	 * 
	 * @return boolean    No return value - function exits script
	 */
	function logout($redirect)
	{
		header("Location: $redirect") ;
		exit(0) ;
	}
	
	/**
	 * Match the given ip address against the given network address.  If ip is within
	 * network, then return true, otherwise false
	 *
	 * @var string $network Network address to test match (0.0.0.0/x, 0.0.0.0-0.0.0.0, 0.0.0.*)
	 * @var string $ip IP address to match against network
	 *
	 * http://stackoverflow.com/questions/10421613/match-ipv4-address-given-ip-range-mask
	 *
	 * @return boolean True if the ip address matches the network, otherwise false
	 */
	protected function netMatch($network, $ip)
	{
		$network=trim($network);
		$orig_network = $network;
		$ip = trim($ip);

		$network = str_replace(' ', '', $network);
		if (strpos($network, '*') !== FALSE)
		{
			if (strpos($network, '/') !== FALSE)
			{
				$asParts = explode('/', $network);
				$network = @ $asParts[0];
			}
			$nCount = substr_count($network, '*');
			$network = str_replace('*', '0', $network);
			if ($nCount == 1)
			{
				$network .= '/24';
			}
			else if ($nCount == 2)
			{
				$network .= '/16';
			}
			else if ($nCount == 3)
			{
				$network .= '/8';
			}
			else if ($nCount > 3)
			{
				return TRUE; // if *.*.*.*, then all, so matched
			}
		}

		$d = strpos($network, '-');
		if ($d === FALSE)
		{
			$ip_arr = explode('/', $network);
			//If there is no '/' then the network is a specific IP address
			if($ip_arr[0] === $network)
				return (ip2long($ip) == ip2long($network)) ;

			if (!preg_match("@\d*\.\d*\.\d*\.\d*@", $ip_arr[0], $matches))
			{
				$ip_arr[0].=".0";    // Alternate form 194.1.4/24
			}
			$network_long = ip2long($ip_arr[0]);
			$x = ip2long($ip_arr[1]);
			$mask = long2ip($x) == $ip_arr[1] ? $x : (0xffffffff << (32 - $ip_arr[1]));
			$ip_long = ip2long($ip);
			return ($ip_long & $mask) == ($network_long & $mask);
		}
		else
		{
			$from = trim(ip2long(substr($network, 0, $d)));
			$to = trim(ip2long(substr($network, $d+1)));
			$ip = ip2long($ip);
			return ($ip>=$from and $ip<=$to);
		}
	}


}


?>
