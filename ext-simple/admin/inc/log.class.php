<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

class Log {

  static private function entry($level, $message, $params) {
    $fh = fopen(ES_LOGSPATH.'extsimple.log', 'a');
    $line = $level.' '.date('Y-m-d H:i:s.u').' '.sprintf($message, $params);
    fwrite($fh, $line."\r\n");
    fclose($fh);
  }
  
  static public function debug($message, $params) {
    if (!is_debug()) return;
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