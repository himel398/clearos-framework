<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|	https://codeigniter.com/user_guide/general/hooks.html
|
*/

// ClearFoundation - define our hooks
$hook['pre_controller'][] = array(
	'class'    => 'Login_Session',
	'function' => 'start',
	'filename' => 'webconfig.php',
	'filepath' => 'hooks'
);

$hook['pre_controller'][] = array(
	'class'    => '',
	'function' => 'webconfig_cache',
	'filename' => 'webconfig.php',
	'filepath' => 'hooks'
);

$hook['pre_controller'][] = array(
	'class'    => 'Login_Session',
	'function' => 'check_acl',
	'filename' => 'Login_Session.php',
	'filepath' => 'libraries'
);

$hook['post_controller_constructor'][] = array(
	'class'    => 'Page',
	'function' => 'load_theme',
	'filename' => 'Page.php',
	'filepath' => 'libraries'
);

/* End of file hooks.php */
/* Location: ./application/config/hooks.php */
