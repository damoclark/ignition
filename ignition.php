<?php

/**
 * The ignition class is the first object to load for any page controller script
 * and will obtain the php include_path from the apache environment using the
 * PHP_INCLUDE_PATH environment variable and prepend this to the existing
 * include_path
 */
class ignition
{
	protected $conf = array
	(
		'OLT_CONFIG_PATH' => 'Directory containing ini files with shared and app specific settings',
		'PHP_INCLUDE_PATH' => 'The include_path setting for the given app',
		'PHP_HTML_TEMPLATE_ROOT' => 'The directory where the HTML-Template templates are located',
		'APPDIR' => 'The base directory for where an app is installed, generally in /usr/local/www',
		'SMARTYDIR' => 'The base directory where the smarty template files are stored and includes templates, templates_c, cache & and config directories',
		'SMARTYSHAREDDIR' => 'The base directory where shared Smarty templates are stored',
		'PROPELBUILD' => 'The base directory for the propel build area',
		'APPFACTORYINCLUDE' => 'The PHP file to \'include\' that contains the factory class for the given app, relative to the include_path (not absolute)',
		'APPFACTORYOBJECT' => 'Object name to instantiate as the factory object'
	) ;
	
	/**
	 * Constructor for ignition class.  Will load environment variables and
	 * set the include_path if the PHP_INCLUDE_PATH environment variable is set
	 * 
	 * @return ignition    An ignition object
	 */
	function __construct()
	{
		$this->data = array() ;
		foreach($this->conf as $var => $value)
		{
			$val = getenv($var) ;
			if($val)
				$this->data[$var] = $val ;
		}
		
		//If the include path env variable was provided, then set the include_path
		if(array_key_exists('PHP_INCLUDE_PATH',$this->data))
		{
			error_log('Setting php include_path',E_USER_NOTICE) ;
			set_include_path($this->data['PHP_INCLUDE_PATH'].':'.get_include_path()) ;
		}
		error_log('DEBUG: include_path='.get_include_path(),E_USER_NOTICE) ;
		
		if(array_key_exists('APPFACTORYINCLUDE',$this->data))
		{
			include_once($this->data['APPFACTORYINCLUDE']) ;
		}
	}
	
	/**
	 * Get the value of the given environment variable
	 * 
	 * @param string $var Variable name
	 * 
	 * @return string    Value of the variable or null if not defined
	 */
	function getenv($var)
	{
		return $this->data[$var] ;
	}
}

?>
