<?php
require_once('simpletest/autorun.php');

class AllTests extends TestSuite {
  function __construct() {
    parent::__construct();
    $this->addFile('es_tests.php');
  }
}