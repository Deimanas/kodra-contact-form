
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
    $prefixes=apply_filters('kcf_phone_prefixes',$prefixes);
    if(!is_array($prefixes)) return [];
    $normalized=[];
    foreach($prefixes as $prefix){
      if(!is_array($prefix)) continue;
      $value=isset($prefix['value'])?(string)$prefix['value']:'';
      if($value==='') continue;
      $flag=isset($prefix['flag'])?(string)$prefix['flag']:'';
      $emoji=isset($prefix['emoji'])?(string)$prefix['emoji']:'';
      if($emoji===''){
        $emoji=kcf_flag_to_emoji($flag);
      }
      $prefix['value']=$value;
      $prefix['flag']=$flag;
      $prefix['emoji']=$emoji;
      $normalized[]=$prefix;
    }
    return $normalized;
  }
}

if(!function_exists('kcf_flag_to_emoji')){
  function kcf_flag_to_emoji($code){
    $code=strtoupper(trim((string)$code));
    if($code==='') return '';
    if(strlen($code)!==2) return '';
    $letters=str_split($code);
    $emoji='';
    foreach($letters as $letter){
      $ord=ord($letter);
      if($ord<65||$ord>90){
        return '';
      }
      $emoji.=html_entity_decode('&#'.(127397+$ord).';',ENT_NOQUOTES,'UTF-8');
    }
    return $emoji;
  }
}

if(!function_exists('kcf_get_recaptcha_config')){
  function kcf_get_recaptcha_config(){
    $mode=kcf_settings_get('recaptcha_mode','');
    $enabled=intval(kcf_settings_get('recaptcha_enabled',0));
    if($mode===''){
      $mode=$enabled? 'v3':'none';
    }
    $mode=$mode==='v3'?'v3':'none';
    if($enabled===0){
      $mode='none';
    }
    $site=trim((string)kcf_settings_get('recaptcha_site_key',''));
    $secret=trim((string)kcf_settings_get('recaptcha_secret_key',''));
    $threshold=floatval(kcf_settings_get('recaptcha_threshold',0.5));
    if($threshold<0) $threshold=0;
    if($threshold>1) $threshold=1;
    if($mode!=='none' && $site===''){
      $mode='none';
    }
    return [
      'mode'=>$mode,
      'site_key'=>$site,
      'secret_key'=>$secret,
      'threshold'=>$threshold,
      'action'=>'kcf_submit',
    ];
  }
}

if(!function_exists('kcf_sanitize_class_list')){
  function kcf_sanitize_class_list($value){
    if(is_array($value)) $value=implode(' ',$value);
    $value=trim((string)$value);
    if($value==='') return '';
    $parts=preg_split('/\s+/',$value,-1,PREG_SPLIT_NO_EMPTY);
    $clean=[];
    foreach($parts as $part){
      $san=sanitize_html_class($part);
      if($san!=='') $clean[]=$san;
    }
    return implode(' ',$clean);
  }
}

if(!function_exists('kcf_sanitize_inline_style')){
  function kcf_sanitize_inline_style($value){
    $value=wp_strip_all_tags((string)$value);
    if($value==='') return '';
    $value=preg_replace("#[^A-Za-z0-9#:%;,.\\-\\s\\(\\)\\[\\]\\\"']#","",$value);
    return trim($value);
  }
}

