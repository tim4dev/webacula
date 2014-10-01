<?php
/**
 * Copyright 2009, 2010, 2011, 2014 Yuriy Timofeev <tim4dev@gmail.com>
 * @author Yuriy Timofeev <tim4dev@gmail.com>
 * @package webacula
 * @license http://www.gnu.org/licenses/gpl-3.0.html GNU Public License
 */

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting( E_ALL | E_STRICT );

/* Determine the root and library directories of the application */
$appRoot = realpath(dirname(__FILE__) . '/../..');
$libDir   = "$appRoot/library";
$modelDir = "$appRoot/application/models";
$formDir  = "$appRoot/application/forms";
$path = array( $libDir, $modelDir, $formDir, get_include_path() );
set_include_path(implode(PATH_SEPARATOR, $path));

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', $appRoot . '/application');

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

require_once "Zend/Loader/Autoloader.php";
Zend_Loader_Autoloader::getInstance();

/*
 * from index.php
 */
define('WEBACULA_VERSION', '7.x, build for tests');
define('BACULA_VERSION', 14); // Bacula Catalog version
define('ROOT_DIR',   $appRoot );
define('CACHE_DIR',  ROOT_DIR.'/data/cache' );

// load my ACL classes
Zend_Loader::loadClass('MyClass_Session_SaveHandler_DbTable'); // PHP session storage
Zend_Loader::loadClass('MyClass_WebaculaAcl');
Zend_Loader::loadClass('MyClass_ControllerAclAction');
Zend_Loader::loadClass('MyClass_BaculaAcl');
Zend_Loader::loadClass('Wbresources');
Zend_Loader::loadClass('Wbroles');
// helpers
Zend_Controller_Action_HelperBroker::addPrefix('MyClass_Action_Helper');
// other classes
Zend_Loader::loadClass('MyClass_HomebrewBase64');
Zend_Loader::loadClass('MyClass_GaugeTime');
Zend_Loader::loadClass('MyClass_PasswordHash');
Zend_Loader::loadClass('Version');

// load all configuration sections and save to registry
$config = new Zend_Config_Ini('../application/config.ini');
$registry = Zend_Registry::getInstance();
$registry->set('config', $config);

// set timezone
if ( isset($config->general->def->timezone) )
    date_default_timezone_set($config->general->def->timezone);
else {
    Zend_Loader::loadClass('Zend_Date');
    $date = new Zend_Date();
    date_default_timezone_set( $date->getTimeZone() );
}

// set self version
Zend_Registry::set('bacula_version',   BACULA_VERSION);
Zend_Registry::set('webacula_version', WEBACULA_VERSION);

// set global const
Zend_Registry::set('UNKNOWN_VOLUME_CAPACITY', -200); // tape drive
Zend_Registry::set('NEW_VOLUME', -100);
Zend_Registry::set('ERR_VOLUME', -1);

/**
 * Database, table, field and columns names in PostgreSQL are case-independent, unless you created them with double-quotes
 * around their name, in which case they are case-sensitive.
 * Note: that PostgreSQL actively converts all non-quoted names to lower case and so returns lower case in query results.
 * In MySQL, table names can be case-sensitive or not, depending on which operating system you are using.
 */

// setup database bacula
Zend_Registry::set('DB_ADAPTER', strtoupper($config->general->db->adapter) );
$params = $config->general->db->config->toArray();
// for cross database compatibility with PDO, MySQL, PostgreSQL
$params['options'] = array(Zend_Db::CASE_FOLDING => Zend_Db::CASE_LOWER, Zend_DB::AUTO_QUOTE_IDENTIFIERS => FALSE);
$db_bacula = Zend_Db::factory($config->general->db->adapter, $params);
Zend_Db_Table::setDefaultAdapter($db_bacula);
Zend_Registry::set('db_bacula', $db_bacula);
unset($params);

// setup controller, exceptions handler
$frontController = Zend_Controller_Front::getInstance();
$frontController->setControllerDirectory($appRoot . '/application/controllers');
$frontController->throwExceptions(false);

