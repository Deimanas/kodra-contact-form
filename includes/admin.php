
<?php if(!defined('ABSPATH')) exit;
add_action('admin_menu',function(){ add_menu_page('Kodra žinutės','Kodra žinutės','manage_options','kcf-messages','kcf_admin_messages_page','dashicons-email-alt2',26); add_submenu_page('kcf-messages','Gauti laiškai','Gauti laiškai','manage_options','kcf-inbox','kcf_admin_inbox_page'); add_submenu_page('kcf-messages','Nustatymai','Nustatymai','manage_options','kcf-settings','kcf_admin_settings_page'); add_submenu_page('kcf-messages','Diagnostika','Diagnostika','manage_options','kcf-diagnostics','kcf_admin_diagnostics_page'); });
add_action('admin_post_kcf_delete_message',function(){ if(!current_user_can('manage_options')) wp_die('Unauthorized'); check_admin_referer('kcf_delete_message'); global $wpdb; $id=intval($_GET['id']??0); if($id){ $wpdb->delete(KCF_TABLE,['id'=>$id]); $wpdb->delete(KCF_REPLIES_TABLE,['message_id'=>$id]); } wp_safe_redirect(admin_url('admin.php?page=kcf-messages&deleted=1')); exit; });
add_action('admin_post_kcf_bulk_delete_messages',function(){ if(!current_user_can('manage_options')) wp_die('Unauthorized'); check_admin_referer('kcf_bulk_delete_messages'); $ids=isset($_POST['ids'])&&is_array($_POST['ids'])?array_map('intval',$_POST['ids']):[]; $ids=array_filter($ids); $redirect=admin_url('admin.php?page=kcf-messages'); if(!empty($_POST['redirect_to'])){ $maybe=wp_unslash($_POST['redirect_to']); $maybe=$maybe!==''?wp_validate_redirect($maybe,$redirect):''; if($maybe){ $redirect=$maybe; } }
  if(!$ids){ wp_safe_redirect(add_query_arg('bulk_error',1,$redirect)); exit; }
  global $wpdb;
  $placeholders=implode(',',array_fill(0,count($ids),'%d'));
  $sql_replies='DELETE FROM '.KCF_REPLIES_TABLE.' WHERE message_id IN ('.$placeholders.')';
  $wpdb->query($wpdb->prepare($sql_replies,$ids));
  $sql_messages='DELETE FROM '.KCF_TABLE.' WHERE id IN ('.$placeholders.')';
  $wpdb->query($wpdb->prepare($sql_messages,$ids));
  $deleted=intval($wpdb->rows_affected);
  wp_safe_redirect(add_query_arg('bulk_deleted',$deleted,$redirect));
  exit;
});
add_action('admin_post_kcf_bulk_delete_message_replies',function(){ if(!current_user_can('manage_options')) wp_die('Unauthorized'); check_admin_referer('kcf_bulk_delete_message_replies'); $message_id=intval($_POST['message_id']??0); $ids=isset($_POST['ids'])&&is_array($_POST['ids'])?array_map('intval',$_POST['ids']):[]; $ids=array_filter($ids); $default_redirect=$message_id?add_query_arg(['page'=>'kcf-messages','view'=>$message_id],admin_url('admin.php')):admin_url('admin.php?page=kcf-messages'); $redirect=$default_redirect; if(!empty($_POST['redirect_to'])){ $maybe=wp_unslash($_POST['redirect_to']); $maybe=$maybe!==''?wp_validate_redirect($maybe,$redirect):''; if($maybe){ $redirect=$maybe; } }
  if(!$ids){ wp_safe_redirect(add_query_arg('bulk_error',1,$redirect)); exit; }
  if($message_id<=0){ wp_safe_redirect(add_query_arg('bulk_error',1,$redirect)); exit; }
  global $wpdb;
  $placeholders=implode(',',array_fill(0,count($ids),'%d'));
  $sql='DELETE FROM '.KCF_REPLIES_TABLE.' WHERE message_id=%d AND id IN ('.$placeholders.')';
  $params=array_merge([$message_id],$ids);
  $wpdb->query($wpdb->prepare($sql,$params));
  $deleted=intval($wpdb->rows_affected);
  wp_safe_redirect(add_query_arg('bulk_deleted',$deleted,$redirect));
  exit;
});
add_action('admin_post_kcf_reply_message',function(){ if(!current_user_can('manage_options')) wp_die('Unauthorized'); check_admin_referer('kcf_reply_message'); global $wpdb; $message_id=intval($_POST['message_id']); $row=$wpdb->get_row($wpdb->prepare('SELECT email FROM '.KCF_TABLE.' WHERE id=%d',$message_id)); $to=$row?$row->email:''; $subject=sanitize_text_field(wp_unslash($_POST['subject']??'')); $body_raw=isset($_POST['body'])?wp_unslash($_POST['body']):''; $body=kcf_clean_textarea($body_raw); if($body===''){ $redirect=admin_url('admin.php?page=kcf-messages'.($message_id?('&view='.$message_id):'')); $redirect=add_query_arg('reply_error',1,$redirect); wp_safe_redirect($redirect); exit; } $user_id=get_current_user_id(); $tokens=[]; $headers=['Content-Type: text/html; charset=UTF-8']; if($message_id>0){ $tokens['KCF-ID']=$message_id; $headers[]='X-KCF-ID: '.$message_id; } $body_html=kcf_prepare_email_body($body,$tokens); $sent=wp_mail($to,$subject,$body_html,$headers); $thread_root=$message_id>0?$message_id:0; $wpdb->insert(KCF_REPLIES_TABLE,['message_id'=>$message_id,'thread_root'=>$thread_root,'created_at'=>current_time('mysql'),'wp_user_id'=>$user_id,'direction'=>0,'to_email'=>$to,'subject'=>$subject,'body'=>$body,'sent'=>$sent?1:0,'seen'=>0],['%d','%d','%s','%d','%d','%s','%s','%s','%d','%d']); wp_safe_redirect(admin_url('admin.php?page=kcf-messages&view='.$message_id.'&replied='.($sent?'1':'0'))); exit; });
add_action('admin_post_kcf_delete_inbox',function(){ if(!current_user_can('manage_options')) wp_die('Unauthorized'); check_admin_referer('kcf_delete_inbox'); global $wpdb; $id=intval($_GET['id']??0); if($id){ $wpdb->delete(KCF_REPLIES_TABLE,['id'=>$id,'direction'=>1]); } $redirect=admin_url('admin.php?page=kcf-inbox'); if(isset($_REQUEST['redirect_to'])){ $maybe=wp_unslash($_REQUEST['redirect_to']); $maybe=$maybe!==''?wp_validate_redirect($maybe,$redirect):''; if($maybe){ $redirect=$maybe; } } wp_safe_redirect(add_query_arg('deleted',1,$redirect)); exit; });
add_action('admin_post_kcf_bulk_delete_inbox',function(){ if(!current_user_can('manage_options')) wp_die('Unauthorized'); check_admin_referer('kcf_bulk_delete_inbox'); $ids=isset($_POST['ids'])&&is_array($_POST['ids'])?array_map('intval',$_POST['ids']):[]; $ids=array_filter($ids); $redirect=admin_url('admin.php?page=kcf-inbox'); if(!empty($_POST['redirect_to'])){ $maybe=esc_url_raw(wp_unslash($_POST['redirect_to'])); if($maybe){ $redirect=$maybe; } } if(!$ids){ wp_safe_redirect(add_query_arg('bulk_error',1,$redirect)); exit; } global $wpdb; $placeholders=implode(',',array_fill(0,count($ids),'%d')); $sql='DELETE FROM '.KCF_REPLIES_TABLE.' WHERE direction=1 AND id IN ('.$placeholders.')'; $wpdb->query($wpdb->prepare($sql,$ids)); $deleted=intval($wpdb->rows_affected); wp_safe_redirect(add_query_arg('bulk_deleted',$deleted,$redirect)); exit; });
add_action('admin_post_kcf_reply_inbox',function(){ if(!current_user_can('manage_options')) wp_die('Unauthorized'); check_admin_referer('kcf_reply_inbox'); global $wpdb; $inbox_id=intval($_POST['inbox_id']??0); $redirect=admin_url('admin.php?page=kcf-inbox'); if($inbox_id){ $redirect=add_query_arg(['page'=>'kcf-inbox','view'=>$inbox_id],admin_url('admin.php')); } if(!$inbox_id){ wp_safe_redirect(add_query_arg('reply_error',1,$redirect)); exit; } $row=$wpdb->get_row($wpdb->prepare('SELECT to_email,subject,message_id FROM '.KCF_REPLIES_TABLE.' WHERE id=%d AND direction=1',$inbox_id),ARRAY_A); if(!$row){ wp_safe_redirect(add_query_arg('reply_error',1,$redirect)); exit; } $to=sanitize_email($row['to_email']); $subject=sanitize_text_field(wp_unslash($_POST['subject']??'')); $body_raw=isset($_POST['body'])?wp_unslash($_POST['body']):''; $body=kcf_clean_textarea($body_raw); if(!$to||$body===''){ wp_safe_redirect(add_query_arg('reply_error',1,$redirect)); exit; } if($subject===''){ $subject='(be temos)'; } $user_id=get_current_user_id(); $tokens=[]; if(intval($row['message_id'])>0){ $tokens['KCF-ID']=intval($row['message_id']); } $tokens['KCF-INBOX-ID']=$inbox_id; $body_html=kcf_prepare_email_body($body,$tokens); $headers=['Content-Type: text/html; charset=UTF-8','X-KCF-INBOX-ID: '.$inbox_id]; if(!empty($tokens['KCF-ID'])) $headers[]='X-KCF-ID: '.intval($row['message_id']); $sent=wp_mail($to,$subject,$body_html,$headers); $message_id=intval($row['message_id']); $thread_root=$message_id>0?$message_id:$inbox_id; $wpdb->insert(KCF_REPLIES_TABLE,['message_id'=>$message_id,'thread_root'=>$thread_root,'created_at'=>current_time('mysql'),'wp_user_id'=>$user_id,'direction'=>0,'to_email'=>$to,'subject'=>$subject,'body'=>$body,'sent'=>$sent?1:0,'seen'=>1],['%d','%d','%s','%d','%d','%s','%s','%s','%d','%d']); wp_safe_redirect(add_query_arg('replied',$sent?1:0,$redirect)); exit; });
add_action('admin_post_kcf_check_imap_now',function(){ if(!current_user_can('manage_options')) wp_die('Unauthorized'); check_admin_referer('kcf_check_imap_now'); do_action('kcf_check_mail_event'); wp_safe_redirect(admin_url('admin.php?page=kcf-diagnostics')); exit; });
function kcf_mark_message_seen($id){ global $wpdb; $wpdb->update(KCF_TABLE,['seen'=>1],['id'=>$id]); $wpdb->query($wpdb->prepare('UPDATE '.KCF_REPLIES_TABLE.' SET seen=1 WHERE message_id=%d AND seen=0',$id)); }
function kcf_mark_inbox_seen($id){
  global $wpdb;
  $id=intval($id);
  if($id<=0) return;
  $table=KCF_REPLIES_TABLE;
  $root=$wpdb->get_var($wpdb->prepare('SELECT CASE WHEN thread_root>0 THEN thread_root ELSE id END FROM '.$table.' WHERE id=%d',$id));
  if(!$root){
    return;
  }
  $root=intval($root);
  $wpdb->query($wpdb->prepare('UPDATE '.$table.' SET seen=1 WHERE id=%d OR thread_root=%d',$root,$root));
}
function kcf_admin_maybe_check_imap(){ static $done=false; if($done) return; $done=true; do_action('kcf_check_mail_event'); }
function kcf_admin_messages_page(){ if(!current_user_can('manage_options')) return; global $wpdb; $table=KCF_TABLE; $highlight_script='<script>(function(){document.addEventListener("DOMContentLoaded",function(){var highlightEls=document.querySelectorAll(".kcf-highlight");if(!highlightEls.length)return;setTimeout(function(){highlightEls.forEach(function(el){el.classList.add("kcf-fade-end");setTimeout(function(){el.classList.remove("kcf-fade-end");el.classList.remove("kcf-highlight");},800);});},60000);});})();</script>';
  $thread_bulk_script='<script>(function(){document.addEventListener("DOMContentLoaded",function(){var forms=document.querySelectorAll(".kcf-thread-bulk-form");if(!forms.length)return;forms.forEach(function(form){var boxes=Array.prototype.slice.call(form.querySelectorAll(".kcf-thread-row"));if(!boxes.length)return;var masters=Array.prototype.slice.call(form.querySelectorAll(".kcf-thread-select-all"));var buttons=Array.prototype.slice.call(form.querySelectorAll(".kcf-thread-bulk-delete"));function anyChecked(){return boxes.some(function(cb){return cb.checked;});}function sync(){var any=anyChecked();buttons.forEach(function(btn){btn.disabled=!any;});masters.forEach(function(master){var total=boxes.length;var checked=boxes.filter(function(cb){return cb.checked;}).length;master.checked=any&&checked===total;master.indeterminate=checked>0&&checked<total;});}boxes.forEach(function(cb){cb.addEventListener("change",sync);});masters.forEach(function(master){master.addEventListener("change",function(){var checked=this.checked;boxes.forEach(function(cb){cb.checked=checked;});sync();});});form.addEventListener("submit",function(e){if(!anyChecked()||!confirm("Ar tikrai ištrinti pažymėtus įrašus?")){e.preventDefault();}});sync();});});})();</script>';
  kcf_admin_maybe_check_imap(); echo '<div class="wrap"><h1>Kodra žinutės</h1><style>.kcf-highlight{background:#fff3cd!important;transition:background-color .6s ease;}.kcf-highlight.kcf-fade-end{background:#fff!important;}.kcf-unread-dot{display:inline-block;width:8px;height:8px;background:#d63638;border-radius:50%;margin-left:6px;vertical-align:middle}</style>';
  if(isset($_GET['deleted'])) echo '<div class="updated notice notice-success is-dismissible"><p>Žinutė ištrinta.</p></div>';
  if(isset($_GET['bulk_deleted'])){ $cnt=max(0,intval($_GET['bulk_deleted'])); if($cnt>1){ $msg=sprintf('%d žinučių ištrinta.',$cnt); } elseif($cnt===1){ $msg='1 žinutė ištrinta.'; } else { $msg='Neištrinta nė viena žinutė.'; } echo '<div class="updated notice notice-success is-dismissible"><p>'.esc_html($msg).'</p></div>'; }
  if(isset($_GET['bulk_error'])) echo '<div class="error notice notice-error"><p>Pasirinkite bent vieną žinutę.</p></div>';
  if(isset($_GET['replied'])){ if(intval($_GET['replied'])===1) echo '<div class="updated notice notice-success is-dismissible"><p>Atsakymas išsiųstas.</p></div>'; else echo '<div class="error notice notice-error"><p>Atsakymo išsiųsti nepavyko.</p></div>'; }
  if(isset($_GET['reply_error'])) echo '<div class="error notice notice-error"><p>Patikrinkite atsakymo laukus.</p></div>';
  $q=sanitize_text_field($_GET['q']??''); echo '<form method="get" style="margin:8px 0 12px"><input type="hidden" name="page" value="kcf-messages"><input type="text" name="q" value="'.esc_attr($q).'" placeholder="Paieška (vardas, paštas, tel., įmonė)" style="min-width:340px;margin-right:6px"><button class="button button-primary">Ieškoti</button> <a class="button" href="'.admin_url('admin.php?page=kcf-messages').'">Išvalyti</a></form>';
  if(isset($_GET['view'])){ $id=intval($_GET['view']); $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d",$id),ARRAY_A); echo '<h2>#'.intval($id).'</h2>'; if($row){ echo '<div class="kcf-two-col"><div class="kcf-col kcf-col-left"><h3>Pagrindinė informacija</h3><table class="widefat striped">'; foreach(['Vardas'=>$row['name'],'Telefonas'=>$row['phone'],'El. paštas'=>$row['email'],'Žinutė'=>$row['message']] as $l=>$v){ echo '<tr><th>'.esc_html($l).'</th><td>'.wp_kses_post(nl2br(esc_html((string)$v))).'</td></tr>'; } echo '</table></div><div class="kcf-col kcf-col-right"><h3>Kita</h3><table class="widefat striped">'; foreach(['Data'=>$row['created_at'],'Įmonė'=>$row['company'],'IP adresas'=>$row['ip'],'Naršyklė'=>$row['user_agent'],'Įrašo ID'=>$row['id']] as $l=>$v){ echo '<tr><th>'.esc_html($l).'</th><td>'.wp_kses_post(nl2br(esc_html((string)$v))).'</td></tr>'; } echo '</table></div></div>'; echo '<style>.kcf-two-col{display:grid;grid-template-columns:7fr 3fr;gap:18px;max-width:100%;width:100%}.kcf-two-col th{width:220px}.kcf-two-col th,.kcf-two-col td{word-break:break-word;overflow-wrap:anywhere}.kcf-thread-item.kcf-latest strong{font-weight:500}.kcf-unread-dot{display:inline-block;width:8px;height:8px;background:#d63638;border-radius:50%;margin-left:6px;vertical-align:middle}</style>';
    $delete_url=wp_nonce_url(admin_url('admin-post.php?action=kcf_delete_message&id='.$id),'kcf_delete_message'); echo '<p style="margin-top:12px"><a href="'.$delete_url.'" class="button button-danger kcf-del">Ištrinti</a> <a href="'.admin_url('admin.php?page=kcf-messages').'" class="button">Grįžti</a></p><script>document.addEventListener("click",function(e){if(e.target.classList&&e.target.classList.contains("kcf-del")&&!confirm("Ar tikrai ištrinti šią žinutę?")){e.preventDefault();}});</script>';
    echo '<hr><h2>Atsakymų gija</h2>'; $replies=$wpdb->get_results($wpdb->prepare("SELECT r.*, u.display_name FROM ".KCF_REPLIES_TABLE." r LEFT JOIN {$wpdb->users} u ON u.ID=r.wp_user_id WHERE r.message_id=%d ORDER BY r.id ASC",$id),ARRAY_A);
    if($replies){
      $total=count($replies);
      $i=0;
      $view_url=add_query_arg(['page'=>'kcf-messages','view'=>$id],admin_url('admin.php'));
      echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="kcf-thread-bulk-form" id="kcf-message-thread-form">';
      echo '<input type="hidden" name="action" value="kcf_bulk_delete_message_replies">';
      wp_nonce_field('kcf_bulk_delete_message_replies');
      echo '<input type="hidden" name="message_id" value="'.intval($id).'">';
      echo '<input type="hidden" name="redirect_to" value="'.esc_attr($view_url).'">';
      echo '<div class="kcf-thread-bulk-actions"><label><input type="checkbox" class="kcf-thread-select-all" aria-label="Pažymėti visus įrašus"> Pažymėti visus</label> <button type="submit" class="button button-small button-danger kcf-thread-bulk-delete" disabled>Trinti pažymėtus</button></div>';
      echo '<ul class="kcf-thread" id="kcf-thread">';
      foreach($replies as $rep){
        $i++;
        $by=intval($rep['direction'])===1?'Klientas':(!empty($rep['display_name'])?$rep['display_name']:'Admin');
        $cls=intval($rep['seen'])===0?' kcf-unread-burst kcf-highlight':'';
        if($i===$total){
          $cls.=' kcf-latest';
        }
        $clean=function_exists('kcf_trim_body_for_admin')?kcf_trim_body_for_admin($rep['body']):$rep['body'];
        $subject_display=kcf_decode_mime_header($rep['subject']);
        $checkbox='<div class="kcf-thread-checkbox"><input type="checkbox" class="kcf-thread-row" name="ids[]" value="'.intval($rep['id']).'" aria-label="Pažymėti įrašą #'.intval($rep['id']).'"></div>';
        echo '<li class="kcf-thread-item'.$cls.'">'.$checkbox.'<div class="kcf-thread-body"><div><strong>'.esc_html($subject_display).'</strong></div><div><em>'.esc_html($rep['created_at']).'</em> — '.esc_html($by).'</div><div style="word-break:break-word">'.wp_kses_post(nl2br(esc_html($clean))).'</div></div></li>';
      }
      echo '</ul><div class="kcf-thread-bulk-actions"><label><input type="checkbox" class="kcf-thread-select-all" aria-label="Pažymėti visus įrašus"> Pažymėti visus</label> <button type="submit" class="button button-small button-danger kcf-thread-bulk-delete" disabled>Trinti pažymėtus</button></div></form>'.$thread_bulk_script.$highlight_script;
    } else {
      echo '<p>Gijoje dar nėra įrašų.</p>';
    }
    $default_subject='Re: [KCF #'.$id.'] Atsakymas į Jūsų žinutę'; echo '<hr><h2>Atsakyti</h2><form method="post" action="'.admin_url('admin-post.php?action=kcf_reply_message').'" style="max-width:700px">'; wp_nonce_field('kcf_reply_message'); echo '<input type="hidden" name="message_id" value="'.intval($id).'"><input type="hidden" name="to" value=""><p><label>Tema: <input type="text" name="subject" value="'.esc_attr($default_subject).'" style="width:100%" required></label></p><p><label>Žinutė:<br><textarea name="body" rows="8" style="width:100%" required></textarea></label></p><p><button type="submit" class="button button-primary">Siųsti atsakymą</button></p></form>'; kcf_mark_message_seen($id);
  } else { echo '<p>Įrašas nerastas.</p>'; } echo '</div>'; return; }
  $where='WHERE 1=1'; $params=[]; if($q){ $like='%'.$wpdb->esc_like($q).'%'; $where.=' AND (name LIKE %s OR email LIKE %s OR phone LIKE %s OR company LIKE %s)'; $params=[$like,$like,$like,$like]; }
  $per=20; $paged=max(1,intval($_GET['paged']??1)); $off=($paged-1)*$per; $sql_total="SELECT COUNT(*) FROM {$table} $where"; $total=(int)($params? $wpdb->get_var($wpdb->prepare($sql_total,$params)) : $wpdb->get_var($sql_total));
  $sql_items="SELECT id,created_at,name,email,phone,company,LEFT(message,160) AS excerpt, seen FROM {$table} $where ORDER BY id DESC LIMIT %d OFFSET %d"; if($params){ $args=array_merge($params,[$per,$off]); $items=$wpdb->get_results($wpdb->prepare($sql_items,$args),ARRAY_A); } else { $items=$wpdb->get_results($wpdb->prepare($sql_items,$per,$off),ARRAY_A); }
  $base=admin_url('admin.php?page=kcf-messages'); $current_url=isset($_SERVER['REQUEST_URI'])?esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])):''; echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" id="kcf-messages-form">'; echo '<input type="hidden" name="action" value="kcf_bulk_delete_messages">'; wp_nonce_field('kcf_bulk_delete_messages'); echo '<input type="hidden" name="redirect_to" value="'.esc_attr($current_url).'">'; echo '<div class="tablenav top"><div class="alignleft actions bulkactions"><button type="submit" class="button kcf-bulk-delete" disabled>Trinti pažymėtus</button></div></div>';
  echo '<table class="widefat striped" style="table-layout:fixed"><thead><tr><td id="cb" class="manage-column column-cb check-column"><input type="checkbox" class="kcf-select-all" aria-label="Pažymėti visus"></td><th style="width:60px">ID</th><th style="width:160px">Data</th><th style="width:140px">Vardas</th><th style="width:220px">El. paštas</th><th style="width:160px">Telefonas</th><th style="width:160px">Įmonė</th><th>Žinutė</th><th style="width:190px">Veiksmai</th></tr></thead><tbody>';
  if($items){
    foreach($items as $it){ $view=esc_url(add_query_arg(['view'=>$it['id']],$base)); $del=wp_nonce_url(admin_url('admin-post.php?action=kcf_delete_message&id='.$it['id']),'kcf_delete_message'); $row_cls=intval($it['seen'])===0?' class="kcf-highlight"':''; $dot=intval($it['seen'])===0?' <span class="kcf-unread-dot" title="Neskaityta"></span>':''; echo '<tr'.$row_cls.'><th scope="row" class="check-column"><input type="checkbox" class="kcf-row-checkbox" name="ids[]" value="'.intval($it['id']).'" aria-label="Pažymėti žinutę #'.intval($it['id']).'"></th><td><a href="'.$view.'">'.intval($it['id']).'</a>'.$dot.'</td><td>'.esc_html($it['created_at']).'</td><td>'.esc_html($it['name']).'</td><td style="word-break:break-word"><a href="mailto:'.esc_attr($it['email']).'">'.esc_html($it['email']).'</a></td><td>'.esc_html($it['phone']).'</td><td>'.esc_html($it['company']).'</td><td style="word-break:break-word">'.esc_html($it['excerpt']).'</td><td><a href="'.$view.'" class="button">Peržiūrėti</a> <a href="'.$del.'" class="button kcf-del">Ištrinti</a></td></tr>'; }
  } else { echo '<tr><td colspan="9">Nėra įrašų.</td></tr>'; }
  echo '</tbody></table><div class="tablenav bottom"><div class="alignleft actions bulkactions"><button type="submit" class="button kcf-bulk-delete" disabled>Trinti pažymėtus</button></div></div></form>';
  echo '<script>(function(){function initBulk(formId){var form=document.getElementById(formId);if(!form)return;var boxes=Array.prototype.slice.call(form.querySelectorAll(".kcf-row-checkbox"));var master=form.querySelector(".kcf-select-all");var buttons=Array.prototype.slice.call(form.querySelectorAll(".kcf-bulk-delete"));function anyChecked(){return boxes.some(function(cb){return cb.checked;});}function updateButtons(){var active=anyChecked();buttons.forEach(function(btn){btn.disabled=!active;});if(master){var total=boxes.length;var checked=boxes.filter(function(cb){return cb.checked;}).length;master.checked=active&&checked===total;master.indeterminate=checked>0&&checked<total;}}boxes.forEach(function(cb){cb.addEventListener("change",updateButtons);});if(master){master.addEventListener("change",function(){var checked=this.checked;boxes.forEach(function(cb){cb.checked=checked;});updateButtons();});}updateButtons();form.addEventListener("submit",function(e){if(!anyChecked()||!confirm("Ar tikrai ištrinti pažymėtas žinutes?")){e.preventDefault();}});}document.addEventListener("DOMContentLoaded",function(){initBulk("kcf-messages-form");});document.addEventListener("click",function(e){if(e.target.classList&&e.target.classList.contains("kcf-del")&&!confirm("Ar tikrai ištrinti?")){e.preventDefault();}});})();</script>';
  echo $highlight_script; $pages=max(1,ceil($total/$per)); if($pages>1){ echo '<div class="tablenav"><div class="tablenav-pages">'.paginate_links(['base'=>$base.'%_%','format'=>'&paged=%#%','current'=>$paged,'total'=>$pages]).'</div></div>'; } echo '</div>'; }
