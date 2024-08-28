<?php 

class Input_element{
    private $_args = [];

    function __construct($args = ['value' => '']){
        $this->_args = (object)$args;
    }

    function render(){
        return $this->_args->value;
    }
    
}