<?php if(!defined('ABSPATH')) exit;

function kcf_form_builder_sanitize_locked_field($base,$input){
  if(!is_array($base)) return [];
  $input=is_array($input)?$input:[];
  $label=isset($input['label'])?sanitize_text_field(wp_unslash($input['label'])):$base['label'];
  if($label==='') $label=$base['label'];
  $placeholder=isset($input['placeholder'])?sanitize_text_field(wp_unslash($input['placeholder'])):$base['placeholder'];
  $required=!empty($input['required'])?1:0;
  $width_raw=isset($input['width'])?strtolower(sanitize_text_field(wp_unslash($input['width']))):$base['width'];
  $width=in_array($width_raw,['half','full'],true)?$width_raw:$base['width'];
  $wrapper=kcf_sanitize_class_list(isset($input['wrapper_class'])?wp_unslash($input['wrapper_class']):'');
  $input_class=kcf_sanitize_class_list(isset($input['input_class'])?wp_unslash($input['input_class']):'');
  $order=isset($input['order'])?intval($input['order']):$base['order'];
  $data=[
    'label'=>$label,
    'placeholder'=>$placeholder,
    'required'=>$required,
    'width'=>$width,
    'wrapper_class'=>$wrapper,
    'input_class'=>$input_class,
    'order'=>$order,
  ];
  if($base['type']==='textarea'){
    $rows=isset($input['rows'])?intval($input['rows']):$base['rows'];
    if($rows<=0) $rows=$base['rows'];
    $data['rows']=$rows;
  }
  return $data;
}

function kcf_form_builder_sanitize_custom_field($input,$existing_keys){
  if(!is_array($input)) return null;
  $key=isset($input['key'])?sanitize_key(wp_unslash($input['key'])):'';
  if($key==='') return null;
  if(isset($existing_keys[$key])) return null;
  $name=isset($input['name'])?sanitize_key(wp_unslash($input['name'])):$key;
  if($name==='') $name=$key;
  $type=isset($input['type'])?strtolower(sanitize_text_field(wp_unslash($input['type']))):'text';
  $allowed=['text','email','tel','textarea','number'];
  if(!in_array($type,$allowed,true)) $type='text';
  $label=isset($input['label'])?sanitize_text_field(wp_unslash($input['label'])):'';
  if($label==='') $label=ucfirst($key);
  $placeholder=isset($input['placeholder'])?sanitize_text_field(wp_unslash($input['placeholder'])):'';
  $required=!empty($input['required'])?1:0;
  $width_raw=isset($input['width'])?strtolower(sanitize_text_field(wp_unslash($input['width']))):'half';
  $width=in_array($width_raw,['half','full'],true)?$width_raw:'half';
  $wrapper=kcf_sanitize_class_list(isset($input['wrapper_class'])?wp_unslash($input['wrapper_class']):'');
  $input_class=kcf_sanitize_class_list(isset($input['input_class'])?wp_unslash($input['input_class']):'');
  $order=isset($input['order'])?intval($input['order']):0;
  $rows=1;
  if($type==='textarea'){
    $rows=isset($input['rows'])?intval($input['rows']):5;
    if($rows<=0) $rows=5;
  }
  $field=[
    'key'=>$key,
    'name'=>$name,
    'type'=>$type,
    'label'=>$label,
    'placeholder'=>$placeholder,
    'required'=>$required,
    'width'=>$width,
    'wrapper_class'=>$wrapper,
    'input_class'=>$input_class,
    'order'=>$order,
    'rows'=>$rows,
    'locked'=>0,
  ];
  return $field;
}

add_action('admin_menu',function(){
  add_submenu_page('kcf-messages','Formos laukai','Formos laukai','manage_options','kcf-form-layout','kcf_admin_form_layout_page');
});

