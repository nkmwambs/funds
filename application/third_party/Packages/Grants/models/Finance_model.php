<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Finance_model extends CI_Model
{

  function __construct(){
    parent::__construct();
    $this->load->database();

  }

  function index(){

  }


  // function transaction_month($office_id){
  //   return '2019-11-01';
  // }

 
}
