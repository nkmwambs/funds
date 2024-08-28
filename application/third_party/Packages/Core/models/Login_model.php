<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


/**
 *  @package grants <Finance management system for NGOs>
 *	@author  Onduso <londuso@ke.ci.org>
 *	@date	29th June, 2020
 *  @method void __construct() main method, first to be executed and initializes variables.
 *  @method void index().
 *  @method array get_countries(): return an array of account systems/countries.
 *  @method array get_offices(): return an array of offices like fcp/cluster/region.
 *  @method int get_country_language(): returns language id.
 *  @method array get_user_departments(): returns departments based on selected office context e.g. fcp/cluster
 *  @method array get_user_roles(): returns roles based on context definiation and account system id.
 *  @method int  get_country_currency(): returns currency id.
 *  @method array get_user_designations(): returns designations based on context definiation.
 *  @method int email_exists(): check if email exists.
 *  @method int pull_activator_users_for_fcp_users(): returns activator_user_ids.
 *  @method array get_user_activator_ids(): returns activator_user_ids
 *  @method array pull_activator_users_for_national_staffs(): returns activator_user_ids
 *  @method array pull_activator_users_for_country_administrators(): returns activator_user_ids

 */

class Login_model extends CI_Model
{

  function __construct()
  {
    parent::__construct();
    $this->load->database();
  }

  function index()
  {
  }
  /**
   * get_countries(): return an array of account systems/countries
   * @author Onduso 
   * @access public 
   * @return array
   */
  public function get_countries(): array
  {

    $this->read_db->select(['account_system_id', 'account_system_code']);

    $this->read_db->where(['account_system_is_active' => 1]);

    $countries = $this->read_db->get('account_system')->result();

    $country_ids = array_column($countries, 'account_system_id');

    $country_code = array_column($countries, 'account_system_code');

    $country_ids_and_codes = array_combine($country_ids, $country_code);


    return $country_ids_and_codes;
  }

  /**
   * get_offices(): return an array of offices like fcp/cluster/region
   * @author Onduso 
   * @access public 
   * @return array
   * @param int $account_system_id, int $context_definition_id
   */
  public function get_offices(int $account_system_id, int $context_definition_id): array
  {

    $this->read_db->select(['office_id', 'office_name']);

    $this->read_db->where(['office_is_active' => 1, 'fk_account_system_id' => $account_system_id, 'fk_context_definition_id' => $context_definition_id]);

    $offices = $this->read_db->get('office')->result();

    $office_ids = array_column($offices, 'office_id');

    $office_names = array_column($offices, 'office_name');

    $office_ids_and_names = array_combine($office_ids, $office_names);


    return $office_ids_and_names;
  }

  /**
   * get_country_language(): returns language id
   * @author Onduso 
   * @access public 
   * @return int
   * @param int $account_system_id
   */
  public function get_country_language(int $account_system_id):int
  {

    $this->read_db->select(['fk_language_id']);

    $this->read_db->where(['fk_account_system_id' => $account_system_id]);

    $language_id = $this->read_db->get('account_system_language')->row();//fk_language_id;

    if($language_id==null){
      return 0;
    }

    return $language_id->fk_language_id;
  }
  /**
   * email_exists(): check if email exists
   * @author Onduso 
   * @access public 
   * @return int
   */
  function email_exists($email): int
  {

    //$email = $this->input->post('email');

    $this->read_db->select(['user_email']);

    $this->read_db->where(['user_email' => trim($email)]);

    $email_exists = $this->read_db->get('user')->result();

    $is_email_present = 0;

    if (!empty($email_exists)) {
      $is_email_present = 1;
    }

    return $is_email_present;
  }

  /**
   * get_country_currency(): returns currency id
   * @author Onduso 
   * @access public 
   * @return int
   * @param int $account_system_id
   */
  public function get_country_currency(int $account_system_id): int
  {

    $this->read_db->select(['country_currency_id']);

    $this->read_db->where(['fk_account_system_id' => $account_system_id]);

    // $country_currency_id = $this->read_db->get('country_currency')->row()->country_currency_id;

    $country_currency_id = $this->read_db->get('country_currency')->row();//country_currency_id;

    if($country_currency_id==null){
      return 0;
    }

    return $country_currency_id->country_currency_id; 

  }

