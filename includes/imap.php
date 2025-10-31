<?php if(!defined('ABSPATH')) exit;

function kcf_extract_kcf_id($subject,$raw,$body){
  if(preg_match('/^X-KCF-ID:\s*(\d+)/mi',$raw,$m)) return (int)$m[1];
  if(preg_match('/\[KCF\s*#(\d+)\]/',$subject,$m)) return (int)$m[1];
  if(preg_match('/<!--\s*KCF-ID:\s*(\d+)\s*-->/i',$body,$m)) return (int)$m[1];
  if(preg_match('/KCF-ID:\s*(\d+)/i',wp_strip_all_tags($body),$m)) return (int)$m[1];
  return 0;
}

function kcf_extract_inbox_id($raw,$body){
  if(preg_match('/^X-KCF-INBOX-ID:\s*(\d+)/mi',$raw,$m)) return (int)$m[1];
  if(preg_match('/<!--\s*KCF-INBOX-ID:\s*(\d+)\s*-->/i',$body,$m)) return (int)$m[1];
  if(preg_match('/KCF-INBOX-ID:\s*(\d+)/i',wp_strip_all_tags($body),$m)) return (int)$m[1];
  return 0;
}

function kcf_imap_normalize_body($body){
  $text=wp_strip_all_tags($body);
  $text=preg_replace('/^\s*KCF-(?:ID|INBOX-ID):\s*\d+\s*$/mi','',$text);
  $text=preg_replace("/\n{3,}/","\n\n",$text);
  return trim($text);
}

function kcf_imap_part_charset($part){
  foreach(['parameters','dparameters'] as $set){
    if(isset($part->$set)&&is_array($part->$set)){
      foreach($part->$set as $param){
        if(isset($param->attribute,$param->value) && strtolower($param->attribute)==='charset'){
          return (string)$param->value;
        }
      }
    }
  }
  return '';
}

function kcf_imap_decode_text_part($inbox,$no,$partNumber,$part){
  $section=$partNumber!==''?$partNumber:'1';
  $data=($section==='1'&&(!isset($part->bytes)||$part->bytes==0))?imap_body($inbox,$no):imap_fetchbody($inbox,$no,$section);
  $encoding=isset($part->encoding)?(int)$part->encoding:0;
  if($encoding===3){
    $data=base64_decode($data);
  } elseif($encoding===4){
    $data=quoted_printable_decode($data);
  }
  $charset=kcf_imap_part_charset($part);
  if($charset!==''){
    $data=kcf_convert_charset_to_utf8($data,$charset);
  }
  return $data;
}

function kcf_imap_collect_text($inbox,$no,$structure=null,$prefix=''){
  if(!$structure){
    $structure=imap_fetchstructure($inbox,$no);
    if(!$structure){
      return imap_body($inbox,$no);
    }
  }
  $body='';
  if(isset($structure->parts)&&is_array($structure->parts)&&count($structure->parts)){
    foreach($structure->parts as $index=>$part){
      $partNumber=$prefix!==''?$prefix.'.'.($index+1):(string)($index+1);
      if(isset($part->type)&&$part->type==0){
        $body.=kcf_imap_decode_text_part($inbox,$no,$partNumber,$part);
      } elseif(isset($part->parts)){
        $body.=kcf_imap_collect_text($inbox,$no,$part,$partNumber);
      }
    }
  } else {
    if(isset($structure->type)&&$structure->type==0){
      $body.=kcf_imap_decode_text_part($inbox,$no,$prefix,$structure);
    }
  }
  if($body===''){
    $body=imap_body($inbox,$no);
  }
  return $body;
}

add_action('kcf_check_mail_event',function(){
  if(!function_exists('imap_open')){ kcf_log('php-imap not enabled'); return; }
  $host=kcf_settings_get('imap_host');
  $port=(int)kcf_settings_get('imap_port',993);
  $enc=kcf_settings_get('imap_encrypt','ssl');
  $user=kcf_settings_get('imap_user');
  $pass=kcf_settings_get('imap_pass');
  $folder=kcf_settings_get('imap_folder','INBOX');
  if(!$host||!$user||!$pass){ kcf_log('IMAP config missing'); return; }
  $mailbox=sprintf('{%s:%d/%s}%s',$host,$port,($enc==='none'?'novalidate-cert':$enc),$folder);
  $inbox=@imap_open($mailbox,$user,$pass);
  if(!$inbox){ kcf_log('imap_open failed'); return; }
  $emails=imap_search($inbox,'UNSEEN');
  if($emails){
    global $wpdb;
    foreach($emails as $no){
      $raw=imap_fetchheader($inbox,$no);
      $h=imap_headerinfo($inbox,$no);
      $subject_raw=isset($h->subject)?$h->subject:'';
      $subject=sanitize_text_field(kcf_decode_mime_header($subject_raw));
      $body=kcf_imap_collect_text($inbox,$no);
      if(!is_string($body)) $body='';
      $id=kcf_extract_kcf_id($subject,$raw,$body);
      $inbox_ref=kcf_extract_inbox_id($raw,$body);
      kcf_log('Email #'.strval($no).' subj="'.$subject.'" -> KCF-ID='.strval($id).($inbox_ref?('; INBOX='.$inbox_ref):''));
      $fromaddr='';
      if(isset($h->fromaddress)&&$h->fromaddress){
        $parsed=imap_rfc822_parse_adrlist($h->fromaddress,'');
        if(!empty($parsed)&&isset($parsed[0]->mailbox,$parsed[0]->host)){
          $fromaddr=$parsed[0]->mailbox.'@'.$parsed[0]->host;
        }
      }
      if(!$fromaddr && isset($h->from[0]->mailbox,$h->from[0]->host)){
        $fromaddr=$h->from[0]->mailbox.'@'.$h->from[0]->host;
      }
      $message_id=$id>0?$id:0;
      if($message_id===0 && $inbox_ref>0){
        $linked=$wpdb->get_var($wpdb->prepare('SELECT message_id FROM '.KCF_REPLIES_TABLE.' WHERE id=%d',$inbox_ref));
        if($linked){
          $message_id=(int)$linked;
        }
      }
      $stored_body=kcf_imap_normalize_body($body);
      $thread_root=0;
      if($inbox_ref>0){
        $thread_root=$inbox_ref;
      } elseif($message_id>0){
        $thread_root=$message_id;
      }
      $wpdb->insert(
        KCF_REPLIES_TABLE,
        [
          'message_id'=>$message_id,
          'thread_root'=>$thread_root,
          'created_at'=>current_time('mysql'),
          'wp_user_id'=>0,
          'direction'=>1,
          'to_email'=>$fromaddr,
          'subject'=>$subject,
          'body'=>$stored_body,
          'sent'=>1,
          'seen'=>0,
        ],
        ['%d','%d','%s','%d','%d','%s','%s','%s','%d','%d']
      );
      $reply_id=$wpdb->insert_id;
      if(!$thread_root && $reply_id){
        $wpdb->update(KCF_REPLIES_TABLE,['thread_root'=>$reply_id],['id'=>$reply_id],['%d'],['%d']);
      }
      if($message_id>0){
        kcf_log('Stored inbound reply for message '.$message_id);
      } else {
        kcf_log('Stored inbound email without KCF-ID in inbox');
      }
      imap_setflag_full($inbox,(string)$no,"\\Seen");
    }
  } else {
    kcf_log('No unseen emails.');
  }
  imap_close($inbox);
});

?>
