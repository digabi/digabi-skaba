<?php
/*
Plugin Name: Digabi Skaba
Plugin URI: https://github.com/digabi/digabi-feedback
Description: Allows authenticated users to write posts using web form
Version: 1.0
Author: Matti Lattu
License: GPL3
Text Domain: digabi_skaba
*/

/*  Copyright 2014 Matti Lattu
 
    Digabi Skaba is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 
*/

// Add hook for internationalisation, also loads global variables
// from settings.php
add_action('init', 'digabi_skaba_activate_i18n');

include_once(ABSPATH.'wp-admin/includes/plugin.php' );
include_once(dirname(__FILE__).'/settings.php');
include_once(dirname(__FILE__).'/digabi_skaba_functions.php');
include_once(dirname(__FILE__).'/digabi_skaba_post.php');

// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
require_once(ABSPATH . 'wp-admin/includes/image.php' );

// Include Securimage for Captcha function
if (!class_exists('Securimage')) {
    // Securimage was not loaded by another plugin
    include_once(dirname(__FILE__).'/securimage/securimage.php');
}

/*
 * @global array $digabi_skaba_user_messages Messages to show to user
 */
$digabi_skaba_user_messages = Array();

/**
 * Initialises Wordpress I18N support as explained at
 * http://codex.wordpress.org/I18n_for_WordPress_Developers
 */
function digabi_skaba_activate_i18n () {
    $plugin_dir = basename(dirname(__FILE__));
    load_plugin_textdomain('digabi_skaba', FALSE, $plugin_dir.'/lang/');
    digabi_skaba_set_global_settings();
}

// Add hook to show the Digabi Skaba submission form
add_filter('the_content', 'digabi_skaba_form');

/**
 * Replace the post content by hardware data retrieved from custom fields.
 * This function is called by filter hook 'the_content'.
 * @global WP_Query $wp_query
 * @global WP_Post $post
 * @global array $digabi_skaba_user_messages
 * @global string $DIGABI_SKABA_CSS
 * @param type $post_content Initial post content.
 * @return string Changed post content
 */