if(!function_exists('kcf_form_layout_defaults')){
  function kcf_form_layout_defaults(){
    return [
      'fields'=>[
        [
          'key'=>'vardas',
          'name'=>'vardas',
          'type'=>'text',
          'label'=>'Vardas',
          'placeholder'=>'Vardas',
          'required'=>1,
          'width'=>'half',
          'wrapper_class'=>'',
          'input_class'=>'',
          'order'=>10,
          'rows'=>1,
          'locked'=>1,
        ],
        [
          'key'=>'imone',
          'name'=>'imone',
          'type'=>'text',
          'label'=>'Įmonė',
          'placeholder'=>'Įmonė',
          'required'=>0,
          'width'=>'half',
          'wrapper_class'=>'',
          'input_class'=>'',
          'order'=>20,
          'rows'=>1,
          'locked'=>1,
        ],
        [
          'key'=>'telefonas',
          'name'=>'telefonas',
          'type'=>'phone',
          'label'=>'Telefono numeris',
          'placeholder'=>'',
          'required'=>1,
          'width'=>'half',
          'wrapper_class'=>'',
          'input_class'=>'',
          'order'=>30,
          'rows'=>1,
          'locked'=>1,
          'number_name'=>'telefonas_number',
          'prefix_name'=>'telefonas_prefix',
          'hidden_name'=>'telefonas',
        ],
        [
          'key'=>'email',
          'name'=>'email',
          'type'=>'email',
          'label'=>'El. Paštas',
          'placeholder'=>'El. Paštas',
          'required'=>1,
          'width'=>'half',
          'wrapper_class'=>'',
          'input_class'=>'',
          'order'=>40,
          'rows'=>1,
          'locked'=>1,
        ],
        [
          'key'=>'zinute',
          'name'=>'zinute',
          'type'=>'textarea',
          'label'=>'Žinutė',
          'placeholder'=>'Žinutė',
          'required'=>1,
          'width'=>'full',
          'wrapper_class'=>'',
          'input_class'=>'',
          'order'=>50,
          'rows'=>5,
          'locked'=>1,
        ],
      ],
      'custom_fields'=>[],
      'button'=>[
        'text'=>'Siųsti žinutę',
        'classes'=>'kcf-button',
        'wrapper_class'=>'',
        'style'=>'',
      ],
    ];
  }
}

if(!function_exists('kcf_normalize_form_field')){
  function kcf_normalize_form_field($field){
    if(!is_array($field)) return null;
    $allowed_types=['text','email','tel','textarea','number','phone'];
    $field['key']=isset($field['key'])?sanitize_key($field['key']):'';
    if($field['key']==='') return null;
    $field['name']=isset($field['name'])?sanitize_key($field['name']):$field['key'];
    if($field['name']==='') $field['name']=$field['key'];
    $type=isset($field['type'])?strtolower((string)$field['type']):'text';
    if(!in_array($type,$allowed_types,true)) $type='text';
    $field['type']=$type;
    $field['label']=isset($field['label'])?sanitize_text_field($field['label']):'';
    $field['placeholder']=isset($field['placeholder'])?sanitize_text_field($field['placeholder']):'';
    $field['required']=!empty($field['required'])?1:0;
    $width=isset($field['width'])?strtolower((string)$field['width']):'half';
    $field['width']=in_array($width,['half','full'],true)?$width:'half';
    $field['wrapper_class']=kcf_sanitize_class_list($field['wrapper_class']??'');
    $field['input_class']=kcf_sanitize_class_list($field['input_class']??'');
    $field['order']=isset($field['order'])?intval($field['order']):0;
    $field['rows']=isset($field['rows'])?max(1,intval($field['rows'])):1;
    $field['locked']=!empty($field['locked'])?1:0;
    if($field['type']==='phone'){
      $field['number_name']=isset($field['number_name'])?sanitize_key($field['number_name']):'telefonas_number';
      if($field['number_name']==='') $field['number_name']='telefonas_number';
      $field['prefix_name']=isset($field['prefix_name'])?sanitize_key($field['prefix_name']):'telefonas_prefix';
      if($field['prefix_name']==='') $field['prefix_name']='telefonas_prefix';
      $field['hidden_name']=isset($field['hidden_name'])?sanitize_key($field['hidden_name']):'telefonas';
      if($field['hidden_name']==='') $field['hidden_name']='telefonas';
    }
    return $field;
  }
}

if(!function_exists('kcf_normalize_custom_field')){
  function kcf_normalize_custom_field($field){
    $field=kcf_normalize_form_field($field);
    if(!$field) return null;
    if($field['type']==='phone'){
      $field['type']='tel';
    }
    $field['locked']=0;
    return $field;
  }
}

if(!function_exists('kcf_normalize_button_settings')){
  function kcf_normalize_button_settings($button){
    if(!is_array($button)) $button=[];
    $out=[];
    $out['text']=isset($button['text'])?sanitize_text_field($button['text']):'';
    $out['classes']=kcf_sanitize_class_list($button['classes']??'');
    $out['wrapper_class']=kcf_sanitize_class_list($button['wrapper_class']??'');
    $out['style']=kcf_sanitize_inline_style($button['style']??'');
    return $out;
  }
}