function kcf_admin_form_layout_page(){
  if(!current_user_can('manage_options')) return;
  $defaults=kcf_form_layout_defaults();
  $messages=[];
  $errors=[];
  if(isset($_POST['kcf_form_layout_submit'])){
    check_admin_referer('kcf_form_layout');
    $fields_input=isset($_POST['fields'])&&is_array($_POST['fields'])?$_POST['fields']:[];
    $custom_input=isset($_POST['custom_fields'])&&is_array($_POST['custom_fields'])?$_POST['custom_fields']:[];
    $button_input=isset($_POST['button'])&&is_array($_POST['button'])?$_POST['button']:[];
    $stored_fields=[];
    foreach($defaults['fields'] as $base){
      $key=$base['key'];
      $incoming=$fields_input[$key]??[];
      $stored_fields[$key]=kcf_form_builder_sanitize_locked_field($base,$incoming);
    }
    $existing_keys=[];
    foreach($defaults['fields'] as $base){
      $existing_keys[$base['key']]=true;
    }
    $custom_fields=[];
    $custom_error=false;
    foreach($custom_input as $item){
      $san=kcf_form_builder_sanitize_custom_field($item,$existing_keys);
      if($san){
        $custom_fields[]=$san;
        $existing_keys[$san['key']]=true;
      } else {
        $custom_error=true;
      }
    }
    if($custom_error){
      $errors[]='Vienas ar daugiau pasirinktinių laukų nebuvo įrašyti dėl neteisingų reikšmių ar pasikartojančių ID.';
    }
    $button_settings=kcf_normalize_button_settings($button_input);
    if(empty($button_settings['text'])){
      $button_settings['text']=$defaults['button']['text'];
    }
    if(empty($button_settings['classes'])){
      $button_settings['classes']=$defaults['button']['classes'];
    }
    $payload=[
      'fields'=>$stored_fields,
      'custom_fields'=>$custom_fields,
      'button'=>$button_settings,
    ];
    update_option('kcf_form_layout',$payload,false);
    if(!$errors){
      $messages[]='Formos nustatymai išsaugoti.';
    }
  }
  $layout=kcf_get_form_layout();
  echo '<div class="wrap"><h1>Formos laukų nustatymai</h1>';
  foreach($messages as $msg){
    echo '<div class="updated notice notice-success is-dismissible"><p>'.esc_html($msg).'</p></div>';
  }
  foreach($errors as $err){
    echo '<div class="notice notice-warning"><p>'.esc_html($err).'</p></div>';
  }
  echo '<form method="post">';
  wp_nonce_field('kcf_form_layout');
  echo '<p>Šiame lange galite keisti kontaktinės formos laukų pavadinimus, placeholder tekstus, išdėstymą bei pridėti papildomus laukus.</p>';
  echo '<style>.kcf-builder-section{background:#fff;border:1px solid #dcdcde;padding:20px;margin-top:20px;box-shadow:0 1px 1px rgba(0,0,0,0.04);}';
  echo '.kcf-builder-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;}';
  echo '.kcf-builder-grid label{display:block;font-weight:500;}';
  echo '.kcf-builder-grid input[type="text"],.kcf-builder-grid input[type="number"],.kcf-builder-grid select{width:100%;}';
  echo '.kcf-builder-row{border:1px solid #ccd0d4;padding:16px;margin-top:16px;background:#f9f9f9;position:relative;}';
  echo '.kcf-builder-row h3{margin-top:0;}';
  echo '.kcf-builder-row .kcf-remove-field{position:absolute;top:16px;right:16px;}';
  echo '.kcf-builder-actions{margin-top:12px;}';
  echo '.kcf-builder-inline{display:flex;gap:12px;align-items:center;flex-wrap:wrap;}';
  echo '</style>';
  echo '<div class="kcf-builder-section"><h2>Pagrindiniai laukai</h2>';
  foreach($layout['fields'] as $field){
    $key=$field['key'];
    $type=$field['type'];
    $config=$layout['fields'];
    echo '<div class="kcf-builder-row">';
    echo '<h3>'.esc_html($field['label']!==''?$field['label']:$field['key']).' <small style="font-weight:normal">('.esc_html($key).')</small></h3>';
    echo '<div class="kcf-builder-grid">';
    echo '<label>Etiketė<input type="text" name="fields['.esc_attr($key).'][label]" value="'.esc_attr($field['label']).'"></label>';
    echo '<label>Placeholder<input type="text" name="fields['.esc_attr($key).'][placeholder]" value="'.esc_attr($field['placeholder']).'"></label>';
    echo '<label>Plotis<select name="fields['.esc_attr($key).'][width]"><option value="half"'.selected($field['width'],'half',false).'>Pusė pločio</option><option value="full"'.selected($field['width'],'full',false).'>Pilnas plotis</option></select></label>';
    echo '<label>Eiliškumas<input type="number" name="fields['.esc_attr($key).'][order]" value="'.esc_attr($field['order']).'"></label>';
    echo '<label>Apvado klasės<input type="text" name="fields['.esc_attr($key).'][wrapper_class]" value="'.esc_attr($field['wrapper_class']).'"></label>';
    echo '<label>Įvesties klasės<input type="text" name="fields['.esc_attr($key).'][input_class]" value="'.esc_attr($field['input_class']).'"></label>';
    if($type==='textarea'){
      echo '<label>Eilučių skaičius<input type="number" name="fields['.esc_attr($key).'][rows]" value="'.esc_attr($field['rows']).'" min="1"></label>';
    }
    echo '</div>';
    echo '<div class="kcf-builder-actions"><label><input type="checkbox" name="fields['.esc_attr($key).'][required]" value="1"'.checked($field['required'],1,false).'> Privalomas laukas</label>';
    if($type==='phone'){
      echo '<p><em>Šis laukas naudoja valstybių prefiksų pasirinkimą.</em></p>';
    }
    echo '</div>';
    echo '</div>';
  }
  echo '</div>';
  echo '<div class="kcf-builder-section"><h2>Papildomi laukai</h2>';
  echo '<p>Čia galite pridėti papildomus laukus. Lauko ID turi būti unikalus ir naudojamas duomenų bazėje.</p>';
  echo '<div id="kcf-custom-fields">';
  if(!empty($layout['custom_fields'])){
    foreach($layout['custom_fields'] as $idx=>$field){
      echo kcf_form_builder_render_custom_row($idx,$field);
    }
  }
  echo '</div>';
  echo '<p><button type="button" class="button" id="kcf-add-field">Pridėti lauką</button></p>';
  echo '</div>';
  $button=$layout['button'];
  echo '<div class="kcf-builder-section"><h2>Pateikimo mygtukas</h2>';
  echo '<div class="kcf-builder-grid">';
  echo '<label>Mygtuko tekstas<input type="text" name="button[text]" value="'.esc_attr($button['text']).'"></label>';
  echo '<label>Mygtuko klasės<input type="text" name="button[classes]" value="'.esc_attr($button['classes']).'"></label>';
  echo '<label>Mygtuko konteinerio klasės<input type="text" name="button[wrapper_class]" value="'.esc_attr($button['wrapper_class']).'"></label>';
  echo '<label>Inline stiliai<input type="text" name="button[style]" value="'.esc_attr($button['style']).'" placeholder="pvz. background:#000; color:#fff;"></label>';
  echo '</div>';
  echo '</div>';
  echo '<p><button type="submit" class="button button-primary" name="kcf_form_layout_submit" value="1">Išsaugoti</button></p>';
  echo '</form>';
  echo '<script type="text/template" id="kcf-custom-field-template">'.kcf_form_builder_render_custom_row('__INDEX__').'</script>';
  echo '<script>(function(){document.addEventListener("DOMContentLoaded",function(){var container=document.getElementById("kcf-custom-fields");var tpl=document.getElementById("kcf-custom-field-template");if(!container||!tpl)return;var counter=container.querySelectorAll(".kcf-builder-row").length;function bindRow(row){if(!row)return;row.querySelectorAll(".kcf-custom-type").forEach(function(select){var holder=row.querySelector(".kcf-rows");if(holder){holder.style.display=select.value==="textarea"?"block":"none";}select.addEventListener("change",function(){if(holder){if(this.value==="textarea"){holder.style.display="block";}else{holder.style.display="none";}}});});}container.querySelectorAll(".kcf-builder-row").forEach(bindRow);var addBtn=document.getElementById("kcf-add-field");if(addBtn){addBtn.addEventListener("click",function(){var html=tpl.innerHTML.replace(/__INDEX__/g,counter);counter++;var wrap=document.createElement(\'div\');wrap.innerHTML=html.trim();var node=wrap.firstElementChild;if(node){container.appendChild(node);bindRow(node);}});}container.addEventListener("click",function(e){if(e.target.classList.contains("kcf-remove-field")){e.preventDefault();var row=e.target.closest(\'.kcf-builder-row\');if(row){row.remove();}}});});})();</script>';
  echo '</div>';
}

