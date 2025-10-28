
<?php
/**
 * Plugin Name: Kodra Contact Form
 * Version: 1.6.3.7
 */
if(!defined('ABSPATH')) exit;
define('KCF_VERSION','1.6.3.7');
define('KCF_PATH', plugin_dir_path(__FILE__));
define('KCF_URL', plugin_dir_url(__FILE__));
require_once KCF_PATH.'includes/utils.php';
require_once KCF_PATH.'includes/shortcode.php';
require_once KCF_PATH.'includes/handler.php';
require_once KCF_PATH.'includes/admin.php';
require_once KCF_PATH.'includes/settings.php';
require_once KCF_PATH.'includes/imap.php';
?>