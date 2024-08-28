<?php 

class Email_library extends Grants {

    private $CI;
    
    function __construct(){
        parent::__construct();
        $this->CI =& get_instance();
    }
    
    function index(){} 

    function scan_unsent_emails(){
        log_message('error', 'Emails sent');
    }

}