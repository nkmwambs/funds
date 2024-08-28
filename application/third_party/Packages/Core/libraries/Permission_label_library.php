<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Permission_label_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){

  }

  // function list(){

  //   $result = $this->CI->Bank_model->list();

  //   $table_array = array(
  //     'table_header'=>$this->CI->general_library->camel_case_header('bank',$this->CI->Bank_model->hidden_columns),
  //     'table_body'=>$result
  //   );

  //   return $this->CI->load->view('templates/list',$table_array,true);

  // }

}