if(!function_exists('kcf_get_form_layout')){
  function kcf_get_form_layout(){
    $defaults=kcf_form_layout_defaults();
    $stored=get_option('kcf_form_layout',[]);
    $result=$defaults;
    if(isset($stored['fields'])&&is_array($stored['fields'])){
      foreach($result['fields'] as $idx=>$field){
        $key=$field['key'];
        if(isset($stored['fields'][$key])&&is_array($stored['fields'][$key])){
          $merged=array_merge($field,$stored['fields'][$key]);
          $normalized=kcf_normalize_form_field($merged);
          if($normalized){
            $normalized['locked']=$field['locked'];
            if($field['type']==='phone'){
              $normalized['type']='phone';
              $normalized['number_name']=$field['number_name'];
              $normalized['prefix_name']=$field['prefix_name'];
              $normalized['hidden_name']=$field['hidden_name'];
            }
            $result['fields'][$idx]=$normalized;
          }
        }
      }
    }
    $result['custom_fields']=[];
    if(isset($stored['custom_fields'])&&is_array($stored['custom_fields'])){
      foreach($stored['custom_fields'] as $item){
        $normalized=kcf_normalize_custom_field($item);
        if($normalized) $result['custom_fields'][]=$normalized;
      }
    }
    $button_defaults=$result['button'];
    $normalized_button=kcf_normalize_button_settings(isset($stored['button'])?$stored['button']:[]);
    if(empty($normalized_button['text'])){
      $normalized_button['text']=$button_defaults['text'];
    }
    if(empty($normalized_button['classes'])){
      $normalized_button['classes']=$button_defaults['classes'];
    }
    $result['button']=array_merge($button_defaults,$normalized_button);
    return $result;
  }
}

if(!function_exists('kcf_index_form_fields')){
  function kcf_index_form_fields($layout){
    $indexed=[];
    if(isset($layout['fields'])&&is_array($layout['fields'])){
      foreach($layout['fields'] as $field){
        if(isset($field['key'])) $indexed[$field['key']]=$field;
      }
    }
    if(isset($layout['custom_fields'])&&is_array($layout['custom_fields'])){
      foreach($layout['custom_fields'] as $field){
        if(isset($field['key'])) $indexed[$field['key']]=$field;
      }
    }
    return $indexed;
  }
}

if(!function_exists('kcf_format_field_label')){
  function kcf_format_field_label($field,$append_required=true){
    $label='';
    if(is_array($field)&&isset($field['label'])){
      $label=trim((string)$field['label']);
    }
    if($label===''){
      $key=is_array($field)&&isset($field['key'])?$field['key']:'';
      if($key!==''){
        $label=ucfirst(str_replace('_',' ',$key));
      }
    }
    if($append_required && is_array($field) && !empty($field['required']) && strpos($label,'*')===false){
      $label=rtrim($label).' *';
    }
    return $label;
  }
}

if(!function_exists('kcf_strip_required_marker')){
  function kcf_strip_required_marker($label){
    $label=trim((string)$label);
    if($label==='') return '';
    return trim(preg_replace('/\s*\*+$/','',$label));
  }
}

if(!function_exists('kcf_decode_message_extra')){
  function kcf_decode_message_extra($value){
    if(empty($value)) return [];
    if(is_string($value)){
      $decoded=json_decode($value,true);
    } else {
      $decoded=$value;
    }
    if(!is_array($decoded)) return [];
    $clean=[];
    foreach($decoded as $item){
      if(!is_array($item)) continue;
      $key=isset($item['key'])?sanitize_key($item['key']):'';
      if($key==='') continue;
      $clean[]=[
        'key'=>$key,
        'label'=>isset($item['label'])?sanitize_text_field($item['label']):'',
        'value'=>isset($item['value'])?$item['value']:'',
        'type'=>isset($item['type'])?sanitize_text_field($item['type']):'',
      ];
    }
    return $clean;
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
