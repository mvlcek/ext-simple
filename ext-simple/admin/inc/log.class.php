<?php

class Log {

  static private function entry($level, $message, $params) {
    // TODO
  }
  
  static public function debug($message, $params) {
    if (!is_array($params)) { 
      $params = func_get_args();
      array_shift($params);
    }
    entry('D', $message, $params);
  }
  
  static public function info($message, $params) {
    if (!is_array($params)) { 
      $params = func_get_args();
      array_shift($params);
    }
    entry('I', $message, $params);
  }
  
  static public function warning($message, $params) {
    if (!is_array($params)) { 
      $params = func_get_args();
      array_shift($params);
    }
    entry('W', $message, $params);
  }

  static public function error($message, $params) {
    if (!is_array($params)) { 
      $params = func_get_args();
      array_shift($params);
    }
    entry('E', $message, $params);
  }

}
?>