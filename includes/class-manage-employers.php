<?php

namespace Interimu\Interimu;

class Manage_Employers
{
 public function wp_interimu_employers($request)
 {
    $company_name = $request['company_name'];
    $description = $request['description'];
    $logo = $_FILES["company_logo"];
    $featured = $request['featured'] == 'yes'? 'on' : 'off';
    $email = $request['email'] ? $request['email'] : '';
    $phone = $request['phone'] ? $request['phone'] : '';
    $website = $request['website'] ? $request['website'] : '';
    $company_location = $request['company_location'] ? $request['company_location'] : '';
    $company_year_founded = $request['company_year_founded'] ? $request['company_year_founded'] : '';
    $company_size = $request['company_size'] ? $request['company_size'] : '';
    $intro_video_url = $request['intro_video_url'] ? $request['intro_video_url'] : '';
    $company_categories = $request['company_categories'] ? $request['company_categories'] : [];
    $ID = $request['ID'] ? $request['ID'] : '';
    
    $response = array('statusCode'=> '200', "message" => 'created');
    $current_user = wp_get_current_user();

    if(!( $current_user instanceof \WP_User )) {
        return array('statusCode'=> '57', "message" => 'wp_job_pro_user_not_logged_in');
    }

    if(!defined('WP_JOB_BOARD_PRO_EMPLOYER_PREFIX')) {
        return array('statusCode'=> '57', "message" => 'wp_job_pro_not_activated');
    }

    if (!isset($company_name)){
        return array('statusCode'=> '57', "message" => 'no_job_title');
    }

    if (!isset($description)){
        return array('statusCode'=> '57', "message" => 'no_job_description');
    }

    // create user or get user
    $company_user_id = 0;
    $company_user = get_user_by('email', $email);
    if ($company_user) {
        $company_user_id = $company_user->ID;
    }
    else{

        // User does not exist, create a new user
        $company_user_id = wp_create_user($email, $email, $email);
        if (!is_wp_error($company_user_id)) {

            // User created successfully, add first name and last name
            wp_update_user(array(
                'ID' => $company_user_id,
                'first_name' => $company_name,
                'display_name' => $company_name,
                'role' => 'wp_job_board_pro_employer'
            ));
        }
        else{
            return array('statusCode'=> '57', "message" => 'error_creating_user');
        }
    }

    if ($ID != '') {
        $post   = get_post( $ID );
        if (!isset($post)) {
            // post does not exist/ create it
            $ID = '';
        }
    }

    if ( $ID != '') {
        $data = array(
            'post_title'     => sanitize_text_field( $company_name ),
            'post_type'      => 'employer',
            // 'post_date'      => $post_date,
            'post_content'   => wp_kses_post( $description ),
            'ID' => $ID
        );
    
        $post_id = wp_update_post( $data, true );
        if (is_wp_error($post_id)) {
            return array('statusCode'=> '57', "message" => 'failed_to_update_company');
        }
    }
    else{
        $data = array(
            'post_title'     => sanitize_text_field( $company_name ),
            'post_author'    => $current_user->ID,
            'post_status'    => "publish",
            'post_type'      => 'employer',
            'post_content'   => wp_kses_post( $description ),
        );
    
        $post_id = wp_insert_post( $data, true );
        if (is_wp_error($post_id)) {
            return array('statusCode'=> '57', "message" => 'failed_to_save_company');
        }
    }
    
    if($post_id){

        if($logo){
            $attach_id = $this->upload_image_to_media_library($logo);
            if ($attach_id ) {
                // Set post thumbnail (featured image)
                set_post_thumbnail( $post_id, $attach_id );
            }
        }
        
        update_post_meta($post_id, '_employer_featured', $featured );
        update_post_meta($post_id, '_employer_phone', $phone );
        update_post_meta($post_id, '_employer_email', $email );
        update_post_meta($post_id, '_employer_website', $website );
        update_post_meta($post_id, '_employer_founded_date', $company_year_founded );
        update_post_meta($post_id, '_employer_company_size', $company_size );
        update_post_meta($post_id, '_employer_video_url', $intro_video_url );
        update_post_meta($post_id, '_employer_user_id', $company_user_id );

        if(isset($company_location)){
            $term = term_exists($company_location, 'employer_location' ); 
        
            if (!$term) {
                $term = wp_insert_term($company_location,'employer_location');
                if ( ! is_wp_error( $term ) ) {
                    $term = $term['term_id'];
                }
            } 
    
            if ($term) {
                if (is_object($term)) {
                    $term = $term->term_id;
                }
    
                if ( is_array( $term ) ) {
                    $term = $term['term_id'];
                }
                wp_set_post_terms( $post_id,[$term], 'employer_location' );
            }
        }
        
        $company_categories = is_array($company_categories)? $company_categories : json_decode($company_categories, true);
        if(!empty($company_categories)) {
            $posts_ids = [];
            foreach ($company_categories as $company_category) {
                $term = term_exists($company_category, 'employer_category' ); 
        
                if (!$term) {
                    $term = wp_insert_term($company_category,'employer_category');
                    if ( ! is_wp_error( $term ) ) {
                        $term = $term['term_id'];
                    }
                } 
        
                if ($term) {
                    if (is_object($term)) {
                        $term = $term->term_id;
                    }
        
                    if ( is_array( $term ) ) {
                        $term = $term['term_id'];
                    }
                }

                $posts_ids[] = $term;
            }

            wp_set_post_terms( $post_id, $posts_ids , 'employer_category' );
        }
        
    }
    return $response;
 }
 
// Function to upload image to media library
function upload_image_to_media_library($uploaed_file) {
    // Get upload directory
    $upload_dir = wp_upload_dir();

    // Generate a filename
    $filename = $uploaed_file["name"];

    // Create the file path
    if (wp_mkdir_p($upload_dir['path'])) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }

    // Move the uploaded file to the destination directory
    move_uploaded_file($uploaed_file["tmp_name"], $file);

    // Check the file type
    $wp_filetype = wp_check_filetype($filename, null);

    // Prepare attachment data
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    // Insert the attachment into the media library
    $attach_id = wp_insert_attachment($attachment, $file);

    // Include image meta data
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Generate attachment meta data and update the database
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Check if attachment ID was successfully generated
    if (!is_wp_error($attach_id)) {
        // Add the attachment ID to the array
        return $attach_id;
    } 

    return false;
}

}