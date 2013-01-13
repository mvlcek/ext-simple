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
require_once(ES_ADMINPATH.'inc/settings.class.php');

class Init {
  
  private static $isFrontend = null;
  private static $language = null;
  private static $variant = null;
  private static $user = false;
  
  public static function definePaths() {
    $pos = strrpos(dirname(__FILE__),DIRECTORY_SEPARATOR.'inc');
    $adm = substr(dirname(__FILE__), 0, $pos);
    define('ES_ADMINPATH', $adm.'/');
    $pos = strrpos($adm,DIRECTORY_SEPARATOR);
    if (!defined('ES_ADMIN')) {
      define('ES_ADMIN', substr($adm,$pos+1));
    } else if (ES_ADMIN != substr($adm,$pos+1)) {
      Log::warning("ES_ADMIN is incorrectly defined: it is '%s', but should be '%s'!", ES_ADMIN, substr($adm,$pos+1));
    }
    $pos = strrpos($adm, DIRECTORY_SEPARATOR);
    define('ES_ROOTPATH', substr(__FILE__,0,$pos).'/');
    define('ES_DATAPATH', ES_ROOTPATH.'data/');
    define('ES_SETTINGSPATH', ES_DATAPATH.'settings/');
    define('ES_USERSPATH', ES_DATAPATH.'users/');
    define('ES_PAGESPATH', ES_DATAPATH.'pages/');
    define('ES_UPLOADSPATH', ES_DATAPATH.'uploads/');
    define('ES_THUMBNAILSPATH', ES_DATAPATH.'thumbs/');
    define('ES_LOGSPATH', ES_DATAPATH.'logs/');
    define('ES_BACKUPSPATH', ES_ROOTPATH.'backups/');
    define('ES_THEMESPATH', ES_ROOTPATH.'theme/');
    define('ES_PLUGINSPATH', ES_ROOTPATH.'plugins/');
  }

  public static function stripSlashesFromParam($value) {
    if (is_array($value)) {
      $result = array();
      foreach ($value as $v) $result[] = stripslashes($v);
      return $result;
    } else {
      return stripslashes($value);
    }
  }
  
  public static function isFrontend() {
    if (self::$isFrontend === null) {
      self::$isFrontend = ($_SERVER['SCRIPT_NAME'] == ES_ROOTPATH.'index.php');
    }
    return self::$isFrontend;
  }
  
  public static function getUser() {
    if (self::$user === false) {
      // determine user
      self::$user = null;
      $username = $_COOKIE['ES_USER'];
      if ($username) {
        $cookiename = sha1($username.Settings::getSalt());
        if (isset($_COOKIE[$cookiename])) {
          $cookieval = sha1($_SERVER['REMOTE_ADDR'].$username.Settings::getSalt());
          if ($_COOKIE[$cookiename] == $cookieval) {
            require_once(ES_ADMINPATH.'inc/user.class.php');
            self::$user = new User($username);
          }
        }
      }
    }
    return self::$user;
  }
  
  public static function isLoggedIn() {
    return getUser() != null;
  }
  
  public static function setLanguage($language) {
    self::$language = $language;
  }
  
  public static function getLanguage() {
    if (!self::$language) {
      if (self::isFrontend()) {
        if (($lang = Settings::getWebsiteLanguage())) self::$language = $lang;
      } else {
        $user = self::getUser();
        if (($lang = $user->getLanguage())) self::$language = $lang;
      }
      if (!self::$language && defined(ES_LANGUAGE) && ES_LANGUAGE) {
        self::$language = ES_LANGUAGE;
      }
    }
  }

  public static function setVariant($variant) {
    self::$variant = $variant;
  }
  
  public static function getVariant() {
    return self::$variant;
  }
  
  public static function setTimezone($timezone) {
    date_default_timezone_set($timezone);
  }
  
  public static function setLocale($locale) {
    if (is_array($locale)) {
      setlocale(LC_ALL, $locale);
    } else {
      setlocale(LC_ALL, preg_split('/\s*,\s*/', $locale));
    }
  }
  
}


class Common {

  private static $i18n = array();
  private static $i18n_loaded = array();
  
  private static $page = null;
  private static $styles = array();
  private static $scripts = array();
  
  public static function loadLanguage($plugin=null, $defaultLanguage=null) {
    if (@self::$i18n_loaded[$plugin]) return;
    $path = $plugin ? ES_PLUGINSPATH.$plugin.'/lang/' : ES_ADMINPATH.'/lang';
    $lang = Init::getLanguage();
    if (!self::loadLanguageFile($plugin, $path.$lang) && strlen($lang) > 2) {
      self::loadLanguageFile($plugin, $path.substr($lang,0,2));
    }
    if ($defaultLanguage != null) {
      self::loadLanguageFile($plugin, $path.$defaultLanguage);
    }
    self::$i18n_loaded[$plugin] = true;
  }
  
