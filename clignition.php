<?php

//Include ignition superclass
require_once('ignition.php') ;

/**
 * This class is designed to configure the environment for a command line PHP script
 * by looking at the environment variables configured in an apache config file
 * or if no apache config file is provided, the UNIX environment variables
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
class clignition extends ignition
{
	/**
	 * @var string Default apache configuration directory containing multiple .conf files
	 */
	protected $apacheConfDir = '/etc/httpd/conf.d' ;
		
	/**
	 * @var array Keep a copy of parsed command line options if already parsed
	 */
	protected $commandLineOptions = null ;
	
	/**
	 * Constructor for cligition class
	 *
	 * The main difference in the constructor for the cligition class over the
	 * ignition class is that by default clignition doesn't attempt to
	 * automatically authenticate using ignition_auth_none class (which of
	 * course does not authenticate anyway)
	 *
	 * @param boolean $authenticate Whether to automatically authenticate (default false)
	 * @param string $username     Username to authenticate with (stored but not checked)
	 * @param string $password     Password for authentication (ignored)
	 * 
	 * @return cligition    An instance of this object
	 */
	function __construct($authenticate=false,$username=null,$password=null)
	{
		parent::__construct($authenticate,$username,$password) ;
	}

	/**
	 * This method will parse the command line options and return an array
	 * representation of what was given.  It works similarly to getopt
	 * php function, with important exceptions:
	 *
	 * 1. If an option has no arguments, the key in the array will be the
	 * option name, but the value will be a true rather than false
	 *
	 * 2. An array of default options and values can be provided and if
	 * they are not provided on the command line, they will be used instead
	 *
	 * 3. Short options are not supported, only long options of the form --option
	 * 
	 *
	 * @param array $defaults An array of long options with the key using the option syntax (see use of colons in getopt function) and the value is the default value for the option if it is not given on the command line
	 * 
	 * For example:
	 * $ignition->getCmdLineOptions
	 * (
	 *   array
	 *   (
	 *     'help' => false, //Not given by default
	 *     'jobs:' => 1, //Default to 1 if jobs option not given
	 *     'rows-per-job:' => 10000, //Default number of rows
	 *     'debug::' => 1, //Debug level defaults to 1 if level number not given
	 *   )
	 * ) ;
	 *
	 * returns:
	 * array
	 * (
	 *   'help' => true, //If --help given
	 *   'jobs' => 1, //If --jobs option not given
	 *   'rows-per-job' => 20000, //If --rows-per-job=20000 given
	 *   'debug' => 1 //If --debug given but not value or if --debug=1 given
	 * 
	 * @return array    The options as per example
	 */
	function getCmdLineOptions($options=null)
	{
		//If we have already parsed, them, just return again
		if(!is_null($this->commandLineOptions))
			return $this->commandLineOptions ;
		
		if(is_null($options))
			throw new ignition_Exception("No command line options specified to extract from command line",ignition_Exception::GENERIC_ERROR) ;

		//For each key, remove the colons from the end
		$defaults = array() ;
		foreach($options as $option => $default)
		{
			$option = rtrim($option,':') ;
			$defaults[$option] = $default ;
		}

		//Get options from command line
		$results = getopt('',array_keys($options)) ;

		//If an option is given that does't accept any values (eg --help), getopt
		//will return a value of false.  Doesn't make much sense so let's change that to true
		foreach($results as $option => $result)
		{
			if($result === false)
				$results[$option] = true ;
		}

		//Merge the updated results with the defaults - results overriding defaults
		$this->commandLineOptions = array_replace($defaults,$results) ;
		
		return $this->commandLineOptions ;
	}

	/**
	 * This method will scan the directory $apacheConfDir or the one given with
	 * -c option on command line looking for apache .conf files.  It will
	 * search each conf file looking at the <Directory> directive to perform
	 * a best match against the directory the running script is located in
	 * This will identify which 'project' the running script is from and
	 * therefore which conf file to extract environment variables from.  This
	 * method reimplements what apache would provide to the running script
	 * automatically.  All SetEnv directives will be assigned to the running
	 * process' UNIX environment, thus making them available to the running script
	 */
	protected function storeEnv()
	{
		/**
		 * @var string Directory to the script that was executed on command line (ie '/usr/local/www/spa4/bin')
		 */
		$scriptPath = realpath(dirname($_SERVER['PHP_SELF'])) ;
		
		/**
		 * @var integer Debug flag
		 */
		$DEBUG = $this->getenv('DEBUG') ;
		
		if($DEBUG)
			echo "path of script: $scriptPath\n" ;
		
		/**
		 * @var string The selected config file to import environment variables from
		 */
		$configFile = '' ;
		
		/**
		 * @var integer Stores the length of string representing path to potential $configFile
		 */
		$configFileMatch = 0 ;
		
		/**
		 * @var string What type of parameter is provided to -c option (file or dir)
		 */
		$configParameterType = 'dir' ;
		
		/**
		 * @var array Get the command line parameters and store in assoc array
		 */
		$params = getopt('c:d') ;
		
		//TODO: Take -c command line option.  If provided, check extension of filename
		//and if it is a .ini then load environment variables from [clignition] section
		//otherwise if it ends in .conf then just read that apache file.
		//If it is a directory, the read in all the apache conf files, and match the right
		//one.  If -c not provided, then default to reading in apache conf files from
		// /etc/httpd/conf.d/
		
		//If -c parameter given
		if(isset($params['c']))
		{
			/**
			 * @var string The path specified with -c option whether it be a file or dir
			 */
			$configParameter = $params['c'] ;
			
			//Check to see if it is a file, if so then add it as an element to $fileListIterator array/iterator
			if(is_file($configParameter))
			{
				//Create a SplFileInfo object based on this filename, and add as a single
				//element to an array
				$fileListIterator = array(new SplFileInfo(realpath($configParameter))) ;
				
				//The selected config file that will be used by the script is set now
				$configFile = $fileListIterator[0]->getPathname() ;
				//The type of the configFile variable is a file (not a dir)
				$configParameterType = 'file' ;
			}
			//Otherwise its a dir - create a DirectoryIterator so each file can be examined
			//$configParameterType defaults to dir anyway
			elseif(is_dir($configParameter))
				$fileListIterator = new DirectoryIterator($configParameter) ;
		}
		else //No -c, so use default $apacheConfDir and import all .conf files
		{
			$fileListIterator = new DirectoryIterator($this->apacheConfDir) ;
			//No -c option given, so set $configParameter to be default for apache
			$configParameter = $this->apacheConfDir ;
		}
		
		//Store environment variables from the configuration
		/**
		 * @var array Holds assoc array containing all environment variable settings from config file
		 */
		$envVariables = array() ;
		
		//Iterate over the list of SplFileInfo object in $fileListIterator array/iterator
		//If -c provided a single file, then only one element in $fileListIterator array
		foreach ($fileListIterator as $fileObject)
		{
			//If current file is a directory, skip it
			if($fileObject->isDir())
				continue ;
		
			//If we were given a directory to go through, and current file doesn't end in .conf
			//then skip
			//Cant use line below as getExtension method isnt added to PHP until 5.3.6
			//if(is_dir($configParameter) and $fileObject->getExtension() != 'conf')
			if(is_dir($configParameter) and pathinfo($fileObject->getFilename(), PATHINFO_EXTENSION) != 'conf')
				continue ;
		
			//Get a new config object to parse config file
			$conf = new Config() ;
			
			if($DEBUG)
				echo "Opening file: " . $fileObject->getPathname() . "\n" ;
			
			//Parse the apache config file
			$root = $conf->parseConfig($fileObject->getPathname(),'apache') ;
		
			/**
			 * @var integer Variable to keep track of which item in apache config looking at
			 */
			$i = 0 ;
			
			//Look for the Directory sections to find a match with our scripts directory
			while($sectionItem = $root->getItem('section','Directory',null,null,$i++))
			{
				if($DEBUG)
				{
					echo $sectionItem->name . ': ' . join(',',$sectionItem->attributes) . "\n" ;
					echo "ConfigFile=" . $fileObject->getPathname() . "\n" ;
				}
		
				/**
				 * @var string Holds the path specified by Directory option (eg. <Directory "/usr/local/www/spa4/phpdocs">)
				 */
				$sectionPath = str_replace('"','',$sectionItem->attributes[0]) ;
		
				if($DEBUG)
					echo "sectionPath=$sectionPath\n" ;
		
				/**
				 * @var integer Variable to keep track of which SetEnv within the Directory we are looking at
				 */
				$j = 0 ;
		
				//Go through each SetEnv option
				while($setEnvItem = $sectionItem->getItem('directive','SetEnv',null,null,$j++))
				{
					/**
					 * @var string Contains each SetEnv option as a string of form 'variable "value"'
					 */
					$line = $setEnvItem->getContent() ;
		
					/**
					 * @var array An array to hold the env variable name and value as parsed by RE
					 */
					$setEnvMatches = array() ;
					
					//$line example (without single quotes): 'variable "value"'
					//Match from start of line 1 or more non-spaces, followed by 1 or more spaces
					//followed by a double quote followed by 0 or more characters that are not
					//a double quote, followed by a double quote.  Place variable name & value
					//into array $setEnvMatches
					preg_match('/^([^\s]+)\s+"?([^"]*)"?/',$line,$setEnvMatches) ;
		
					//$setEnvMatches[1] contains variable name
					//$setEnvMatches[2] contains variable value
					//Store all SetEnv values from apache so they can be added to this process environment
					$envVariables[$fileObject->getPathname()][$setEnvMatches[1]] = $setEnvMatches[2] ;
		
					if($DEBUG)
						echo $setEnvMatches[1] . "=" . $setEnvMatches[2] . "\n" ;
		
					/*
					 If searching a directory for the right config file and
		
					 current variable name is APPDIR and
		
					 value of APPDIR is a substring from beginning of the path to this script and
		
					 the length of the APPDIR value string is longer than any previously matching
					 APPDIR value (it is the most specific match)
		
					 then
					*/
					if($configParameterType === 'dir' and
					$setEnvMatches[1] === 'APPDIR' and
					strpos($scriptPath,$setEnvMatches[2]) === 0 and
					strlen($setEnvMatches[2]) > $configFileMatch)
					{
						if($DEBUG)
							echo "Found a possible match for Directory\n" ;
		
						//This is the best matching config file
						$configFile = $fileObject->getPathname() ;
						//Capture the length of the APPDIR value to compare with other potential matches
						$configFileMatch = strlen($setEnvMatches[2]) ;
					}
				}
			}
		}
		
		//Then in debug mode so output what config file would be used, and the config variables
		if(isset($params['d']))
		{
			echo "clignition DEBUG mode [-d] - script not executing\n" ;
			echo "Config file: $configFile\nEnvironment:\n" ;
			foreach($envVariables[$configFile] as $var => $val)
			{
				echo "$var = $val\n" ;
			}
			exit(0) ;
		}
		
		//Store the retrieves environment variables in clignition instance
		$this->data = $envVariables[$configFile] ;
		
		//Also set the environment variables from config into UNIX environment
		foreach($this->data as $var => $val)
		{
			putenv("$var=$val") ;
		}
	}
	
	/**
	 * This method will use the auth_none class and not actually perform any
	 * authentication
	 * 
	 * @param string $username     Username to authenticate
	 * @param string $password     Password is ignored
	 */
	public function authenticate($username=null,$password=null)
	{
		//Now that our INCLUDE_PATH is set, let's get about doing some authentication
		if($this->getenv('DEBUG')) error_log('Including ignition_auth_none.php ') ;
		require_once('ignition_auth_none.php') ;
		
		if($this->getenv('DEBUG')) error_log('Setting up ignition_auth') ;

		//Create an instance of our auth class - it should be in our PHP_INCLUDE_PATH
		$this->auth = new ignition_auth_none($this) ;
		
		//If not already authenticated
		if(!$this->auth->isAuthenticated())
		{
			if($this->getenv('DEBUG')) error_log('User not authenticated: '.$username) ;
			$this->auth->authenticate($username,$password) ;
		}
		else
		{
			if($this->getenv('DEBUG')) error_log('User already signed-on: '.$this->auth->getUsername()) ; 
		}
	}
	
	
	
}
