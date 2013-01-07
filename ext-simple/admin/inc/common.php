<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

define('ES_VERSION', '1.0');
define('IN_ES', true);

// workaround if magic quotes is turned on
if (get_magic_quotes_gpc()) {
  foreach ($_GET as $key => $value) $_GET[$key] = Init::stripSlashesFromParam($value);
  foreach ($_POST as $key => $value) $_POST[$key] = Init::stripSlashesFromParam($value);
  foreach ($_REQUEST as $key => $value) $_REQUEST[$key] = Init::stripSlashesFromParam($value);
}

Init::definePaths();
require_once(ES_ROOTPATH.'esconfig.php');
require_once(ES_ADMINPATH.'inc/file.class.php');

class Init {
  
  private static $isFrontend = null;
  private static $variant = null;
  
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
    define('ES_BACKUPSPATH', ES_ROOTPATH.'backups/');
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

  static public function setVariant($variant) {
    self::$variant = $variant;
  }
  
  static public function getVariant() {
    return self::$variant;
  }
  
  static public function setTimezone($timezone) {
    date_default_timezone_set($timezone);
  }
  
  static public function setLocale($locale) {
    if (is_array($locale)) {
      setlocale(LC_ALL, $locale);
    } else {
      setlocale(LC_ALL, preg_split('/\s*,\s*/', $locale));
    }
  }
  
}


class Common {

  static private $i18n = array();
  
  function loadLanguage($lang=null) {
    global $LANG;
    if (!$lang) $lang = $LANG;
  }
  
  function loadPluginLanguage($plugin, $lang=null) {
    global $LANG;
    if (!$lang) $lang = $LANG;
    if (!file_exists(ES_PLUGINSPATH.$plugin.'/lang/'.$lang.'.php')) {
      return false;
    }
    $i18n = array();
    @include(ES_PLUGINSPATH.$plugin.'/lang/'.$lang.'.php'); 
    if (count($i18n) > 0) foreach ($i18n as $code => $text) {
      if (!array_key_exists($plugin.'/'.$code, self::$i18n)) {
        self::$i18n[$plugin.'/'.$code] = $text;
      }
    }
    return true;
  }

  static public function getString($name, $args=null) {
    if (array_key_exists($name, self::$i18n)) {
      $msg = self::$i18n[$name];
      if (is_array($args)) $msg = vsprintf($msg, $args);
      return $msg;
    } else {
      return '{'.$name.'}';
    }
  }
  
  function isDebug() {
    return defined('ES_DEBUG') && ES_DEBUG;
  }
  
  function isFrontend() {
    return Init::isFrontend();
  }
  
  function isBackend() {
    return !self::isFrontend();
  }
  
  function getVariant() {
    return Init::getVariant();
  }
  
}


/**
 * return the localized formatted string
 *
 * @since 1.0
 * @author mvlcek
 * @param string $name
 * @param varargs $args
 */
function get_s($name, $args) {
  $args = func_get_args();
  array_shift($args);
  return I18nHelper::getString($name, $args);
}

/**
 * output the localized formatted string 
 * 
 * @since 1.0
 * @author mvlcek
 * @param string $name
 * @param varargs $args
 */
function put_s($name, $args) {
  $args = func_get_args();
  array_shift($args);
  echo I18nHelper::getString($name, $args);
}


function put_date($date, $format=null, $default=null) {
  $time = is_numeric($date) ? (int) $date : strtotime($date);
  if ($time) {
    if ($format) {
      $fmt = get_s('DATE_FORMAT_'.$format);
      if (!$fmt) $fmt = $format;
    } else {
      $fmt = get_s('DATE_FORMAT');
      if (!$fmt) $fmt = '%Y-%m-%d %H:%M';
    }
    echo htmlspecialchars(strftime($fmt, $time)); 
    return true;
  } else {
    if ($default != null) echo $default;
    return false;
  }
}

function put_number($number, $format, $default) {
  if ($number != null) {
    if ($format) {
      $fmt = get_s('NUMBER_FORMAT_'.$format);
      if (!$fmt) $fmt = $format;
    } else {
      $fmt = get_s('NUMBER_FORMAT');
      if (!$fmt) $fmt = '%.2f';
    }
    echo htmlspecialchars(sprintf($fmt, $number));
    return true;
  } else {
    if ($default != null) echo $default;
    return false;
  }
}
