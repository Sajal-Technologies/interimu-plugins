<?php

namespace Interimu\Interimu;

class Manage_Jobs
{
 public function wp_interimu_register_routes()
 {
    $manager_employeers = new Manage_Employers();

    register_rest_route(
        'api/v1',
        '/jobs/',
        array(
            'methods'  => 'POST',
            'callback' => [$this, 'wp_interimu_jobs'],
            'permission_callback'=> '__return_true'
        )
    );

    register_rest_route(
        'api/v1',
        '/employers/',
        array(
            'methods'  => 'POST',
            'callback' => [$manager_employeers, 'wp_interimu_employers'],
            'permission_callback'=> '__return_true'
        )
    );
 }

 public function wp_interimu_jobs($request)
 {
   
    $response = array('statusCode'=> '200', "message" => 'created');

    $post_id     = '';
    $title       = $request['title'];
    $description = $request['description'];
    $post_status = $request['post_status'] ? $request['post_status']: "publish";
    $id          = $request['id']? $request['id']:'';
    $job_type    = $request['job_type'] ? $request['job_type']: ['CONTRACTOR'] ;
    $employer    = null;
    $post_author = null;
    $employer_id = null;
    $employer_email = $request['employer_mail'];
    $job_location   = $request['job_location'] ? $request['job_location'] : '';

    // This is the location that reflects on the map
    $map_location   = $request['job_map_location'];
    
    $map_location_data = [];

    if(!defined('WP_JOB_BOARD_PRO_JOB_ALERT_PREFIX')) {
        return array('statusCode'=> '57', "message" => 'wp_job_pro_not_activated');
    }

    if (!isset($title)){
        return array('statusCode'=> '57', "message" => 'no_job_title');
    }

    if (!isset($description)){
        return array('statusCode'=> '57', "message" => 'no_job_description');
    }
   
    if (!isset($employer_email)){
        return array('statusCode'=> '57', "message" => 'no_employer_email');
    }
    
    if (isset($employer_email)){
        // get the employer details
        $args = array(
            'post_type' => 'employer',
            'posts_per_page' => -1,
            'meta_key' => '_employer_email',
            'meta_value' => $employer_email
        );
        $employer = get_posts( $args );
        if (!empty($employer)) {
            $employer_id = $employer[0]->ID;
        }else {
            $post_author = get_user_by('email',sanitize_text_field($employer_email));
            if(!$post_author){
                return array('statusCode'=> '57', "message" => 'no_employer_found');
            }
            if($post_author){
                $employer_id = get_user_meta($post_author->ID, 'employer_id', true);
                if(!$employer_id){
                    return array('statusCode'=> '57', "message" => 'no_employer_found');
                }
            }
        }
    }

    if (isset($map_location)){
        if (is_string($map_location)) {
            $map_location_data = json_decode($map_location,true);
        }else{
            $map_location_data = $map_location;
        }
    }

    if ($id != '') {
        $post   = get_post( $id );
        if (!isset($post)) {
            // post does not exist/ create it
            $id = '';
        }
    }

    if ( $id != '') {
        $data = array(
            'post_title'     => sanitize_text_field( $title ),
            'post_author'    => $post_author->ID,
            'post_status'    => $post_status,
            'post_type'      => 'job_listing',
            // 'post_date'      => $post_date,
            'post_content'   => wp_kses_post( $description ),
            'ID' => $id
        );
    
        $post_id = wp_update_post( $data, true );
        if (is_wp_error($post_id)) {
            return array('statusCode'=> '57', "message" => 'failed_to_save_job');
        }

        $response = array('statusCode'=> '200', "message" => 'updated');

    }else{
        $data = array(
            'post_title'     => sanitize_text_field( $title ),
            'post_author'    => $post_author->ID,
            'post_status'    => $post_status,
            'post_type'      => 'job_listing',
            // 'post_date'      => $post_date,
            'post_content'   => wp_kses_post( $description ),
        );
        $post_id = wp_insert_post( $data, true );
        if (is_wp_error($post_id)) {
            return array('statusCode'=> '57', "message" => 'failed_to_save_job');
        }
    }
    
    if($post_id != ''){
        
        $featured = $request['featured'];
        $urgent   = $request['urgent'];
        $application_deadline_date = $request['application_deadline_date'] ? date( 'Y-m-d', strtotime( sanitize_text_field( $request['application_deadline_date'] ) ) ) : '' ;
        // $gender = $request['gender'] ? $request['gender']: '' ;
        $salary_type = $request['salary_type'] ? $request['salary_type']: 'hourly' ;
        $max_salary = $request['max_salary'] ? $request['max_salary']: '0' ;
        $min_salary = $request['min_salary'] ? $request['min_salary']: '0' ;
        $experience = $request['experience'] ? $request['experience']: '' ;
        // $career_level = $request['career_level'] ? $request['career_level']: '' ;
        $qualification = $request['qualification'] ? $request['qualification']: '' ;
        // $video_url = $request['video_url'] ? $request['video_url']: '' ;
        $friendly_address = $request['friendly_address'] ? $request['friendly_address']: '' ;
        $filled = $request['filled'];
        $job_starts = $request['job_starts'] ? $request['job_starts']: '' ;
        $job_start_date = $request['job_start_date'] ? $request['job_start_date']: '' ;
        $hours_per_week = $request['hours_per_week'] ? $request['hours_per_week']: '' ;  
        $duration = $request['duration'] ? $request['duration']: '' ;
        $extend_option = $request['extend_option'] ? $request['extend_option']: '' ;
        $start_assignment = $request['start_assignment'] ? $request['start_assignment']: '' ;
        $educational_attainment = $request['educational_attainment'] ? $request['educational_attainment']: '' ;
        $job_categories = $request['job_categories'] ? $request['job_categories']: [];
        $job_tags =  $request['job_tags'] ? $request['job_tags']: [] ;
        $freelance = $request['freelance']? $request['freelance'] : 'no';
        $detachering = $request['detachering']? $request['detachering'] : 'no';
        $source   = $request['source'] ? $request['source']: '';
        
        update_post_meta($post_id,'_job_employer_posted_by', $employer_id);
        
        if (strtolower($featured) == 'yes') {
            update_post_meta($post_id, '_job_featured', $featured );
        }else{
            delete_post_meta($post_id, '_job_featured');
        }

        if ($urgent == 'yes') {
            update_post_meta($post_id, '_job_urgent', $urgent );
        }else{
            delete_post_meta($post_id, '_job_urgent');
        }

        if ($filled == 'yes') {
            update_post_meta($post_id, '_job_filled', $filled );
        }else{
            delete_post_meta($post_id, '_job_filled');
        }
        
        if (strtolower($freelance) == 'yes') {
            update_post_meta($post_id, '_job_freelance', $freelance );
        }else{
            delete_post_meta($post_id, '_job_freelance');
        }
        
        if (strtolower($detachering) == 'yes') {
            update_post_meta($post_id, '_job_detachering', $detachering );
        }else{
            delete_post_meta($post_id, '_job_detachering');
        }

        update_post_meta($post_id, '_job_expiry_date', $application_deadline_date );
        // update_post_meta($post_id, '_job_gender', $gender );
        update_post_meta($post_id, '_job_apply_type', 'with_email');
        update_post_meta($post_id, '_job_apply_email',get_option('admin_email'));
        update_post_meta($post_id, '_job_salary_type', $salary_type);
        update_post_meta($post_id, '_job_salary', $max_salary);
        update_post_meta($post_id, '_job_max_salary', $max_salary);
        update_post_meta($post_id, '_job_min_salary', $min_salary);
        update_post_meta($post_id, '_job_experience', $experience );
        // update_post_meta($post_id, '_job_career_level', $career_level );
        update_post_meta($post_id, '_job_qualification', $qualification );
        // update_post_meta($post_id, '_job_video_url', $video_url );
        update_post_meta($post_id, '_job_application_deadline_date', $application_deadline_date );
        //update_post_meta($post_id, '_job_address', $friendly_address );
        // update_post_meta($post_id, '_job_map_location', $map_location );
        update_post_meta($post_id, '_job_source', $source );
       
        // Custom fields
        update_post_meta($post_id,"custom-select-31844018",$job_starts);
        update_post_meta($post_id,"custom-date-31256218",$job_start_date);
        update_post_meta($post_id,"custom-text-31383555",$hours_per_week);
        update_post_meta($post_id,"custom-text-31766635",$duration);
        update_post_meta($post_id,"custom-text-32553764",$extend_option);
        update_post_meta($post_id,"custom-date-36173761",$start_assignment);
        update_post_meta($post_id,"custom-text-34612698",$educational_attainment);

        // types
        $type_ids = [];
        
        if($job_type){

            if (is_string($job_type)) {
                $job_type = explode(",", $job_type);
            }

            foreach ($job_type as $type) {
                $term = term_exists($type, 'job_listing_type' );
                if (!$term) {
                    $term = wp_insert_term($type,'job_listing_type');
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
                    $type_ids[] = $term;
                }
            }
            wp_set_post_terms( $post_id, $type_ids, 'job_listing_type' );
        }
        
        // job_categories
        $job_categories_ids = [];
        if($job_categories){
            if (is_string($job_categories)) {
                $job_categories = explode(",", $job_categories);
            }
            foreach ($job_categories as $key => $category) { 
                $term = term_exists($category, 'job_listing_category' ); 
                if ($term) {
                    if (is_object($term)) {
                        $term = $term->term_id;
                    }
                    if ( is_array( $term ) ) {
                        $term = $term['term_id'];
                    }
                    $job_categories_ids[] = $term;
                }
            }
            wp_set_post_terms( $post_id, $job_categories_ids, 'job_listing_category' );
        }

        // if region is not set on the map location, update map region value to the location provided on the api
        if(isset($job_location) && !isset($map_location_data['addressRegion']) ){
            $map_location_data['addressRegion'] = $job_location;
        }
        
        // this is the location details that appears on the google rich text
        $map_location_data['streetAddress']   = $map_location_data['streetAddress'] ? $map_location_data['streetAddress'] : '';
		$map_location_data['addressLocality'] = $map_location_data['addressLocality'] ? $map_location_data['addressLocality'] : '';
		$map_location_data['addressRegion']   = $map_location_data['addressRegion'] ? $map_location_data['addressRegion'] : '';
		$map_location_data['postalCode']      = $map_location_data['postalCode'] ? $map_location_data['postalCode'] : '';
		$map_location_data['addressCountry']  = $map_location_data['addressCountry'] ? $map_location_data['addressCountry'] : '';
        
        $place_adr = '';

        if(isset($map_location_data['streetAddress']) && !empty($map_location_data['streetAddress'])){
            $place_adr = $map_location_data['streetAddress'];
        }

        if(isset($map_location_data['addressLocality']) && !empty($map_location_data['addressLocality'])){
            if(!empty($place_adr)){
                $place_adr = $place_adr. ', '.$map_location_data['addressLocality'];
            }else{
                $place_adr = $map_location_data['addressLocality'];
            }
        }

        if(isset($map_location_data['addressRegion']) && !empty($map_location_data['addressRegion'])){
            if(!empty($place_adr)){
                $place_adr = $place_adr. ', '.$map_location_data['addressRegion'];
            }else{
                $place_adr = $map_location_data['addressRegion'];
            }
        }

        if(isset($map_location_data['postalCode']) && !empty($map_location_data['postalCode'])){
            if(!empty($place_adr)){
                $place_adr = $place_adr. ', '.$map_location_data['postalCode'].'.';
            }
        }

        $map_properties = [
            'house_number' => '',
            'latitude' => '',
            'longitude' => '',
            'formatted_address' => $place_adr
        ];

        $address_data = [
            'latitude' => '',
            'longitude' => '',
            'address' => $place_adr
        ];

        $map_properties['road']         = $map_location_data['streetAddress'];
		$map_properties['city']         = $map_location_data['addressLocality'];
		$map_properties['state']        = $map_location_data['addressRegion'];
		$map_properties['postcode']     = $map_location_data['postalCode'];
		$map_properties['country_code'] = $map_location_data['addressCountry'];
    
        $address_data['streetAddress']   = $map_location_data['streetAddress'];
		$address_data['addressLocality'] = $map_location_data['addressLocality'];
		$address_data['addressRegion']   = $map_location_data['addressRegion'];
		$address_data['postalCode']      = $map_location_data['postalCode'];
		$address_data['addressCountry']  = $map_location_data['addressCountry'];
        
        // job_locations
        update_post_meta($post_id, '_job_map_location_latitude',''); 
        update_post_meta($post_id, '_job_map_location_longitude','');
        update_post_meta($post_id, '_job_map_location_address', $address_data['address'] );
		update_post_meta($post_id, '_job_map_location', $address_data );
		update_post_meta($post_id, '_job_map_location_properties', $map_properties); 

        // if location is not set but region is set on the mao location, update job laction based on the map location provided on the api
        if(!isset($job_location) && isset($map_location_data['addressRegion']) ){
            $job_location = [$map_location_data['addressRegion']];
        }
        
        if (isset($job_location)) {
            $term = term_exists($job_location, 'job_listing_location' ); 
            if (!$term) {
                $term = wp_insert_term($job_location,'job_listing_location');
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
                wp_set_post_terms( $post_id,[$term], 'job_listing_location' );
            }
        }
        
        // job_tags
        if ($job_tags) {
            if (is_string($job_tags)) {
                $job_tags = explode(",", $job_tags);
            }
            foreach ($job_tags as $key => $tag) {
                wp_set_object_terms($post_id, $tag, 'job_listing_tag', true);
            }
        }

        // save Images
        $uploaded_images = $_FILES["photos"];
        if (!empty($uploaded_images)) {
            //Loop through uploaded images
            foreach ($uploaded_images["name"] as $key => $image_name) {
                $attachment_id = $this->upload_image_to_media_library($key, $_FILES);
                
                if ($attachment_id) {
                    // Attach image to post as meta
                    $this->attach_image_to_post($attachment_id, $post_id);
                }
            }
        }

        // Update Yoast seo job meta fields
        if(isset($request['seo_focus_keyword'])){
            $this->update_yoast_meta_data($post_id,'focuskw',$request['seo_focus_keyword']);
        }
        if(isset($request['seo_keyword_synonyms'])){
            $this->update_yoast_meta_data($post_id,'keywordsynonyms',$request['seo_keyword_synonyms']);
        }
        if(isset($request['seo_meta_description'])){
            $this->update_yoast_meta_data($post_id,'metadesc',$request['seo_meta_description']);
        }
    }

    return $response;
 }

