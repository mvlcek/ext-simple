<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

# requires ES_SETTINGSPATH, ES_PLUGINSPATH


/**
 * Your plugins must be derived from this class
 */
abstract class AbstractPlugin {
  
  private $id = null;
  private $name = null;
  private $version = null;
  private $author = null;
  private $website = null;
  private $description = null;
  
  /**
   * @param string $name the human-readable name of the plugin
   * @param string $version the version of the plugin, e.g. 0.3, 1.0.3
   * @param string $author the author of the website (preferably real name)
   * @param string $website a link to the website, where the plugin is described
   * @param string $description a human-readable description
   * @param string $language default language for loading resources/strings
   */
  protected function __construct($name, $version, $author, $website, $description) {
    $this->name = $name;
    $this->version = $version;
    $this->author = $author;
    $this->website = $website;
    $this->description = $description;
  }
  
  /**
   * Add your listeners in this method
   */
  public function initialize($id) {
    $this->id = $id;
    // for adding listeners, e.g.
    // addListener('filter-content', array($this,'filter'));
  }
  
  public function getId() { return $this->id; }
  public function getName() { return $this->name; }
  public function getVersion() { return $this->version; }
  public function getAuthor() { return $this->author; }
  public function getWebsite() { return $this->website; }
  public function getDescription() { return $this->description; }
  
  public function getDefaultLanguage() { return 'en_US'; }
  
  public function getRequiredPlugins() { return null; }
  
}


/**
 * The Plugins class provides all functions for plugins.
 * The most important of these functions are also available as separate functions.
 */
class Plugins {

  private static $plugins = array();
  private static $listeners = array();

  private static $currentPluginId = null;
  private static $allowListeners = true;

  /**
   * Loads all enabled plugins
   * @since 1.0
   */
  public static function loadPlugins() {
    $enabledPluginIds = Plugins::getEnabledPluginIds();
    foreach ($enabledPluginIds as $id) {
      self::loadPlugin($id);
    }
    // from now on adding listeners is not possible any more.
    self::$allowListeners = false;
  }
  
  /**
   * Loads a single plugin by id (regardless of whether it is enabled or not)
   * @since 1.0
   * @param string $id the ID of the plugin
   */
  public static function loadPlugin($id) {
    self::$currentPluginId = $id;
    if (file_exists(ES_PLUGINSPATH.$id.'.php')) {
      require_once(ES_PLUGINSPATH.$id.'.php');
    }
    self::$currentPluginId = null;
  }

