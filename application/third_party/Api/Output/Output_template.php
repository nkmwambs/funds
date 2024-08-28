<?php

class Output_template{
    
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

    /**
     * output 
     * 
     * This is an exit method to the Output_base class
     * 
     * @return Array - List page array
     */
    public function output(): Array {
        return $this->_output();
    }
}