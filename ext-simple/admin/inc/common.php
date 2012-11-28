<?php

define('ES_VERSION', '1.0');

// workaround if magic quotes is turned on
if (get_magic_quotes_gpc()) {
  foreach ($_GET as $key => $value) $_GET[$key] = Init::stripSlashesFromParam($value);
  foreach ($_POST as $key => $value) $_POST[$key] = Init::stripSlashesFromParam($value);
  foreach ($_REQUEST as $key => $value) $_REQUEST[$key] = Init::stripSlashesFromParam($value);
}

Init::definePaths();

class Init {
  
  private static $isFrontend = null;
  
  static public function definePaths() {
    $pos = strrpos(dirname(__FILE__),DIRECTORY_SEPARATOR.'inc');
    $adm = substr(dirname(__FILE__), 0, $pos);
    define('ES_ADMINPATH', tsl($adm));
    $pos = strrpos($adm, DIRECTORY_SEPARATOR);
    define('ES_ROOTPATH', tsl(__FILE__, 0 , $pos));
    define('ES_DATAPATH', ES_ROOTPATH.'data/');
    define('ES_SETTINGSPATH', ES_DATAPATH.'other/');
    define('ES_USERSPATH', ES_DATAPATH.'users/');
    define('ES_PAGESPATH', ES_DATAPATH.'pages/');
    define('ES_UPLOADSPATH', ES_DATAPATH.'uploads/');
    define('ES_THUMBNAILSPATH', ES_DATAPATH.'thumbs/');
    define('ES_BACKUPPATH', ES_ROOTPATH.'backups/');
    define('ES_THEMESPATH', ES_ROOTPATH.'theme/');
    define('ES_PLUGINSPATH', ES_ROOTPATH.'plugins/');
  }

  static public function stripSlashesFromParam($value) {
    if (is_array($value)) {
      $result = array();
      foreach ($value as $v) $result[] = stripslashes($v);
      return $result;
    } else {
      return stripslashes($value);
    }
  }
  
  static public function isFrontend() {
    if (self::$isFrontend === null) {
      self::$isFrontend = ($_SERVER['SCRIPT_NAME'] == ES_ROOTPATH.'index.php');
    }
    return self::$isFrontend;
  }

  static public function setLanguage() {
    
  }
  
  static public function setTimezone() {
    
  }
  
}


class I18N {

  static private $i18n = array();
  
  function load($lang=null) {
    global $LANG;
    if (!$lang) $lang = $LANG;
  }
  
  function loadPlugin($plugin, $lang=null) {
    global $LANG;
    if (!$lang) $lang = $LANG;
    if (!file_exists(ES_PLUGINSPATH.$plugin.'/lang/'.$lang.'.php')) {
      return false;
    }
    $i18n = array();
    @include(ES_PLUGINSPATH.$plugin.'/lang/'.$lang.'.php'); 
    if (count($i18n) > 0) foreach ($i18n as $code => $text) {
      if (!array_key_exists($plugin.'/'.$code, I18N::$i18n)) {
        I18N::$i18n[$plugin.'/'.$code] = $text;
      }
    }
    return true;
  }

  static public function get($name, $args=null) {
    if (array_key_exists($name, I18N::$i18n)) {
      $msg = I18N::$i18n[$name];
      if (is_array($args)) $msg = vsprintf($msg, $args);
      return $msg;
    } else {
      return '{'.$name.'}';
    }
  }

}


/**
 * output the localized formatted string 
 * 
 * @since 1.0
 * @author mvlcek
 * @param string $name
 * @param varargs $args
 */
function putString($name, $args) {
  $args = func_get_args();
  array_shift($args);
  echo I18N::get($name, $args);
}

function putS($name, $args) {
  $args = func_get_args();
  array_shift($args);
  echo I18N::get($name, $args);
}

/**
 * return the localized formatted string
 *
 * @since 1.0
 * @author mvlcek
 * @param string $name
 * @param varargs $args
 */
function getString($name, $args) {
  $args = func_get_args();
  array_shift($args);
  return I18N::get($name, $args);
}

function getS($name, $args) {
  $args = func_get_args();
  array_shift($args);
  return I18N::get($name, $args);
}

/**
 * Merges a plugin's language file with the global language map
 * This is the function that plugin developers will call to initiate 
 * the language merge.
 *
 * @since 1.0
 * @author mvlcek
 * @param string $plugin
 * @param string $language, default=null
 * @return bool
 */
function loadStrings($plugin, $language=null) {
  return I18N::loadPlugin($plugin, $language);
}

/**
 * Add a trailing slash to a path, if necessasry
 * 
 * @since 1.0
 * @author mvlcek
 * @param string $path
 */
function tsl($path) {
  return substr($path,-1) == '/' ? $path : $path.'/'; 
}

function isDebug() {
  return defined('ES_DEBUG') && ES_DEBUG;
}

function isFrontend() {
  return Init::isFrontend();
}

function isBackend() {
  return !isFrontend();
}

function getLanguage() {
  // TODO
}

function getDefaultLanguage() {
  // TODO
}