function kcf_admin_inbox_page(){ if(!current_user_can('manage_options')) return; global $wpdb; $table=KCF_REPLIES_TABLE; $highlight_script='<script>(function(){document.addEventListener("DOMContentLoaded",function(){var highlightEls=document.querySelectorAll(".kcf-highlight");if(!highlightEls.length)return;setTimeout(function(){highlightEls.forEach(function(el){el.classList.add("kcf-fade-end");setTimeout(function(){el.classList.remove("kcf-fade-end");el.classList.remove("kcf-highlight");},800);});},60000);});})();</script>';$thread_bulk_script='<script>(function(){document.addEventListener("DOMContentLoaded",function(){var forms=document.querySelectorAll(".kcf-thread-bulk-form");if(!forms.length)return;forms.forEach(function(form){var boxes=Array.prototype.slice.call(form.querySelectorAll(".kcf-thread-row"));if(!boxes.length)return;var masters=Array.prototype.slice.call(form.querySelectorAll(".kcf-thread-select-all"));var buttons=Array.prototype.slice.call(form.querySelectorAll(".kcf-thread-bulk-delete"));function anyChecked(){return boxes.some(function(cb){return cb.checked;});}function sync(){var any=anyChecked();buttons.forEach(function(btn){btn.disabled=!any;});masters.forEach(function(master){var total=boxes.length;var checked=boxes.filter(function(cb){return cb.checked;}).length;master.checked=any&&checked===total;master.indeterminate=checked>0&&checked<total;});}boxes.forEach(function(cb){cb.addEventListener("change",sync);});masters.forEach(function(master){master.addEventListener("change",function(){var checked=this.checked;boxes.forEach(function(cb){cb.checked=checked;});sync();});});form.addEventListener("submit",function(e){if(!anyChecked()||!confirm("Ar tikrai ištrinti pažymėtus įrašus?")){e.preventDefault();}});sync();});});})();</script>';
  kcf_admin_maybe_check_imap(); echo '<div class="wrap"><h1>Gauti laiškai</h1><style>.kcf-highlight{background:#fff3cd!important;transition:background-color .6s ease;}.kcf-highlight.kcf-fade-end{background:#fff!important;}.kcf-unread-dot{display:inline-block;width:8px;height:8px;background:#d63638;border-radius:50%;margin-left:6px;vertical-align:middle}</style>';
  if(isset($_GET['deleted'])) echo '<div class="updated notice notice-success is-dismissible"><p>Laiškas ištrintas.</p></div>';
  if(isset($_GET['bulk_deleted'])){ $cnt=max(0,intval($_GET['bulk_deleted'])); if($cnt>1){ $msg=sprintf('%d laiškai ištrinti.',$cnt); } elseif($cnt===1){ $msg='1 laiškas ištrintas.'; } else { $msg='Neištrintas nė vienas laiškas.'; } echo '<div class="updated notice notice-success is-dismissible"><p>'.esc_html($msg).'</p></div>'; }
  if(isset($_GET['bulk_error'])) echo '<div class="error notice notice-error"><p>Pasirinkite bent vieną laišką.</p></div>';
  if(isset($_GET['replied'])){ if(intval($_GET['replied'])===1) echo '<div class="updated notice notice-success is-dismissible"><p>Atsakymas išsiųstas.</p></div>'; else echo '<div class="error notice notice-error"><p>Atsakymo išsiųsti nepavyko.</p></div>'; }
  if(isset($_GET['reply_error'])) echo '<div class="error notice notice-error"><p>Patikrinkite atsakymo laukus.</p></div>';
  $q=sanitize_text_field($_GET['q']??''); echo '<form method="get" style="margin:8px 0 12px"><input type="hidden" name="page" value="kcf-inbox"><input type="text" name="q" value="'.esc_attr($q).'" placeholder="Paieška (tema, siuntėjas, žinutė)" style="min-width:340px;margin-right:6px"><button class="button button-primary">Ieškoti</button> <a class="button" href="'.admin_url('admin.php?page=kcf-inbox').'">Išvalyti</a></form>';
  if(isset($_GET['view'])){ $id=intval($_GET['view']); $sql="SELECT * FROM {$table} WHERE id=%d AND direction=1"; $row=$wpdb->get_row($wpdb->prepare($sql,$id),ARRAY_A); if($row&&intval($row['thread_root'])>0&&intval($row['thread_root'])!==$id){ $root=intval($row['thread_root']); wp_safe_redirect(add_query_arg(['page'=>'kcf-inbox','view'=>$root],admin_url('admin.php'))); exit; }
    echo '<h2>#'.intval($id).'</h2>';
    if($row){ $thread_root=intval($row['thread_root'])>0?intval($row['thread_root']):intval($row['id']);
      $sender=$row['to_email']!==''?$row['to_email']:'—'; $subject_raw=$row['subject']!==''?$row['subject']:'(be temos)'; $subject=kcf_decode_mime_header($subject_raw);
      echo '<table class="widefat striped" style="max-width:720px"><tbody><tr><th style="width:180px">Siuntėjas</th><td>'.esc_html($sender).'</td></tr><tr><th>Tema</th><td>'.esc_html($subject).'</td></tr><tr><th>Gauta</th><td>'.esc_html($row['created_at']).'</td></tr></tbody></table>';
      $thread=$wpdb->get_results($wpdb->prepare('SELECT r.*, u.display_name FROM '.$table.' r LEFT JOIN '.$wpdb->users.' u ON u.ID=r.wp_user_id WHERE (r.id=%d OR r.thread_root=%d) ORDER BY r.id ASC',$thread_root,$thread_root),ARRAY_A);
      if($thread){
        $view_redirect=add_query_arg(['page'=>'kcf-inbox','view'=>$thread_root],admin_url('admin.php'));
        $has_inbound=false;
        foreach($thread as $item){
          if(intval($item['direction'])===1){
            $has_inbound=true;
            break;
          }
        }
        echo '<div style="margin-top:18px"><h3>Gija</h3>';
        if($has_inbound){
          echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" class="kcf-thread-bulk-form" id="kcf-inbox-thread-form">';
          echo '<input type="hidden" name="action" value="kcf_bulk_delete_inbox">';
          wp_nonce_field('kcf_bulk_delete_inbox');
          echo '<input type="hidden" name="redirect_to" value="'.esc_attr($view_redirect).'">';
          echo '<div class="kcf-thread-bulk-actions"><label><input type="checkbox" class="kcf-thread-select-all" aria-label="Pažymėti visus laiškus"> Pažymėti visus</label> <button type="submit" class="button button-small button-danger kcf-thread-bulk-delete" disabled>Trinti pažymėtus</button></div>';
        }
        echo '<ul class="kcf-thread">';
        $total=count($thread);
        $i=0;
        foreach($thread as $item){
          $i++;
          $by=intval($item['direction'])===1?'Klientas':(!empty($item['display_name'])?$item['display_name']:'Admin');
          $cls=intval($item['seen'])===0?' kcf-highlight':'';
          if($i===$total){
            $cls.=' kcf-latest';
          }
          $subj=kcf_decode_mime_header($item['subject']);
          $body_clean=function_exists('kcf_trim_body_for_admin')?kcf_trim_body_for_admin($item['body']):$item['body'];
          $checkbox='';
          if($has_inbound){
            if(intval($item['direction'])===1){
              $checkbox='<div class="kcf-thread-checkbox"><input type="checkbox" class="kcf-thread-row" name="ids[]" value="'.intval($item['id']).'" aria-label="Pažymėti laišką #'.intval($item['id']).'"></div>';
            } else {
              $checkbox='<div class="kcf-thread-checkbox"></div>';
            }
          }
          echo '<li class="kcf-thread-item'.$cls.'">'.$checkbox.'<div class="kcf-thread-body"><div><strong>'.esc_html($subj!==''?$subj:'(be temos)').'</strong></div><div><em>'.esc_html($item['created_at']).'</em> — '.esc_html($by).'</div><div style="word-break:break-word">'.wp_kses_post(nl2br(esc_html($body_clean))).'</div></div></li>';
        }
        echo '</ul>';
        if($has_inbound){
          echo '<div class="kcf-thread-bulk-actions"><label><input type="checkbox" class="kcf-thread-select-all" aria-label="Pažymėti visus laiškus"> Pažymėti visus</label> <button type="submit" class="button button-small button-danger kcf-thread-bulk-delete" disabled>Trinti pažymėtus</button></div></form>';
        }
        echo '</div>'.$thread_bulk_script.$highlight_script;
      } else {
        echo '<div style="margin-top:18px"><h3>Žinutė</h3><div style="background:#fff;border:1px solid #ddd;padding:16px;max-width:720px;word-break:break-word">'.nl2br(esc_html($row['body'])).'</div></div>';
      }
      $default_subject=$row['subject']!==''?'Re: '.kcf_decode_mime_header($row['subject']):'Atsakymas į Jūsų laišką';
      if(intval($row['message_id'])>0){ $needle='[KCF #'.intval($row['message_id']).']'; if(strpos($default_subject,$needle)===false){ $default_subject=$needle.' '.$default_subject; } }
      echo '<hr><h2>Atsakyti</h2><form method="post" action="'.esc_url(admin_url('admin-post.php?action=kcf_reply_inbox')).'" style="max-width:720px">';
      wp_nonce_field('kcf_reply_inbox');
      echo '<input type="hidden" name="inbox_id" value="'.intval($id).'">';
      echo '<p><label>Tema:<br><input type="text" name="subject" value="'.esc_attr($default_subject).'" style="width:100%" required></label></p>';
      echo '<p><label>Žinutė:<br><textarea name="body" rows="8" style="width:100%" required></textarea></label></p>';
      echo '<p><button type="submit" class="button button-primary">Siųsti atsakymą</button></p></form>';
      $delete_url=wp_nonce_url(admin_url('admin-post.php?action=kcf_delete_inbox&id='.$id),'kcf_delete_inbox'); echo '<p style="margin-top:18px"><a href="'.$delete_url.'" class="button button-danger kcf-del">Ištrinti</a> <a href="'.admin_url('admin.php?page=kcf-inbox').'" class="button">Grįžti</a></p><script>document.addEventListener("click",function(e){if(e.target.classList.contains("kcf-del")&&!confirm("Ar tikrai ištrinti šį laišką?")){e.preventDefault();}});</script>';
      kcf_mark_inbox_seen($thread_root);
    } else { echo '<p>Laiškas nerastas.</p>'; }
    echo '</div>';
    return;
  }
  $where='WHERE r.direction=1 AND (r.thread_root=0 OR r.thread_root=r.id)'; $params=[]; if($q){ $like='%'.$wpdb->esc_like($q).'%'; $where.=' AND (r.subject LIKE %s OR r.to_email LIKE %s OR r.body LIKE %s)'; $params=[$like,$like,$like]; }
  $per=20; $paged=max(1,intval($_GET['paged']??1)); $off=($paged-1)*$per; $sql_total="SELECT COUNT(*) FROM {$table} r $where"; $total=(int)($params? $wpdb->get_var($wpdb->prepare($sql_total,$params)) : $wpdb->get_var($sql_total));
  $sql_items="SELECT r.id,r.created_at,r.subject,r.to_email,r.body,r.seen FROM {$table} r $where ORDER BY r.id DESC LIMIT %d OFFSET %d";
  if($params){ $args=array_merge($params,[$per,$off]); $items=$wpdb->get_results($wpdb->prepare($sql_items,$args),ARRAY_A); }
  else { $items=$wpdb->get_results($wpdb->prepare($sql_items,$per,$off),ARRAY_A); }
  $base=admin_url('admin.php?page=kcf-inbox'); $current_url=isset($_SERVER['REQUEST_URI'])?esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])):''; echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'" id="kcf-inbox-form">'; echo '<input type="hidden" name="action" value="kcf_bulk_delete_inbox">'; wp_nonce_field('kcf_bulk_delete_inbox'); echo '<input type="hidden" name="redirect_to" value="'.esc_attr($current_url).'">'; echo '<div class="tablenav top"><div class="alignleft actions bulkactions"><button type="submit" class="button kcf-bulk-delete" disabled>Trinti pažymėtus</button></div></div>';
  echo '<table class="widefat striped" style="table-layout:fixed"><thead><tr><td id="cb" class="manage-column column-cb check-column"><input type="checkbox" class="kcf-select-all" aria-label="Pažymėti visus"></td><th style="width:70px">ID</th><th style="width:160px">Data</th><th style="width:220px">Siuntėjas</th><th style="width:220px">Tema</th><th>Žinutė</th><th style="width:140px">Veiksmai</th></tr></thead><tbody>';
  if($items){ foreach($items as $it){ $view=esc_url(add_query_arg(['view'=>$it['id']],$base)); $row_cls=intval($it['seen'])===0?' class="kcf-highlight"':''; $dot=intval($it['seen'])===0?' <span class="kcf-unread-dot" title="Neskaitytas"></span>':''; $raw_body=(string)$it['body']; $excerpt=wp_trim_words($raw_body,30,'…');
    $sender=$it['to_email']!==''?$it['to_email']:'—'; $subject_raw=$it['subject']!==''?$it['subject']:'(be temos)'; $subject=kcf_decode_mime_header($subject_raw);
    $delete_base=admin_url('admin-post.php?action=kcf_delete_inbox&id='.$it['id']); if($current_url){ $delete_base=add_query_arg('redirect_to',$current_url,$delete_base); } $del=wp_nonce_url($delete_base,'kcf_delete_inbox');
    echo '<tr'.$row_cls.'><th scope="row" class="check-column"><input type="checkbox" class="kcf-row-checkbox" name="ids[]" value="'.intval($it['id']).'" aria-label="Pažymėti laišką #'.intval($it['id']).'"></th><td><a href="'.$view.'">'.intval($it['id']).'</a>'.$dot.'</td><td>'.esc_html($it['created_at']).'</td><td style="word-break:break-word">'.esc_html($sender).'</td><td style="word-break:break-word">'.esc_html($subject).'</td><td style="word-break:break-word">'.esc_html($excerpt).'</td><td><a href="'.$view.'" class="button">Peržiūrėti</a> <a href="'.esc_url($del).'" class="button kcf-del">Ištrinti</a></td></tr>'; }
  } else { echo '<tr><td colspan="7">Nėra gautų laiškų.</td></tr>'; }
  echo '</tbody></table><div class="tablenav bottom"><div class="alignleft actions bulkactions"><button type="submit" class="button kcf-bulk-delete" disabled>Trinti pažymėtus</button></div></div></form>';
  echo '<script>(function(){document.addEventListener("click",function(e){if(e.target.classList.contains("kcf-del")&&!confirm("Ar tikrai ištrinti?")){e.preventDefault();}});document.addEventListener("DOMContentLoaded",function(){var form=document.getElementById("kcf-inbox-form");if(!form)return;var boxes=Array.prototype.slice.call(form.querySelectorAll(".kcf-row-checkbox"));var master=form.querySelector(".kcf-select-all");var buttons=Array.prototype.slice.call(form.querySelectorAll(".kcf-bulk-delete"));function anyChecked(){return boxes.some(function(cb){return cb.checked;});}function updateButtons(){var active=anyChecked();buttons.forEach(function(btn){btn.disabled=!active;});if(master){var total=boxes.length;var checked=boxes.filter(function(cb){return cb.checked;}).length;master.checked=active&&checked===total;master.indeterminate=checked>0&&checked<total;}}boxes.forEach(function(cb){cb.addEventListener("change",updateButtons);});if(master){master.addEventListener("change",function(){var checked=this.checked;boxes.forEach(function(cb){cb.checked=checked;});updateButtons();});}updateButtons();form.addEventListener("submit",function(e){if(!anyChecked()||!confirm("Ar tikrai ištrinti pažymėtus laiškus?")){e.preventDefault();}});});})();</script>';
  echo $highlight_script; $pages=max(1,ceil($total/$per)); if($pages>1){ echo '<div class="tablenav"><div class="tablenav-pages">'.paginate_links(['base'=>$base.'%_%','format'=>'&paged=%#%','current'=>$paged,'total'=>$pages]).'</div></div>'; }
  echo '</div>';
}
function kcf_admin_diagnostics_page(){ if(!current_user_can('manage_options')) return; $ret_days=intval(kcf_settings_get('log_retention_days',30)); if(isset($_POST['kcf_clear_logs'])&&check_admin_referer('kcf_clear_logs')){ delete_option('kcf_log'); echo '<div class="updated"><p>Logai ištrinti.</p></div>'; } $log=get_option('kcf_log',[]); if($log){ $kept=[]; $cutoff=strtotime('-'.$ret_days.' days'); foreach($log as $line){ $ts=strtotime(substr($line,1,19)); if(!$ts || $ts>=$cutoff) $kept[]=$line; } if(count($kept)!=count($log)) update_option('kcf_log',$kept,false); $log=$kept; } echo '<div class="wrap"><h1>Diagnostika</h1><form method="post" action="'.admin_url('admin-post.php?action=kcf_check_imap_now').'" style="margin-bottom:12px">'; wp_nonce_field('kcf_check_imap_now'); echo '<button class="button button-primary">Patikrinti IMAP dabar</button></form><form method="post" onsubmit="return confirm(\'Ištrinti visus logus?\')">'; wp_nonce_field('kcf_clear_logs'); echo '<button class="button" name="kcf_clear_logs" value="1">Ištrinti logus</button></form><h2>Paskutiniai logai</h2>'; if($log){ echo '<pre style="background:#fff;border:1px solid #ddd;padding:10px;max-height:420px;overflow:auto">'; foreach($log as $line){ echo esc_html($line)."\n"; } echo '</pre>'; } else { echo '<p>Logų nėra.</p>'; } echo '</div>'; }
?>
