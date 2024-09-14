<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Expense_account_model extends MY_Model
{
  public $table = 'expense_account'; // you MUST mention the table name
  public $primary_key = 'expense_account_id'; // you MUST mention the primary key


  function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  function delete($id = null)
  {
  }

  function index()
  {
  }

  function lookup_tables()
  {
    //return list_lookup_tables('expense_account');
    return ['income_account'];
  }

  function lookup_values()
  {
    $lookup_values = parent::lookup_values();
    
    $this->read_db->select(['income_account_id', 'income_account_name']);
    $lookup_values['income_account'] = $this->read_db->get('income_account')->result_array(); 

    return $lookup_values;
  }

  /**
   * get_expense_income_account_by_id
   * 
   * Get the income account id for a given expense account id
   * 
   * @author Nicodemus Karisa
   * @authored_date 8th June 2023
   * @access public
   * 
   * @param int $expense_account_id
   * 
   * @return int - Income account id
   */

  public function get_expense_income_account_id(int $expense_account_id): int
  {

    $income_account_id = 0;

    $this->read_db->select(array('fk_income_account_id'));
    $this->read_db->where(array('expense_account_id' => $expense_account_id));
    $expense_account_obj = $this->read_db->get('expense_account');

    if ($expense_account_obj->num_rows() > 0) {
      $income_account_id = $expense_account_obj->row()->fk_income_account_id;
    }

    return  $income_account_id;
  }

  function detail_tables()
  {
    return ['expense_account_office_association'];
  }

  function transaction_validate_duplicates_columns()
  {

    return ['expense_account_code', 'fk_income_account_id'];
  }

  //This piece code/ function added by Onduso 28/7/2022
  function action_before_insert($post_array)
  {

    $fk_income_account_id = $post_array['header']['fk_income_account_id'];

    $expense_account = $this->grants_model->overwrite_field_value_on_post(
      $post_array,
      'expense_account',
      'expense_account_is_active',
      1,
      0,
      [
        'fk_income_account_id' => $fk_income_account_id,
        'expense_account_is_active' => 1
      ]
    );

    $expense_account['header']['expense_account_code'] = return_sanitized_code($expense_account['header']['expense_account_code']);

    return $expense_account;
  }

  function get_all_active_expense_accounts(int $income_id, $fk_office_id)
  {
    $this->read_db->select(['expense_account_id', 'expense_account_name']);
    $this->read_db->where(['fk_income_account_id' => $income_id, 'expense_account_is_active' => 1]);
    $all_expenses_with_T_accounts = $this->read_db->get('expense_account')->result_array();

    ///return $all_expenses_with_T_accounts;
    $this->read_db->select(['fk_expense_account_id']);
    $this->read_db->where(['fk_office_id' => $fk_office_id]);
    $associatiated_accs = $this->read_db->get('expense_account_office_association')->result_array();

    $assoc_accs = array_column($associatiated_accs, 'fk_expense_account_id');


    //Remove T Accounts from array if the office not head quarter e.g. CITAM
    
    if (count($assoc_accs) == 0) {

      foreach ($all_expenses_with_T_accounts as $key => $all_expenses_with_T_account) {
 
        //Check if Not HQ Unset and value starts with 'T' 
        if(strpos($all_expenses_with_T_account['expense_account_name'], 'T') === 0){


            unset($all_expenses_with_T_accounts[$key]);
  
            break;

        };
       
      }
    }

    return  $all_expenses_with_T_accounts;


  }

  // /**
  //  * get_expense_income_account_by_id
  //  * 
  //  * Get the income account id for a given expense account id
  //  * 
  //  * @author Nicodemus Karisa
  //  * @authored_date 8th June 2023
  //  * @access public
  //  * 
  //  * @param int $expense_account_id
  //  * 
  //  * @return int - Income account id
  //  */

  //  public function get_expense_income_account_id(int $expense_account_id): int
  //  {
 
  //    $income_account_id = 0;
 
  //    $this->read_db->select(array('fk_income_account_id'));
  //    $this->read_db->where(array('expense_account_id' => $expense_account_id));
  //    $expense_account_obj = $this->read_db->get('expense_account');
 
  //    if ($expense_account_obj->num_rows() > 0) {
  //      $income_account_id = $expense_account_obj->row()->fk_income_account_id;
  //    }
 
  //    return  $income_account_id;
  //  }


  function list()
  {
  }

  function view()
  {
  }

  public function single_form_add_visible_columns()
  {
    return [
      "expense_account_name",
      "expense_account_description",
      "expense_account_code",
      "expense_account_is_admin",
      "expense_vote_heads_category_name",
      "expense_account_is_active",
      "expense_account_is_budgeted",
      "income_account_name"
    ];
  }
}
