<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
 *	@author 	: Nicodemus Karisa
 *	@date		: 27th September, 2018
 *	Finance management system for NGOs
 *	https://techsysnow.com
 *	NKarisa@ke.ci.org
 */

class Office_model extends MY_Model
{

  public $table = 'office'; // you MUST mention the table name


  function __construct(){
    parent::__construct();
    $this->load->database();
  }

  function index(){}

  function delete($id = null){

  }

  public function lookup_tables(){
    return array('context_definition','account_system','country_currency');
  }

    /**
   * get_office_id
   * Get the office id
   * @param Int $office_bank_id
   * @author Livingstone Onduso
   * @updated Nicodemus Karisa - Move the method from voucher model
   * @date 2024-04-22
   * @access private
   * @return Int
   */
  public function get_office_by_office_bank_id(int $office_bank_id): array
  {

    $this->read_db->select(['office_id as office_id', 'office_code', 'fk_account_system_id as account_system_id']);
    $this->read_db->where(['office_bank_id' => $office_bank_id]);
    $this->read_db->join('office','office.office_id=office_bank.fk_office_id');
    $office = $this->read_db->get('office_bank')->row_array();

    return  $office;
  }

  public function get_office_by_id($office_id):array {
    $this->read_db->select(['office_id', 'office_code', 'fk_account_system_id as account_system_id']);
    $this->read_db->where(['office_id' => $office_id]);
    $office = $this->read_db->get('office')->row_array();

    return  $office;
  }

  public function get_office_code($fcp_ids)
    {
        //Get office code
        $this->read_db->select(array('office_code'));
        $this->read_db->where_in('office_id', $fcp_ids);
        $office_code = $this->read_db->get('office');

        $fcp_number = $office_code ? $office_code->result_array() : [];

        if (sizeof($fcp_number) == 1) {

            return ['message' => 1, 'fcps' => $fcp_number[0]['office_code']];
        } elseif ($fcp_number == 0) {
            return  0;
        } else {
            return  -1;
        }
    }

  private function context_definition_name_by_office_id($office_id){
    //Get office context
    $this->read_db->join('context_definition','context_definition.context_definition_id=office.fk_context_definition_id');
    return $context_definition_name = $this->read_db->get_where('office',array('office_id'=>$office_id))->row()->context_definition_name;
    
  }
  public function detail_tables(){
    $context_definition_name = $this->context_definition_name_by_office_id(hash_id($this->id,'decode'));
   
    return array('context_'.strtolower($context_definition_name),'budget','financial_report','office_bank','project_allocation','system_opening_balance','office_bank');
  }

  public function master_table_visible_columns(){
    // return array(
    //   'center_track_number','center_name','center_code','center_start_date',
    //   'center_end_date','center_is_active','group_cluster_name','approval_name','status_name'
    // );
  }

  public function master_table_hidden_columns(){}

  public function list_table_visible_columns(){
      return ['office_track_number','office_code','office_name','account_system_name','context_definition_name','office_start_date','status_name'];
  }
  public function list_table_where(){

    if(!$this->session->system_admin){
      
      $this->read_db->where(array('account_system_code'=>$this->session->user_account_system));
    }

  }

  public function list_table_hidden_columns(){}

  public function detail_list_table_visible_columns(){}

  public function detail_list_table_hidden_columns(){}

  //public function single_form_add_visible_columns(){}

  //public function single_form_add_hidden_columns(){}

  public function master_multi_form_add_visible_columns(){}

  public function detail_multi_form_add_visible_columns(){}

  public function master_multi_form_add_hidden_columns(){}

  public function detail_multi_form_add_hidden_columns(){}

  function single_form_add_visible_columns(){
    return array('office_name','office_code','office_start_date','office_is_active',
    'context_definition_name');
  }

  function edit_visible_columns()
  {
    return [
      'office_name',
      'office_code',
      'office_description',
      'office_start_date',
      //'office_end_date',
      'office_is_active'
    ];
  }

  //function detail_list_query(){}

  function master_view(){}

  public function list(){}

  public function view(){}
  
  /**
   * check_if_office_has_any_context_association
   * 
   * This method checks if an office has a context association. An office can only be associated to 
   * only 1 context once.
   * 
   * @param Int $office_id - Primary ID of the office
   * @return Bool - True if has association, False if not
   */