    // Function to upload image to media library
    function upload_image_to_media_library($key, $files) {
        // Get upload directory
        $upload_dir = wp_upload_dir();

        // Generate a filename
        $filename = $files["photos"]["name"][$key];

        // Create the file path
        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        // Move the uploaded file to the destination directory
        move_uploaded_file($files["photos"]["tmp_name"][$key], $file);

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

    // Function to attach image to post as meta
    function attach_image_to_post($attachment_id, $post_id) {
        $job_photos = get_post_meta($post_id, '_job_photos', true);
        if (!$job_photos) {
            $job_photos = array();
        }
        $job_photos[$attachment_id] = wp_get_attachment_url($attachment_id);
        update_post_meta($post_id, '_job_photos', $job_photos);
    }

    function update_custom_field_by_field_name($post_id,$field_name,$field_value){
        $obj_job_meta  = \WP_Job_Board_Pro_Job_Listing_Meta::get_instance($post_id);
        $custom_fields = $obj_job_meta->get_post_metas();
        
        if ( !empty($custom_fields) ) {
            foreach ($custom_fields as $field) {
                if($field['name']){
                    if($field['name'] == $field_name){
                        $field_id = $field['id'];
                        update_post_meta($post_id,$field_id,$field_value);
                    }
                }
            }
        }
    }
   
    public static function update_yoast_meta_data($post_id,$field_id,$field_value) {
        if ( is_plugin_active( 'wordpress-seo/wp-seo.php' ) ) {
            require_once( WP_PLUGIN_DIR . '/wordpress-seo/inc/class-wpseo-meta.php' );
            require_once( WP_PLUGIN_DIR . '/wordpress-seo/inc/class-wpseo-utils.php' );
            $meta_key = \WPSEO_Meta::$meta_prefix .$field_id;

            if ( $field_id === 'focuskw' ) {
                $search = [
                    '&lt;',
                    '&gt;',
                    '&#96',
                    '<',
                    '>',
                    '`',
                ];
                $field_value = str_replace( $search, '',$field_value);
            }
            
            if(in_array($field_id,['focuskw','metadesc'])){
                if (is_string($field_value) ) {
                    $field_value = \WPSEO_Utils::sanitize_text_field($field_value);
                }
            }

            if($field_id == 'keywordsynonyms'){
                if (is_string($field_value) ) {
                    $field_value = \WPSEO_Utils::sanitize_text_field($field_value);
                    $field_value = \WPSEO_Utils::format_json_encode(array(trim($field_value)));
                }
            }  
            update_post_meta($post_id,$meta_key,$field_value);
        }
	}
    
    public function edit_jobs_filter_date($options)
    {
         // Remove '1hour', '24hours', and '30days' options
        $options = array_filter( $options, function( $option ) {
            return ! in_array( $option['value'], array( '1hour', '24hours', '30days' ) );
        } );

        // Add '3days' option at the start
        array_unshift( $options, array(
            'value' => '3days',
            'text'  => __( 'Last 3 days', 'wp-job-board-pro' ),
        ) );

        return $options;
    }

    public function apply_jobs_filter_date($query, $params)
    {
        if (!empty($params) && $params['filter-date-posted'] == '3days') {
            $query['date_query'] = array(
                'after'     => '-3 days',  
                 'inclusive' => true,
            );
        }
        
        return $query;
    }
    
    function filter_listing_structured_data($data, $post ){
        if(!is_admin()){
            $data['baseSalary']['value']['minValue'] = 0;
            unset($data['baseSalary']['value']['value']);
        }
        return $data;
    }
    
    function update_job_listing_post_meta($meta_id, $post_id, $meta_key='', $meta_value='') {
        $post = get_post($post_id);
         if($post->post_type == 'job_listing'){
            if($meta_key=='_job_max_salary') {
                update_post_meta($post_id, '_job_salary',$meta_value);
            }
        }
    }

    function filter_job_types($employment_types , $post){
        if(empty($employment_types)){
            $employment_types[] = 'CONTRACTOR';
        }
        return $employment_types;
    }
    
    function get_max_salary_html($price, $post_id, $html = true ) {
		if ( $html ) {
            $meta_obj = \WP_Job_Board_Pro_Job_Listing_Meta::get_instance($post_id);
            if ( !$meta_obj->check_post_meta_exist('max_salary') ) {
                return 'Marktconform';
            }
            $price = $meta_obj->get_post_meta( 'max_salary' );
            if( empty( $price ) || ! is_numeric( $price ) ) {
                return 'Marktconform';
            }
		}
		return 'Marktconform';
	}

    function get_salary_html($price_html, $post_id ) {
        if (strpos($price_html,'Marktconform') !== false) {
            return 'Marktconform';
        } 
		return $price_html;
	}

    function output_job_listing_structured_data($output_structured_data, $post ){
       return true;
    }

    function override_jobs_post_type_slug() {
        $args = get_post_type_object( 'job_listing' ); // Change 'post' to the name of the post type you want to override
    
        if ( $args ) {
            $args->rewrite = array(
                'slug' => 'opdracht', // Replace 'new-slug' with your desired slug
            );
    
            register_post_type( 'job_listing', $args );
        }
    }
    
    
    public function redirect_candidate_to_home()
    {
        if (is_user_logged_in() && !current_user_can('manage_options') ) {
            // Redirect the user to the home page
            wp_redirect(home_url());
            exit;
        }
    }
    
    public function job_listing_query_args($query_args, $filter_params)
    {

        $meta_query = array();

        if (!empty($filter_params) && isset($filter_params['filter-title'])) {
            if(!empty($query_args) && isset($query_args['meta_query'])){
                $meta_query = $query_args['meta_query'];
            }

            $meta_query[] = array(
				'key'       => WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX . 'filled',
				'compare'   => 'NOT EXISTS',
			);

            $meta_query[] = array(
				'key'       => WP_JOB_BOARD_PRO_JOB_LISTING_PREFIX . 'expiry_date',
                'value'     => date('Y-m-d'),
				'compare'   => '>=',
                'type'      => 'DATE'
			);
        }

        if (!empty($meta_query)) {
            $query_args['meta_query'] = $meta_query;
        }

        // order jobs by date
        $query_args['orderby'] = ["date" => 'DESC'];

        return $query_args;
    }
    
    public function job_listing_get_salary_html($price_html, $post_id)
    {
        $html = true;
        $min_salary = get_post_meta( $post_id, '_job_min_salary', true );
		$max_salary = get_post_meta( $post_id, '_job_max_salary', true );

        if ( $min_salary == '0' ) {
			$min_salary = 0;
		} elseif ( empty( $min_salary ) || ! is_numeric( $min_salary ) ) {
			$min_salary = 0;
		}

        if ( $max_salary == '0' ) {
			$max_salary = 0;
		} elseif ( empty( $max_salary ) || ! is_numeric( $max_salary ) ) {
			$max_salary = 0;
		}

		if ( !$html ) {
			$min_salary = \WP_Job_Board_Pro_Price::format_price_without_html( $min_salary );
            $max_salary = \WP_Job_Board_Pro_Price::format_price_without_html( $max_salary );
		} else {
			$min_salary = \WP_Job_Board_Pro_Price::format_price( $min_salary );
            $max_salary = \WP_Job_Board_Pro_Price::format_price( $max_salary );
		}

		$price_html = '';
		if ( $min_salary ) {
			$price_html = $min_salary;
		}
		if ( $max_salary ) {
			$price_html .= (!empty($price_html) ? ' - ' : '').$max_salary;
		}

        // prefix
        if ($min_salary && empty($max_salary)) {
            $price_html = 'Vanaf '. $price_html;
        }
        
        if (empty($min_salary) && $max_salary) {
            $price_html = 'Max '. $price_html;
        }

		if ( $price_html ) {
			$salary_type = get_post_meta( $post_id, '_job_salary_type', true );

			$salary_type_html = '';
			switch ($salary_type) {
				case 'yearly':
					$salary_type_html = esc_html__(' per year', 'wp-job-board-pro');
					break;
				case 'monthly':
					$salary_type_html = esc_html__(' per month', 'wp-job-board-pro');
					break;
				case 'weekly':
					$salary_type_html = esc_html__(' per week', 'wp-job-board-pro');
					break;
				case 'daily':
					$salary_type_html = esc_html__(' per day', 'wp-job-board-pro');
					break;
				case 'hourly':
					$salary_type_html = esc_html__(' per hour', 'wp-job-board-pro');
					break;
				default:
					$types = \WP_Job_Board_Pro_Mixes::get_default_salary_types();
					if ( !empty($types[$salary_type]) ) {
						$salary_type_html = ' / '.$types[$salary_type];
					}
					break;
			}
			$salary_type_html = apply_filters( 'wp-job-board-pro-child-get-salary-type-html', $salary_type_html, $salary_type, $post_id );
			$price_html = $price_html.$salary_type_html;

            return $price_html;
		  }
    }
}