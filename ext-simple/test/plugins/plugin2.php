<?php
registerPlugin('plugin2', 'Plugin 2', '0.2', 'N.N.', '', 'Something else', 'en', null);

addListener('do-something', 'Plugin2::doit', array(1=>'uno',2=>'due',3=>'tre'));
addListener('return-something', 'Plugin2::doit', array(1=>'uno',2=>'due'));

class Plugin2 {
  
  public static function doit($args) {
    $args = func_get_args();
    echo join(', ', $args);
    return $args;
  }
  
}