  function check_if_office_has_any_context_association(int $office_id):Bool{
    // Just check if this office has any hierarchy association 

    $this->read_db->select(array('context_definition_name'));
    $context_definition_names = $this->read_db->get('context_definition')->result_array();

    $has_association = false;
    

    foreach(array_column($context_definition_names,'context_definition_name') as $context_definition_name){
        $context_table = 'context_'.$context_definition_name;

        $office_count = $this->read_db->get_where($context_table,
        array('fk_office_id'=>$office_id))->num_rows();

        if($office_count > 0){
          $has_association = true;
          break;
        }
    }

    return $has_association;

  }


  public function get_all_account_system_offices($account_system_id, $context_definition_id = 0){
    
    $this->read_db->select(array('office_id','office_track_number','office_code','context_definition_id','context_definition_name',
    'office_name','office_start_date','context_cluster_name','context_cohort_name','context_cohort_name','context_country_name','office_is_suspended')); 
    
    if(!$this->session->system_admin){
      $this->read_db->where(array('fk_account_system_id' => $account_system_id));
      $this->read_db->where_in('office_id',array_column($this->session->hierarchy_offices,'office_id'));
    }

    if($context_definition_id > 0){
      $this->read_db->where(array('office.fk_context_definition_id' => $context_definition_id));
    }

    $this->read_db->join('context_definition','context_definition.context_definition_id=office.fk_context_definition_id');
    $this->read_db->join('context_center','context_center.fk_office_id=office.office_id',"LEFT");
    $this->read_db->join('context_cluster','context_cluster.context_cluster_id=context_center.fk_context_cluster_id',"LEFT");
    $this->read_db->join('context_cohort','context_cohort.context_cohort_id=context_cluster.fk_context_cohort_id',"LEFT");
    $this->read_db->join('context_country','context_country.context_country_id=context_cohort.fk_context_country_id',"LEFT");
    $this->read_db->join('context_region','context_region.context_region_id=context_country.fk_context_region_id',"LEFT");
    $this->read_db->join('context_global','context_global.context_global_id=context_region.fk_context_global_id',"LEFT");
    $offices_obj = $this->read_db->get('office');

    $offices = [];

    if($offices_obj->num_rows() > 0){
      $offices = $offices_obj->result_array();
    }

    return $offices;
  }

   /**
   * Get List of Offices records
   * 
   * This method retrives records to be edited 
   * @return Array - array of a row
   * @Author :Livingstone Onduso
   * @Date: 08/08/2022
   */

  // function get_list_of_offices(int $context_definition):Array{

  //   if(!$this->session->system_admin){
  //     $this->read_db->where(array('office.fk_account_system_id'=>$this->session->user_account_system_id));
  //   }

  //   switch($context_definition){

  //     case 1:
  //       $this->read_db->select(array('office.office_id','office.office_track_number','office.office_code','office.office_name','office.office_start_date', 'context_cluster_name','context_cohort_name','context_country_name','office_is_suspended'));
  //       $this->read_db->where(array('office.fk_context_definition_id'=>$context_definition));

  //       //Relationship tables to context center
  //       $this->read_db->join('context_cluster', 'context_cluster.context_cluster_id=context_center.fk_context_cluster_id');
  //       $this->read_db->join('context_cohort','context_cohort.context_cohort_id=context_cluster.fk_context_cohort_id');
  //       $this->read_db->join('context_country','context_country.context_country_id=context_cohort.fk_context_country_id');

  //       $this->read_db->join('office','office.office_id=context_center.fk_office_id');

  //       //$this->read_db->limit(2);
  //       $this->read_db->order_by('context_center_id DESC');
  //       $fcp_offices=$this->read_db->get('context_center')->result_array();

  //       break;

  //     case 2:
  //       $this->read_db->select(['office.office_id','office.office_track_number','office.office_code','office.office_name','office.office_start_date','context_cohort_name','context_country_name']);
  //       $this->read_db->where(array('office.fk_context_definition_id'=>$context_definition));
  //       //Relationship tables to context center
  //       $this->read_db->join('context_cohort','context_cohort.context_cohort_id=context_cluster.fk_context_cohort_id');
  //       $this->read_db->join('context_country','context_country.context_country_id=context_cohort.fk_context_country_id');

