(function($){
  function getRecaptchaMode(){
    if(typeof KCF!=='undefined' && KCF && KCF.recaptcha_mode){
      return KCF.recaptcha_mode;
    }
    return 'none';
  }
  function ensureRecaptchaToken($form){
    if(getRecaptchaMode()!=='v3' || !window.grecaptcha || !KCF || !KCF.recaptcha_site_key){
      return Promise.resolve();
    }
    var action=KCF.recaptcha_action||'kcf_submit';
    return new Promise(function(resolve){
      grecaptcha.ready(function(){
        grecaptcha.execute(KCF.recaptcha_site_key,{action:action}).then(function(token){
          $form.find('input[name="g-recaptcha-response"]').val(token);
          resolve();
        });
      });
    });
  }
  function updatePhoneField($wrap){
    if(!$wrap||!$wrap.length) return;
    var $select=$wrap.find('.kcf-phone__prefix-select');
    var $number=$wrap.find('.kcf-phone__number');
    var $hidden=$wrap.find('input[name="telefonas"]');
    if(!$select.length||!$number.length||!$hidden.length) return;
    var $opt=$select.find('option:selected');
    var prefix=($select.val()||'')+'';
    var length=parseInt($opt.data('length'),10);
    if(!isFinite(length)||length<0) length=0;
    var digits=($number.val()||'').replace(/[^0-9]/g,'');
    if(length>0){
      digits=digits.slice(0,length);
      $number.attr('pattern','\\d{'+length+'}');
      $number.attr('maxlength',length);
      var placeholder=Array(length+1).join('x');
      if(placeholder){
        $number.attr('placeholder',placeholder);
      }
    } else {
      $number.removeAttr('pattern maxlength placeholder');
    }
    if($number.val()!==digits) $number.val(digits);
    var $flag=$wrap.find('.kcf-phone__flag');
    var emoji=$opt.data('emoji')||'';
    var flagCode=$opt.data('flag')||'';
    var country=$opt.data('country')||'';
    if($flag.length){
      var base='kcf-phone__flag';
      if(flagCode) base+=' kcf-phone__flag--'+flagCode;
      $flag.attr('class',base).text(emoji);
    }
    var $prefixValue=$wrap.find('.kcf-phone__prefix-value');
    if($prefixValue.length){
      $prefixValue.text(prefix||'');
    }
    var $container=$wrap.find('.kcf-phone__prefix');
    if($container.length){
      if(country){
        $container.attr('title',country);
        $container.attr('aria-label',country);
      } else {
        $container.removeAttr('title');
        $container.removeAttr('aria-label');
      }
    }
    $hidden.val(prefix+digits);
  }
  function refreshPhoneFields(context){
    var $context=context?(context.jquery?context:$(context)):$(document);
    $context.find('.kcf-phone').each(function(){
      updatePhoneField($(this));
    });
  }
  $(function(){ refreshPhoneFields(document); });
  $(document).on('input','.kcf-phone__number',function(){ updatePhoneField($(this).closest('.kcf-phone')); }).on('change','.kcf-phone__prefix-select',function(){ updatePhoneField($(this).closest('.kcf-phone')); });
  $(document).on('kcf:refresh',function(e,ctx){ refreshPhoneFields(ctx); });
  $(document).on('submit','.kcf-form',function(e){
    e.preventDefault();
    var $f=$(this),$b=$f.find('.kcf-button'),$m=$f.find('.kcf-msg');
    refreshPhoneFields($f);
    function setMessage(type,text){
      if(!$m.length) return;
      $m.removeClass('kcf-msg--success kcf-msg--error');
      if(type){
        $m.addClass('kcf-msg--'+type);
      }
      $m.text(text);
    }
    setMessage('','');
    $b.prop('disabled',true);
    console.group('KCF submit');
    console.info('Submitting to', KCF&&KCF.ajaxurl);
    var payload=$f.serialize();
    console.debug('Payload',payload);
    if(getRecaptchaMode()==='v2'){
      var $respField=$f.find('textarea[name="g-recaptcha-response"]');
      if(!$respField.length || !$respField.val()){
        console.warn('reCAPTCHA v2 response is empty');
        setMessage('error','Patvirtinkite, kad nesate robotas.');
        $b.prop('disabled',false);
        console.groupEnd();
        return;
      }
    }
    ensureRecaptchaToken($f).then(function(){
      $.ajax({
        url:KCF.ajaxurl,
        type:'POST',
        data:payload,
        success:function(r){
          console.info('Response',r);
          if(r&&r.success){
            setMessage('success', r.data&&r.data.message?r.data.message:'OK');
            $f[0].reset();
            refreshPhoneFields($f);
            $(document).trigger('kcf:refresh',$f);
          } else if(r&&r.data&&r.data.message){
            setMessage('error', r.data.message);
          } else {
            setMessage('error','Įvyko klaida.');
          }
          if(window.grecaptcha){
            if(getRecaptchaMode()==='v3'){
              $f.find('input[name="g-recaptcha-response"]').val('');
            } else if(getRecaptchaMode()==='v2'){
              try{
                grecaptcha.reset();
              }catch(err){
                console.warn('Failed to reset reCAPTCHA',err);
              }
            }
          }
        },
        error:function(xhr,status,err){
          console.error('Request error',status,err,xhr&&xhr.responseText);
          setMessage('error','Įvyko klaida.');
        },
        complete:function(){
          console.groupEnd();
          $b.prop('disabled',false);
        }
      });
    });
  });
})(jQuery);

