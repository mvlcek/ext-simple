<?php
if (!defined('IN_ES')) { die('You cannot load this page directly.'); }

require_once(ES_ADMINPATH.'inc/template.php');

$what = basename($_SERVER['PHP_SELF'], '.php');
if ($what != 'index') exec_action('before-admin-template');
?>
<!DOCTYPE html>
<html lang="<?php echo TemplateHelper::getLanguage(); ?>">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"  />
  <title><?php echo $title ?></title>
  <link rel="shortcut icon" href="favicon.png" type="image/x-icon" />
  <meta name="generator" content="ExtSimple - <?php echo ES_VERSION; ?>" />
  <link rel="author" href="humans.txt" />
  <meta name="robots" content="noindex, nofollow">
  <link rel="apple-touch-icon" href="apple-touch-icon.png"/>
  <link rel="stylesheet" type="text/css" href="template/css/default.css" media="screen" />
  <script type="text/javascript" src="template/js/jquery.js"></script>   
  <script type="text/javascript" src="template/js/jquery-ui.js"></script>
  <?php exec_action('end-admin-header'); ?>   
</head>
<body class="<?php echo $what; ?>" > 
  <div class="header" id="header" >
    <div class="wrapper clearfix">
 <?php exec_action('header-body'); ?>