  //       $this->read_db->join('office','office.office_id=context_cluster.fk_office_id');

  //       $fcp_offices=$this->read_db->get('context_cluster')->result_array();
        
  //       break;

  //     case 3:
  //       $this->read_db->select(['office.office_id','office.office_track_number','office.office_code','office.office_name','office.office_start_date','context_country_name']);
  //       $this->read_db->where(array('office.fk_context_definition_id'=>$context_definition));
  //       //Relationship tables to context center
  //       $this->read_db->join('context_country','context_country.context_country_id=context_cohort.fk_context_country_id');

  //       $this->read_db->join('office','office.office_id=context_cohort.fk_office_id');

  //       $fcp_offices=$this->read_db->get('context_cohort')->result_array();
        
  //       break;


  //     case 4:
  //       $this->read_db->select(['office.office_id','office.office_track_number','office.office_code','office.office_name','office.office_start_date', 'context_region_name']);
       
  //       $this->read_db->where(array('office.fk_context_definition_id'=>$context_definition));
  //       //Relationship tables to context center
  //       $this->read_db->join('context_region','context_region.context_region_id=context_country.fk_context_region_id');

  //       $this->read_db->join('office','office.office_id=context_country.fk_office_id');

  //       $fcp_offices=$this->read_db->get('context_country')->result_array();
        
  //       break;

  //   }
  //   return  $fcp_offices ;
    
  // }

   /**
   * Get List of Clusters records
   * 
   * This method retrives records of clusters 
   * .
   * 
   * @return Array - array of a array
   * @Author :Livingstone Onduso
   * @Date: 08/08/2022
   */

  function get_clusters_or_cohorts_or_countries(string $table_name,string $column_id, string $column_name, bool $return_active_office_only=false, $add_user_form):Array{
   
    
    if(!$this->session->system_admin){
      $this->read_db->where(array('office.fk_account_system_id'=>$this->session->user_account_system_id));
    }

    if($return_active_office_only){
      $this->read_db->where(array('office.office_is_active'=>1));
    }


    $join_string='office.office_id='.$table_name.'.fk_office_id';

    //If not Add user Form and we are on EDIT user Form
    if($add_user_form==0){
      $column_id='office_id';
    }

    $this->read_db->select([$column_id,$column_name]);

    $this->read_db->join('office', $join_string);
   
    $clusters_or_cohort_or_contries_offices=$this->read_db->get($table_name)->result_array();

    $office_ids=array_column($clusters_or_cohort_or_contries_offices,$column_id);
    $office_names=array_column($clusters_or_cohort_or_contries_offices, $column_name);

    $office_ids_and_names=array_combine($office_ids,$office_names);

    return  $office_ids_and_names;
    
  }
 /**
   * Get the user's context
   * This method retrives combined ids
   * @return Array - array
   * @Author :Livingstone Onduso
   * @Date: 23/09/2022
   */
  function user_office($context_id, $user_id):array{

    //Check context
    switch($context_id){
      case 1:
        $context_office='context_center';
        break;
      case 2: 
        $context_office='context_cluster';
        break;
      case 3:
        $context_office='context_cohort';
        break;
      case 4:
        $context_office='context_country';
        break;
      case 5:
        $context_office='context_region';
        break;
      case 6:
        $context_office='context_global';
        break;
    }
    //Get office for a user e.g. KE0415- Ekambuli CDC
    $this->read_db->select(array('office_name', 'office_id'));
    $this->read_db->join($context_office, $context_office.'.fk_office_id=office.office_id');
    $this->read_db->join($context_office.'_user', $context_office.'_user.fk_'.$context_office.'_id='.$context_office.'.'.$context_office.'_id');
    $this->read_db->where(['fk_user_id' => $user_id]);
    $office_name = $this->read_db->get('office')->result_array();

    return   $office_name;
  }
   
   /**
   * Get ids and names columns details from the tables
   * This method retrives combined ids
   * @return Array - array
   * @Author :Livingstone Onduso
   * @Date: 07/08/2022
   */

