<?php

/**
 * This class is designed to configure the environment for each PHP page by
 * looking at the environment variables passed through apache conf.
 *
 * The class is the first object to load for any page controller script
 * and will obtain the php include_path from the apache environment using the
 * PHP_INCLUDE_PATH environment variable and prepend this to the existing
 * include_path.  It will also lookup a range of other apache environment
 * variables and configure related PHP libraries as necessary.  
 *
 * Currently implemented environment variables are:
 *
 * APPDIR
 * APPCONF
 * APPCONFCLASS
 * PHP_INCLUDE_PATH
 * PHP_INCLUDE
 * STARTUP_SCRIPT
 * AUTHCLASS
 * SMARTYDIR
 * SMARTYSHAREDDIR
 * PROPELRUNTIME
 * DEBUG
 *
 * See the $conf property array for an explanation of each parameter.
 */
class ignition
{
	/**
	 * @var array Associative array of environment variables supported by ignition
	 */
	protected $conf = array
	(
		'APPDIR' => 'The base directory for where a web apps files are installed, such as /usr/local/www/appname',
		'APPCONF' => 'Relative (to APPDIR) or absolute path to a configuration file for this app.  Can be any conf file format, but default implementation (ignition_app_config) uses ini files',
		'APPCONFCLASS' => 'The class to use to parse the APPCONF configuration file (default: ignition_app_config)',
		'PHP_INCLUDE_PATH' => 'The include_path setting for the given app to be prepared to the existing include path',
		'PHP_INCLUDE' => 'A colon separated list of php files to "include" into memory, either absolute or relative to PHP_INCLUDE_PATH',
		'STARTUP_SCRIPT' => 'The path to a php startup script that can be called to initialise libraries and other startup functions.  This is called after the PHP_INCLUDE files have been "included", but before anything else',
		'AUTHCLASS' => 'Name of class that implements ignition_Auth to use as an Authentication object. The php file containing the class implementation must have the same name as the class with added .php on end and located in the include path. ',
		'SMARTYDIR' => 'The base directory where the smarty template files are stored and includes templates, templates_c, cache & and config directories',
		'SMARTYSHAREDDIR' => 'The base directory where shared Smarty templates are stored',
		'PROPELRUNTIME' => 'Path to runtime configuration php file for propel which is normally passed to the Propel::init call (eg. /usr/local/www/lib/spa4/propel/build/conf/spa-conf.php)',
		'DEBUG' => 'Debug level for logging in the application',
	) ;
	
	/**
	 * @var ignition_Auth Authentication object instance from env AUTHCLASS
	 */
	protected $auth = null ;
	
	/**
	 * @var array Environment variable values from Apache
	 */
	protected $data = array() ;
	
	/**
	 * @var ignition_app_config Application Configuration Object (optional)
	 */
	protected $appconf = null ;
	
	
	/**
	 * @var array Composer autoload classloader
	 */
	protected $composerClassLoader = null ;
	
	/**
	 * Constructor for ignition class.  Will load environment variables and
	 * set the include_path if the PHP_INCLUDE_PATH environment variable is set.
	 *
	 * If a composer vendor/autoload.php script exists, it will be loaded
	 * 
	 * If PROPELRUNTIME is set, it will do a
	 * Propel::init to initialise it.
	 *
	 * If SMARTYDIR is defined, then it will
	 * also 'include' the ignition_Smarty php file
	 *
	 * @param boolean $authenticate If true, authentication is enabled (default)
	 * @param string $username Username that is going to is is authenticated
	 * @param string $password Password to authenticate the user
	 * 
	 * @return ignition    An ignition object
	 */
	function __construct($authenticate=true,$username=null,$password=null)
	{
		//First thing is to require our own autoloader from composer if we have one
		if(!is_readable(__DIR__.'/vendor/autoload.php'))
			throw new ignition_Exception("Vendor libraries have not been installed.  Need to run composer.phar install over the composer.json file",ignition_Exception::GENERIC_ERROR) ;

		require(__DIR__.'/vendor/autoload.php') ;
			
		//Get valid environment variable data and store in instance
		$this->storeEnv() ;
		
		//Configure the php_include_path
		$this->include_path() ;
		
		//If a composer autoload file exists, load it now and store the composer classloader instance
		$this->composer_autoload() ;

		//Include files
		$this->php_include_files() ;
		
		//Include and initialise propel if PROPELRUNTIME is set
		$this->propel() ;
		
		//Include ignition_Smarty if SMARTYDIR is set
		$this->smarty() ;
		
		//If startup script defined in config, then include it
		if($this->getenv('STARTUP_SCRIPT'))
			$this->include_startup($this->getEnvPath('STARTUP_SCRIPT')) ;
		
		//If constructor is told to authenticate, then call the auth method to authenticate session
		if($authenticate)
			$this->authenticate($username,$password) ;
	}
	
