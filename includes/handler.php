
<?php if(!defined('ABSPATH')) exit;
function kcf_sanitize_text($v){ return trim( wp_kses($v,[]) ); }
function kcf_store_message($d){ global $wpdb; $wpdb->insert(KCF_TABLE,[ 'created_at'=>current_time('mysql'),'name'=>$d['vardas'],'company'=>$d['imone'],'phone'=>$d['telefonas'],'email'=>$d['email'],'message'=>$d['zinute'],'ip'=>$_SERVER['REMOTE_ADDR']??'','user_agent'=>$_SERVER['HTTP_USER_AGENT']??'','seen'=>0 ],['%s','%s','%s','%s','%s','%s','%s','%s','%d']); return $wpdb->insert_id; }
function kcf_verify_recaptcha(){ $enabled=(int)kcf_settings_get('recaptcha_enabled',0)===1; if(!$enabled) return true; $secret=kcf_settings_get('recaptcha_secret_key',''); if(empty($secret)) return true; $resp=isset($_POST['g-recaptcha-response'])?sanitize_text_field($_POST['g-recaptcha-response']):''; if(empty($resp)) return false; $r=wp_remote_post('https://www.google.com/recaptcha/api/siteverify',['timeout'=>10,'body'=>['secret'=>$secret,'response'=>$resp,'remoteip'=>$_SERVER['REMOTE_ADDR']??'']]); if(is_wp_error($r)) return false; $d=json_decode(wp_remote_retrieve_body($r),true); if(empty($d['success'])) return false; $thr=floatval(kcf_settings_get('recaptcha_threshold',0.5)); if(isset($d['score']) && floatval($d['score'])<$thr) return false; return true; }
function kcf_handle_submit(){
  if(empty($_POST['kcf_nonce'])||!wp_verify_nonce($_POST['kcf_nonce'],'kcf_nonce')) wp_send_json_error(['message'=>'Neteisinga užklausa.'],400);
  if(!empty($_POST['website'])) wp_send_json_success(['message'=>'Ačiū! Jeigu esate žmogus, Jūsų žinutė gauta.']);
  if(!kcf_verify_recaptcha()) wp_send_json_error(['message'=>'reCAPTCHA nepraėjo.'],403);
  $vardas=kcf_sanitize_text($_POST['vardas']??''); $imone=kcf_sanitize_text($_POST['imone']??''); $telefonas=preg_replace('/[^\d\+]/','',trim($_POST['telefonas']??'')); $email=sanitize_email($_POST['email']??''); $zinute=kcf_sanitize_text($_POST['zinute']??'');
  if(empty($vardas)||empty($telefonas)||empty($email)||empty($zinute)||!is_email($email)) wp_send_json_error(['message'=>'Patikrinkite privalomus laukus.'],422);
  $valid=false; $prefixes=kcf_phone_prefixes();
  if(is_array($prefixes)){
    foreach($prefixes as $p){ $value=isset($p['value'])?preg_replace('/[^\d\+]/','',(string)$p['value']):''; $length=isset($p['length'])?intval($p['length']):0; if($value===''||$length<=0) continue; if(strpos($telefonas,$value)===0){ $rest=substr($telefonas,strlen($value)); if(strlen($rest)===$length&&ctype_digit($rest)){ $valid=true; break; } } }
  }
  if(!$valid) wp_send_json_error(['message'=>'Telefono numeris turi atitikti pasirinkto prefikso formatą.'],422);
  $id=kcf_store_message(['vardas'=>$vardas,'imone'=>$imone,'telefonas'=>$telefonas,'email'=>$email,'zinute'=>$zinute]);
  $to=get_option('admin_email'); $headers=[ 'Content-Type: text/html; charset=UTF-8','Reply-To: '.$vardas.' <'.$email.'>','X-KCF-ID: '.$id ];
  $body='<h2>Kontaktinė forma</h2><p><strong>Vardas:</strong> '.esc_html($vardas).'</p>'.($imone?'<p><strong>Įmonė:</strong> '.esc_html($imone).'</p>':'').'<p><strong>Telefonas:</strong> '.esc_html($telefonas).'</p><p><strong>El. paštas:</strong> '.esc_html($email).'</p><p><strong>Žinutė:</strong><br>'.nl2br(esc_html($zinute)).'</p><!-- KCF-ID: '.intval($id).' -->';
  wp_mail($to,'[KCF #'.strval($id).'] Nauja žinutė iš '.$vardas,$body,$headers);
  wp_send_json_success(['message'=>'Ačiū! Jūsų žinutė sėkmingai išsiųsta.']);
}
add_action('wp_ajax_kcf_submit','kcf_handle_submit'); add_action('wp_ajax_nopriv_kcf_submit','kcf_handle_submit'); ?>