  function retrieve_ids_and_names_records(Array $select_columns, string $table_name):array{

    $this->read_db->select($select_columns);

    $context=$this->read_db->get($table_name)->result_array();

    $ids=array_column($context,$select_columns[0]);
    $names=array_column($context,$select_columns[1]);

    $combined_ids_and_names=array_combine($ids, $names);

    return $combined_ids_and_names;


  }
 /**
   * Get users of context office
   * 
   * This method retrives records of users 
   * .
   * 
   * @param Int $office_id - Primary ID of the office, $definition_id-conext of office, 
   * @return Array - array of a row
   * @Author :Livingstone Onduso
   * @Date: 11/08/2022
   */
  public function get_office_context_users(int $office_id, int $definition_id):array{

   //Get a copy of users before you delete the users when changing definition

   switch($definition_id){
    case 1:
      $context__office_table='center';

      break;
    case 2: 
      $context__office_table='cluster';

      break;

    case 3: 
      $context__office_table='cohort';
      break;

    case 4:

      $context__office_table='country';

      break;

    case 5:

      $context__office_table='region';
  
      break;
    case 5:

      $context__office_table='global';
    
      break;
      
   }

   //Get Users to of the context office
   $this->read_db->select("user.user_id, CONCAT(user.user_firstname, ' ', user.user_lastname, ' - [',user.user_email, ']') AS name", FALSE);
   $this->read_db->join('context_'.$context__office_table,'context_'.$context__office_table.'.fk_office_id=office.office_id');
   $this->read_db->join('context_'.$context__office_table.'_user', 'context_'.$context__office_table.'_user.fk_context_'.$context__office_table.'_id=context_'.$context__office_table.'.context_'.$context__office_table.'_id');
   $this->read_db->join('user','user.user_id=context_'.$context__office_table.'_user.fk_user_id');
   $this->read_db->where(array('office.office_id'=>$office_id));
   $users=$this->read_db->get('office')->result_array();
   $user_ids=array_column($users, 'user_id');
   $user_name=array_column($users, 'name');

   $combine_user_ids_names=array_combine($user_ids,$user_name);

   return ['users'=>$user_ids, 'context_office_user_table_name'=>'context_'.$context__office_table.'_user','user_names'=>$combine_user_ids_names];
  
  }
   /**
   * Deactivate users
   * 
   * This method will deactivate users of the office when office become inactive 
   * .
   * 
   * @param Int $office_id - Primary ID of the office
   * @return Int - array of a row
   * @Author :Livingstone Onduso
   * @Date: 08/05/2022
   */
  function deactivate_or_activate_users_when_office_inactive(int $office_id, int $definition_id, array $users){

    //Get the users to deactivate
    if(sizeof($users)>0){
    $users_and_context_table_arr=$this->get_office_context_users($office_id,$definition_id);

    $users_to_deactivate=$users_and_context_table_arr['users'];

    $context_table_name=$users_and_context_table_arr['context_office_user_table_name'];

    $user_data_to_update_in_context_office_table[$context_table_name.'_is_active']=0;

    $user_table_data['user_is_active']=0;
    
    // if(sizeof($users)>0){

      $users_to_deactivate=$users;

      $user_data_to_update_in_context_office_table[$context_table_name.'_is_active']=1;

      $user_table_data['user_is_active']=1;
    // }

    //Deactivate users in context user table and in the USer table
    //$this->write_db->trans_start();

    //Update Context office user table
    $update_user_table = $this->grants_model->merge_with_history_fields('user', $user_table_data, false, false);

    $this->write_db->where_in('user_id', $users_to_deactivate);
    $this->write_db->update('user',$update_user_table);

    //Update the context tables e.g. context_center_user table
    $update_context_table = $this->grants_model->merge_with_history_fields($context_table_name, $user_data_to_update_in_context_office_table, false, false);

    $this->write_db->where_in('fk_user_id', $users_to_deactivate);
    $this->write_db->update($context_table_name,$update_context_table);

  }

    //$this->write_db->trans_complete();

    //if($this->write_db->trans_status() === FALSE){

      //$this->write_db->trans_rollback();

      //return 0;

    //}else{

      //$this->write_db->trans_commit();

      //return 1;
    //}

  }
   /**
   * Get vouchers records
   * 
   * This method retrives vouchers 
   * .
   * 
   * @param Int $office_id - Primary ID of the office
   * @return Array - array of a row
   * @Author :Livingstone Onduso
   * @Date: 08/17/2022
   */