	/**
	 * Locate composer autoload.php script file under vendor of our project
	 * (not of ignition itself) and if found require it
	 *
	 * @return array This method returns an class map from the composer autoloader with (classname => path to filename)
	 */
	protected function composer_autoload()
	{
		$autoloaderFilename = $this->getenv('APPDIR') . '/vendor/autoload.php' ;
		if(is_readable($autoloaderFilename))
		{
			if($this->getenv('DEBUG')) error_log('Requiring vendor/autoload.php from composer') ;
			//The $autoloaderFilename file calls 'return' with the class map
			//http://stackoverflow.com/questions/1314162/return-from-include-file
			$this->composerClassLoader = (require($autoloaderFilename)) ;
		}
		
	}
	
	/**
	 * This method will initialise the propel runtime based on the environment variable from apaceh PROPELRUNTIME.  It will include the propel/Propel.php file and then call Propel::init with the php file given in the environment variable PROPELRUNTIME
	 */
	protected function propel()
	{
		//If apache says there are propel runtime configs to load, then require
		//the propel library and call Propel::init on each runtime config php file
		if(array_key_exists('PROPELRUNTIME',$this->data))
		{
			if($this->getenv('DEBUG')) error_log('Setting up propel') ;

			//If Propel isn't using the composer autoloader, we need to include file manually
			if(!array_key_exists('Propel',$this->composerClassLoader->getClassMap()))
			{
				if($this->getenv('DEBUG')) error_log('Propel not using Composer, so manually including Propel.php') ;
				//Include the main Propel script on include path first
				//http://php.net/manual/en/function.stream-resolve-include-path.php
				if(stream_resolve_include_path('Propel.php') !== false)
				{
					//Then it is included directly within the include_path
					require_once 'Propel.php' ;
				}
				else
				{
					//Otherwise, this is the path for pear version of propel
					require_once 'propel/Propel.php';
				}
			}
			elseif($this->getenv('DEBUG'))
				error_log('Propel using Composer autoloader') ;
				
			//Now, lets initialise Propel
			Propel::init($this->getEnvPath('PROPELRUNTIME')) ;
		}
	}
	
	/**
	 * This method will include the ignition_Smarty.php file (must be in the include path) if the SMARTYDIR environment variable is set in the apache config
	 */
	protected function smarty()
	{
		//If SMARTYDIR is set, then obviously using smarty so load it up
		if(array_key_exists('SMARTYDIR',$this->data))
		{
			if($this->getenv('DEBUG')) error_log('Including ignition_Smarty.php',E_USER_NOTICE) ;
			include_once('ignition_Smarty.php');
		}
	}
	
	/**
	 * This method will retrieve the value of the APPCONF environment variable
	 * as set in the web server configuration pointing to a configuration file
	 * for the given app.  The config file can be of any format and is to be
	 * parsed by the APP itself.  Although the default implementation class
	 * ignition_app_config parses ini files.  If path to config file is relative
	 * in the web server config, then this method will return an absolute path
	 * using APPDIR path as the base.
	 * 
	 * @return string    Path to filename for Application config file
	 */
	public function getAppConfigPath()
	{
		return $this->getEnvPath('APPCONF') ;
	}
	
	/**
	 * This method returns a copy of the ignition_app_config class for this app
	 * or throws an exception if the config file is not found
	 *
	 * @throws ignition_Exception If the APPCONF environment variable not found or filename does not exist is not readable
	 * @return ignition_app_config    An instance of the config object
	 */
	public function getAppConfig()
	{
		//If we already have it, then just return it
		if(!is_null($this->appconf))
			return $this->appconf ;
		
		//If calling this method, but no class name defined, use default
		$class = $this->getenv('APPCONFCLASS') ;
		if(is_null($class))
			$class = 'ignition_app_config' ;

		$this->appconf = new $class($this->getAppConfigPath()) ;
		return $this->appconf ;
	}
	
