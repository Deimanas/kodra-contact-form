
<?php if(!defined('ABSPATH')) exit;
if(!function_exists('kcf_settings_get')){ function kcf_settings_get($k,$d=''){ $o=get_option(KCF_OPT,[]); return isset($o[$k])?$o[$k]:$d; } }
function kcf_log($msg){ $log=get_option('kcf_log',[]); $log[]='['.current_time('mysql').'] '.$msg; if(count($log)>1000) $log=array_slice($log,-1000); update_option('kcf_log',$log,false); update_option('kcf_last_check', current_time('mysql'), false); }
if(!function_exists('kcf_phone_prefixes')){
  function kcf_phone_prefixes(){
    $prefixes=[
      ['value'=>'+3706','country'=>'Lietuva','flag'=>'lt','emoji'=>'🇱🇹','length'=>7],
      ['value'=>'+3712','country'=>'Latvija','flag'=>'lv','emoji'=>'🇱🇻','length'=>7],
      ['value'=>'+3725','country'=>'Estija','flag'=>'ee','emoji'=>'🇪🇪','length'=>7],
    ];
    return apply_filters('kcf_phone_prefixes',$prefixes);
  }
}

if(!function_exists('kcf_convert_charset_to_utf8')){
  function kcf_convert_charset_to_utf8($text,$charset){
    $charset=trim((string)$charset);
    if($charset==='') return $text;
    $lower=strtolower($charset);
    if($lower==='utf-8' || $lower==='default') return $text;
    $clean=preg_replace('/[^A-Za-z0-9_\-]/','',$charset);
    if($clean==='') return $text;
    if(function_exists('iconv')){
      $converted=@iconv($clean,'UTF-8//TRANSLIT',$text);
      if($converted===false) $converted=@iconv($clean,'UTF-8//IGNORE',$text);
      if($converted!==false) return $converted;
    }
    if(function_exists('mb_convert_encoding')){
      $converted=@mb_convert_encoding($text,'UTF-8',$clean);
      if($converted!==false) return $converted;
    }
    return $text;
  }
}

if(!function_exists('kcf_decode_mime_header')){
  function kcf_decode_mime_header($text){
    $text=(string)$text;
    if($text==='') return '';
    $decoded='';
    if(function_exists('imap_mime_header_decode')){
      $parts=@imap_mime_header_decode($text);
      if(is_array($parts)){
        foreach($parts as $part){
          $segment=isset($part->text)?(string)$part->text:'';
          $charset=isset($part->charset)?(string)$part->charset:'';
          if($charset!==''){
            $segment=kcf_convert_charset_to_utf8($segment,$charset);
          }
          $decoded.=$segment;
        }
      }
    }
    if($decoded===''){
      if(function_exists('mb_decode_mimeheader')){
        $alt=@mb_decode_mimeheader($text);
        if(is_string($alt)&&$alt!==''){
          $decoded=$alt;
        }
      }
    }
    if($decoded===''){
      if(function_exists('imap_utf8')){
        $alt=@imap_utf8($text);
        if(is_string($alt)&&$alt!==''){
          $decoded=$alt;
        }
      }
    }
    if($decoded===''){
      $decoded=$text;
    }
    return $decoded;
  }
}
function kcf_trim_body_for_admin($body){
  $b=str_replace(['<br />','<br/>','<br>'],"\n",$body);
  $b=str_replace(["\r\n","\r"],"\n",$b);
  $patterns=['/\n\s*20\d{2}-\d{2}-\d{2}.*?rašė.*$/su','/\n\s*On .+ wrote:.*/su','/\n\s*[-]{2,}\s*\nKCF-ID:.*/su','/\n\s*[-]{2,}\s*\nKCF-INBOX-ID:.*/su','/\n<!--\s*KCF-ID:\s*\d+\s*-->/i','/\n<!--\s*KCF-INBOX-ID:\s*\d+\s*-->/i','/\n>.*$/su'];
  foreach($patterns as $p){ if(preg_match($p,$b,$m,PREG_OFFSET_CAPTURE)){ $b=substr($b,0,$m[0][1]); break; } }
  return trim($b);
}
function kcf_clean_textarea($text){ return trim(sanitize_textarea_field((string)$text)); }
function kcf_prepare_email_body($text,$tokens=[]){
  $normalized=str_replace(["\r\n","\r"],"\n",(string)$text);
  $html=nl2br(esc_html($normalized));
  if(!empty($tokens)&&is_array($tokens)){
    foreach($tokens as $label=>$value){
      if($value===null) continue;
      $value=is_array($value)?reset($value):$value;
      $value=trim((string)$value);
      if($value==='') continue;
      $label=trim((string)$label);
      if($label==='') continue;
      $pair=$label.': '.$value;
      $hidden='<div style="display:none!important;max-height:0;max-width:0;overflow:hidden;opacity:0;color:transparent">'.esc_html($pair).'</div>';
      $html.="\n<!-- ".$pair." -->\n".$hidden;
    }
  }
  return $html;
}
?>
