<?php
require_once("../../core/inc/common.php");

if (basename($_SERVER['PHP_SELF']) != 'index.php') {
  execWhile('authenticate', null, null);
  if (execUntil('authorize', null, false) === false) {
    if (!execWhile('show-401', null, false)) {
      header("HTTP/1.1 401 Unauthorized");
      echo "You are not authorized!";
    }
    die;
  }
}