// translate
//auto scan lang files may be have bug in ZF ? $translate = new Zend_Translate('gettext', '../languages', null, array('scan' => Zend_Translate::LOCALE_DIRECTORY));
$translate = new Zend_Translate('gettext', '../languages/en/webacula_en.mo', 'en');
// additional languages
// see also http://framework.zend.com/manual/en/zend.locale.appendix.html
$translate->addTranslation('../languages/en/webacula_en.mo', 'en_US');
$translate->addTranslation('../languages/de/webacula_de.mo', 'de');
$translate->addTranslation('../languages/fr/webacula_fr.mo', 'fr');
$translate->addTranslation('../languages/ru/webacula_ru.mo', 'ru');
$translate->addTranslation('../languages/ru/webacula_ru.mo', 'ru_RU');
$translate->addTranslation('../languages/pt/webacula_pt_BR.mo', 'pt_BR');
$translate->addTranslation('../languages/it/webacula_it.mo', 'it');
$translate->addTranslation('../languages/es/webacula_es.mo', 'es');

if ( isset($config->general->locale) ) {
    // locale is user defined
    $locale = new Zend_Locale(trim($config->general->locale));
} else {
    // autodetect locale
    // Search order is: given Locale, HTTP Client, Server Environment, Framework Standard
    try {
        $locale = new Zend_Locale('auto');
    } catch (Zend_Locale_Exception $e) {
        $locale = new Zend_Locale('en');
    }
}
if ( $translate->isTranslated('Desktop', false, $locale) ) {
    // can be translated (есть перевод)
    $translate->setLocale($locale);
} else {
    // can't translated (нет перевода)
    // set to English by default
    $translate->setLocale('en');
    $locale = new Zend_Locale('en');
}
// assign the $translate object to the registry so that it can be retrieved elsewhere in the application
$registry->set('translate', $translate);
$registry->set('locale',    $locale);
$registry->set('language',  $locale->getLanguage());

// Show human readable short Job description instead of Job Bacula names
Zend_Registry::set('show_job_description', 2);
/*if ( isset($config->general->show_job_description) )    {
    if ( ( $config->general->show_job_description < 0 ) || ( $config->general->show_job_description > 2 ) )
        $show_job_description = 0;
    else
        $show_job_description = $config->general->show_job_description;
}  else
    $show_job_description = 0;
$registry->set('show_job_description', $show_job_description);*/

Zend_Layout::startMvc(array(
    'layoutPath' => $appRoot . '/application/layouts/' . $config->layout->path,
    'layout' => 'main'
));

try {
	$db = Zend_Db_Table::getDefaultAdapter();
	$db->getConnection();
} catch (Zend_Db_Adapter_Exception $e) {
	// возможно СУБД не запущена
	throw new Zend_Exception("Fatal error: Can't connect to SQL server");
}
/*
 * Check Bacula Catalog version
 */
$ver = new Version();
if ( !$ver->checkVesion(BACULA_VERSION) )   {
    echo '<pre>';
    throw new Zend_Exception("Version error for Catalog database (wanted ".BACULA_VERSION.",".
            " got ". $ver->getVesion().") ");
}
/*
 * Check CACHE_DIR is writable
 */
if ( !is_writable( CACHE_DIR ) ) {
    echo '<pre>';
    throw new Zend_Exception('Directory "'.CACHE_DIR.'" is not exists or not writable.');
}
/*
 * Start session
 */
Zend_Session::setOptions(array(
    'use_only_cookies' => 1
));

//create your Zend_Session_SaveHandler_DbTable and
//set the save handler for Zend_Session
$config_session = array(
    'name'           => 'webacula_php_session',
    'primary'        => 'id',
    'modifiedColumn' => 'modified',
    'lifetimeColumn' => 'lifetime',
    'dataColumn'     => 'data_session'
);
Zend_Session::setSaveHandler(new MyClass_Session_SaveHandler_DbTable($config_session));

Zend_Session::start();

if ( APPLICATION_ENV == 'production') {
    Zend_Session::regenerateId();
}

// для подсчета кол-ва неудачных логинов для вывода капчи
$defNamespace = new Zend_Session_Namespace('Default');
if (!isset($defNamespace->numLoginFails))
    $defNamespace->numLoginFails = 0; // начальное значение

/*
 * Zend_Cache
 */
$frontendOptions = array(
    'lifetime' => 3600, // время жизни кэша - 1 час
    'automatic_serialization' => true
);
$backendOptions = array(
    'cache_dir' => CACHE_DIR.'/'      // директория, в которой размещаются файлы кэша
);
// получение объекта Zend_Cache_Core
$cache = Zend_Cache::factory(
    'Core',
    'File',
    $frontendOptions,
    $backendOptions
);
Zend_Registry::set('cache', $cache); // save to Registry


/***************************************
 * end from index.php
 ***************************************/


/* Zend_Application */
require_once 'Zend/Application.php';
require_once 'ControllerTestCase.php';
require_once 'ModelTestCase.php';
