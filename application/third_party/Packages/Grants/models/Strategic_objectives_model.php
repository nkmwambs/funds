<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Strategic_objectives_model extends CI_Model
{

  function __construct(){
    parent::__construct();
    $this->load->database();

  }

  function index(){

  }

  function get_strategic_objectives_costing($budget_id){

    $this->read_db->select(array('budget_item_id','budget_item_objective'));
    $this->read_db->select_sum('budget_item_detail_amount');
    $this->read_db->where(array('fk_budget_id' => $budget_id));
    $this->read_db->join('budget_item_detail','budget_item_detail.fk_budget_item_id=budget_item.budget_item_id');
    $this->read_db->group_by('budget_item_id');
    $budget_items_with_objectives_obj = $this->read_db->get('budget_item');

    $budget_items_with_objectives = [];
    $summaries = ['tabulation' => [], 'tallies' => []];

    $count_with_objectives = 0;
    $count_without_objectives = 0;
    $amount_with_objectives = 0;
    $amount_without_objectives = 0;

    if($budget_items_with_objectives_obj->num_rows() > 0){
      $budget_items_with_objectives_array = $budget_items_with_objectives_obj->result_array();

      foreach($budget_items_with_objectives_array as $budget_item){
        if($budget_item['budget_item_objective'] == NULL){
          $count_without_objectives++;
          $amount_without_objectives += $budget_item['budget_item_detail_amount'];
          continue;
        }else{
          $amount_with_objectives += $budget_item['budget_item_detail_amount'];
          $count_with_objectives++;
        }

        $objective = json_decode($budget_item['budget_item_objective']);
        $budget_items_with_objectives[$budget_item['budget_item_id']] =  [
          'objective_id' => $objective->pca_strategy_objective_id,
          'objective_name' => $objective->pca_strategy_objective_name,
          'intervention_id'  => $objective->pca_strategy_intervention_id,
          'intervention_name'  => $objective->pca_strategy_intervention_name,
          'budget_item_amount' => $budget_item['budget_item_detail_amount']
        ];
      }

     foreach($budget_items_with_objectives as $budget_item_id => $summary_item){
      $summaries['tabulation']['objectives_summary'][$summary_item['objective_id']][] = ['name' => $summary_item['objective_name'], 'amount' => $summary_item['budget_item_amount']];
      $summaries['tabulation']['interventions_summary'][$summary_item['intervention_id']][] = [ 'name' => $summary_item['intervention_name'], 'amount' => $summary_item['budget_item_amount']];
     }

    }

    $summaries['tallies']['with_objectives']['count'] = $count_with_objectives;
    $summaries['tallies']['without_objectives']['count'] = $count_without_objectives;
    $summaries['tallies']['with_objectives']['amount'] = $amount_with_objectives;
    $summaries['tallies']['without_objectives']['amount'] = $amount_without_objectives;

    return $summaries;
  }
 
}
