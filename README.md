
# Ignition #

The ignition library provides a very simple framework for initialising (or igniting) an Apache PHP controller script or command line script for execution.  

Ignition supports a range of Proprietary and Open Source PHP-based projects developed at [CQUniversity Australia](http://www.cqu.edu.au).

Open Source Projects include:

 - [Moodle Activity Viewer](https://github.com/damoclark/mav)

## Requirements ##

Apache 2.2+
PHP 5.2+

## Synopsis ##

It is designed to configure the environment for each PHP controller script by looking at the environment variables passed through Apache configuration via a `<Document>` configuration directive. 

The class integrates with both the Smarty 3.x Templating library for views, and Propel 1.x ORM for models, bringing together its own MVC.  While more sophisticated frameworks have emerged such as Symphony and Cake, some existing projects persist with Ignition, although they are likely to move to something more modern in the future.

The class is the first object to load for any page controller script and will obtain the php include_path from the apache environment using the PHP_INCLUDE_PATH environment variable and prepend this to the existing include_path. It will also lookup a range of other apache environment variables and configure related PHP libraries as necessary. 

## Installation ##

Download Ignition

```bash
cd /usr/local/php/lib
git clone https://github.com/damoclark/ignition.git
```

Add _/usr/local/php/lib/ignition_ to PHP include_path in php.ini and restart Apache

```bash
sudo apachectl graceful
```

## Usage ##

### Apache Environment Variables ###

```apache
<Document /usr/local/www/app>
		#The base directory for where a web apps files are installed, such as
		#/usr/local/www/appname. Usually matches the Document directive (required)
		SetEnv APPDIR "/usr/local/www/app"
		#Relative (to APPDIR) or absolute path to a configuration file for this app.
		#Can be any conf file format, but default implementation
		#(ignition_app_config) uses ini files
		SetEnv APPCONF "etc/app.ini"
		#Relative (to APPDIR) or absolute path to a configuration file for this app.
		#Can be any conf file format, but default implementation
		#(ignition_app_config) uses ini files
		SetEnv APPCONFCLASS "AppConfig"
		#The include_path setting for the given app to be prepended to the existing
		#include path
		SetEnv PHP_INCLUDE_PATH "/usr/local/www/app/lib:/var/simplesamlphp:..."
		#The base directory where the smarty template files are stored and includes
		#templates, templates_c, cache & and config directories (if Smarty used)
		SetEnv SMARTYDIR "/usr/local/www/app/smarty"
		#The base directory where shared Smarty templates are stored. These
		#templates can be shared between projects using ignition and can be pointed
		#to the templates directory in the ignition library
		SetEnv SMARTYSHAREDDIR "/usr/local/www/lib/ignition/templates"
		#Name of class that implements ignition_Auth to use as an Authentication
		#object. The php file containing the class implementation must have the same
		#name as the class with added .php on end and located in the include path. A
		#range of authentication classes already provided in ignition, which is
		#already in PHP include path. Using SSO (simplesampl) authentication
		SetEnv AUTHCLASS "ignition_auth_sso"
		#Debug level for logging in the application Set debug level for development
		#server
		SetEnv DEBUG "9"
		#A colon separated list of php files to "include" into memory, either
		#absolute or relative to PHP_INCLUDE_PATH
		SetEnv PHP_INCLUDE "lib/lib.php"
		#The path to a php startup script that can be called to initialise libraries
		#and other startup functions. This is called after the PHP_INCLUDE files
		#have been "included", but before anything else
		SetEnv STARTUP_SCRIPT "etc/startup.php"
</Document>
```

### Apache Controller Scripts ###

```php
//Load ignition class
require_once('ignition.php') ;

//Instantiate ignition (no parameters means authenticate by default)
$ignition = new ignition() ;

//Get the authenticated username
$username = $ignition->getAuth()->getUsername() ;

//Parse config file identified by by APPCONF Apache variable using APPCONFCLASS PHP class (or if not specified, default ignition_app_config class)
$appConfig = $ignition->getAppConfig() ; 

```

### Command Line Scripts Located Within the Codebase Directory ###

```php
//Load command line ignition
require_once('clignition.php') ;
//Instantiate clignition (false means don't authenticate - no need at command line)
$ignition = new clignition(false) ;

//Retrieve command line parameters using ignition
$options = $ignition->getCmdLineOptions
(
	array
	(
		'help' => false, //Not given by default
		'debug' => false, //Not given by default
		'jobs:' => 1, //Default to running only 1 concurrent calculation jobs
		'rows-per-job:' => 10000, //Default number rows for each child process
		'progress' => false, //Output progress markers (not given by default)
		'update:' => false, //No default (but must be provided)
		'purge' => false,
		'list' => false
	)
) ;

//Parse config file identified by by APPCONF Apache variable using APPCONFCLASS PHP class (or if not specified, default ignition_app_config class)
$appConfig = $ignition->getAppConfig() ; 

```

## Licence ##


Ignition is licenced under the terms of the [GPLv3](http://www.gnu.org/licenses/gpl-3.0.en.html).

## Contributions ##

Contributions are welcome - fork and push away.  Contact me ([Damien Clark](mailto:damo.clarky@gmail.com)) for further information.
