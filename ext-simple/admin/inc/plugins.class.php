<?php

class Plugins {

  private static $plugins = array();
  private static $actions = array();
  private static $filters = array();


  public static function registerPlugin($id, $name, $ver=null, $auth=null, $auth_url=null, $desc=null, $type=null, $loaddata=null) {
    self::$plugins[$id] = array(
      'name' => $name,
      'version' => $ver,
      'author' => $auth,
      'author_url' => $auth_url,
      'description' => $desc,
      'page_type' => $type,
      'load_data' => $loaddata
    );
  }
  
  public static function addAction($hook, $function, $args=null) {
    if (!array_key_exists($hook, self::$actions)) self::$actions[$hook] =array();
    self::$actions[$hook][] = array(
      'function' => $function,
      'args' => $args ? $args : array()
    );
  }
  
  public static function execAction($hook, $args=null, $returnOn=null) {
    if (!isset(self::$actions[$hook])) return null;
    if (!$args) $args = array();
    foreach (self::$actions[$hook] as $action) {
      $result = call_user_func_array($action['function'], array_merge($args, $action['args']));
      if ($returnOn !== null && $result == $returnOn) return $result;
    }
    return null;
  }
  
  public static function addFilter($hook, $function, $args=null) {
    if (!array_key_exists($hook, self::$filters)) self::$filters[$hook] =array();
    self::$filters[$hook][] = array(
      'function' => $function,
      'args' => $args ? $args : array()
    );
  }
  
  public static function execFilter($hook, $args=null, $returnOn=null) {
    if (!isset(self::$filters[$hook])) return null;
    if (!$args) $args = array(null);
    foreach (self::$filters[$hook] as $filter) {
      $args[0] = call_user_func_array($filter['function'], array_merge($args, $filter['args']));
      if ($returnOn !== null && $args[0] == $returnOn) return $args[0];
    }
    return $args[0];
  }
  
}

/**
 * Create Side Menu
 *
 * This adds a side level link to a control panel's section
 *
 * @since 2.0
 * @uses $plugins
 *
 * @param string $id ID of the link you are adding
 * @param string $txt Text to add to tabbed link
 */

function createSideMenu($id, $txt, $action=null, $always=true){
  $current = false;
  if (isset($_GET['id']) && $_GET['id'] == $id && (!$action || isset($_GET[$action]))) {
    $current = true;
  }
  if ($always || $current) {
    echo '<li id="sb_'.$id.'"><a href="load.php?id='.$id.($action ? '&amp;'.$action : '').'" '.($current ? 'class="current"' : '').' >'.$txt.'</a></li>';
  }
}

/**
 * Create Navigation Tab
 *
 * This adds a top level tab to the control panel
 *
 * @since 2.0
 * @uses $plugins
 *
 * @param string $id Id of current page
 * @param string $txt Text to add to tabbed link
 * @param string $klass class to add to a element
 */
function createNavTab($tabname, $id, $txt, $action=null) {
  global $plugin_info;
  $current = false;
  if (basename($_SERVER['PHP_SELF']) == 'load.php') {
    $plugin_id = @$_GET['id'];
    if ($plugin_info[$plugin_id]['page_type'] == $tabname) $current = true;
  }
  echo '<li id="nav_'.$id.'"><a href="load.php?id='.$id.($action ? '&amp;'.$action : '').'" '.($current ? 'class="current"' : '').' >'.$txt.'</a></li>';
}

?>