  function get_vouchers_for_office_to_edit(int $office_id):array{

    $this->read_db->select(array('journal_id'));
    $this->read_db->where(array('fk_office_id'=>$office_id));
    $this->read_db->limit(2);
    return $this->read_db->get('journal')->result_array();

  }
  
   /**
   * Get edit records
   * 
   * This method retrives records to be edited 
   * .
   * 
   * @param Int $office_id - Primary ID of the office
   * @return Array - array of a row
   * @Author :Livingstone Onduso
   * @Date: 08/05/2022
   */

  function get_edit_office_records(int $office_id){

    //Get the context_defination to edit
    $context_definition_id=$this->read_db->get_where('office', ['office_id'=>$office_id])->row()->fk_context_definition_id;

    //Get reporting_context_id and name

    switch($context_definition_id) {
      case 1:
         $table_name='context_center';
         $context_reporting_column_name='fk_context_cluster_id';
         break;
      case 2:
        $table_name='context_cluster';
        $context_reporting_column_name='fk_context_cohort_id';
        break;
      case 3: 
        $table_name='context_cohort';
        $context_reporting_column_name='fk_context_country_id';
        break;
      case 4:
        $table_name='context_country';
        $context_reporting_column_name='fk_context_region_id';
        break;
      case 5: 
        $table_name='context_region';
        $context_reporting_column_name='fk_context_global_id';
        break;
      case 6:
        $table_name='context_global';
        break;

    }

    //Get the join table and get the reporting context office 
    $explode_to_column=explode('_', $context_reporting_column_name);

    $join_table_name=$explode_to_column[1].'_'.$explode_to_column[2]; 

    $join_column_id_str=$join_table_name.'.'.$join_table_name.'_id='.$table_name.'.'.$context_reporting_column_name;

    $this->read_db->select([$context_reporting_column_name,$join_table_name.'_name']);
    $this->read_db->join($join_table_name, $join_column_id_str);
    $this->read_db->where(array($table_name.'.'.'fk_office_id'=>$office_id));
    $reporting_context=$this->read_db->get($table_name)->row_array();

    //Get the office record to edit

    $this->read_db->select(['office_id','office_name','office_code','office_description', 'office_start_date', 'office_is_active','office_is_readonly', 'office.fk_context_definition_id as fk_context_definition_id','context_definition_name', 'account_system_name','fk_account_system_id','fk_country_currency_id']);
    $this->read_db->join('context_definition', 'context_definition.context_definition_id=office.fk_context_definition_id');

    $this->read_db->join('account_system','account_system.account_system_id=office.fk_account_system_id');

    $this->read_db->where(['office_id'=>$office_id]);

    $records_to_be_edited=$this->read_db->get('office')->row_array();

    //Merge the reporting office and the office records

    $all_records_to_be_edited=array_merge($records_to_be_edited,$reporting_context);

    return $all_records_to_be_edited;
    

  }

  function currency_name(int $currency_id){

    $this->read_db->where(array('country_currency_id'=>$currency_id));

    return $this->read_db->get('country_currency')->row_array()['country_currency_name'];

  }

  /**
   * 
   * get_office_context_association
   * 
   * Get the context record for the office. The return array has a key of the context definition name
   * of the office
   * 
   * @param int $center
   * @return Array 
   *  */  

  function get_office_context_association(int $office_id):Array{

    $this->read_db->join('office','office.fk_context_definition_id=context_definition.context_definition_id');
    $this->read_db->select(array('context_definition_name'));
    $association_table_obj = $this->read_db->get_where('context_definition',array('office_id'=>$office_id));

    $association_return = array();
    $association_table = '';

    if($association_table_obj->num_rows() > 0){
        $context_definition_name = $association_table_obj->row()->context_definition_name;
        $association_table = 'context_'.$context_definition_name;
        
        $association_obj = $this->read_db->get_where($association_table,
             array('fk_office_id'=>$office_id));

             if($association_obj->num_rows()>0){
              $association_return[$context_definition_name] = $association_obj->row();
             }

      
    }

    return $association_return;

  }

  /**
   * 
   * get_all_office_context
   * 
   * Get the all office context. 
   * 
   * @param null
   * @return Array 
   * @author Livingstone Onduso
   * @dated 07/09/2022
   *  */  

