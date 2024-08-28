<?php

class Position_output{
    
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

    protected $current_library;

    function __construct(){

        // Class property initialization
        $this->CI =& get_instance();
        $this->access = new Access_base();
        $this->controller = $this->CI->controller;
        $this->current_model = $this->CI->current_model;
        $this->current_library = $this->CI->current_library;
    }

    function index(){

    }

    function output(...$args){
        
        $position_title = $args[0];

        $page_action = isset($args[1])?$args[1]:$this->CI->action;

        $lib = $this->current_library;

        $page_positions = null;
        
        if(method_exists($this->CI->$lib,'page_position')){
           $page_positions = $this->CI->$lib->page_position();
        }
        
        if(is_array($page_positions) && array_key_exists($position_title,$page_positions)){
            if(array_key_exists($page_action,$page_positions[$position_title])){
                if(is_array($page_positions[$position_title][$page_action])){
                    return implode(" ",$page_positions[$position_title][$page_action]);
                }else{
                    return $page_positions[$position_title][$page_action];
                }
            }elseif(!is_array($page_positions[$position_title])){
                return $page_positions[$position_title];
            }else{
                return null;
            }
        }else{
            return null;//'<i>Page position key "'.$position_title.'" in not defined in "'.$lib.'"!</i>';
        }
        
    }
}
