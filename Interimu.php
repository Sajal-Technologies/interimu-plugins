<?php
/**
 * Plugin Name: Interimu
 * Description: Interimu
 * Plugin URI:  https://interimu.nl
 * Version:     1.0.0
 * Author:      interimu
 * Author URI:  https://interimu.nl
 * Text Domain: interimu
 */

namespace Interimu\Interimu;

/**
 * Class Interimu
 *
 * Main Plugin class
 */
class Interimu{

    /**
     * Instance
     *
     * @since 1.0.0
     * @access private
     * @static
     *
     * @var $_instance object The single instance of the class.
     */
    private static $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     *
     * @return Interimu An instance of the class.
     * @since 1.2.0
     * @access public
     *
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Include Plugin files
     *
     * Register Plugin Required Files
     *
     * @access public
     */
    public function register_includes(){
	    require_once ( INTERIMU_DIR . '/includes/class-scripts.php' );
        require_once ( INTERIMU_DIR . '/includes/class-manage-emails.php' );
        require_once ( INTERIMU_DIR . '/includes/class-manage-employers.php' );
        require_once ( INTERIMU_DIR . '/includes/class-manage-jobs.php' );
        require_once ( INTERIMU_DIR . '/includes/class-hook-registry.php' );
    }

    /**
     * Plugin Constants
     *
     * Register plugin required constants
     *
     * @access public
     */
    function define_constants(){
        define('INTERIMU_DIR', __DIR__ ); 
        define('INTERIMU_FILE', __FILE__ );
        define('INTERIMU_URL', plugin_dir_url( __FILE__ ));
    }

    /**
     *  Plugin class constructor
     *
     * Register plugin action hooks and filters
     *
     * @access public
     */
    public function __construct(){

        //Define Constants
        $this->define_constants();

        //Register Includes
        $this->register_includes();
    }
}

// Instantiate Plugin Class
Interimu::instance();

