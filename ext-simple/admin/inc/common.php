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
  
  static public function definePaths() {
    $pos = strrpos(dirname(__FILE__),DIRECTORY_SEPARATOR.'inc');
    $adm = substr(dirname(__FILE__), 0, $pos);
    define('ES_ADMINPATH', tsl($adm));
    $pos = strrpos($adm, DIRECTORY_SEPARATOR);
    define('ES_ROOTPATH', tsl(__FILE__, 0 , $pos));
    define('ES_DATAPATH', GSROOTPATH.'data/');
    define('ES_PAGEPATH', GSROOTPATH. 'data/pages/');
    define('ES_UPLOADPATH', GSROOTPATH. 'data/uploads/');
    define('ES_THUMBNAILSPATH', GSROOTPATH. 'data/thumbs/');
    define('ES_BACKUPPATH', GSROOTPATH. 'backups/');
    define('ES_THEMEPATH', GSROOTPATH. 'theme/');
    define('ES_USERPATH', GSROOTPATH. 'data/users/');
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
    if (!file_exists(GSPLUGINPATH.$plugin.'/lang/'.$lang.'.php')) {
      return false;
    }
    $i18n = array();
    @include(GSPLUGINPATH.$plugin.'/lang/'.$lang.'.php'); 
    if (count($i18n) > 0) foreach ($i18n as $code => $text) {
      if (!array_key_exists($plugin.'/'.$code, I18N::$i18n)) {
        I18N::$i18n[$plugin.'/'.$code] = $text;
      }
    }
    return true;
  }

  static public function get($name) {
    if (array_key_exists($name, I18N::$i18n)) return I18N::$i18n[$name];
    return '{'.$name.'}';
  }

}


/**
 * output the localized string
 * 
 * @since 1.0
 * @author mvlcek
 * @param string $name
 */
function i18n($name) {
  echo I18N::get($name);
}

/**
 * return the localized string
 *
 * @since 1.0
 * @author mvlcek
 * @param string $name
 */
function i18n_r($name) {
  return I18N::get($name);
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
function i18n_merge($plugin, $language=null) {
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

function is_debug() {
  return defined('ES_DEBUG') && ES_DEBUG;
}

