<?php

class Log {

  static private function entry($level, $message, $params) {
    
  }
  
  static public function debug($message, $params) {
    entry('D', $message, $params);
  }
  
  static public function info($message, $params) {
    entry('I', $message, $params);
  }
  
  static public function warning($message, $params) {
    
  }
}
?>