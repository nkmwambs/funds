<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Budget_item_model extends MY_Model 
{
  public $table = 'budget_item'; // you MUST mention the table name


  function __construct(){
    parent::__construct();
  }
  
  function delete($id = null){

  }


  function index(){}

  public function lookup_tables(){
    return array('budget','expense_account','status','approval');
  }

  public function show_add_button(){
    return false;
}

  public function detail_tables(){
    return array('budget_item_detail');
  }

  public function master_table_visible_columns(){}

  public function master_table_hidden_columns(){}

  public function list_table_visible_columns(){}

  public function list_table_hidden_columns(){}

  public function detail_list_table_visible_columns(){}

  public function detail_list_table_hidden_columns(){}

  //public function single_form_add_visible_columns(){}

  //public function single_form_add_hidden_columns(){}

  public function master_multi_form_add_visible_columns(){
    return array('budget_item_description','budget_item_total_cost','expense_account_name','project_allocation_name');
  }

  public function detail_multi_form_add_visible_columns(){}

  public function master_multi_form_add_hidden_columns(){}

  public function detail_multi_form_add_hidden_columns(){}

  function detail_list(){}

  function master_view(){}

  public function list(){}

  public function view(){}

  function post_approval_action_event($event_payload){
    $item = $event_payload['item'];
    $item_id = $event_payload['post']['item_id'];
    $status_id = $event_payload['post']['next_status'];

    $max_approval_status_ids = $this->general_model->get_max_approval_status_id($item);

    // Unmark the item if marked for review
    if(in_array($status_id, $max_approval_status_ids)){
      $this->write_db->where(array('budget_item_id' => $item_id));
      $data['budget_item_marked_for_review'] = 0;
      $this->write_db->update('budget_item', $data);
    }

    // Turn the unlocked revisions to locked
    $this->read_db->select(array('budget_item_revisions'));
    $this->read_db->where(array('budget_item_id' => $item_id));
    $budget_item_revisions_obj = $this->read_db->get('budget_item');

    if($budget_item_revisions_obj->num_rows() > 0){
      $budget_item_revisions = $budget_item_revisions_obj->row()->budget_item_revisions;

      if($budget_item_revisions != NULL && $budget_item_revisions != '[]'){
        $budget_item_revisions_array = json_decode($budget_item_revisions, true);

        foreach($budget_item_revisions_array as $key => $budget_item_revision){
          if(array_key_exists('locked', $budget_item_revision)){
            $budget_item_revisions_array[$key]['locked'] = true;
          }
        }

        $update_revision['budget_item_revisions'] = json_encode($budget_item_revisions_array);

        $this->write_db->where(array('budget_item_id' => $item_id));
        $this->write_db->update('budget_item', $update_revision);

      }
    }
  }

  public function get_budget_id_by_budget_item_id($budget_item_id){

    $this->read_db->select('fk_budget_id');

    $this->read_db->where(['budget_item_id'=>$budget_item_id]);

    $budget_id=$this->read_db->get('budget_item')->row()->fk_budget_id;

    return $budget_id;

  }

}
