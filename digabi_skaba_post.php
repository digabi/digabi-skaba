<?php

/*  Copyright 2014 Matti Lattu

    This file is part of Digabi Skaba.
 
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

// Initialise feedback receive function
add_action('parse_request', 'digabi_skaba_process_post');
add_filter('query_vars', 'digabi_skaba_register_queryvars');

function digabi_skaba_register_queryvars ($vars) {
    $vars[] = 'digabi_skaba';
    $vars[] = 'digabi_skaba_header';
    $vars[] = 'digabi_skaba_content';
    $vars[] = 'digabi_skaba_url';
    $vars[] = 'digabi_skaba_email';
    $vars[] = 'digabi_skaba_captcha_code';
    
    return $vars;
}

/*
 * Processes Digabi Skaba post data. Echoes respose to the user. Called by
 * WordPress hook "parse_request".
 * @param WP_Object $wp WordPress Object.
 */
function digabi_skaba_process_post ($wp) {
    global $DIGABI_SKABA_POST_AUTHOR_ID;
    global $DIGABI_SKABA_POST_CATEGORIES;
    global $digabi_skaba_user_messages;
    
    if (array_key_exists('digabi_skaba', $wp->query_vars) and $wp->query_vars['digabi_skaba'] == '1') {
        // This is a Digabi Skaba post
        
        if (@$_SERVER['HTTP_REFERER'] == '') {
            digabi_skaba_warning("HTTP_REFERER is empty -> throwing away");
        }
        
        $post_header = digabi_skaba_html_cleanup_text($wp->query_vars['digabi_skaba_header']);
        $post_content = digabi_skaba_html_cleanup_text($wp->query_vars['digabi_skaba_content']);
        $post_url = digabi_skaba_html_cleanup_url($wp->query_vars['digabi_skaba_url']);
        $post_email = digabi_skaba_html_cleanup_text($wp->query_vars['digabi_skaba_email']);
        
        $post_errors = FALSE;
        
        if ($post_header == '') {
            array_push($digabi_skaba_user_messages, __("Please add a header to your post", "digabi_skaba"));
            $post_errors = TRUE;
        }
        if ($post_url == '') {
            array_push($digabi_skaba_user_messages, __("Please add an URL to your post", "digabi_skaba"));
            $post_errors = TRUE;
        }
        if ($post_email == '') {
            array_push($digabi_skaba_user_messages, __("Please enter your email", "digabi_skaba"));
            $post_errors = TRUE;
        }
        if ($post_content == '') {
            array_push($digabi_skaba_user_messages, __("Please add a description to your post", "digabi_skaba"));
            $post_errors = TRUE;
        }
        
        if ($post_errors) {
            return;
        }

        $post_hash = digabi_skaba_hash($post_header, $post_url, $post_content);
        
        if (digabi_skaba_hash_exists($post_hash)) {
            // A post with similar data already exists. Stop here.
            
            return;
        }
        
        // Check catpcha code
        $seci = new Securimage();
        if ($seci->check($wp->query_vars['digabi_skaba_captcha_code']) == false) {
            // Captcha check failed
            array_push($digabi_skaba_user_messages, __("Please check the security question", "digabi_skaba"));
            return;
        }
        
        // Create post
        $wp_post_data = Array(
           'post_status' => 'publish',
           'post_type' => 'post',
           'post_author' => $DIGABI_SKABA_POST_AUTHOR_ID,
           'post_parent' => 0,
            'post_content' => '<p>'.
                '<a href="'.$post_url.'">'.$post_url.'</a>'.
                '</p>'.
                '<p>'.$post_content.'</p>',
           'post_title' => $post_header,
           // 'post_name' => $post_slug,
           'post_category' => $DIGABI_SKABA_POST_CATEGORIES,
        );
        
        // Post data
        $wp_error = NULL;
        $wp_post_id = wp_insert_post($wp_post_data, $wp_error);

        if ($wp_post_id == 0) {
           // We have an error

           digabi_skaba_warning("Could not insert WP post. ".join("; ", $wp_error->errors));
           
           array_push($digabi_skaba_user_messages, __("Unfortunately we were unable to save your entry. Please try again.", "digabi_skaba"));
        }
        else {
            digabi_skaba_warning("Inserted post");
            
            add_post_meta($wp_post_id, 'digabi_skaba_hash', $post_hash, TRUE);
            add_post_meta($wp_post_id, 'digabi_skaba_email', $post_email, TRUE);
            
            // Get WP post URL (we give this later to the user)
            $wp_post_url = get_permalink($wp_post_id);
            
            // Get the image of the $post_url
            
            // Get current upload path
            $upload_dir_info = wp_upload_dir();
            $upload_path = $upload_dir_info['path'];
            
            $attachment_path = $upload_path.'/'.$wp_post_id.'_'.'digabi_skaba.jpeg';
            
            // Use Bluga thumbnail generator service
            // More thumbnail services: http://snapcasa.com
            
            if (digabi_skaba_get_bluga_thumbnail($post_url, $attachment_path)) {
                // We have a thumbnail
                
                // Check the type of tile. We'll use this as the 'post_mime_type'.
                $filetype = wp_check_filetype( basename( $attachment_path ), null );

                $attachment = Array(
                    'guid' => $upload_dir_info['url'].'/'.basename($attachment_path),
                    'post_mime_type' => $filetype['type'],
                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $attachment_path) ),
                    'post_content' => '',
                    'post_status' => 'inherit',
                );

                // Insert the attachment.
                $attach_id = wp_insert_attachment( $attachment, $attachment_path, $wp_post_id );
                $attach_url = wp_get_attachment_url($attach_id);

                // Generate the metadata for the attachment, and update the database record.
                $attach_data = wp_generate_attachment_metadata( $attach_id, $attachment_path );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                
                // Update the post content to include final texts (image, link etc)
                $new_post = Array(
                    'ID' => $wp_post_id,
                    'post_content' => '<p>'.
                        '<a href="'.$post_url.'"><img src="'.$attach_url.'"></a><br/>'. // This gets resized
                        '<a href="'.$post_url.'">'.$post_url.'</a>'.
                        '</p>'.
                        '<p>'.$post_content.'</p>',
                );

                wp_update_post($new_post);

                // Set post thumbnail
                // For some reason, the featured images are REALLY big. Maybe we should
                // blame the theme?
                set_post_thumbnail($wp_post_id, $attach_id);
                

            }
            else {
                digabi_skaba_warning("Could not get bluga thumbnail from URL ".$post_url);
            }
            if ($wp_post_url) {
                array_push($digabi_skaba_user_messages, sprintf(__("Your %scontest entry</a> was saved.", 'digabi_skaba'), '<a href="'.$wp_post_url.'">'));
            }
            else {
                // Post was saved but we don't know the URL ... huh?
                array_push($digabi_skaba_user_messages, __("Your contest entry was saved.", 'digabi_skaba'));
            }
        }
    }
}




?>
