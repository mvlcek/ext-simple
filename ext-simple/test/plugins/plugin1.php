<?php

registerPlugin('plugin1', 'Plugin 1', '0.1', 'N.N.', '', 'Something', 'en', null);

addListener('do-something', 'plugin1_doit', array(1=>'one',2=>'two',3=>'three'));
addListener('return-something', 'plugin1_doit', array(1=>'one',2=>'two'));

function plugin1_doit($args) {
  $args = func_get_args();
  echo join(', ', $args);
  return $args;
}