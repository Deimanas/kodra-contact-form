
<?php if(!defined('ABSPATH')) exit;
function kcf_extract_kcf_id($subject,$raw,$body){
  if(preg_match('/^X-KCF-ID:\s*(\d+)/mi',$raw,$m)) return (int)$m[1];
  if(preg_match('/\[KCF\s*#(\d+)\]/',$subject,$m)) return (int)$m[1];
  if(preg_match('/<!--\s*KCF-ID:\s*(\d+)\s*-->/i',$body,$m)) return (int)$m[1];
  if(preg_match('/KCF-ID:\s*(\d+)/i',wp_strip_all_tags($body),$m)) return (int)$m[1];
  return 0;
}
add_action('kcf_check_mail_event',function(){
  if(!function_exists('imap_open')){ kcf_log('php-imap not enabled'); return; }
  $host=kcf_settings_get('imap_host'); $port=(int)kcf_settings_get('imap_port',993); $enc=kcf_settings_get('imap_encrypt','ssl');
  $user=kcf_settings_get('imap_user'); $pass=kcf_settings_get('imap_pass'); $folder=kcf_settings_get('imap_folder','INBOX');
  if(!$host||!$user||!$pass){ kcf_log('IMAP config missing'); return; }
  $mailbox=sprintf('{%s:%d/%s}%s',$host,$port,($enc==='none'?'novalidate-cert':$enc),$folder);
  $inbox=@imap_open($mailbox,$user,$pass); if(!$inbox){ kcf_log('imap_open failed'); return; }
  $emails=imap_search($inbox,'UNSEEN'); if($emails){ global $wpdb;
    foreach($emails as $no){
      $raw=imap_fetchheader($inbox,$no); $h=imap_headerinfo($inbox,$no); $subject=isset($h->subject)?imap_utf8($h->subject):'';
      $structure=imap_fetchstructure($inbox,$no); $body=''; if(isset($structure->parts)){ for($i=1;$i<=count($structure->parts);$i++){ $p=$structure->parts[$i-1]; if($p->type==0){ $c=imap_fetchbody($inbox,$no,$i); if($p->encoding==3)$c=base64_decode($c); elseif($p->encoding==4)$c=quoted_printable_decode($c); $body.=$c; } } } else { $body=imap_body($inbox,$no); }
      $id=kcf_extract_kcf_id($subject,$raw,$body); kcf_log('Email #'.strval($no).' subj="'.$subject.'" -> KCF-ID='.strval($id));
      if($id>0){ $fromaddr=(isset($h->from[0]->mailbox)&&isset($h->from[0]->host))?$h->from[0]->mailbox.'@'.$h->from[0]->host:'';
        $wpdb->insert(KCF_REPLIES_TABLE,['message_id'=>$id,'created_at'=>current_time('mysql'),'wp_user_id'=>0,'direction'=>1,'to_email'=>$fromaddr,'subject'=>$subject,'body'=>wp_strip_all_tags($body),'sent'=>1,'seen'=>0],['%d','%s','%d','%d','%s','%s','%s','%d','%d']); kcf_log('Stored inbound reply for message '.$id);
      } else { kcf_log('Skipped email #'.$no.' (no KCF-ID)'); }
      imap_setflag_full($inbox,(string)$no,"\\Seen");
    }
  } else { kcf_log('No unseen emails.'); }
  imap_close($inbox);
});
?>