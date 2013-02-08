<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

# requires ES_SETTINGSPATH, ES_PLUGINSPATH

class Plugins {

  private static $currentPlugin = null;
  private static $allowListeners = true;
  private static $plugins = array();
  private static $listeners = array();


  public static function loadPlugins() {
    $enabledPluginIds = Plugins::getEnabledPluginIds();
    foreach ($enabledPluginIds as $name) {
      self::loadPlugin($name);
    }
  }
  
  public static function loadPlugin($name) {
    if (file_exists(ES_PLUGINSPATH.$name.'.php')) {
      require_once(ES_PLUGINSPATH.$name.'.php');
    }
  }

  public static function getEnabledPluginIds() {
    if (file_exists(ES_SETTINGSPATH.'plugins.txt')) {
      return file(ES_SETTINGSPATH.'plugins.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }
    return array();
  }
  
  private static function setEnabledPluginIds($pluginIds=array()) {
    return file_put_contents(ES_SETTINGSPATH.'plugins.txt', implode(PHP_EOL, $pluginIds)) !== false;
  }

  public static function enablePlugin($id) {
    $pluginIds = self::getEnabledPluginIds();
    if (!in_array($id, $pluginIds)) $pluginIds[] = $id;
    self::setEnabledPluginIds($pluginIds);
  }
  
  public static function disablePlugin($id) {
    $pluginIds = self::getEnabledPluginIds();
    $index = array_search($id, $pluginIds);
    if ($index !== false) unset($pluginIds[$index]);
    self::setEnabledPluginIds($pluginIds);
  }

  public static function registerPlugin($id, $name, $version=null, 
      $author=null, $website=null, $description=null, $language=null, $requires=null) {
    self::$plugins[$id] = array(
      'name' => $name,
      'version' => $version,
      'author' => $author,
      'website' => $website,
      'description' => $description,
      'language' => $language,
      'requires' => (is_array($requires) ? $requires : $requires ? array($requires) : null)
    );
    self::$currentPlugin = $id;
  }
  
  public static function getPlugin($name) {
    return self::$plugins[$name];
  }
  
  public static function setAllowListeners($allow) {
    self::$allowListeners = $allow;
  }
  
  public static function addListener($hook, $function, $args=null) {
    if (!self::$allowListeners) return;
    if (!array_key_exists($hook, self::$listeners)) self::$listeners[$hook] = array();
    self::$listeners[$hook][] = array(
      'plugin' => self::$currentPlugin,
      'function' => $function,
      'args' => $args ? $args : array()
    );
  }
  
  public static function callFunction($name, $args) {
    $pos = strpos($name, '::');
    $fct = $pos > 0 ? array(substr($name,0,$pos), substr($name, $pos+2)) : $name;
    return call_user_func_array($fct, $args); 
  }
  
  private static function callListener($listener, $args) {
    #if (is_debug()) Log::debug('Calling listener %s of plugin %s.', $listener['function'], $listener['plugin']);
    return self::callFunction($listener['function'], array_merge($args, $listener['args']));
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
    return $result;
  }
  
  public static function execWhile($hook, $args=null, $whileValue=null) {
    if (!isset(self::$listeners[$hook])) return null;
    if ($args === null) $args = array(); else if (!is_array($args)) $args = array($args);
    $result = null;
    foreach (self::$listeners[$hook] as $listener) {
      $result = self::callListener($listener, $args);
      if ($result !== $whileValue) return $result;
    }
    return $result;
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
    
}


function registerPlugin($id, $name, $version=null, $author=null, $website=null, $description=null, $language=null, $requires=null) {
  Plugins::registerPlugin($id, $name, $version, $author, $website, $description, $language, $requires);
}

function addListener($hook, $function, $args=null) {
  Plugins::addListener($hook, $function, $args);
}

function execAction($hook, $args=null) {
  Plugins::execAction($hook, $args);
}

function execFilter($hook, $args=null) {
  return Plugins::execFilter($hook, $args);
}

function execUntil($hook, $args=null, $untilValue=null) {
  return Plugins::execUntil($hook, $args, $untilValue);
}

function execWhile($hook, $args=null, $whileValue=null) {
  return Plugins::execWhile($hook, $args, $whileValue);
}

function execForInfo($hook, $args=null) {
  return Plugins::execForInfo($hook, $args);
}

function isInPlugin($name) {
  return basename($_SERVER['PHP_SELF']) == 'load.php' && @$_GET['id'] == $name;
}
