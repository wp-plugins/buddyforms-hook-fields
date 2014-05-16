<?php
/*
 Plugin Name: BuddyForms Hook Fields
 Plugin URI: http://themekraft.com
 Description: BuddyForms Hook Fields
 Version: 1.0
 Author: svenl77
 Author URI: http://themekraft.com
 Licence: GPLv3
 Network: false
 */

add_filter('buddyforms_formbuilder_fields_options', 'buddyforms_hook_options_into_formfields', 2, 3);
function buddyforms_hook_options_into_formfields($form_fields,$form_slug,$field_id){
    global $buddyforms;

    $buddyforms_options	= $buddyforms;

    $buddyforms['hooks']['form_element'] = array('no','before_the_title','after_the_title','before_the_content','after_the_content');


    $buddyforms['hooks']['form_element'] = apply_filters('buddyforms_form_element_hooks',$buddyforms['hooks']['form_element'],$form_slug);


    $form_fields['right']['html_display']		= new Element_HTML('<div class="bf_element_display_'.$form_slug.'">');

    $display = 'false';
    if(isset($buddyforms_options['buddyforms'][$form_slug]['form_fields'][$field_id]['display']))
        $display = $buddyforms_options['buddyforms'][$form_slug]['form_fields'][$field_id]['display'];

    $form_fields['right']['display']	= new Element_Select("Display? <i>This only works for the single view</i>", "buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][display]", $buddyforms['hooks']['form_element'], array('value' => $display));

    $hook = '';
    if(isset($buddyforms_options['buddyforms'][$form_slug]['form_fields'][$field_id]['hook']))
        $hook = $buddyforms_options['buddyforms'][$form_slug]['form_fields'][$field_id]['hook'];

    $form_fields['right']['hook']		= new Element_Textbox("Hook: <i>Add hook name works global</i>", "buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][hook]", array('value' => $hook));


    $display_name = 'false';
    if(isset($buddyforms_options['buddyforms'][$form_slug]['form_fields'][$field_id]['display_name']))
        $display_name = $buddyforms_options['buddyforms'][$form_slug]['form_fields'][$field_id]['display_name'];
    $form_fields['right']['display_name']		= new Element_Checkbox("Display name?","buddyforms_options[buddyforms][".$form_slug."][form_fields][".$field_id."][display_name]",array(''),array('value' => $display_name));

    $form_fields['right']['html_display_end']	= new Element_HTML('</div>');

    return $form_fields;
}

function buddyforms_form_display_element_frontend(){
    global $buddyforms, $post;

    if(is_admin())
        return;

    if (!isset($buddyforms['buddyforms']))
        return;

    $post_type = get_post_type($post);

    $form = get_post_meta( $post->ID, '_bf_form_slug', true );

    if(!isset($form))
        return;

    if (!empty($buddyforms['buddyforms'][$form]['form_fields'])) {

        foreach ($buddyforms['buddyforms'][$form]['form_fields'] as $key => $customfield) :

            $customfield_slug = $customfield['slug'];

            $customfield_value = get_post_meta($post->ID, $customfield_slug, true);

            if (isset($customfield_value)) :

                $post_meta_tmp = '<div class="post_meta ' . $customfield_slug . '">';

                if (isset($customfield['display_name']))
                    $post_meta_tmp .= '<label>' . $customfield['name'] . '</label>';


                if (is_array($customfield_value)) {
                    $meta_tmp = "<p>" . implode(',', $customfield_value) . "</p>";
                } else {
                    $meta_tmp = "<p>" . $customfield_value . "</p>";
                }


                switch ($customfield['type']) {
                    case 'Taxonomy':
                        $meta_tmp = get_the_term_list( $post->ID, $customfield['taxonomy'], "<p>", ' - ', "</p>" );
                        break;
                    case 'Link':
                        $meta_tmp = "<p><a href='" . $customfield_value . "' " . $customfield['name'] . ">" . $customfield_value . " </a></p>";
                        break;
                    default:
                        apply_filters('buddyforms_form_element_display_frontend',$customfield,$post_type);
                        break;
                }

                $post_meta_tmp .= $meta_tmp;

                $post_meta_tmp .= '</div>';

                apply_filters('buddyforms_form_element_display_frontend_before_hook',$post_meta_tmp);


                if( isset( $customfield['hook'] ) && !empty($customfield['hook'])){
                    add_action( $customfield['hook'], create_function('', 'echo  "' . addcslashes($post_meta_tmp, '"') . '";') );
                }

                if(is_single()){
                    switch ($customfield['display']) {
                        case 'before_the_title':
                            add_filter( 'the_title', create_function('', 'return "' . addcslashes($post_meta_tmp.$post->post_title, '"') . '";') );
                            break;
                        case 'after_the_title':
                            add_filter( 'the_title', create_function('', 'return "' . addcslashes($post->post_title.$post_meta_tmp, '"') . '";') );
                            break;
                        case 'before_the_content':
                            add_filter( 'the_content', create_function('', 'return "' . addcslashes($post_meta_tmp.$post->post_content, '"') . '";') );
                            break;
                        case 'after_the_content':
                            add_filter( 'the_content', create_function('', 'return "' . addcslashes($post->post_content.$post_meta_tmp, '"') . '";') );
                            break;
                    }
                }

            endif;

        endforeach;
    }
}

add_action('the_post','buddyforms_form_display_element_frontend');
