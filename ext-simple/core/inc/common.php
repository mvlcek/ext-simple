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
require_once(ES_COREPATH.'inc/file.class.php');
require_once(ES_COREPATH.'inc/user.class.php');
require_once(ES_COREPATH.'inc/settings.class.php');
require_once(ES_COREPATH.'inc/plugins.class.php');

class Init {
  
  private static $isFrontend = null;
  private static $language = null;
  private static $variants = null;
  private static $user = false;
  private static $page = null;
  
  public static function definePaths() {
    $pos = strrpos(dirname(__FILE__),DIRECTORY_SEPARATOR.'inc');
    $core = substr(dirname(__FILE__), 0, $pos);
    define('ES_COREPATH', $core.'/');
    $pos = strrpos($core, DIRECTORY_SEPARATOR);
    define('ES_ROOTPATH', substr(__FILE__,0,$pos).'/');
    define('ES_ADMINPATH', ES_ROOTPATH.'admin/');
    define('ES_COREPATH', ES_ROOTPATH.'core/');
    
    define('ES_DATAPATH', ES_ROOTPATH.'data/');
    define('ES_SETTINGSPATH', ES_DATAPATH.'settings/');
    define('ES_USERSPATH', ES_DATAPATH.'users/');
    define('ES_PAGESPATH', ES_DATAPATH.'pages/');
    define('ES_UPLOADSPATH', ES_DATAPATH.'uploads/');
    define('ES_THUMBNAILSPATH', ES_DATAPATH.'thumbs/');
    define('ES_LOGSPATH', ES_DATAPATH.'logs/');

    define('ES_BACKUPSPATH', ES_ROOTPATH.'backups/');
    define('ES_CACHEPATH', ES_DATAPATH.'cache');
    define('ES_THEMESPATH', ES_ROOTPATH.'themes/');
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
  
  public static function setFrontend($isFrontend) {
    self::$isFrontend = $isFrontend;
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
            require_once(ES_COREPATH.'inc/user.class.php');
            self::$user = new User($username);
            if (!self::isFrontend()) {
              $timezone = self::$user->getTimezone();
              if ($timezone) self::setTimezone($timezone);
              $language = self::$user->getLanguage();
              if ($language) self::setLanguage($language);
            }
          }
        }
      }
    }
    return self::$user;
  }
  
  public static function isLoggedIn() {
    return self::getUser() != null;
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

  public static function setVariants($variants) {
    self::$variants = is_array($variants) ? $variants : func_get_args();
  }
  
  public static function getVariants() {
    return self::$variants;
  }
  
  public static function getVariant() {
    return self::$variants && count(self::$variants) > 0 ? self::$variants[0] : null;
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
  
  public static function setPage($page) {
    self::$page = $page;
  }
  
  public static function getPage() {
    return self::$page;
  }
  
}


class Common {

  private static $i18n = array();
  private static $i18n_loaded = array();
  
  private static $page = null;
  private static $styles = array();
  private static $scripts = array();
  
  public static function loadLanguage($plugin=null) {
    $id = $plugin ? $plugin->getId() : null;
    if (!isset(self::$i18n_loaded[$id])) {
      $path = $id ? ES_PLUGINSPATH.$id.'/lang/' : ES_COREPATH.'lang/';
      $lang = Init::getLanguage();
      if (self::loadLanguageFile($id, $path.$lang)) {
        self::$i18n_loaded[$id] = true;
      } else if (strlen($lang) > 2 && self::loadLanguageFile($id, $path.substr($lang,0,2))) {
        self::$i18n_loaded[$id] = true;
      } else if ($plugin && self::loadLanguageFile($id, $path.$plugin->getDefaultLanguage())) {
        self::$i18n_loaded[$id] = true;
      } else if (self::loadLanguageFile($id, $path.'en_US')) {
        self::$i18n_loaded[$id] = true;
      } else {
        self::$i18n_loaded[$id] = false;
      }
    }
    return (bool) self::$i18n_loaded[$id];
  }
  
  private static function loadLanguageFile($pluginId, $fileWithoutExt) {
    $prefix = $pluginId ? $pluginId.'/' : '';
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
      fclose($fh);
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
    return false;
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
  
  public static function getVariants() {
    return Init::getVariants();
  }
  
  public static function getVariant() {
    return Init::getVariant();
  }
  
  public static function getUser() {
    return Init::getUser();
  }
  
  public static function isLoggedIn() {
    return Init::isLoggedIn();
  }
  
  public static function ensureLoggedIn() {
    if (!Init::isLoggedIn()) die('You are not allowed to view this page!');
  }
  
  public static function getLanguage() {
    return Init::getLanguage();
  }
  
  public static function getPage() {
    return Init::getPage();
  }
  
  public static function loadPlugins() {
    require_once(ES_COREPATH.'inc/plugins.php');
    Plugins::loadPlugins();
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
      echo '<link href="'.htmlspecialchars($style['src']).'" rel="stylesheet"'.($style['media'] ? ' media="'.htmlspecialchars($style['media']).'"' : '').'>'."\n";
    }
  }
  
  public static function putScripts() {
    foreach (self::$scripts as $script) {
      echo '<script src="'.htmlspecialchars($script['src']).'"></script>'."\n";
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
function get_s($name, $args=null) {
  $args = func_get_args();
  array_shift($args);
  return Common::getString($name, $args);
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
  echo Common::getString($name, $args);
}

function put_t($name, $args) {
  $args = func_get_args();
  array_shift($args);
  echo htmlspecialchars(Common::getString($name, $args));
}

function put_time($time, $format=null, $default=null) {
  $time = is_numeric($time) ? (int) $time : strtotime($time);
  if ($time) {
    if ($format) {
      $fmt = get_s('DATE_FORMAT_'.$format);
      if (!$fmt) $fmt = $format;
    } else {
      $fmt = get_s('DATE_FORMAT');
      if (!$fmt) $fmt = '%Y-%m-%d %H:%M';
    }
    if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
      // workaround for Windows, as strftime returns ISO-8859-1 encoded string
      echo htmlspecialchars(utf8_encode(strftime($fmt, $time))); 
    } else {
      echo htmlspecialchars(strftime($fmt, $time)); 
    }
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
    # TODO
  }
}