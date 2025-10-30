
<?php if(!defined('ABSPATH')) exit;
function kcf_contact_form_shortcode(){ wp_enqueue_style('kcf-style'); wp_enqueue_script('kcf-script'); $site=kcf_settings_get('recaptcha_site_key',''); $rec=((int)kcf_settings_get('recaptcha_enabled',0)===1)&&!empty($site); ob_start(); if($rec){ echo '<script src="https://www.google.com/recaptcha/api.js?render='.esc_attr($site).'"></script>'; } ?>
<form class="kcf-form" method="post" novalidate>
<input type="hidden" name="action" value="kcf_submit" /><input type="hidden" name="kcf_nonce" value="<?php echo esc_attr( wp_create_nonce('kcf_nonce') ); ?>" /><?php if($rec): ?><input type="hidden" name="g-recaptcha-response" class="kcf-grecaptcha" value=""><?php endif; ?>
<div class="kcf-grid">
  <div class="kcf-field"><label>Vardas *</label><input type="text" name="vardas" required placeholder="Vardas"></div>
  <div class="kcf-field"><label>Įmonė</label><input type="text" name="imone" placeholder="Įmonė"></div>
  <div class="kcf-field"><label>Telefono numeris *</label><input type="tel" name="telefonas" required inputmode="numeric" pattern="\+3706\d{7}" value="+3706" maxlength="12" placeholder="+3706xxxxxxx"></div>
  <div class="kcf-field"><label>El. Paštas *</label><input type="email" name="email" required placeholder="El. Paštas"></div>
  <div class="kcf-field kcf-field-full"><label>Žinutė *</label><textarea name="zinute" required rows="5" placeholder="Žinutė"></textarea></div>
</div>
<div class="kcf-actions"><button type="submit" class="kcf-button">Siųsti žinutę</button></div>
<?php return ob_get_clean(); } add_shortcode('kodra_contact_form','kcf_contact_form_shortcode'); ?>
