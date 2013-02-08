<?php
require_once('common.php');

class ESTests extends TestSuite {
  function __construct() {
    parent::__construct();
    #$this->addFile('es_file_tests.php');
    #$this->addFile('es_link_tests.php');
    $this->addFile('es_plugin_tests.php');
  }
}

