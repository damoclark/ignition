<?php

//require_once('Smarty.class.php') ;

/**
 * ignition_Smarty extends Smarty and provides customisations to suit app development as used by ignition
 */
class ignition_Smarty extends Smarty
{
  /**
   * @var string Class used for templates
   */
  public $template_class = 'ignition_Smarty_Internal_Template';

  /**
   * Constructor for ignition_Smarty
   *
   */
  function __construct()
  {
    //date_default_timezone_set('Australia/Queensland') ;
    parent::__construct() ;

		//This change will allow templates to be included within a block section of
		//existing template where the filename of the template to be included is
		//defined by a smarty variable
		//http://www.smarty.net/forums/viewtopic.php?p=87138&sid=a2e2e821702e733505d1579c3eb2abaf
		$this->inheritance_merge_compiled_includes = false;
		
		//Turn off the undefined variable errors if debugging is off
		$debug = getenv('DEBUG') ;
		if(!is_numeric($debug) or $debug < 1)
			$this->error_reporting = E_ALL & ~E_NOTICE ;
		
    //If SMARTYDIR env var provided, use it
    if(!$smartydir = getenv('SMARTYDIR'))
    {
      //Otherwise, look for APPDIR and add /smarty on end
      if($smartydir = getenv('APPDIR'))
        $smartydir .= DS . 'smarty' ;
    }

    //If the environment provided a location use it with default dirs aftwards
    // DS = directory separate from superclass
    if($smartydir)
    {
      $smartyshareddir = getenv('SMARTYSHAREDDIR') ;
      if($smartyshareddir != null)
        $templateDir = array($smartydir . DS . 'templates',$smartyshareddir) ;
      else
        $templateDir = $smartydir . DS . 'templates' ;
        
      $this->setTemplateDir($templateDir) ;
      $this->setCompileDir($smartydir . DS . 'templates_c') ;
      $this->setCacheDir($smartydir . DS . 'cache') ;
      $this->setConfigDir($smartydir . DS . 'configs') ;
    }
  }

  /**
   * Function called by array_walk_recursive and converts objects to arrays.  Can operate on objects that implement ArrayAccess (such as PropelCollection), or objects that implement either a <code>toArray</code> or <code>getObjectAsAssocArray</code> method.
   * 
   * @param mixed $element Value from an array which if an object with a <code>toArray()</code> method will call that method and replace itself with the result
   * @param mixed $key     The key for the given value
   */
  public static function translateObjects(&$element,$key)
  {
    if(is_object($element) and ($element instanceof ArrayAccess))
    {
      $earray = array() ;
      foreach ($element as $e)
      {
        array_push($earray,array_change_key_case($e)) ;
      }
      $element = $earray ;
    }
    elseif(is_object($element) and method_exists($element,'toArray'))
      $element = array_change_key_case($element->toArray()) ;
    elseif(is_object($element) and method_exists($element,'getObjectAsAssocArray'))
      $element = array_change_key_case($element->getObjectAsAssocArray()) ;
  }
  
  /**
   * A class method that is called by other sub-classes of the Smarty framework to assign variables that can contain Propel objects
   * 
   * @param string $tpl_var Reference to template variable name
   * @param mixed $value   The value assigned to the variable name
   * @param bool $nocache Whether to the variable is to be cached or not
   * 
   * @return void
   */
  public static function staticAssign(&$tpl_var, &$value = null, &$nocache = false)
  {
    if (is_array($tpl_var))
        array_walk_recursive($tpl_var,'self::translateObjects') ;
    elseif(is_array($value))
        array_walk_recursive($value,'self::translateObjects') ;
		elseif(is_object($value))
				self::translateObjects($value,$tpl_var) ;
  }
  
  /**
   * A class method that is called by other sub-classes of the Smarty framework to append variables that can contain Propel objects
   * 
   * @param string $tpl_var Reference to template variable name
   * @param mixed $value   The value assigned to the variable name
   * @param bool $merge   Whether to merge with the append
   * @param bool $nocache Whether to the variable is to be cached or not
   * 
   * @return void
   */
  public static function staticAppend(&$tpl_var, &$value = null, &$merge = false, &$nocache = false)
  {
    if (is_array($tpl_var))
      array_walk_recursive($tpl_var,'self::translateObjects') ;
    elseif(is_array($value))
      array_walk_recursive($value,'self::translateObjects') ;
  }
  
  /**
   * A class method that is called by other sub-classes of the Smarty framework to append variables that can contain Propel objects
   * 
   * @param string $tpl_var Reference to template variable name
   * @param mixed $value   The value assigned to the variable name
   * @param bool $nocache Whether to the variable is to be cached or not
   * 
   * @return void
   */
  public static function staticAssignGlobal(&$varname, &$value, &$nocache)
  {
      if ($varname != '' and is_array($value))
          array_walk_recursive($value,'self::translateObjects') ;
  } 