function kcf_form_builder_render_custom_row($index,$field=[]){
  $field=is_array($field)?$field:[];
  $key=$field['key']??'';
  $name=$field['name']??$key;
  $type=$field['type']??'text';
  $label=$field['label']??'';
  $placeholder=$field['placeholder']??'';
  $required=!empty($field['required']);
  $width=$field['width']??'half';
  $wrapper=$field['wrapper_class']??'';
  $input_class=$field['input_class']??'';
  $order=$field['order']??0;
  $rows=$field['rows']??($type==='textarea'?5:1);
  $options='<div class="kcf-builder-row"><button type="button" class="button-link-delete kcf-remove-field">Šalinti</button>';
  $options.='<h3>Papildomas laukas</h3><div class="kcf-builder-grid">';
  $options.='<label>Lauko ID<input type="text" name="custom_fields['.esc_attr($index).'][key]" value="'.esc_attr($key).'"></label>';
  $options.='<label>HTML name atributas<input type="text" name="custom_fields['.esc_attr($index).'][name]" value="'.esc_attr($name).'"></label>';
  $options.='<label>Etiketė<input type="text" name="custom_fields['.esc_attr($index).'][label]" value="'.esc_attr($label).'"></label>';
  $options.='<label>Placeholder<input type="text" name="custom_fields['.esc_attr($index).'][placeholder]" value="'.esc_attr($placeholder).'"></label>';
  $options.='<label>Tipas<select name="custom_fields['.esc_attr($index).'][type]" class="kcf-custom-type">';
  $types=['text'=>'Tekstas','email'=>'El. paštas','tel'=>'Telefonas','textarea'=>'Daugiataškis','number'=>'Skaičius'];
  foreach($types as $value=>$label_text){
    $selected=$value===$type?' selected':'';
    $options.='<option value="'.esc_attr($value).'"'.$selected.'>'.esc_html($label_text).'</option>';
  }
  $options.='</select></label>';
  $options.='<label>Plotis<select name="custom_fields['.esc_attr($index).'][width]">';
  $options.='<option value="half"'.($width==='half'?' selected':'').'>Pusė pločio</option><option value="full"'.($width==='full'?' selected':'').'>Pilnas plotis</option>';
  $options.='</select></label>';
  $options.='<label>Eiliškumas<input type="number" name="custom_fields['.esc_attr($index).'][order]" value="'.esc_attr($order).'"></label>';
  $options.='<label>Apvado klasės<input type="text" name="custom_fields['.esc_attr($index).'][wrapper_class]" value="'.esc_attr($wrapper).'"></label>';
  $options.='<label>Įvesties klasės<input type="text" name="custom_fields['.esc_attr($index).'][input_class]" value="'.esc_attr($input_class).'"></label>';
  $options.='<label class="kcf-rows"'.($type==='textarea'?'':' style="display:none"').'>Eilučių skaičius<input type="number" name="custom_fields['.esc_attr($index).'][rows]" value="'.esc_attr($rows).'" min="1"></label>';
  $options.='</div><div class="kcf-builder-actions"><label><input type="checkbox" name="custom_fields['.esc_attr($index).'][required]" value="1"'.($required?' checked':'').'> Privalomas</label></div></div>';
  return $options;
}

?>
