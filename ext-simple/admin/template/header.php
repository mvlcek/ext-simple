<?php
if (!defined('IN_ES')) { die('You cannot load this page directly.'); }

require_once(ES_ADMINPATH.'inc/template.php');

$tab = $tab;
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
  <?php exec_action('after-admin-header'); ?>   
</head>
<body class="<?php echo $what; ?>" > 
  <div class="header" id="header" >
    <h1 class="title"><a href="<?php echo Common->getSiteURL(); ?>" target="_blank">Ext-Simple</a></h1>
    <div class="welcome" id="welcome">
      <ul class="welcome">
        <li></li>
      </ul>
    </div>
  </div>
  <div class="main-menu" id="main-menu">
    <ul class="main-menu">
      <?php put_es_menu_tab("show-pages.php", get_s('TAB_PAGES'), $tab=='pages'); ?>
      <?php put_es_menu_tab("show-files.php", get_s('TAB_FILES'), $tab=='files'); ?>
      <?php put_es_menu_tab("show-theme.php", get_s('TAB_THEME'), $tab=='themes'); ?>
      <?php put_es_menu_tab("show-backups.php", get_s('TAB_BACKUPS'), $tab=='backups'); ?>
      <?php put_es_menu_tab("show-plugins.php", get_s('TAB_PLUGINS'), $tab=='plugins'); ?>
      <?php exec_action('after-tabs'); ?>
      <?php put_es_menu_tab("show-settings.php", get_s('TAB_SETTINGS'), $tab=='settings'); ?>
    </ul>
  </div>
  <div class="side-menu" id="side-menu">
    <ul class="side-menu-<?php echo $nav; ?>">
	  <?php if ($tab == 'pages') { ?>
	  	<?php put_es_menu_entry('show-pages.php', get_s('SIDE_SHOW_PAGES')); ?>
	  	<?php put_es_menu_entry('create-page.php', get_s('SIDE_CREATE_PAGE')); ?>
	  	<?php put_es_menu_entry('edit-page.php', get_s('SIDE_EDIT_PAGE'), false); ?>
	  	<?php put_es_menu_entry('edit-menu.php', get_s('SIDE_EDIT_MENU')); ?>
	  	<?php exec_action('after-sidebar-pages'); ?>
	  <?php } else if ($tab == 'files') { ?>
	  	<?php put_es_menu_entry('upload-files.php', get_s('SIDE_UPLOAD_FILE')); ?>
	  	<?php exec_action('after-sidebar-files'); ?>
	  <?php } else if ($tab == 'theme') { ?>
	  	<?php put_es_menu_entry('switch-theme.php', get_s('SIDE_SWITCH_THEME')); ?>
	  	<?php put_es_menu_entry('edit-theme.php', get_s('SIDE_EDIT_THEME')); ?>
	  	<?php put_es_menu_entry('show-components.php', get_s('SIDE_SHOW_COMPONENTS')); ?>
	  	<?php put_es_menu_entry('create-component.php', get_s('SIDE_CREATE_COMPONENT')); ?>
	  	<?php put_es_menu_entry('edit-component.php', get_s('SIDE_EDIT_COMPONENT'), false); ?>
	  	<?php exec_action('after-sidebar-themes'); ?>
	  <?php } else if ($tab == 'backups') { ?>
	  	<?php exec_action('after-sidebar-backups'); ?>
	  <?php } else if ($tab == 'plugins') { ?>
	  	<?php exec_action('after-sidebar-plugins'); ?>
	  <?php } else if ($tab == 'settings') { ?>
	  	<?php exec_action('after-sidebar-settings'); ?>
	  <?php } ?>
	</ul>
  </div>