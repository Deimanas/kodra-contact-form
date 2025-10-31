
(function($){
  function ensureRecaptchaToken($form){ if(!window.grecaptcha||!KCF||!KCF.recaptcha_site_key) return Promise.resolve(); return new Promise(function(res){ grecaptcha.ready(function(){ grecaptcha.execute(KCF.recaptcha_site_key,{action:'kcf_submit'}).then(function(t){ $form.find('input[name="g-recaptcha-response"]').val(t); res(); }); }); }); }
  function updatePhoneField($wrap){ if(!$wrap||!$wrap.length) return; var $select=$wrap.find('.kcf-phone__prefix-select'); var $number=$wrap.find('.kcf-phone__number'); var $hidden=$wrap.find('input[name="telefonas"]'); if(!$select.length||!$number.length||!$hidden.length) return; var $opt=$select.find('option:selected'); var prefix=($select.val()||'')+''; var length=parseInt($opt.data('length'),10); if(!isFinite(length)||length<0) length=0; var digits=($number.val()||'').replace(/[^0-9]/g,''); if(length>0){ digits=digits.slice(0,length); $number.attr('pattern','\\d{'+length+'}'); $number.attr('maxlength',length); var placeholder=Array(length+1).join('x'); if(placeholder){ $number.attr('placeholder',placeholder); } } else { $number.removeAttr('pattern maxlength placeholder'); }
    if($number.val()!==digits) $number.val(digits);
    var $flag=$wrap.find('.kcf-phone__flag'); var emoji=$opt.data('emoji')||''; var flagCode=$opt.data('flag')||''; var country=$opt.data('country')||''; if($flag.length){ var base='kcf-phone__flag'; if(flagCode) base+=' kcf-phone__flag--'+flagCode; $flag.attr('class',base).text(emoji); if(country){ $flag.attr('title',country); $flag.attr('aria-label',country); } else { $flag.removeAttr('title'); $flag.removeAttr('aria-label'); } }
    $hidden.val(prefix+digits);
  }
  function refreshPhoneFields(context){ var $context=context?(context.jquery?context:$(context)):$(document); $context.find('.kcf-phone').each(function(){ updatePhoneField($(this)); }); }
  $(function(){ refreshPhoneFields(document); });
  $(document).on('input','.kcf-phone__number',function(){ updatePhoneField($(this).closest('.kcf-phone')); }).on('change','.kcf-phone__prefix-select',function(){ updatePhoneField($(this).closest('.kcf-phone')); });
  $(document).on('kcf:refresh',function(e,ctx){ refreshPhoneFields(ctx); });
  $(document).on('submit','.kcf-form',function(e){ e.preventDefault(); var $f=$(this),$b=$f.find('.kcf-button'),$m=$f.find('.kcf-msg');
    refreshPhoneFields($f);
    function setMessage(type,text){ if(!$m.length) return; $m.removeClass('kcf-msg--success kcf-msg--error'); if(type){ $m.addClass('kcf-msg--'+type); } $m.text(text); }
    setMessage('', ''); $b.prop('disabled',true); console.group('KCF submit'); console.info('Submitting to', KCF&&KCF.ajaxurl); var payload=$f.serialize(); console.debug('Payload',payload);
    ensureRecaptchaToken($f).then(function(){ $.ajax({url:KCF.ajaxurl,type:'POST',data:payload,
      success:function(r){ console.info('Response',r); if(r&&r.success){ setMessage('success', r.data&&r.data.message?r.data.message:'OK'); $f[0].reset(); } else if(r&&r.data&&r.data.message){ setMessage('error', r.data.message); } else { setMessage('error','Įvyko klaida.'); } },
      error:function(xhr,status,err){ console.error('Request error',status,err,xhr&&xhr.responseText); setMessage('error','Įvyko klaida.'); },
      complete:function(){ console.groupEnd(); $b.prop('disabled',false); }
    }); });
  });
})(jQuery);
