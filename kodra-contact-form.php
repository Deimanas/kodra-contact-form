
<?php
/**
  * Plugin Name: Kodra Contact Form
 * Version: 1.6.4.5.2
 * GitHub Plugin URI: https://github.com/Deimanas/kodra-contact-form
 * Primary Branch: main
 */
if(!defined('ABSPATH')) exit;
define('KCF_VERSION','1.6.4.5.2'); define('KCF_PATH', plugin_dir_path(__FILE__)); define('KCF_URL', plugin_dir_url(__FILE__));
add_filter('upgrader_source_selection',function($source,$remote_source,$upgrader,$hook_extra){
  if(empty($hook_extra['plugin'])||$hook_extra['plugin']!==plugin_basename(__FILE__)) return $source;
  $expected='kodra-contact-form'; $basename=basename($source);
  if($basename===$expected) return $source;
  $normalized=trailingslashit(dirname($source)).$expected;
  if(@rename($source,$normalized)) return $normalized;
  return $source;
},10,4);
global $wpdb; define('KCF_TABLE', $wpdb->prefix.'kodra_messages'); define('KCF_REPLIES_TABLE', $wpdb->prefix.'kodra_message_replies'); define('KCF_OPT','kcf_settings');
require_once ABSPATH.'wp-admin/includes/upgrade.php';
function kcf_install_tables(){ global $wpdb; $cc=$wpdb->get_charset_collate();
  dbDelta("CREATE TABLE IF NOT EXISTS ".KCF_TABLE." (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,created_at DATETIME NOT NULL,name VARCHAR(190) NOT NULL,company VARCHAR(190) NULL,phone VARCHAR(190) NOT NULL,email VARCHAR(190) NOT NULL,message LONGTEXT NOT NULL,ip VARCHAR(100) NULL,user_agent TEXT NULL,seen TINYINT(1) NOT NULL DEFAULT 0,PRIMARY KEY(id), KEY created_at(created_at), KEY email(email), KEY seen(seen)) $cc;");
  dbDelta("CREATE TABLE IF NOT EXISTS ".KCF_REPLIES_TABLE." (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,message_id BIGINT UNSIGNED NOT NULL,created_at DATETIME NOT NULL,wp_user_id BIGINT UNSIGNED NULL,direction TINYINT(1) NOT NULL DEFAULT 0,to_email VARCHAR(190) NOT NULL,subject VARCHAR(255) NOT NULL,body LONGTEXT NOT NULL,sent TINYINT(1) NOT NULL DEFAULT 0,seen TINYINT(1) NOT NULL DEFAULT 0,PRIMARY KEY(id), KEY message_id(message_id), KEY direction(direction), KEY seen(seen)) $cc;");
} register_activation_hook(__FILE__,'kcf_install_tables');

add_action('wp_enqueue_scripts',function(){ wp_register_style('kcf-style',KCF_URL.'assets/css/style.css',[],KCF_VERSION); wp_register_script('kcf-script',KCF_URL.'assets/js/form.js',['jquery'],KCF_VERSION,true); $o=get_option(KCF_OPT,[]); wp_localize_script('kcf-script','KCF',['ajaxurl'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('kcf_nonce'),'recaptcha_site_key'=>!empty($o['recaptcha_enabled'])?($o['recaptcha_site_key']??''):'' ]); });

add_action('init',function(){ if(!session_id()) @session_start(); if(empty($_SESSION['kcf_last_check'])||(time()-intval($_SESSION['kcf_last_check']))>300){ $_SESSION['kcf_last_check']=time(); do_action('kcf_check_mail_event'); }},2);
add_filter('cron_schedules',function($s){$s['kcf_every_5_minutes']=['interval'=>300,'display'=>'Kas 5 minutes'];return $s;});
if(!wp_next_scheduled('kcf_check_mail_event')) wp_schedule_event(time()+60,'kcf_every_5_minutes','kcf_check_mail_event');
register_deactivation_hook(__FILE__,function(){ wp_clear_scheduled_hook('kcf_check_mail_event'); });

function kcf_safe_load($f){ if(file_exists($f)){ require_once $f; return true; } add_action('admin_notices',function()use($f){ echo '<div class="notice notice-error"><p>Trūksta failo: <code>'.esc_html($f).'</code></p></div>';}); return false; }
kcf_safe_load(KCF_PATH.'includes/utils.php'); kcf_safe_load(KCF_PATH.'includes/shortcode.php'); kcf_safe_load(KCF_PATH.'includes/handler.php');
kcf_safe_load(KCF_PATH.'includes/admin.php'); kcf_safe_load(KCF_PATH.'includes/settings.php'); kcf_safe_load(KCF_PATH.'includes/imap.php'); add_filter('wp_mail_from',function($e){$o=get_option(KCF_OPT,[]);return(!empty($o['from_email'])&&is_email($o['from_email']))?$o['from_email']:$e;}); add_filter('wp_mail_from_name',function($n){$o=get_option(KCF_OPT,[]);return(!empty($o['from_name']))?$o['from_name']:$n;}); ?>