  function get_all_office_context():Array{

   $this->read_db->select(array('context_definition_id', 'context_definition_name'));

   if(!$this->session->system_admin){
    $this->read_db->where_in('context_definition_level', [1,2,3,4]);
   }
   $all_context_offices=$this->read_db->get('context_definition')->result_array();

   $all_office_context_ids=array_column($all_context_offices, 'context_definition_id');
   $all_office_context_names=array_column($all_context_offices, 'context_definition_name');

   $all_office_context_ids_and_names=array_combine($all_office_context_ids, $all_office_context_names );

    return $all_office_context_ids_and_names;

  }

  function lookup_values(){
    
    $lookup_values = [];

    // Use this when filling in context tables
    if(substr($this->controller,0,8) == 'context_'){
      $context_definition_name = str_replace('context_','',$this->controller);
      $this->read_db->join('context_definition','context_definition.context_definition_id=office.fk_context_definition_id');
      $lookup_values = $this->read_db->get_where('office',array('context_definition_name'=>$context_definition_name))->result_array();
    }
    else{
       $office_ids=array_column($this->session->hierarchy_offices,'office_id');

       $this->read_db->where_in('office_id',$office_ids);

        $lookup_values = $this->read_db->get('office')->result_array();
    }

    // Show context definitions below the user's
    if(!$this->session->system_admin){
      $user_context_definition_id = $this->session->context_definition['context_definition_id'];
      $this->read_db->select(array('context_definition_id', 'context_definition_name'));
      $this->read_db->where(array('context_definition_id < ' => $user_context_definition_id));
      $context_definitions = $this->read_db->get('context_definition')->result_array();
  
      $lookup_values['context_definition'] = $context_definitions;  
    }
    
    return $lookup_values;
  }

  /**
   * get_office_start_date_by_id
   * 
   * Get the start date of the office and first day of the start month by a given office Id
   * 
   * @author Nicodemus Karisa
   * @authored_date 14th June 2023
   * @reviewed_date None
   * 
   * @param int $office_id - Office Id
   * 
   * @return array - Returns 2 dates that is the first day of the office start month and the actual office start date with keys 
   * actual_start_date and month_start_date respectively
   */

  public function get_office_start_date_by_id(int $office_id):array{

    $office_start_date = date('Y-m-01');
    $office_start_month = date('Y-m-01');

    $this->read_db->where(array('office_id' => $office_id));
    $office_start_date_obj = $this->read_db->get('office');

    if($office_start_date_obj->num_rows() > 0){
      $office_start_date = $office_start_date_obj->row()->office_start_date;
      $office_start_month = date('Y-m-01', strtotime($office_start_date));
    }

    $dates = ['actual_start_date' => $office_start_date, 'month_start_date' => $office_start_month];

    return $dates;
  }

  function check_if_table_has_relationship_with_office($table_name){
    $table_has_relationship_with_office = false;
    $fields = $this->grants->list_fields($table_name);
    $lookup_tables = list_lookup_tables($table_name);

    foreach($lookup_tables as $lookup_table){
      if($lookup_table == 'status' || $lookup_table == 'approval') continue;
      $lookup_table_fields = $this->grants->list_fields($lookup_table);
      $fields = array_merge($fields , $lookup_table_fields);
    }
    
    if(in_array('fk_office_id', $fields)){ 
      $table_has_relationship_with_office = true;
    }

    return $table_has_relationship_with_office;
  }


  function intialize_table(Array $foreign_keys_values = []){
  
    $context_definitions = $this->config->item('context_definitions');
    $global_context_key = count($context_definitions) + 1;

    $office_data['office_track_number'] = $this->grants_model->generate_item_track_number_and_name('office')['office_track_number'];
    $office_data['office_name'] = 'Head Office';
    $office_data['office_description'] = 'Head Office';
    $office_data['office_code'] = 'G001'; 
    $office_data['fk_context_definition_id'] = $global_context_key;
    $office_data['fk_country_currency_id'] = 1;
    $office_data['office_start_date'] = date('Y-m-01');
    $office_data['office_end_date'] = "0000-00-00";
    $office_data['office_is_active'] = 1;
    $office_data['fk_account_system_id'] = 1;//$foreign_keys_values['account_system_id'];
        
    $office_data_to_insert = $this->grants_model->merge_with_history_fields('office',$office_data,false);
    $this->write_db->insert('office',$office_data_to_insert);

    return $this->write_db->insert_id();
}
  
}
