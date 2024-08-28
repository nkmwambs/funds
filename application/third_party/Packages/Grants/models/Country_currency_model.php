<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Country_currency_model extends MY_Model{

    public $table = 'country_currency'; 
    public $dependant_table = '';
    public $name_field = 'country_currency_name';
    public $create_date_field = "country_currency_created_date";
    public $created_by_field = "country_currency_created_by";
    public $last_modified_date_field = "country_currency_last_modified_date";
    public $last_modified_by_field = "country_currency_last_modified_by";
    public $deleted_at_field = "country_currency_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('account_system');
    }

    public function detail_tables(){
        return ['currency_conversion'];
    }

    public function single_form_add_visible_columns(){
        return ['country_currency_name', 'country_currency_code','account_system_name'];
    }

    public function detail_multi_form_add_visible_columns(){}

    function lookup_values(){
        $lookup_values = $this->read_db->get('country_currency')->result_array();

        if(!$this->session->system_admin){
            $user_currency_code = $this->session->user_currency_code;
            $lookup_values = $this->read_db->get_where('country_currency',array('country_currency_code'=>$user_currency_code))->result_array();
        }
        
        return $lookup_values;
    }

    function intialize_table(Array $foreign_keys_values = []){
        
        $user_data['country_currency_id'] = 1;
        $user_data['country_currency_track_number'] = $this->grants_model->generate_item_track_number_and_name('country_currency')['country_currency_track_number'];
        $user_data['country_currency_name'] = 'USD';
        $user_data['country_currency_code'] = 'USD';
            
        $user_data_to_insert = $this->grants_model->merge_with_history_fields('country_currency',$user_data,false);
        $this->write_db->insert('country_currency',$user_data_to_insert);
      
        return $this->write_db->insert_id();
      }

  /**
   * get_country_currency_code() 
   * This method returns the currenncy of a country
   *@return Array
   * @Author: Livingstone Onduso
   * @Dated: 10/03/2022
   */
    public function get_country_currency_code()
    {


        $country_currency='USD';

        $this->read_db->select(array('country_currency_code'));

        if (!$this->session->system_admin) {
            $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id));
        }
        $country_currency =$this->read_db->get(' country_currency')->row()->country_currency_code;
        
        return  $country_currency;
    }

    public function get_country_currency_code_by_office_id($office_id){

        $this->read_db->select(array('country_currency_code'));
        $this->read_db->join('office','office.fk_country_currency_id=country_currency.country_currency_id');
        $this->read_db->where(array('office_id' => $office_id));
        $country_currency_obj = $this->read_db->get('country_currency');

        $country_currency_code = '';

        if($country_currency_obj->num_rows() > 0){
            $country_currency_code = $country_currency_obj->row()->country_currency_code;
        }

        return $country_currency_code;
    }

    /**
   * get_country_currency_id() 
   * This method returns the currenncy of a country
   *@return Array
   * @Author: Livingstone Onduso
   * @Dated: 03/08/2022
   */
  public function get_country_currency_id()
  {


      $this->read_db->select(array('country_currency_id'));

      if (!$this->session->system_admin) {
          $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id));
      }
      $country_currency_id =$this->read_db->get(' country_currency')->row()->country_currency_id;
      
      return  $country_currency_id;
  }

   /**
   * get_country_currency() 
   * This method returns the currenncy of a country
   *@return Array
   * @Author: Livingstone Onduso
   * @Dated: 09/09/2022
   */
  public function get_country_currency()
  {

      $this->read_db->select(array('country_currency_id','country_currency_name'));

      if (!$this->session->system_admin) {
          $this->read_db->where(array('fk_account_system_id' => $this->session->user_account_system_id));
      }

      $country_currency_id_and_names =$this->read_db->get('country_currency')->result_array();

      $curreny_ids=array_column($country_currency_id_and_names,'country_currency_id');

      $curreny_names=array_column($country_currency_id_and_names,'country_currency_name');

      $curreny_ids_and_names=array_combine($curreny_ids,$curreny_names);

      
      return   $curreny_ids_and_names;
  }
      
}