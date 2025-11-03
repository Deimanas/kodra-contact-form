
<?php if(!defined('ABSPATH')) exit;
function kcf_sanitize_text($v){ return trim( wp_kses($v,[]) ); }
function kcf_store_message($d){ global $wpdb; $wpdb->insert(KCF_TABLE,[ 'created_at'=>current_time('mysql'),'name'=>$d['vardas'],'company'=>$d['imone'],'phone'=>$d['telefonas'],'email'=>$d['email'],'message'=>$d['zinute'],'extra'=>$d['extra'],'ip'=>$_SERVER['REMOTE_ADDR']??'','user_agent'=>$_SERVER['HTTP_USER_AGENT']??'','seen'=>0 ],['%s','%s','%s','%s','%s','%s','%s','%s','%s','%d']); return $wpdb->insert_id; }
function kcf_verify_recaptcha(){ $enabled=(int)kcf_settings_get('recaptcha_enabled',0)===1; if(!$enabled) return true; $secret=kcf_settings_get('recaptcha_secret_key',''); if(empty($secret)) return true; $resp=isset($_POST['g-recaptcha-response'])?sanitize_text_field($_POST['g-recaptcha-response']):''; if(empty($resp)) return false; $r=wp_remote_post('https://www.google.com/recaptcha/api/siteverify',['timeout'=>10,'body'=>['secret'=>$secret,'response'=>$resp,'remoteip'=>$_SERVER['REMOTE_ADDR']??'']]); if(is_wp_error($r)) return false; $d=json_decode(wp_remote_retrieve_body($r),true); if(empty($d['success'])) return false; $thr=floatval(kcf_settings_get('recaptcha_threshold',0.5)); if(isset($d['score']) && floatval($d['score'])<$thr) return false; return true; }
function kcf_handle_submit(){
  if(empty($_POST['kcf_nonce'])||!wp_verify_nonce($_POST['kcf_nonce'],'kcf_nonce')) wp_send_json_error(['message'=>'Neteisinga užklausa.'],400);
  if(!empty($_POST['website'])) wp_send_json_success(['message'=>'Ačiū! Jeigu esate žmogus, Jūsų žinutė gauta.']);
  if(!kcf_verify_recaptcha()) wp_send_json_error(['message'=>'reCAPTCHA nepraėjo.'],403);
  $layout=kcf_get_form_layout();
  $fields=array_merge($layout['fields'],$layout['custom_fields']);
  if(empty($fields)){
    $defaults=kcf_form_layout_defaults();
    $fields=$defaults['fields'];
  }
  usort($fields,function($a,$b){
    $ao=isset($a['order'])?intval($a['order']):0;
    $bo=isset($b['order'])?intval($b['order']):0;
    if($ao===$bo){
      $ak=isset($a['key'])?$a['key']:'';
      $bk=isset($b['key'])?$b['key']:'';
      return strcmp($ak,$bk);
    }
    return $ao<=>$bo;
  });
  $prefixes=kcf_phone_prefixes();
  $submission=['vardas'=>'','imone'=>'','telefonas'=>'','email'=>'','zinute'=>''];
  $extra=[];
  $email_fields=[];
  $errors=[];
  $phone_required=false;
  foreach($fields as $field){
    $key=isset($field['key'])?$field['key']:'';
    $name=isset($field['name'])?$field['name']:$key;
    if($key==='') continue;
    $type=isset($field['type'])?strtolower($field['type']):'text';
    $required=!empty($field['required']);
    $value='';
    if($type==='phone'){
      $hidden_name=isset($field['hidden_name'])?$field['hidden_name']:'telefonas';
      $raw_hidden=isset($_POST[$hidden_name])?wp_unslash($_POST[$hidden_name]):'';
      $value=preg_replace('/[^\d\+]/','',trim((string)$raw_hidden));
      $phone_required=$required;
    } elseif($type==='textarea'){
      $value=kcf_clean_textarea($_POST[$name]??'');
    } elseif($type==='email'){
      $value=sanitize_email($_POST[$name]??'');
    } elseif($type==='tel'){
      $raw=isset($_POST[$name])?wp_unslash($_POST[$name]):'';
      $value=preg_replace('/[^0-9\+]/','',trim((string)$raw));
    } elseif($type==='number'){
      $raw=isset($_POST[$name])?wp_unslash($_POST[$name]):'';
      $raw=trim((string)$raw);
      $value=$raw===''?'' : preg_replace('/[^0-9,\.\-]/','',$raw);
    } else {
      $value=kcf_sanitize_text($_POST[$name]??'');
    }
    if($required && $value===''){
      $errors[]=$key;
    }
    if($type==='email' && $value!==''){
      if(!is_email($value)){
        $errors[]=$key;
      }
    }
    $label_no_star=kcf_strip_required_marker(kcf_format_field_label($field,false));
    if($label_no_star===''){
      $label_no_star=ucfirst($key);
    }
    $include_in_email=true;
    if($value===''){
      if($key==='zinute'){
        $include_in_email=true;
      } else {
        $include_in_email=false;
      }
    }
    if(array_key_exists($key,$submission)){
      $submission[$key]=$value;
      if($include_in_email){
        $email_fields[]=['label'=>$label_no_star,'value'=>$value,'type'=>$type];
      }
    } else {
      if($include_in_email){
        $email_fields[]=['label'=>$label_no_star,'value'=>$value,'type'=>$type];
      }
      $extra[]=['key'=>$key,'label'=>$label_no_star,'value'=>$value,'type'=>$type];
    }
  }
  if(!empty($errors)){
    wp_send_json_error(['message'=>'Patikrinkite privalomus laukus.'],422);
  }
  $telefonas=$submission['telefonas'];
  $phone_valid=true;
  if($telefonas!==''){
    $phone_valid=false;
    if(is_array($prefixes)){
      foreach($prefixes as $p){
        $value=isset($p['value'])?preg_replace('/[^\d\+]/','',(string)$p['value']):'';
        $length=isset($p['length'])?intval($p['length']):0;
        if($value==='') continue;
        if(strpos($telefonas,$value)===0){
          $rest=substr($telefonas,strlen($value));
          if($length>0){
            if(strlen($rest)===$length && ctype_digit($rest)){
              $phone_valid=true;
              break;
            }
          } else {
            if($rest!=='' && ctype_digit($rest)){
              $phone_valid=true;
              break;
            }
          }
        }
      }
    }
  } elseif($phone_required){
    $phone_valid=false;
  }
  if(!$phone_valid){
    wp_send_json_error(['message'=>'Telefono numeris turi atitikti pasirinkto prefikso formatą.'],422);
  }
  $extra_json=!empty($extra)?wp_json_encode($extra):'';
  if(!is_string($extra_json)) $extra_json='';
  $id=kcf_store_message(['vardas'=>$submission['vardas'],'imone'=>$submission['imone'],'telefonas'=>$submission['telefonas'],'email'=>$submission['email'],'zinute'=>$submission['zinute'],'extra'=>$extra_json]);
  $to=get_option('admin_email');
  $from_name=$submission['vardas']!==''?$submission['vardas']:'Kontaktinė forma';
  $headers=[ 'Content-Type: text/html; charset=UTF-8','Reply-To: '.$from_name.' <'.$submission['email'].'>','X-KCF-ID: '.$id ];
  $body='<h2>Kontaktinė forma</h2>';
  foreach($email_fields as $field_info){
    $val=$field_info['value'];
    if($val==='') continue;
    $label=trim((string)$field_info['label']);
    $type=strtolower($field_info['type']??'');
    if($type==='textarea'){
      $body.='<p><strong>'.esc_html($label).':</strong><br>'.nl2br(esc_html($val)).'</p>';
    } else {
      $body.='<p><strong>'.esc_html($label).':</strong> '.esc_html($val).'</p>';
    }
  }
  $body.='<!-- KCF-ID: '.intval($id).' -->';
  wp_mail($to,'[KCF #'.strval($id).'] Nauja žinutė iš '.$from_name,$body,$headers);
  wp_send_json_success(['message'=>'Ačiū! Jūsų žinutė sėkmingai išsiųsta.']);
}
add_action('wp_ajax_kcf_submit','kcf_handle_submit'); add_action('wp_ajax_nopriv_kcf_submit','kcf_handle_submit'); ?>