function digabi_skaba_form ($post_content) {
	global $wp_query;
	global $post;
   global $digabi_skaba_user_messages;
   global $DIGABI_SKABA_CSS;
   
   if ($post->post_type == 'page' or $post->post_type == 'post') {
       // Define HW data array here so you don't have to re-read it if
       // you have more than one menu in a single page
       $hw_post_data = NULL;
       
       // Do we have user messages?
       if (count($digabi_skaba_user_messages) > 0) {
           // Yep, add a <ul> group to the top of the $post content
           $new_post_content = '<ul>';
           foreach ($digabi_skaba_user_messages as $this_user_message) {
               $new_post_content .= '<li>'.$this_user_message.'</li>';
           }
           $new_post_content .= '</ul>';
           
           $post_content = $new_post_content . $post_content;
       }
       
       if (preg_match('/\[digabi_skaba_form\]/i', $post_content)) {
           // The post/page contains our magic shortcode

           $securimage_url = get_bloginfo('wpurl')."/wp-content/plugins/digabi_skaba/securimage/securimage_show.php";
           
           $form_header = digabi_skaba_html_cleanup_text(get_query_var('digabi_skaba_header'));
           $form_content = digabi_skaba_html_cleanup_text(get_query_var('digabi_skaba_content'));
           $form_url = digabi_skaba_html_cleanup_url(get_query_var('digabi_skaba_url'));
           $form_email = digabi_skaba_html_cleanup_url(get_query_var('digabi_skaba_email'));

           $shortcode_replacement = $DIGABI_SKABA_CSS;
           $shortcode_replacement .= "<form name='digabi_skaba_form'><input type='hidden' name='digabi_skaba' value='1'>";

           $shortcode_replacement .= wp_nonce_field('digabi_skaba_form_submission', 'digabi_skaba_form', true, false);

           $shortcode_replacement .= "<table>";
           $shortcode_replacement .= "<tr><td class='digabi_skaba_cell_left'>".__("Header",'digabi_skaba')."</td><td class='digabi_skaba_cell_right'><input type='text' name='digabi_skaba_header' size='32' maxlength='32' value='".$form_header."'><br/><span class='digabi_skaba_legend'>".__("The header will be used in the final post.",'digabi_skaba')."</span></td></tr>";
           $shortcode_replacement .= "<tr><td class='digabi_skaba_cell_left'>".__("Web Address",'digabi_skaba')."</td><td class='digabi_skaba_cell_right'><input type='text' name='digabi_skaba_url' size='32' maxlength='256' value='".$form_url."'><br/><span class='digabi_skaba_legend'>".__("The web address to your contest entry.",'digabi_skaba')."</span></td></tr>";
           $shortcode_replacement .= "<tr><td class='digabi_skaba_cell_left'>".__("Description",'digabi_skaba')."</td><td class='digabi_skaba_cell_right'><textarea name='digabi_skaba_content' rows='3' cols='40' wrap='soft'>".$form_content."</textarea><br/><span class='digabi_skaba_legend'>".__("Please summarise your contest entry in few words. Dont't forget that the web address will be included to the final post. You don't have to repeat the actual entry here.",'digabi_skaba')."</span></td></tr>";             
           $shortcode_replacement .= "<tr><td class='digabi_skaba_cell_left'>".__("Your Email",'digabi_skaba')."</td><td class='digabi_skaba_cell_right'><input type='text' name='digabi_skaba_email' size='32' maxlength='256' value='".$form_email."'><br/><span class='digabi_skaba_legend'>".__("We need your email to contact you. It will not be published.",'digabi_skaba')."</span></td></tr>";
           $shortcode_replacement .= "<tr><td class='digabi_skaba_cell_left'>".__("Security question",'digabi_skaba')."</td><td class='digabi_skaba_cell_right'><img id='digabi_skaba_captcha' src='".$securimage_url."' alt='CAPTCHA Image' /><a href=\"#\" onclick=\"document.getElementById('digabi_skaba_captcha').src = '".$securimage_url."?' + Math.random(); return false\">[".__("Different Image",'digabi_skaba')."]</a><br/><input type='text' name='digabi_skaba_captcha_code' size='10' maxlength='6' /><br/><div class='digabi_skaba_legend'>".__("Please enter the characters shown in the image",'digabi_skaba')."</span></td></tr>\n";
           $shortcode_replacement .= "<tr><td colspan='2' class='digabi_skaba_cell_wide'><input id='digabihw_submit_button' type='button' value='".__("Submit your entry",'digabi_skaba')."' onclick='javascript:digabihw_submit_entry();'></td></tr>";

           $shortcode_replacement .= "<script language='JavaScript'>\n";
           $shortcode_replacement .= "function digabihw_submit_entry() {\n";
           $shortcode_replacement .= "if (document.forms['digabi_skaba_form'].digabi_skaba_header.value == '') { alert('".__("Check your header!",'digabi_skaba')."');return;}\n";
           $shortcode_replacement .= "if (document.forms['digabi_skaba_form'].digabi_skaba_url.value == '') { alert('".__("Check your URL!",'digabi_skaba')."');return;}\n";            
           $shortcode_replacement .= "if (document.forms['digabi_skaba_form'].digabi_skaba_content.value == '') { alert('".__("Check your description!",'digabi_skaba')."');return;}\n";
           $shortcode_replacement .= "if (document.forms['digabi_skaba_form'].digabi_skaba_email.value == '') { alert('".__("Check your email!",'digabi_skaba')."');return;}\n";
           $shortcode_replacement .= "var the_button = document.getElementById('digabihw_submit_button');\n";
           $shortcode_replacement .= "the_button.value='".__("Please wait...",'digabi_skaba')."';\n";
           $shortcode_replacement .= "the_button.disabled = true;\n";
           $shortcode_replacement .= "document.forms['digabi_skaba_form'].submit();\n";
           $shortcode_replacement .= "}\n";
           $shortcode_replacement .= "</script>\n";

           $shortcode_replacement .= '</table>';

           $shortcode_replacement .= "</form>";
           $post_content = preg_replace('/\[digabi_skaba_form\]/i', $shortcode_replacement, $post_content);
       }
   }

	// This was not our post type, return unchanged content
	return $post_content;
}


?>
