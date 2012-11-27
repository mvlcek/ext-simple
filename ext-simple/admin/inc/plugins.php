<?php

class Plugins {

  private static $plugins = array();
  private static $listeners = array();


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
  }
  
  public static function addListener($hook, $function, $args=null) {
    if (!array_key_exists($hook, self::$listeners)) self::$listeners[$hook] = array();
    self::$listeners[$hook][] = array(
      'function' => $function,
      'args' => $args ? $args : array()
    );
  }
  
  public static function execAction($hook, $args=null) {
    if (!isset(self::$listeners[$hook])) return null;
    if ($args === null) $args = array(); else if (!is_array($args)) $args = array($args);
    foreach (self::$listeners[$hook] as $listener) {
      call_user_func_array($listener['function'], array_merge($args, $listener['args']));
    }
  }
  
  public static function execFilter($hook, $args=null) {
    if (!isset(self::$listeners[$hook]) || $args === null) return null;
    if (!is_array($args)) $args = array($args);
    foreach (self::$listeners[$hook] as $listener) {
      $args[0] = call_user_func_array($listener['function'], array_merge($args, $listener['args']));
    }
    return $args[0];
  }

  public static function execUntil($hook, $args=null, $untilValue=null) {
    if (!isset(self::$listeners[$hook])) return null;
    if ($args === null) $args = array(); else if (!is_array($args)) $args = array($args);
    foreach (self::$listeners[$hook] as $listener) {
      $result = call_user_func_array($listener['function'], array_merge($args, $listener['args']));
      if ($result == $untilValue) return $result;
    }
    return $result;
  }
  
  public static function execWhile($hook, $args=null, $whileValue=null) {
    if (!isset(self::$listeners[$hook])) return null;
    if ($args === null) $args = array(); else if (!is_array($args)) $args = array($args);
    foreach (self::$listeners[$hook] as $listener) {
      $result = call_user_func_array($listener['function'], array_merge($args, $listener['args']));
      if ($result != $whileValue) return $result;
    }
    return $result;
  }
    
}

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
