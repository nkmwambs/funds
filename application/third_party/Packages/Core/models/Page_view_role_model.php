<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Page_view_role_model extends MY_Model{

    public $table = 'page_view_role'; 
    public $dependant_table = '';
    public $name_field = 'page_view_role_name';
    public $create_date_field = "page_view_role_created_date";
    public $created_by_field = "page_view_role_created_by";
    public $last_modified_date_field = "page_view_role_last_modified_date";
    public $last_modified_by_field = "page_view_role_last_modified_by";
    public $deleted_at_field = "page_view_role_deleted_at";
    
    function __construct(){
        parent::__construct();
        $this->load->database();
    }

    function index(){}

    public function lookup_tables(){
        return array('role','page_view');
    }

    public function detail_tables(){}

    public function detail_multi_form_add_visible_columns(){}

    public function single_form_add_visible_columns(){
        return ['page_view_role_is_default','role_name','page_view_name'];
    }

    function prevent_posting_more_than_one_default_view($post_array){
        
        $header = $post_array['header'];

        //Get the fk_role_id from the post array
        $role_id = $header['fk_role_id'];

        //Get the value of page_view_role_is_default
        $page_view_role_is_default = $header['page_view_role_is_default'];

        //Get count of db records with passed fk_role_id that has page_view_role_is_default of 1
        $records  = $this->read_db->get_where('page_view_role',
        array('fk_role_id'=>$role_id,'page_view_role_is_default'=>$page_view_role_is_default));

        //If the count above is > 0 and the incoming page_view_role_is_default is 1, 
        //update the page_view_role_is_default of passed fk_role_id to 0 
        if($records->num_rows() > 0 && $page_view_role_is_default == 1 ){

            $data['page_view_role_is_default'] = 0;
            $this->write_db->where(array('fk_role_id'=>$role_id));
            $this->write_db->update('page_view_role',$data);
        }
    }

    function action_before_insert($post_array){

        $this->prevent_posting_more_than_one_default_view($header);

        return $post_array;

    }

    function action_before_edit($post_array){
        
        $this->prevent_posting_more_than_one_default_view($post_array);

        return $post_array;

    }
}