	/**
	 * This method will retrieve the environment variable given by $var from the
	 * apache environment settings as a directory or file path, and if it is
	 * a relative path, make it absolute from the APPDIR environment variable
	 * 
	 * @param string $var Apache environment variable containing path
	 * 
	 * @return string    Absolute path to $var
	 * @throws ignition_Exception	If the environment variable does not exist, a generic exception is thrown
	 */
	public function getEnvPath($var)
	{
		$value = getenv($var) ;
		if($value === false)
			throw new ignition_Exception("Environment variable $var not defined in web server config") ;
		
		//If the path is not absolute (meaning it doesn't start with a /)
		if(strpos($value,'/') !== 0) //Prepend the APPDIR path
			return $this->getenv('APPDIR') . '/' . $value ;
		
		//Otherwise, it is absolute, so just return it
		return $value ;
	}
	
	/**
	 * Execute include script if one provided in the environment variable STARTUP_SCRIPT
	 * 
	 * @param string $script Absolute path to the startup script
	 * 
	 */
	protected function include_startup($script)
	{
		//Do a require, because if the script does not exist, the program will barf
		require($script) ;
	}
	
	/**
	 * This method will use the $conf instance variable to extract environment variable values and store in this instance
	 */
	protected function storeEnv()
	{
		foreach($this->conf as $var => $value)
		{
			$val = getenv($var) ;
			if($val)
				$this->data[$var] = $val ;
		}
	}
	
	/**
	 * This method configures the php_include_path based on apache environment variables
	 */
	protected function include_path()
	{
		//If the include path env variable was provided, then set the include_path
		if(array_key_exists('PHP_INCLUDE_PATH',$this->data))
		{
			if($this->getenv('DEBUG')) error_log('Setting php include_path',E_USER_NOTICE) ;
			set_include_path($this->data['PHP_INCLUDE_PATH'].':'.get_include_path()) ;
		}
		if($this->getenv('DEBUG')) error_log('DEBUG: include_path='.get_include_path(),E_USER_NOTICE) ;
		
	}
	
	/**
	 * This method uses the colon separated files provided through the PHP_INCLUDE
	 * environment variable to 'require' the files into memory
	 * 
	 */
	protected function php_include_files()
	{
		if(isset($this->data['PHP_INCLUDE']))
		{
			$files = explode(':',$this->getenv('PHP_INCLUDE')) ;
			foreach($files as $file)
			{
				require($file) ;
			}
		}
	}
	
	/**
	 * This method will require an authentication class php file based on the value of the AUTHCLASS apache environment variable, and then create an ignition_auth instance based on the class name given in the AUTHCLASS environment variable from Apache.  It will use this instance to authenticate the user, by passing in the $username, $session or $password from the constructor of ignition
	 * 
	 * @param string $username     Username to authenticate or null (ignition_auth will get it some other way ie. SSO)
	 * @param string $password     Password provided by user for authentication or null (ignition_auth subclass will get it some other way ie. SSO)
	 */
	public function authenticate($username=null,$password=null)
	{
		//Now that our INCLUDE_PATH is set, let's get about doing some authentication
		if(array_key_exists('AUTHCLASS',$this->data))
		{
			if($this->getenv('DEBUG')) error_log('Including ignition_auth php '.$this->data['AUTHCLASS'].".php") ;
			require_once($this->data['AUTHCLASS'].'.php') ;
			
			if($this->getenv('DEBUG')) error_log('Setting up ignition_auth') ;
			//Create an instance of our auth class - it should be in our PHP_INCLUDE_PATH
			$this->auth = new $this->data['AUTHCLASS']($this) ;
			
			//If not already authenticated
			if(!$this->auth->isAuthenticated())
			{
				if($this->getenv('DEBUG')) error_log('User not authenticated: '.$username) ;
				//Redirect browser so user can login and authenticate
				$this->auth->authenticate($username,$password) ;
			}
			else
			{
				if($this->getenv('DEBUG')) error_log('User already signed-on: '.$this->auth->getUsername()) ; 
			}
		}
		else
		{
			throw new Exception('ignition attempt to authenticate, but Apache env variable AUTHCLASS not set',1) ;
		}
	}
	
	/**
	 * This method will return an ignition_auth object used for authentication if there
	 * is one, otherwise it will return null
	 * 
	 * @return ignition_auth    ignition_auth object used for authentication
	 */
	function getAuth()
	{
		return $this->auth ;
	}
	
