<?php

class Plugins {

  private static $currentPlugin = null;
  private static $allowListeners = true;
  private static $plugins = array();
  private static $listeners = array();

  private static function getEnabledPlugins() {
    return file(ES_SETTINGSPATH.'plugins.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  }
  
  private static function setEnabledPlugins($plugins=array()) {
    return file_put_contents(ES_SETTINGSPATH.'plugins.txt', implode(PHP_EOL, $plugins)) !== false;
  }

  public static function enablePlugin($name) {
    $plugins = self::getEnabledPlugins();
    $index = array_search($name, $plugins);
    if ($index !== false) unset($plugins[$index]);
    self::setEnabledPlugins($plugins);
  }
  
  public static function disablePlugin($name) {
    $plugins = self::getEnabledPlugins();
    if (!in_array($name, $plugins)) $plugins[] = $name;
    self::setEnabledPlugins($plugins);
  }

  public static function registerPlugin($id, $name, $version=null, $author=null, $website=null, $description=null, $tab=null, $callback=null) {
    self::$plugins[$id] = array(
      'name' => $name,
      'version' => $version,
      'author' => $author,
      'website' => $website,
      'description' => $description,
      'tab' => $tab,
      'callback' => $callback
    );
    self::$currentPlugin = $id;
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
  
  private static function callListener($listener, $args) {
    if (is_debug()) Log::debug('Calling listener %s of plugin %s.', $listener['function'], $listener['plugin']);
    return call_user_func_array($listener['function'], array_merge($args, $listener['args']));
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
      if ($result == $untilValue) return $result;
    }
    return $result;
  }
  
  public static function execWhile($hook, $args=null, $whileValue=null) {
    if (!isset(self::$listeners[$hook])) return null;
    if ($args === null) $args = array(); else if (!is_array($args)) $args = array($args);
    $result = null;
    foreach (self::$listeners[$hook] as $listener) {
      $result = self::callListener($listener, $args);
      if ($result != $whileValue) return $result;
    }
    return $result;
  }
  
  public static function execForInfo($hook, $args=null) {
    if (!isset(self::$listeners[$hook])) return null;
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


$enabledPlugins = Plugins::getEnabledPlugins();
foreach ($enabledPlugins as $name) {
  if (file_exists(ES_PLUGINSPATH.$name.'.php')) {
    require_once(ES_PLUGINSPATH.$name.'.php');
  }
}
unset($enabledPlugins);

function registerPlugin($id, $name, $version=null, $author=null, $website=null, $description=null, $tab=null, $callback=null) {
  Plugins::registerPlugin($id, $name, $version, $author, $website, $description, $tab, $callback);
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