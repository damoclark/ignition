<?php

class ignition_app_config
{
	/**
	 * @var array Configuration parameters as loaded from config file using parse_ini_file
	 */
	protected $config = null ;
	
	/**
	 * @var string Filename to load the config file
	 */
	protected $configFilename = null ;
	
	/**
	 * @var array An array of section headings required in the config e.g. $configSections = array('global' => "The global section is for...", ...)
 	 * To validate, overload these property in subclasses.  This base class does no checking
	 */
	protected $configSections = array() ; //No required section headings (can be arbitrary)
	
	/**
	 * @var array An array of required configuration options per config section name (or * section name to be applied to all sections given in the config file except those nominated in the $configSections list) e.g.
	 * $configOptions = array
	 * (
	 *   '*' => array
	 *   (
	 *     'optionname' => array('default' => 'default value', 'description' => 'Description of optionname option')
	 *   )
	 * )
	 *
	 * To validate, overload these property in subclasses.  This base class does no checking
	 */
	protected $configOptions = array() ;

	/**
	 * Constructor
	 * 
	 * @param string $configFilename Filename to load the config file
	 *
	 * @throws ignition_Exception If no filename is provided or file cannot be read/parsed
	 * 
	 * @return ignition_app_config    An instance of this object
	 */
	function __construct($configFilename=null)
	{
		if($configFilename === null)
			throw new ignition_Exception("Need to provide configuration filename to constructor",ignition_Exception::GENERIC_ERROR) ;

		$this->configFilename = $configFilename ;
		
		$this->config = parse_ini_file($configFilename,true) ;

		if($this->config === false)
			throw new ignition_Exception("Error parsing configuration file $configFilename",ignition_Exception::GENERIC_ERROR) ;
	}

	/**
	 * This method will validate the contents of the config file according to the settings provided in the configSections and configOptions properties of this instance
	 *
	 * @throws Exception If there is a validation error on the config file
	 */
	protected function validateConfig()
	{
		//Make sure all section headings exist (if any stipulated)
		foreach($this->configSections as $configSectionName)
		{
			if(!array_key_exists($configSectionName,$this->config))
				throw new Exception("Missing section $configSectionName: {$this->configSections[$configSectionName]}",1) ;
		}
		
		//Validate options for each section heading
		foreach(array_keys($this->configOptions) as $configSectionName)
		{
			//If * is given as section name in the configOptions array, apply the
			//config test to all options for all sections, except those specifically
			//nominated in the configSections property array.  They will be tested specifically.
			if($configSectionName == '*')
			{
				//Get all section names found in the config file to test, except those
				//nominated in the configSections property - they will be tested with their specific
				//configOptions settings (if given)
				$sectionList = array_diff(array_keys($this->config),array_keys($this->configSections)) ;
			}
			else //Otherwise, just test $configSectionName with its specific configOptions settings
				$sectionList = array($configSectionName) ;
				
			//This will loop only once if $configSectionName is not *, otherwise, it will
			//loop for all sections in the config file except those specified in the configSections
			//property of this instance
			foreach($sectionList as $confFileSectionName)
			{
				//Go through the config options for this config section
				foreach($this->configOptions[$configSectionName] as $optionName => $option)
				{
					//If no default is available, and the option is not given, then throw Exception
					if(!array_key_exists('default',$option) and !array_key_exists($optionName,$this->config[$confFileSectionName]))
						throw new Exception("Missing option $optionName in section $confFileSectionName: {$option['description']}",1) ;
					
					//If a default is available, and the option is not given, then set it to the default
					if(array_key_exists('default',$option) and !array_key_exists($optionName,$this->config[$confFileSectionName]))
						$this->config[$confFileSectionName][$optionName] = $option['default'] ;
					
					//If value validation configOption provided for this optionName, then
					//check to see if the value given is valid
					if(array_key_exists('valid',$option))
					{
						//If valid is a function, call it
						if(is_callable($option['valid']) and !$option['valid']($this->config[$confFileSectionName][$optionName]))
							throw new Exception("Invalid value provided for option $optionName: {$this->config[$confFileSectionName][$optionName]}",1) ;

						//If valid is a string, then use regex
						$re = preg_match($option['valid'],$this->config[$confFileSectionName][$optionName]) ;

						//This means bad RE
						if($re === false)
							throw new Exception("Invalid RE provided for option $optionName in section name $confFileSectionName: {$option['valid']}",1) ;
						
						//This means invalid value
						if($re === 0)
							throw new Exception("Invalid value provided for option $optionName in section name $confFileSectionName: {$this->config[$confFileSectionName][$optionName]}",1) ;
					}
				}
			}
		}
	}
	
	/**
	 * Given section name, return the configuration as an array
	 * 
	 * @param string $section Name of the section (ie. [section]) in config
	 * 
	 * @return array    A data structure representing the section in the config ini file
	 */
	function getConfigSection($section=null)
	{
		if($section === null)
			throw new ignition_Exception("Need to provide section name",ignition_Exception::GENERIC_ERROR) ;
		
		if(!array_key_exists($section,$this->config))
			throw new ignition_Exception("Section '$section' not found in config file {$this->configFilename}",ignition_Exception::GENERIC_ERROR) ;
		
		return $this->config[$section] ;
	}

	/**
	 * Given section name and a variable name, return the value from the config ini
	 * 
	 * @param string $section Name of the section (ie. [section]) in config
	 * @param string $var     Variable name from the config file to retrieve value
	 *
	 * @return mixed    The value from the config
	 */
	function getConfigValue($section=null,$var=null)
	{
		if($section === null)
			throw new ignition_Exception("Need to provide section name",ignition_Exception::GENERIC_ERROR) ;
		
		if($var === null)
			throw new ignition_Exception("Need to provide variable name",ignition_Exception::GENERIC_ERROR) ;
		
		if(!array_key_exists($section,$this->config))
			throw new ignition_Exception("Section '$section' not found in config file {$this->configFilename}",ignition_Exception::GENERIC_ERROR) ;
		
		if(!array_key_exists($var,$this->config[$section]))
			throw new ignition_Exception("Variable '$var' not found in section '$section' in config file {$this->configFilename}",ignition_Exception::GENERIC_ERROR) ;
		
		return $this->config[$section][$var] ;
	}
	
	/**
	 * Return as an array, a list of all the section names found in the config file
	 * 
	 * @return array    An array of section heading names
	 */
	function getConfigSectionNames()
	{
		return array_keys($this->config) ;
	}

}




?>