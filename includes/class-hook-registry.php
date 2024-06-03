<?php

namespace Interimu\Interimu;

class Hook_Registry
{
    public function __construct()
    {
        $this->register_hooks();
    }
    public function register_hooks()
    {
        $scripts  = new Scripts();
        $jobs = new Manage_Jobs();
        $manage_emails = new Manage_Emails();

        //Enqueue Styles and Scripts  
        add_action('wp_enqueue_scripts', [$scripts, 'register_scripts'], 99);
        add_filter('wp-job-board-pro-render-emails-vars',[$manage_emails,'render_email_vars'],99,4);
        add_shortcode('job_alerts_shortcode',[$manage_emails,'test_job_alerts_shortcode']);
        add_action( 'plugins_loaded', [$manage_emails,'override_wp_job_alerts'],99 );
        add_action( 'rest_api_init', [ $jobs, 'wp_interimu_register_routes'] );
        
        add_action( 'wp_job_board_pro_email_daily_notices', [$manage_emails, 'custom_send_job_alert_notice']);

        add_filter('wp-job-board-pro-date-posted-options',[$jobs,'edit_jobs_filter_date'],10,1);
        add_filter('wp_job_board_pro_get_job_employment_types',[$jobs,'filter_job_types'], 99, 2);
        //add_filter('wp-job-board-pro-get-max-salary-html',[$jobs,'get_max_salary_html'], 10, 3);
        //add_filter('wp-job-board-pro-get-salary-html',[$jobs,'get_salary_html'], 10, 2);
        add_filter('wp-job-board-pro-job_listing-query-args',[$jobs,'apply_jobs_filter_date'],10,2);
        add_filter('wp_job_board_pro_get_job_listing_structured_data',[$jobs,'filter_listing_structured_data'], 99, 2);
        add_filter('wp_job_board_pro_output_job_listing_structured_data',[$jobs,'output_job_listing_structured_data'], 99, 2);
        add_action('updated_post_meta',[$jobs,'update_job_listing_post_meta'], 10, 4);
        
        add_filter('superio_job_display_employer_logo',[$manage_emails,'job_display_employer_logo'], 10, 4);
        
        add_action('admin_init', [$jobs, 'redirect_candidate_to_home'] );
        
       add_filter('wp-job-board-pro-job_listing-query-args',[$jobs,'job_listing_query_args'], 999, 2);
       
       
    }
    
}

new Hook_Registry();