  /**
   * assigns a Smarty variable
   * 
   * @param array $ |string $tpl_var the template variable name(s)
   * @param mixed $value the value to assign
   * @param boolean $nocache if true any output of this variable will be not cached
   * @param boolean $scope the scope the variable will have  (local,parent or root)
   */
  public function assign($tpl_var, $value = null, $nocache = false)
  {
    self::staticAssign($tpl_var,$value,$nocache) ;
    parent::assign($tpl_var,$value,$nocache) ;
  } 

  public function append($tpl_var, $value = null, $merge = false, $nocache = false)
  {
    self::staticAppend($tpl_var,$value,$merge,$nocache) ;
    parent::append($tpl_var,$value,$merge,$nocache) ;
  }
    
  public function assignGlobal($varname, $value = null, $nocache = false)
  {
    self::staticAssignGlobal($varname,$value,$nocache) ;
    parent::assignGlobal($varname, $value, $nocache) ;
  } 

  public function createData($parent = null)
  {
      return new ignition_Smarty_Data($parent, $this);
  } 
}

class ignition_Smarty_Internal_Template extends Smarty_Internal_Template
{
  // class used for templates
  public $template_class = 'ignition_Smarty_Internal_Template';
  
  /**
   * Function called by array_walk_recursive and converts objects to arrays.  Can operate on PropelCollection or DBObjects from Propel
   * 
   * @param mixed $element Value from an array which if an object with a <code>toArray()</code> method will call that method and replace itself with the result
   * @param mixed $key     The key for the given value
   */
  protected static function translateObjects(&$element,$key)
  {
    ignition_Smarty::translateObjects($element,$key) ;
  }

  /**
   * assigns a Smarty variable
`   * 
   * @param array $ |string $tpl_var the template variable name(s)
   * @param mixed $value the value to assign
   * @param boolean $nocache if true any output of this variable will be not cached
   * @param boolean $scope the scope the variable will have  (local,parent or root)
   */
  public function assign($tpl_var, $value = null, $nocache = false)
  {
    ignition_Smarty::staticAssign($tpl_var,$value,$nocache) ;
    parent::assign($tpl_var,$value,$nocache) ;
  } 

  public function append($tpl_var, $value = null, $merge = false, $nocache = false)
  {
    ignition_Smarty::staticAppend($tpl_var,$value,$merge,$nocache) ;
    parent::append($tpl_var,$value,$merge,$nocache) ;
  }
    
  public function assignGlobal($varname, $value = null, $nocache = false)
  {
    self::staticAssignGlobal($varname,$value,$nocache) ;
    parent::assignGlobal($varname, $value, $nocache) ;
  } 

  public function createData($parent = null)
  {
      return new ignition_Smarty_Data($parent, $this);
  } 
}

class ignition_Smarty_Data extends Smarty_Data
{
  // class used for templates
  public $template_class = 'ignition_Smarty_Internal_Template';

  /**
   * Function called by array_walk_recursive and converts objects to arrays.  Can operate on PropelCollection or DBObjects from Propel
   * 
   * @param mixed $element Value from an array which if an object with a <code>toArray()</code> method will call that method and replace itself with the result
   * @param mixed $key     The key for the given value
   */
  private static function translateObjects(&$element,$key)
  {
    ignition_Smarty::translateObjects($element,$key) ;
  }


  /**
   * assigns a Smarty variable
   * 
   * @param array $ |string $tpl_var the template variable name(s)
   * @param mixed $value the value to assign
   * @param boolean $nocache if true any output of this variable will be not cached
   * @param boolean $scope the scope the variable will have  (local,parent or root)
   */
  public function assign($tpl_var, $value = null, $nocache = false)
  {
    ignition_Smarty::staticAssign($tpl_var,$value,$nocache) ;
    parent::assign($tpl_var,$value,$nocache) ;
  } 

  public function append($tpl_var, $value = null, $merge = false, $nocache = false)
  {
    ignition_Smarty::staticAppend($tpl_var,$value,$merge,$nocache) ;
    parent::append($tpl_var,$value,$merge,$nocache) ;
  }
    
  public function assignGlobal($varname, $value = null, $nocache = false)
  {
    self::staticAssignGlobal($varname,$value,$nocache) ;
    parent::assignGlobal($varname, $value, $nocache) ;
  } 

  public function createData($parent = null)
  {
      return new ignition_Smarty_Data($parent, $this);
  } 
}

?>
