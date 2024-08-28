<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Request_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){

  }

  function center_name_field_value(){
    return 1;
  }
  
  
  function page_position(){
    $widgets = [];
    //$widgets['position_1'][] = Widget_base::load('comment');

    //$widgets['position_2'][] = Widget_base::load('button',get_phrase('edit'),$this->CI->controller.'/edit/'.$this->CI->id);
    // if($this->CI->user_model->check_role_has_permissions(ucfirst($this->CI->controller),'create')){            
    //   $widgets['position_2'][] = Widget_base::load('button',get_phrase('clone'),$this->CI->controller.'/clone/'.$this->CI->id);
    // }

    // if($this->CI->user_model->check_role_has_permissions(ucfirst($this->CI->controller),'read')){            
    //   $widgets['position_2'][] = Widget_base::load('button',get_phrase('print'),$this->CI->controller.'/clone/'.$this->CI->id,'btn_print');
    // }
    //$widgets['position_2'][] = Widget_base::load('button',get_phrase('delete'),$this->CI->controller.'/delete/'.$this->CI->id);
          
    //$widgets['position_3'][] = "Here is main position two widgets";

    return $widgets;
  }


  // function change_field_type(){
  //   $change_field_type['center_name']['field_type'] = 'select';
  //   $change_field_type['center_name']['options'] = $this->CI->user_model->user_associated_centers_names($this->CI->session->user_id);

  //   return $change_field_type;
  // }

  function default_field_value(){
    return ['request_date'=>date('Y-m-d')];
  }



}
