
<?php if(!defined('ABSPATH')) exit;
if(!function_exists('kcf_settings_get')){ function kcf_settings_get($k,$d=''){ $o=get_option(KCF_OPT,[]); return isset($o[$k])?$o[$k]:$d; } }
function kcf_log($msg){ $log=get_option('kcf_log',[]); $log[]='['.current_time('mysql').'] '.$msg; if(count($log)>1000) $log=array_slice($log,-1000); update_option('kcf_log',$log,false); update_option('kcf_last_check', current_time('mysql'), false); }
function kcf_trim_body_for_admin($body){
  $b=str_replace(['<br />','<br/>','<br>'],"\n",$body);
  $b=str_replace(["\r\n","\r"],"\n",$b);
  $patterns=['/\n\s*20\d{2}-\d{2}-\d{2}.*?rašė.*$/su','/\n\s*On .+ wrote:.*/su','/\n\s*[-]{2,}\s*\nKCF-ID:.*/su','/\n\s*[-]{2,}\s*\nKCF-INBOX-ID:.*/su','/\n<!--\s*KCF-ID:\s*\d+\s*-->/i','/\n<!--\s*KCF-INBOX-ID:\s*\d+\s*-->/i','/\n>.*$/su'];
  foreach($patterns as $p){ if(preg_match($p,$b,$m,PREG_OFFSET_CAPTURE)){ $b=substr($b,0,$m[0][1]); break; } }
  return trim($b);
}
function kcf_clean_textarea($text){ return trim(sanitize_textarea_field((string)$text)); }
function kcf_mail_domain(){
  static $domain='';
  if($domain!=='') return $domain;
  $home=home_url();
  $parsed=parse_url($home,PHP_URL_HOST);
  if(is_string($parsed)&&$parsed!==''){
    $domain=$parsed;
  } elseif(!empty($_SERVER['HTTP_HOST'])){
    $domain=preg_replace('/:\d+$/','',sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])));
  } else {
    $domain='localhost.localdomain';
  }
  return $domain;
}
function kcf_prepare_email_body($text,$tokens=[]){
  $normalized=str_replace(["\r\n","\r"],"\n",(string)$text);
  $html=nl2br(esc_html($normalized));
  if(!empty($tokens)&&is_array($tokens)){
    $plain_tokens=[];
    foreach($tokens as $label=>$value){
      if($value===null) continue;
      $value=is_array($value)?reset($value):$value;
      $value=trim((string)$value);
      if($value==='') continue;
      $label=trim((string)$label);
      if($label==='') continue;
      $pair=$label.': '.$value;
      $plain_tokens[]=$pair;
      $hidden='<div style="display:none!important;max-height:0;max-width:0;overflow:hidden;opacity:0;color:transparent">'.esc_html($pair).'</div>';
      $html.="\n<!-- ".$pair." -->\n".$hidden;
    }
    if($plain_tokens){
      $html.='\n<div style="color:#f5f5f5;font-size:1px;line-height:1px;opacity:0">'.esc_html(implode(' \n ',$plain_tokens)).'</div>';
    }
  }
  return $html;
}
function kcf_wp_mail_with_message_id($to,$subject,$message,$headers,$message_id=''){
  $filter_handle=null;
  if(is_string($message_id)&&$message_id!==''){
    $filter=function($generated) use ($message_id){
      return $message_id;
    };
    add_filter('wp_mail_message_id',$filter);
    $filter_handle=$filter;
  }
  $result=wp_mail($to,$subject,$message,$headers);
  if($filter_handle){
    remove_filter('wp_mail_message_id',$filter_handle);
  }
  return $result;
}
?>