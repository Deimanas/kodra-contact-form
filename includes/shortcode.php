
<?php if(!defined('ABSPATH')) exit;
function kcf_contact_form_shortcode(){ wp_enqueue_style('kcf-style'); wp_enqueue_script('kcf-script'); $site=kcf_settings_get('recaptcha_site_key',''); $rec=((int)kcf_settings_get('recaptcha_enabled',0)===1)&&!empty($site); $prefixes=kcf_phone_prefixes(); if(empty($prefixes)||!is_array($prefixes)){ $prefixes=[[ 'value'=>'+3706','country'=>'Lietuva','flag'=>'lt','emoji'=>'🇱🇹','length'=>7 ]]; }
  $default=current($prefixes); if(!$default){ $default=['value'=>'+3706','country'=>'Lietuva','flag'=>'lt','emoji'=>'🇱🇹','length'=>7]; }
  $default_value=isset($default['value'])?(string)$default['value']:'+3706'; $default_flag=isset($default['flag'])?(string)$default['flag']:''; $default_emoji=isset($default['emoji'])?(string)$default['emoji']:'🇱🇹'; $default_country=isset($default['country'])?(string)$default['country']:'';
  $default_length=isset($default['length'])?max(intval($default['length']),0):7; $default_pattern=$default_length>0?'\d{'.$default_length.'}':''; $default_placeholder=$default_length>0?str_repeat('x',$default_length):'';
  ob_start(); if($rec){ echo '<script src="https://www.google.com/recaptcha/api.js?render='.esc_attr($site).'"></script>'; } ?>
<form class="kcf-form" method="post" novalidate>
<input type="hidden" name="action" value="kcf_submit" /><input type="hidden" name="kcf_nonce" value="<?php echo esc_attr( wp_create_nonce('kcf_nonce') ); ?>" /><?php if($rec): ?><input type="hidden" name="g-recaptcha-response" class="kcf-grecaptcha" value=""><?php endif; ?>
<div class="kcf-grid">
  <div class="kcf-field"><label>Vardas *</label><input type="text" name="vardas" required placeholder="Vardas"></div>
  <div class="kcf-field"><label>Įmonė</label><input type="text" name="imone" placeholder="Įmonė"></div>
  <div class="kcf-field"><label>Telefono numeris *</label>
    <div class="kcf-phone">
      <div class="kcf-phone__prefix">
        <span class="kcf-phone__flag<?php if($default_flag!==''): ?> kcf-phone__flag--<?php echo esc_attr($default_flag); ?><?php endif; ?>" aria-hidden="true"<?php if($default_country!==''): ?> title="<?php echo esc_attr($default_country); ?>"<?php endif; ?>><?php echo esc_html($default_emoji); ?></span>
        <select name="telefonas_prefix" class="kcf-phone__prefix-select" required>
          <?php foreach($prefixes as $prefix): $value=isset($prefix['value'])?(string)$prefix['value']:'+3706'; $country=isset($prefix['country'])?(string)$prefix['country']:''; $flag=isset($prefix['flag'])?(string)$prefix['flag']:''; $emoji=isset($prefix['emoji'])?(string)$prefix['emoji']:''; $length=isset($prefix['length'])?intval($prefix['length']):0; $label=trim($value.' '.$country); ?>
            <option value="<?php echo esc_attr($value); ?>" data-flag="<?php echo esc_attr($flag); ?>" data-emoji="<?php echo esc_attr($emoji); ?>" data-country="<?php echo esc_attr($country); ?>" data-length="<?php echo esc_attr($length); ?>" <?php selected($value,$default_value); ?>><?php echo esc_html($label!==''?$label:$value); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <input type="tel" name="telefonas_number" class="kcf-phone__number" required inputmode="numeric"<?php if($default_pattern): ?> pattern="<?php echo esc_attr($default_pattern); ?>"<?php endif; ?><?php if($default_length>0): ?> maxlength="<?php echo esc_attr($default_length); ?>"<?php endif; ?><?php if($default_placeholder!==''): ?> placeholder="<?php echo esc_attr($default_placeholder); ?>"<?php endif; ?>>
      <input type="hidden" name="telefonas" value="<?php echo esc_attr($default_value); ?>">
    </div>
  </div>
  <div class="kcf-field"><label>El. Paštas *</label><input type="email" name="email" required placeholder="El. Paštas"></div>
  <div class="kcf-field kcf-field-full"><label>Žinutė *</label><textarea name="zinute" required rows="5" placeholder="Žinutė"></textarea></div>
</div>
<div class="kcf-actions"><button type="submit" class="kcf-button">Siųsti žinutę</button></div>
<div class="kcf-msg" role="status" aria-live="polite"></div>
<?php return ob_get_clean(); } add_shortcode('kodra_contact_form','kcf_contact_form_shortcode'); ?>
