<?php

//require_once('views/index.php');

class View_output{

    /**
     * @var Object $CI - A property that hold CodeIgniter Singleton class instance
     */
    protected $CI = null;

    /**
     * @var String $controller - Hold the active/ selected table/controller name
     */
    protected $controller;

    /**
     * @var String $current_model - holds the active model name
     */
    protected $current_model;

    /**
     * @var Object $access - hold the access_base object
     */
    protected $access = null;

    function __construct(){

        // Class property initialization
        $this->CI =& get_instance();
        $this->access = new Access_base();
        $this->controller = $this->CI->controller;
        $this->current_model = $this->CI->current_model;

    }

    function index(){

    }

    function output(...$args){
        ob_start();
		    include_once (__DIR__.'/views/index.php');
		    $buffered_view = ob_get_contents();	
        ob_end_clean();
        
        return $buffered_view;
    }

}