  /**
   * save_user_account_activation_info(): returns currency id
   * @author Onduso 
   * @access public 
   * @return int
   * @param int $account_system_id
   */
  public function save_user_account_activation_info(int $account_system_id): int
  {
  }

  /**
   * get_user_activator_ids(): returns activator_user_ids
   * @author Onduso 
   * @access public 
   * @Dated: 16/8/2023
   * @return array
   * @param int int $user_type, int $office_id
   */
  public function get_user_activator_ids(int $user_type, int $office_id, int $country_id): array
  {
    if ($user_type == 1) {
      //Pfs user_ids to activate fcps staffs
      $activator_user_ids = $this->pull_activator_users_for_fcp_users($office_id);
    } elseif ($user_type == 2 || $user_type == 3 || $user_type == 5) {
      //Country Admnis to activate national office staff
      $activator_user_ids = $this->pull_activator_users_for_national_staffs($country_id);
    } elseif ($user_type == 4) {
      $activator_user_ids = $this->pull_activator_users_for_country_administrators();
    }
    return $activator_user_ids;
  }

  /**
   * pull_activator_users_for_fcp_users(): returns activator_user_ids
   * @author Onduso 
   * @access private 
   * @Dated: 16/8/2023
   * @return array
   * @param int $office_id
   */
  private function pull_activator_users_for_fcp_users(int $office_id): array
  {
    $this->read_db->select(['fk_context_cluster_id']);
    $this->read_db->where(['fk_office_id' => $office_id]);
    $context_cluster_id = $this->read_db->get('context_center')->row()->fk_context_cluster_id;

    //get the activator fk_user_id
    $this->read_db->select('fk_user_id');
    $this->read_db->where(['fk_context_cluster_id' => $context_cluster_id]);
    $user_activator_id = $this->read_db->get('context_cluster_user')->result_array();

    return $user_activator_id;
  }

  /**
   * pull_activator_users_for_national_staffs(): returns activator_user_ids
   * @author Onduso 
   * @access private 
   * @Dated: 16/8/2023
   * @return array
   * @param int country_id
   */
  private function pull_activator_users_for_national_staffs(int $country_id): array
  {
    $this->read_db->select(['user_id']);
    $this->read_db->where(['fk_account_system_id' => $country_id, 'user_is_context_manager' => 1, 'fk_context_definition_id' => 4]);
    $user_ids = $this->read_db->get('user')->result_array();

    return $user_ids;
  }

  /**
   * pull_activator_users_for_country_administrators(): returns activator_user_ids
   * @author Onduso 
   * @access private 
   * @Dated: 17/8/2023
   * @return array
   */
  private function pull_activator_users_for_country_administrators(): array
  {
    $this->read_db->select(['user_id']);
    $this->read_db->where(['fk_context_definition_id' => 6]);
    $user_ids = $this->read_db->get('user')->result_array();

    return $user_ids;
  }

  /**
   * get_user_departments_roles_and_designations(): returns departments based on selected office context e.g. fcp/cluster
   * @author Onduso 
   * @access public 
   * @return array
   * @Dated: 10/8/2023
   * @param int $context_definition_id
   */
  public function get_user_departments_roles_and_designations(int $user_type, string $table_name, int $countryID): array
  {

    $column_id=$table_name.'_id';
    $column_name=$table_name.'_name';

    $this->read_db->select([$column_id, $column_name]);

    //Other national offices represented by user_type 5
    
    if($user_type==5){
      $this->read_db->where(['fk_context_definition_id' => 4]);
    }else{
      $this->read_db->where(['fk_context_definition_id' => $user_type]);
    }

    if($countryID!=0){
      $this->read_db->where(['fk_account_system_id' => $countryID]);
    }
    
    $departments_or_roles_or_designations = $this->read_db->get($table_name)->result_array();

   //Modify if user Type is Country Admins
    $modify_user_for_admins=[];

    if ($user_type == 4) {

      $modify_user_for_admins[]=$departments_or_roles_or_designations[0];

      $departments_or_roles_or_designations=  $modify_user_for_admins;
    }

    //Remove country administrators from dropdown for other national staffs
    if($user_type == 5){

      array_shift($departments_or_roles_or_designations);
    }



    $ids = array_column($departments_or_roles_or_designations, $column_id);

    $names = array_column($departments_or_roles_or_designations, $column_name);

    $ids_and_names = array_combine($ids, $names);

    return $ids_and_names;
  }

