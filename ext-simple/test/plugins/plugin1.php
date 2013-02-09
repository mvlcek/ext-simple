<?php

registerPlugin('plugin1', 'Plugin 1', '0.1', 'N.N.', '', 'Something', 'en', null);

addListener('do-something', 'plugin1_echoit', array('zero','one','two'));
addListener('return-something', 'plugin1_returnit', array('zero','one'));

function plugin1_echoit($args) {
  $args = func_get_args();
  echo join(', ', $args);
}

function plugin1_returnit($args) {
  $args = func_get_args();
  return $args;
}