  private static function loadLanguageFile($plugin, $fileWithoutExt) {
    $prefix = $plugin ? $plugin.'/' : '';
    if (file_exists($fileWithoutExt.'.properties')) {
      $subst = array("\\n"=>"\n", "\\\\"=>"\\", "\\\""=>"\"", "\\'"=>"'", 
                     "\\r"=>"\r", "\\t"=>"\t");
      $fh = fopen($fileWithoutExt.'.properties', 'r');
      while (($line = fgets($fh)) !== false) {
        if (substr($line,0,1) != '#') {
          $pos = strpos($line,'=');
          if ($pos > 0) {
            $code = trim(substr($line,0,$pos));
            $text = ltrim(substr($line,$pos+1));
            while (substr($text,-1) == '\\' && ($line = fgets($fh)) !== false) {
              $text = substr($text,0,-1);
              $text += "\n".ltrim($line);
            }
            if (!array_key_exists($prefix.$code, self::$i18n)) {
              foreach ($subst as $from => $to) $text = str_replace($text, $from, $to);
              self::$i18n[$prefix.$code] = $text;
            }
          }
        }
      }
    } else if (file_exists($fileWithoutExt.'.php')) {
      $i18n = array();
      @include($fileWithoutExt.'.php'); 
      if (count($i18n) > 0) foreach ($i18n as $code => $text) {
        if (!array_key_exists($prefix.$code, self::$i18n)) {
          self::$i18n[$prefix.$code] = $text;
        }
      }
      return true;
    }
  }
  
  public static function loadPluginLanguage($plugin, $lang=null) {
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

  public static function getString($name, $args=null) {
    if (array_key_exists($name, self::$i18n)) {
      $msg = self::$i18n[$name];
      if (is_array($args)) $msg = vsprintf($msg, $args);
      return $msg;
    } else {
      return '{'.$name.'}';
    }
  }
  
  public static function isDebug() {
    return defined('ES_DEBUG') && ES_DEBUG;
  }
  
  public static function isFrontend() {
    return Init::isFrontend();
  }
  
  public static function isBackend() {
    return !self::isFrontend();
  }
  
  public static function getVariant() {
    return Init::getVariant();
  }
  
  public static function getUser() {
    return Init::getUser();
  }
  
  public static function getLanguage() {
    return Init::getLanguage();
  }
  
  public static function getPage() {
    if (!self::$page) {
      if (!self::isFrontend()) return null;
      $slug = $_REQUEST['id'];
      if (Page::existsPage($slug)) {
        self::$page = new Page($slug);
      } else if (Page::existsPage('404')) {
        self::$page = new Page('404');
      }
    }
    return self::$page;
  }
  
  public static function addStyle($name, $src, $version=null, $media=null) {
    if (!isset(self::$styles[strtolower($name)]) || 
        version_compare(self::$styles[strtolower($name)]['version'], $version) < 0) {
      self::$styles[strtolower($name)] = array('src'=>$src, 'version'=>$version, 'media'=>$media);
    }
  }
  
  public static function addScript($name, $src, $version=null) {
    if (!isset(self::$scripts[strtolower($name)]) || 
        version_compare(self::$scripts[strtolower($name)]['version'], $version) < 0) {
      self::$scripts[strtolower($name)] = array('src'=>$src, 'version'=>$version);
    }
  }
  
  public static function putStyles() {
    foreach (self::$styles as $style) {
      echo '<link href="'.htmlspecialchars($style['src']).'" rel="stylesheet"'.($style['media'] ? ' media="'.htmlspecialchars($style['media']).'"' : '').'>';
    }
  }
  
  public static function putScripts() {
    foreach (self::$scripts as $script) {
      echo '<script src="'.htmlspecialchars($script['src']).'"></script>';
    }
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

function add_frontend_js($name, $src, $version=null) {
  if (Common::isFrontend()) Common::addScript($name, $src, $version);  
}

function add_frontend_css($name, $src, $version=null, $media=null) {
  if (Common::isFrontend()) Common::addStyle($name, $src, $version, $media);
}

function add_backend_js($name, $src, $version=null) {
  if (Common::isBackend()) Common::addScript($name, $src, $version);  
}

function add_backend_css($name, $src, $version=null, $media=null) {
  if (Common::isBackend()) Common::addStyle($name, $src, $version, $media);
}

function put_css() {
  Common::putStyles();
}

function put_js() {
  Common::putScripts();
}

function get_page_link($slug) {
  $link = Settings::getPrettyURL();
  if (!$link) {
    $link = Settings::getWebsiteURL();
    if (!substr($link,-1) == '/') $link .= '/';
    $link .= 'index.php?id='.urlencode($slug);
    $link = execFilter('filter-link', array($link));
    return $link;
  } else {
    
  }
}