  /**
   * get_user_departments(): returns departments based on selected office context e.g. fcp/cluster
   * @author Onduso 
   * @access public 
   * @return array
   * @Dated: 10/8/2023
   * @param int $context_definition_id
   */
  public function get_user_departments(int $user_type): array
  {


    $this->read_db->select(['department_id', 'department_name']);

    //Other national offices represented by user_type 5
    
    if($user_type==5){
      $this->read_db->where(['fk_context_definition_id' => 4]);
    }else{
      $this->read_db->where(['fk_context_definition_id' => $user_type]);
    }
    
    $user_departments = $this->read_db->get('department')->result_array();

   //Modify if user Type is Country Admins
    $modify_user_departments_for_admins=[];

    if ($user_type == 4) {

      $modify_user_departments_for_admins[]=$user_departments[0];

      $user_departments=  $modify_user_departments_for_admins;
    }

    //Remove country administrators from dropdown for other national staffs
    if($user_type == 5){

      array_shift($user_departments);
    }



    $department_id = array_column($user_departments, 'department_id');

    $department_name = array_column($user_departments, 'department_name');

    $departments = array_combine($department_id, $department_name);

    return $departments;
  }

  /**
   * get_user_roles(): returns roles based on context definiation and account system id
   * @author Onduso 
   * @access public 
   * @return array
   * @param int $context_definition_id, int $account_system_id
   */
  public function get_user_roles(int $user_type, int $account_system_id): array
  {

    $this->read_db->select(['role_id', 'role_name']);

     //Other national offices represented by user_type 5
    
     if($user_type==5){
      
      $this->read_db->where(['fk_context_definition_id' => 4, 'fk_account_system_id' => $account_system_id, 'role_is_active'=>1]);
    }else{
      
      $this->read_db->where(['fk_context_definition_id' => $user_type, 'fk_account_system_id' => $account_system_id, 'role_is_active'=>1]);
    }


    $user_roles = $this->read_db->get('role')->result();

    //Modify if user Type is Country Admins
    $modify_user_roles_for_admins=[];

    if ($user_type == 4) {

      $modify_user_roles_for_admins[]=$user_roles[0];

      $user_roles=  $modify_user_roles_for_admins;
    }

    //Remove country administrators from dropdown for other national staffs
    if($user_type == 5){

      array_shift($user_roles);
    }

    $role_ids = array_column($user_roles, 'role_id');

    $role_names = array_column($user_roles, 'role_name');

    $roles = array_combine($role_ids, $role_names);

    return $roles;
  }

  /**
   * get_user_designations(): returns designations based on context definiation 
   * @author Onduso 
   * @access public 
   * @return array
   * @param int $context_definition_id
   */
  public function get_user_designations(int $user_type): array
  {

    $this->read_db->select(['designation_id', 'designation_name']);

     //Other national offices represented by user_type 5
    
     if($user_type==5){
      
      $this->read_db->where(['fk_context_definition_id' => 4]);
    }else{
      
      $this->read_db->where(['fk_context_definition_id' => $user_type]);
    }


    $user_designations = $this->read_db->get('designation')->result();

    //Modify if user Type is Country Admins
    $modify_user_designations_for_admins=[];

    if ($user_type == 4) {

      $modify_user_designations_for_admins[]=$user_designations[0];

      $user_designations=  $modify_user_designations_for_admins;
    }

    //Remove country administrators from dropdown for other national staffs
    if($user_type == 5){

      array_shift($user_designations);
    }

    $designation_ids = array_column($user_designations, 'designation_id');

    $designation_names = array_column($user_designations, 'designation_name');

    $designations = array_combine($designation_ids, $designation_names);

    return $designations;
  }
}
