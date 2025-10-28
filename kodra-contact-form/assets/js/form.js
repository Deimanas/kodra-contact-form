
(function($){
  function ensureRecaptchaToken($form){ if(!window.grecaptcha||!KCF||!KCF.recaptcha_site_key) return Promise.resolve(); return new Promise(function(res){ grecaptcha.ready(function(){ grecaptcha.execute(KCF.recaptcha_site_key,{action:'kcf_submit'}).then(function(t){ $form.find('input[name="g-recaptcha-response"]').val(t); res(); }); }); }); }
  function normalizePhone(v){ v=(v||'')+''; var d=v.replace(/[^0-9]/g,''); if(d.indexOf('3706')!==0){ if(d.indexOf('706')===0){d='3706'+d.slice(3);} else if(d[0]==='6'){d='3706'+d.slice(1);} else {d='3706'+d;} } var rest=d.slice(4).slice(0,7); return '+3706'+rest; }
  $(document).on('input','input[name="telefonas"]',function(){ var before=this.value; var n=normalizePhone(before); if(this.value!==n) this.value=n; console.debug('KCF phone input',{before:before,after:n}); }).on('focus','input[name="telefonas"]',function(){ if(!this.value) this.value='+3706'; });
  $(document).on('submit','.kcf-form',function(e){ e.preventDefault(); var $f=$(this),$b=$f.find('.kcf-button'),$m=$f.find('.kcf-msg'); $m.text(''); $b.prop('disabled',true); console.group('KCF submit'); console.info('Submitting to', KCF&&KCF.ajaxurl); var payload=$f.serialize(); console.debug('Payload',payload);
    ensureRecaptchaToken($f).then(function(){ $.ajax({url:KCF.ajaxurl,type:'POST',data:payload,
      success:function(r){ console.info('Response',r); if(r&&r.success){$m.text(r.data.message||'OK');$f[0].reset();} else if(r&&r.data&&r.data.message){$m.text(r.data.message);} else {$m.text('Įvyko klaida.');} },
      error:function(xhr,status,err){ console.error('Request error',status,err,xhr&&xhr.responseText); $m.text('Įvyko klaida.'); },
      complete:function(){ console.groupEnd(); $b.prop('disabled',false); }
    }); });
  });
})(jQuery);