	/**
	 * This method will take an instance of ignition_auth as the auth object for this
	 * session as used by the ignition class.  If you need to do a specialised
	 * authentication for a particular page and wish to do the authentication
	 * outside of ignition, then after authentication, it can be passed into
	 * ignition for future use on the page
	 * 
	 * @param ignition_auth $ignition_auth A concrete class dervied from ignition_auth for authentication
	 */
	function setAuth($ignition_auth)
	{
		$this->auth = $ignition_auth ;
	}
	
	/**
	 * Get the value of the given ignition variable
	 * 
	 * @param string $var Variable name
	 * 
	 * @return string    Value of the variable or null if not defined
	 */
	function getenv($var)
	{
		if(!isset($this->data[$var]))
			return null ;
		
		return $this->data[$var] ;
	}
	
	/**
	 * Get DEBUG level set in the Apache config DEBUG SetEnv.  If not set, returns
	 * zero meaning false.  Can be used to determine if debugging is enabled
	 * 
	 * @return integer    Debug level or 0 for no debug
	 */
	function getDebugLevel()
	{
		if(!isset($this->data['DEBUG']))
			return 0 ;
		
		return $this->data['DEBUG'] ;
	}
	
}

/**
 * The ignition_Exception class extends Exception and provides error handling
 * specifically for the ignition web development projects.  It has specific error
 * codes which can be passed to the constructor and will affect the behaviour
 * of the class when it's handleException method is called to handle the error
 * it is given
 */
class ignition_Exception extends Exception
{
	/**
	 * @var int This is the default error code for the Exception superclass.  It will add the contents of the message of the exception to a template variable calle 'errorMessage'.  The default GENERIC template does this - make sure if you use your own template that it also has a 'errorMessage' template variable
	 */
	const GENERIC_ERROR = 0 ;
	/**
	 * @var int This error code relates to incorrect or invalid data being sent to the script.  The message of the exception is added to the system log, but not displayed to the user
	 */
	const DATA_VALIDATION_ERROR = 1 ;
	/**
	 * @var int This error code relates to attempts to access something the user isn't authorised to access
	 */
	const AUTHORISATION_FAILURE_ERROR = 4 ;
	/**
	 * @var int This error code relates to a user student who has attempted to access a staff only service.  The default template provides a softly worded explanation and advises to contact support if not correct.  
	 */
	const STUDENT_NOT_AUTHORISED = 16 ;
	
	/**
	 * This class takes the same parameters as a standard Exception class.
	 * Class Constants for codes supported by this class are available, such as:
	 * ignition_Exception::AUTHORISATION_FAILURE_ERROR.  Some of these error codes
	 * will make use of the $message parameter in the output while some will not
	 * depending on whether it is relevant to give additional information to the
	 * user.  For instance DATA_VALIDATION_ERROR will not 
	 * 
	 * @param string   $message  Error message
	 * @param int   $code     Error code
	 * @param Exception $previous Previous Exception
	 * 
	 * @return ignition_Exception      ignition_Exception object
	 */
	public function __construct($message = null, $code = 0, Exception $previous = null)
	{
		$this->template = new ignition_Smarty() ;
		parent::__construct($message,$code,$previous) ;
	}
	
	/**
	 * @var array Associate array of template filenames to use for the various error types
	 */
	protected $templateFiles = array
	(
		self::GENERIC_ERROR => 'ignition_Exception-GENERIC.tpl',
		self::STUDENT_NOT_AUTHORISED => 'ignition_Exception-STUDENT_NOT_AUTHORISED.tpl',
		self::DATA_VALIDATION_ERROR => 'ignition_Exception-DATA_VALIDATION_ERROR.tpl',
		self::AUTHORISATION_FAILURE_ERROR => 'ignition_Exception-AUTHORISATION_FAILURE_ERROR.tpl'
	) ;
	
	/**
	 * @var string This variable is set by setTemplateFile method or automatically
	 * based on the error code based on the $templateFiles array
	 */
	private $templateFile = null ;
	
	/**
	 * @var array Associative array of method names to call to handle each event type
	 */
	protected $handlerMethod = array
	(
		self::GENERIC_ERROR => 'handle_GENERIC',
		self::STUDENT_NOT_AUTHORISED => 'handle_DEFAULT',
		self::DATA_VALIDATION_ERROR => 'handle_DEFAULT',
		self::AUTHORISATION_FAILURE_ERROR => 'handle_DEFAULT',
	) ;
	
