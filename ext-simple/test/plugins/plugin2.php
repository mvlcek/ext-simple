<?php
registerPlugin('plugin2', 'Plugin 2', '0.2', 'N.N.', '', 'Something else', 'en', null);

addListener('do-something', 'Plugin2::echoit', array('uno','due','tre'));
addListener('return-something', 'Plugin2::returnit', array('one','uno','due'));
addListener('filter-something', 'Plugin2::filterit', array('one','uno'));

class Plugin2 {
  
  public static function echoit($args) {
    $args = func_get_args();
    echo join(', ', $args);
  }
  
  public static function returnit($args) {
    $args = func_get_args();
    return $args;
  }
  
  public static function filterit($content, $from, $to) {
    return str_replace($from, $to, $content);
  }
  
}