  /**
   * Returns the IDs of the enabled plugins in the order they are loaded
   * @since 1.0
   * @return array plugin IDs
   */
  public static function getEnabledPluginIds() {
    if (file_exists(ES_SETTINGSPATH.'plugins.txt')) {
      return file(ES_SETTINGSPATH.'plugins.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    return array();
  }
  
  private static function setEnabledPluginIds($pluginIds=array()) {
    return file_put_contents(ES_SETTINGSPATH.'plugins.txt', implode(PHP_EOL, $pluginIds)) !== false;
  }

  /**
   * Enables a plugin. It will be loaded last.
   * @since 1.0
   * @param string $id the id of the plugin to enable
   */
  public static function enablePlugin($id) {
    $pluginIds = self::getEnabledPluginIds();
    if (!in_array($id, $pluginIds)) $pluginIds[] = $id;
    self::setEnabledPluginIds($pluginIds);
  }

  /**
   * Disables a plugin. It will not be loaded any more.
   * @since 1.0
   * @param string $id the id of the plugin to be disabled
   */  
  public static function disablePlugin($id) {
    $pluginIds = self::getEnabledPluginIds();
    $index = array_search($id, $pluginIds);
    if ($index !== false) unset($pluginIds[$index]);
    self::setEnabledPluginIds($pluginIds);
  }

  public static function getPlugin($id) {
    return self::$plugins[$id];
  }
  
  public static function registerPlugin($plugin) {
    self::$plugins[self::$currentPluginId] = $plugin;
    $plugin->initialize(self::$currentPluginId);
  }
  
  /**
   * Enable/disable adding of listeners.
   * @since 1.0
   * @param bool $allow false, if requests for adding listeners should be ignored
   */
  public static function setAllowListeners($allow) {
    self::$allowListeners = $allow;
  }
  
  public static function addListener($hook, $methodname, $args=null) {
    if (!self::$allowListeners) return;
    if (!array_key_exists($hook, self::$listeners)) self::$listeners[$hook] = array();
    self::$listeners[$hook][] = array(
      'pluginId' => self::$currentPluginId,
      'method' => $methodname,
      'args' => $args ? $args : array()
    );
  }
  
  public static function hasListener($hook) {
    return array_key_exists($hook, self::$listeners);
  }
  
  private static function callListener($listener, $args) {
    #if (is_debug()) Log::debug('Calling listener %s of plugin %s.', $listener['function'], $listener['plugin']);
    $functionname = $listener['method'];
    if (($pos = strpos($functionname,'::')) !== false) {
      if ($pos == 0) {
        $function = substr($functionname,2);
      } else {
        $function = array(substr($functionname,0,$pos), substr($functionname,$pos+2));
      } 
    } else {
      $function = array(self::$plugins[$listener['pluginId']], $functionname);
    }
    return self::call_user_func_array($function, array_merge($args, $listener['args']));
  }
  
  public static function execAction($hook, $args=null) {
    if (!isset(self::$listeners[$hook])) return null;
    if ($args === null) $args = array(); else if (!is_array($args)) $args = array($args);
    foreach (self::$listeners[$hook] as $listener) {
      self::callListener($listener, $args);
    }
  }
  
  public static function execFilter($hook, $args=null) {
    if (!isset(self::$listeners[$hook]) || $args === null) return null;
    if (!is_array($args)) $args = array($args);
    foreach (self::$listeners[$hook] as $listener) {
      $args[0] = self::callListener($listener, $args);
    }
    return $args[0];
  }

  public static function execUntil($hook, $args=null, $untilValue=null) {
    if (!isset(self::$listeners[$hook])) return null;
    if ($args === null) $args = array(); else if (!is_array($args)) $args = array($args);
    $result = null;
    foreach (self::$listeners[$hook] as $listener) {
      $result = self::callListener($listener, $args);
      if ($result === $untilValue) return $result;
    }
    return null;
  }
  
  public static function execWhile($hook, $args=null, $whileValue=null) {
    if (!isset(self::$listeners[$hook])) return null;
    if ($args === null) $args = array(); else if (!is_array($args)) $args = array($args);
    $result = null;
    foreach (self::$listeners[$hook] as $listener) {
      $result = self::callListener($listener, $args);
      if ($result !== $whileValue) return $result;
    }
    return $whileValue;
  }
  
  public static function execForInfo($hook, $args=null) {
    if (!isset(self::$listeners[$hook])) return array();
    if ($args === null) $args = array(); else if (!is_array($args)) $args = array($args);
    $info = array();
    foreach (self::$listeners[$hook] as $listener) {
      $result = self::callListener($listener, $args);
      if ($result) {
        if (!is_array($result)) $result = array($result);
        $info = array_merge($info, $result);
      }
    }
    return $info;
  }
  
  public static function filterContentPlaceholders($content) {
    return preg_replace_callback("/(<p(?:\s[^>]*)>\s*)?\(%\s*([A-Za-z][A-Za-z0-9_-]*)(\s+(?:[^%]|%[^\)])+)?\s*%\)(\s*<\/p>)?/", 
                                 array('Plugins','replacePlaceholder'), $content);
  }
  
  public static function replacePlaceholder($match) {
    $prefix = $match[1];
    $name = $match[2];
    $paramstr = @$match[3] ? html_entity_decode(trim($match[3]), ENT_QUOTES, 'UTF-8') : '';
    $suffix = $match[4];
    $params = array();
    while (preg_match('/^([A-Za-z][A-Za-z0-9_-]*)[:=]([^"\'\s]*|"[^"]*"|\'[^\']*\')(?:\s|$)/', $paramstr, $pmatch)) {
      $key = $pmatch[1];
      $value = trim($pmatch[2]);
      if (substr($value,0,1) == '"' || substr($value,0,1) == "'") $value = substr($value,1,strlen($value)-2);
      $params[$key] = $value;
      $paramstr = substr($paramstr, strlen($pmatch[0]));
    }
    $replacement = self::execWhile('replace-placeholder-'.$name, array($params, $prefix, $suffix), null);
    return $replacement !== null ? (string) $replacement : $match[0];
  }
  
}

/**
 * Register a plugin. Must be called in the main plugin file.
 * 
 * @since 1.0
 * @param Plugin $plugin   the plugin, an instance of a class extending AbstractPlugin
 */
function registerPlugin($id, $plugin) {
  Plugins::registerPlugin($id, $plugin);
}

/**
 * Add a listener for an event. Should only be called in the initialize() method of the plugin.
 * 
 * @since 1.0
 * @param string $hook        the event
 * @param string $methodname  the plugin's non-static method to call on this event. 
 * @param array  $args        arguments to pass to the function after those supplied by the event
 */
function addListener($hook, $methodname, $args=null) {
  Plugins::addListener($hook, $methodname, $args);
}

function hasListener($hook) {
  return Plugins::hasListener($hook);
}

/**
 * Calls all registered listeners for an event, optionally passing parameters
 * 
 * @since 1.0
 * @param string $hook the event
 * @param array  $args the parameters to pass to the listeners
 */
function execAction($hook, $args=null) {
  Plugins::execAction($hook, $args);
}

/**
 * Calls all registered listeners for an event to filter a value
 * 
 * @since 1.0
 * @param string $hook the event
 * @param array  $args the parameters to pass to the listeners. There must be at least
 *                      one parameter, which is the value being filtered
 * @return mixed the filtered value
 */
function execFilter($hook, $args=null) {
  return Plugins::execFilter($hook, $args);
}

/**
 * Calls all registered listeners for an event, optionally passing parameters,
 * until the return value of one listener is the given one
 * 
 * @since 1.0
 * @param string $hook       the event
 * @param array  $args       the parameters to pass to the listeners
 * @param any    $untilValue the value, at which the calling of listeners is aborted
 * @return mixed the $untilValue or null, if none of the listeners returns this value
 */
function execUntil($hook, $args=null, $untilValue=null) {
  return Plugins::execUntil($hook, $args, $untilValue);
}

/**
 * Calls all registered listeners for an event, optionally passing parameters,
 * while the return value of one listener is the given one
 * 
 * @since 1.0
 * @param string $hook       the event
 * @param array  $args       the parameters to pass to the listeners
 * @param any    $whileValue if the return value of a listener is different than this
 *                            value, the calling of listeners is aborted
 * @return mixed the return value of the first listener returning another value than the
 *                $whileValue, or the $whileValue
 */
function execWhile($hook, $args=null, $whileValue=null) {
  return Plugins::execWhile($hook, $args, $whileValue);
}

function execForInfo($hook, $args=null) {
  return Plugins::execForInfo($hook, $args);
}

function isInPlugin($name) {
  return basename($_SERVER['PHP_SELF']) == 'load.php' && @$_GET['id'] == $name;
}
