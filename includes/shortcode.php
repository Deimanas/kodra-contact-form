<?php if(!defined('ABSPATH')) exit;
function kcf_contact_form_shortcode(){
  wp_enqueue_style('kcf-style');
  wp_enqueue_script('kcf-script');
  $recaptcha=kcf_get_recaptcha_config();
  $rec_mode=$recaptcha['mode'];
  $site=$recaptcha['site_key'];
  $rec_active=$rec_mode!=='none' && $site!=='';
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
  if(empty($prefixes)||!is_array($prefixes)){
    $prefixes=[[ 'value'=>'+3706','country'=>'Lietuva','flag'=>'lt','emoji'=>'🇱🇹','length'=>7 ]];
  }
  $default=current($prefixes);
  if(!$default){
    $default=['value'=>'+3706','country'=>'Lietuva','flag'=>'lt','emoji'=>'🇱🇹','length'=>7];
  }
  $default_value=isset($default['value'])?(string)$default['value']:'+3706';
  $default_flag=isset($default['flag'])?(string)$default['flag']:'';
  $default_emoji=isset($default['emoji'])?(string)$default['emoji']:'🇱🇹';
  $default_country=isset($default['country'])?(string)$default['country']:'';
  $default_length=isset($default['length'])?max(intval($default['length']),0):0;
  $default_pattern=$default_length>0?'\d{'.$default_length.'}':'';
  $default_placeholder=$default_length>0?str_repeat('x',$default_length):'';
  $button=$layout['button'];
  $button_text=$button['text']!==''?$button['text']:'Siųsti žinutę';
  $button_class='kcf-button'.($button['classes']!==''?' '.$button['classes']:'');
  $actions_class='kcf-actions'.($button['wrapper_class']!==''?' '.$button['wrapper_class']:'');
  $button_style=$button['style']!==''?' style="'.esc_attr($button['style']).'"':'';
  $autocomplete_map=['vardas'=>'name','email'=>'email','imone'=>'organization'];
  ob_start();
  if($rec_mode==='v3' && $rec_active){
    echo '<script src="https://www.google.com/recaptcha/api.js?render='.esc_attr($site).'" async defer></script>';
  } elseif($rec_mode==='v2' && $rec_active){
    echo '<script src="https://www.google.com/recaptcha/api.js?hl=lt" async defer></script>';
  }
  ?>
  <form class="kcf-form" method="post" novalidate>
    <input type="hidden" name="action" value="kcf_submit" />
    <input type="hidden" name="kcf_nonce" value="<?php echo esc_attr( wp_create_nonce('kcf_nonce') ); ?>" />
    <?php if($rec_mode==='v3' && $rec_active): ?><input type="hidden" name="g-recaptcha-response" class="kcf-grecaptcha" value=""><?php endif; ?>
    <div class="kcf-grid">
      <?php foreach($fields as $field):
        $key=isset($field['key'])?$field['key']:'';
        $name=isset($field['name'])?$field['name']:$key;
        $type=isset($field['type'])?$field['type']:'text';
        $required=!empty($field['required']);
        $label=kcf_format_field_label($field,true);
        $placeholder=isset($field['placeholder'])?$field['placeholder']:'';
        $wrapper_class='kcf-field';
        if(isset($field['width'])&&$field['width']==='full'){
          $wrapper_class.=' kcf-field-full';
        }
        if(!empty($field['wrapper_class'])){
          $wrapper_class.=' '.$field['wrapper_class'];
        }
        $input_class='';
        if(!empty($field['input_class'])){
          $input_class=' '.$field['input_class'];
        }
        ?>
        <div class="<?php echo esc_attr($wrapper_class); ?>">
          <label><?php echo esc_html($label); ?></label>
          <?php if($type==='phone'):
            $number_name=isset($field['number_name'])?$field['number_name']:'telefonas_number';
            $prefix_name=isset($field['prefix_name'])?$field['prefix_name']:'telefonas_prefix';
            $hidden_name=isset($field['hidden_name'])?$field['hidden_name']:'telefonas';
            $display_placeholder=$default_placeholder!==''?$default_placeholder:$placeholder;
            ?>
            <div class="kcf-phone">
              <div class="kcf-phone__prefix"<?php if($default_country!==''): ?> title="<?php echo esc_attr($default_country); ?>" aria-label="<?php echo esc_attr($default_country); ?>"<?php endif; ?>>
                <span class="kcf-phone__flag<?php if($default_flag!==''): ?> kcf-phone__flag--<?php echo esc_attr($default_flag); ?><?php endif; ?>" aria-hidden="true"><?php echo esc_html($default_emoji); ?></span>
                <span class="kcf-phone__prefix-value" aria-hidden="true"><?php echo esc_html($default_value); ?></span>
                <select name="<?php echo esc_attr($prefix_name); ?>" class="kcf-phone__prefix-select" required>
                  <?php foreach($prefixes as $prefix):
                    $value=isset($prefix['value'])?(string)$prefix['value']:'+3706';
                    $country=isset($prefix['country'])?(string)$prefix['country']:'';
                    $flag=isset($prefix['flag'])?(string)$prefix['flag']:'';
                    $emoji=isset($prefix['emoji'])?(string)$prefix['emoji']:'';
                    $length=isset($prefix['length'])?intval($prefix['length']):0;
                    $option_label=trim($emoji.' '.$value);
                    ?>
                    <option value="<?php echo esc_attr($value); ?>" data-flag="<?php echo esc_attr($flag); ?>" data-emoji="<?php echo esc_attr($emoji); ?>" data-country="<?php echo esc_attr($country); ?>" data-length="<?php echo esc_attr($length); ?>"<?php if($country!==''): ?> title="<?php echo esc_attr($country); ?>"<?php endif; ?> <?php selected($value,$default_value); ?>><?php echo esc_html($option_label!==''?$option_label:$value); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <input type="tel" name="<?php echo esc_attr($number_name); ?>" class="kcf-phone__number<?php echo esc_attr($input_class); ?>"<?php if($required): ?> required<?php endif; ?> inputmode="numeric"<?php if($default_pattern): ?> pattern="<?php echo esc_attr($default_pattern); ?>"<?php endif; ?><?php if($default_length>0): ?> maxlength="<?php echo esc_attr($default_length); ?>"<?php endif; ?><?php if($display_placeholder!==''): ?> placeholder="<?php echo esc_attr($display_placeholder); ?>"<?php endif; ?>>
              <input type="hidden" name="<?php echo esc_attr($hidden_name); ?>" value="<?php echo esc_attr($default_value); ?>">
            </div>
          <?php elseif($type==='textarea'):
            $rows=isset($field['rows'])?max(1,intval($field['rows'])):5;
            ?>
            <textarea name="<?php echo esc_attr($name); ?>" class="kcf-input<?php echo esc_attr($input_class); ?>" rows="<?php echo esc_attr($rows); ?>"<?php if($placeholder!==''): ?> placeholder="<?php echo esc_attr($placeholder); ?>"<?php endif; ?><?php if($required): ?> required<?php endif; ?>></textarea>
          <?php else:
            $input_type='text';
            $inputmode='';
            if($type==='email'){
              $input_type='email';
              $inputmode='email';
            } elseif($type==='tel'){
              $input_type='tel';
              $inputmode='tel';
            } elseif($type==='number'){
              $input_type='number';
              $inputmode='decimal';
            }
            $autocomplete='';
            if(isset($autocomplete_map[$key])){
              $autocomplete=$autocomplete_map[$key];
            }
            ?>
            <input type="<?php echo esc_attr($input_type); ?>" name="<?php echo esc_attr($name); ?>" class="kcf-input<?php echo esc_attr($input_class); ?>"<?php if($placeholder!==''): ?> placeholder="<?php echo esc_attr($placeholder); ?>"<?php endif; ?><?php if($required): ?> required<?php endif; ?><?php if($inputmode!==''): ?> inputmode="<?php echo esc_attr($inputmode); ?>"<?php endif; ?><?php if($input_type==='number'): ?> step="any"<?php endif; ?><?php if($autocomplete!==''): ?> autocomplete="<?php echo esc_attr($autocomplete); ?>"<?php endif; ?>>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php if($rec_mode==='v2' && $rec_active): ?>
      <div class="kcf-recaptcha kcf-recaptcha--v2">
        <div class="g-recaptcha" data-sitekey="<?php echo esc_attr($site); ?>"></div>
      </div>
    <?php endif; ?>
    <div class="<?php echo esc_attr($actions_class); ?>">
      <button type="submit" class="<?php echo esc_attr($button_class); ?>"<?php echo $button_style; ?>><?php echo esc_html($button_text); ?></button>
    </div>
    <div class="kcf-msg" role="status" aria-live="polite"></div>
  </form>
  <?php
  return ob_get_clean();
}
add_shortcode('kodra_contact_form','kcf_contact_form_shortcode');
?>