	/**
	 * @var ignition_Smarty Smarty template used by the class to output the error message
	 */
	protected $template = null ;
	
	
	protected function handle_GENERIC(ignition $i)
	{
		if($this->getMessage())
		{
			$this->template->assign('errorMessage',$this->getMessage()) ;
			return $this->template->fetch($this->templateFile) ;
		}

		//If there is no custom message, then nothing specific to give the user so
		//just throw a generic Exception so the user knows something has gone wrong
		//Really if using an ignition_Exception, you should have some sort of message
		//otherwise what is the point
		throw new Exception() ;
	}

	protected function handle_DEFAULT(ignition $i)
	{
		$this->template->assign('errorMessage',$this->getMessage()) ;
		return $this->template->fetch($this->templateFile) ;
	}
	
	/**
	 * This method can be used to specify a custom template file to use to display
	 * the 
	 * 
	 * @param unknown $filename Description
	 */
	public function setTemplateFile($filename)
	{
		$this->templateFile = $filename ;
	}
	
	/**
	 * This method will return the ignition_Smarty instance that is being used to
	 * render the error page.  You can set your own environment variables and
	 * manipulate the ignition_Smarty object before calling handleException to
	 * customise the output
	 * 
	 * @return ignition_Smarty    The ignition_Smarty object used to render the error page
	 */
	public function getSmarty()
	{
		return $this->template ;
	}
	
	/**
	 * This method will invoke the ignition_Exception by generating a error message
	 * page based on the message and code set for this ignition_Exception instance.
	 * It requires an instance of ignition as it is tightly integrated with the
	 * ignition environment, and uses ignition to send the error message either
	 * to the browser page, or to a javascript function which will place it into
	 * a Jquery dialog.  Calling this method will not return and the script will
	 * exit using the code given to this instance.  It will also log the error
	 * via error_log
	 * 
	 * @param ignition $i The ignition instance for this page
	 */
	public function handleException(ignition $i)
	{
		//Set the template filename based on the defaults for the errorcode
		if($this->templateFile == null)
			$this->templateFile = $this->templateFiles[$this->getCode()] ;

		if($this->template == null)
			$this->template = new ignition_Smarty() ;
			
		//Set isAuthenticated to false by default
		$this->template->assign('loggedin',false) ;
		
		//Check now to see if they are authenticated
		$auth = $i->getAuth() ;
		if(is_object($auth) and $auth instanceof ignition_Auth)
		{
			$this->template->assign('firstname',$auth->getFirstname()) ;
			$this->template->assign('lastname',$auth->getLastname()) ;
			$this->template->assign('email',$auth->getEmail()) ;
			$this->template->assign('username',$auth->getUsername()) ;
			$this->template->assign('loggedin',true) ;
		}
		
		$page = null ;

		if(array_key_exists($this->getCode(),$this->handlerMethod))
		{
			$methodName = $this->handlerMethod[$this->getCode()] ;
			$page = $this->$methodName($i) ;//handlerMethod[$this->getCode]($i) ;
			$this->sendErrorPage($page) ;
		}
		else
			throw $this ;

		
		//Write error to the error log
		$message = sprintf("%s:%d %s (%d) [%s]\nStack trace:\n%s\n\n", $this->getFile(), $this->getLine(), $this->getMessage(), $this->getCode(), get_class($this),$this->getTraceAsString());
		error_log($message) ;
		exit($this->getCode()) ;
	}

	/**
	 * This method is used by the ignition default exception handler and also
	 * by the ignition_Exception class to output any generated error messages back to
	 * the browser. It takes a complete HTML page as its only parameter. If the
	 * PHP script that has errored was called via ajax, it will look for a div
	 * element within the HTML and return only this div as part of a json data
	 * structure.  The javascript function ignition_ajaxErrorHandler as defined in the
	 * ajax-exception-handler.js file will process the returned
	 * json structure and display the error nicely in a jquery dialog box.  Otherwise
	 * the full HTML page is returned to the browser.
	 * 
	 * @param string $page HTML page to be returned to the browser (normal page load or otherwise)
	 */
	function sendErrorPage($page)
	{
		//Was it an ajax call?
		//http://davidwalsh.name/detect-ajax
		if
		(
			!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		)
		{
			//Was an ajax call, so send back json with the error HTML page set to
			//the json property 'errorMessage'.  The ignition_ajaxErrorHandler javascript
			//function in the ajax-exception-handler.js file will handle the error
			//in the browser
			$response = array
			(
				'errorMessage' => $page
			) ;
			header('Content-type: application/json') ;
			echo json_encode($response) ;
		}
		else
		{
			//Was not an ajax call, so send back html
			echo $page ;
		}
	}
	
	
}


?>
