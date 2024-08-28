<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

require_once('vendor/autoload.php');

class Strategic_objectives_library extends Grants
{

  private $CI;

  function __construct(){
    parent::__construct();
    $this->CI =& get_instance();
  }

  function index(){

  }

  function load_strategic_objectives_costing_view($budget_id){

    $this->CI->load->model('strategic_objectives_model');
    
    $data['data'] = $this->CI->strategic_objectives_model->get_strategic_objectives_costing($budget_id);
    
    $strategic_objectives_costing_view = $this->CI->load->view('strategic_objectives/strategic_objectives_costing', $data, true);

    // log_message('error', json_encode($budget_limit_view));

    return $strategic_objectives_costing_view;
  }
  
}
