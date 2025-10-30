
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
function kcf_prepare_email_body($text,$tokens=[]){ $normalized=str_replace(["\r\n","\r"],"\n",(string)$text); $html=nl2br(esc_html($normalized)); if(!empty($tokens)&&is_array($tokens)){ foreach($tokens as $label=>$value){ if($value===null) continue; $value=is_array($value)?reset($value):$value; $value=trim((string)$value); if($value==='') continue; $label=trim((string)$label); if($label==='') continue; $html.="\n<!-- ".$label.': '.$value." -->"; } } return $html; }
?>