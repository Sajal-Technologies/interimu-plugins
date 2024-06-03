<?php

namespace Interimu\Interimu;

class Scripts
{

    public function register_scripts()
    {
        $this->enqueue_styles();
        $this->enqueue_scripts();
        $this->localise_data('interimu-js');
    }

    public function enqueue_styles()
    {
	wp_enqueue_style('interimu-styles', INTERIMU_URL . 'assets/css/styles.css');
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('interimu-js', INTERIMU_URL . '/assets/js/scripts.js', array('jquery') );
    }

    public function localise_data($hook)
    {
        $data = array(
            'ajax_url' => admin_url('admin-ajax.php')
        );

        wp_localize_script($hook, 'InterimuJobs',  $data);
    }
}