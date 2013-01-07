<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

class TemplateHelper {
  
  function getAccessKey($text) {
    if (preg_match('/<em>(\w)<\/em>/', $text, $match)) {
      $c = $match[1];
      return function_exists('mb_strtolower') ? mb_strtolower($c) : strtolower($c); 
    }
    return null;
  }
  
  function getLanguage() {
    return Settings::get('language', 'en_US');
  }
  
}

/**
 * Create Navigation Tab
 *
 * This adds a top level tab to the control panel
 *
 * @param string $tabname  the name of the tab
 * @param string $id       ID of current page
 * @param string $txt      text of the tab
 * @param string $action   further parameter for the link
 */
function put_menu_tab($tabname, $id, $txt, $action=null) {
  $current = false;
  if (basename($_SERVER['PHP_SELF']) == 'load.php') {
    $plugin = getPlugin(@$_GET['id']);
    if ($plugin['tab'] == $tabname) $current = true;
  }
  $linkId = 'nav_'.$id.($action ? '_'.$action : '');
  $accessKey = TemplateHelper::getAccessKey($txt);
  echo '<li id="'.$linkId.'"><a href="load.php?id='.$id.($action ? '&amp;'.$action : '').'"'.($current ? ' class="current"' : '').($accessKey ? ' accesskey="'.$accessKey.'"' : '').'>'.$txt.'</a></li>';
}

function put_es_menu_tab($script, $txt, $current=false) {
  $linkId = 'nav_es_'.basename($script,'.php');
  $accessKey = TemplateHelper::getAccessKey($txt);
  echo '<li id="'.$linkId.'"><a href="'.$script.'"'.($current ? ' class="current"' : '').($accessKey ? ' accesskey="'.$accessKey.'"' : '').'>'.$txt.'</a></li>';
}


/**
 * Create a (side) menu entry
 *
 * This adds a side level link to a control panel's section
 *
 * @param string $id      ID of the plugin
 * @param string $txt     text of the menu entry
 * @param string $action  further parameter for the link
 * @param string $always  set to false, if the menu entry should only be shown when active
 */
function put_menu_entry($id, $txt, $action=null, $always=true){
  $current = false;
  if (isset($_GET['id']) && $_GET['id'] == $id && (!$action || isset($_GET[$action]))) {
    $current = true;
  }
  $linkId = 'sb_'.$id.($action ? '_'.$action : '');
  $accessKey = TemplateHelper::getAccessKey($txt);
  if ($always || $current) {
    echo '<li id="'.$linkId.'"><a href="load.php?id='.$id.($action ? '&amp;'.$action : '').'"'.($current ? ' class="current"' : '').($accessKey ? ' accesskey="'.$accessKey.'"' : '').'>'.$txt.'</a></li>';
  }
}


function put_es_menu_entry($script, $txt, $always=true) {
  $current = false;
  if (basename($_SERVER['PHP_SELF']) == $script) {
    $current = true;
  }
  $linkId = 'sb_es_'.basename($script,'.php');
  $accessKey = TemplateHelper::getAccessKey($txt);
  if ($always || $current) {
    echo '<li id="'.$linkId.'"><a href="'.$script.'"'.($current ? ' class="current"' : '').($accessKey ? ' accesskey="'.$accessKey.'"' : '').'>'.$txt.'</a></li>';
  }
}


