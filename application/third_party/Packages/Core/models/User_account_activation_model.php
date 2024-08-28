<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  @package grants <Finance management system for NGOs>
 *	@author  Onduso <londuso@ke.ci.org>
 *	@date	17th August, 2023
 *  @method void __construct() main method, first to be executed and initializes variables.
 *  @method void index().
 *  @method array lookup_tables(): returns lookup and relationship tables.
 *  @method int get_user_account_id(): returns user account id that need to be activated to each logged in user.
 *  @method int reject_activating_new_user_account(): deletes the user from user, context user related table and department_user table.
 *	@see https://techsysnow.com
 */


class User_account_activation_model extends MY_Model
{

    public $table = 'user_account_activation';
    public $dependant_table = '';
    public $name_field = 'user_account_activation_name';
    public $create_date_field = "user_account_activation_created_date";
    public $created_by_field = "user_account_activation_created_by";
    public $last_modified_date_field = "user_account_activation_last_modified_date";
    public $last_modified_by_field = "user_account_activation_last_modified_by";
    public $deleted_at_field = "user_account_activation_deleted_at";

    function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    function index()
    {
    }



    /**
     * lookup_tables(): returns lookup and relationship tables.
     * @author Onduso 
     * @access public 
     * @return array
     * @Dated: 18/8/2023
     */
    public function lookup_tables(): array
    {
        return array('user');
    }

    /**
     * get_user_account_id(): returns user account id that need to be activated to each logged in user.
     * @author Onduso 
     * @access public 
     * @return array
     * @Dated: 18/8/2023
     * @param int $user_activation_id.
     */
    public function activate_new_user_account(int $user_activation_id): int
    {
        
        //Get user to activate from user_activation tabel and activate user in user table.
        $this->read_db->select(['fk_user_id']);
        $this->read_db->where(['user_account_activation_id' => $user_activation_id]);
        $fk_user_id = $this->read_db->get('user_account_activation')->row()->fk_user_id;

        $this->write_db->where(['user_id'=>$fk_user_id]);
        $account_system_id = $this->read_db->get('user')->row()->fk_account_system_id;

        $this->write_db->trans_start();
        //Update the user Table to activate newly created user
        $update_user['user_is_active']=1;
        $update_user['user_first_time_login']=1;
        $update_user['user_self_created']=1;
        $update_user['user_created_by']=$this->session->user_id;
        $update_user['fk_status_id']=$this->general_model->get_max_approval_status_id('user', $account_system_id)[0];

        $this->write_db->where(['user_id'=>$fk_user_id]);
        $this->write_db->update('user',$update_user);

        //Delete user from user activation table after update goes successful
        // $this->write_db->where(['fk_user_id'=>$fk_user_id]);
        // $this->write_db->delete('user_account_activation');

        //Delete the user once activated
        $this->write_db->where(['user_account_activation_id'=>$user_activation_id]);
        $this->write_db->delete('user_account_activation');

        $this->write_db->trans_complete();

        if ($this->write_db->affected_rows() == '1') {
            return 1;
        } else {
            // any trans error?
            if ($this->write_db->trans_status() === FALSE) {
                return 0;
            }
            return 1;
        }
    }

    /**
     * reject_activating_new_user_account(): deletes the user from user, context user related table and department_user table.
     * @author Onduso 
     * @access public 
     * @return array
     * @Dated: 18/8/2023
     * @param int $user_activation_id.
     */
    public function reject_activating_new_user_account(int $user_activation_id, string $userRejectionReson):int
    {
        
        //Get user to activate from user_activation table and activate user in user table.
        $this->read_db->select(['fk_user_id','user_type']);

        $this->read_db->where(['user_account_activation_id' => $user_activation_id]);

        $new_account_details = $this->read_db->get('user_account_activation')->result();

        $user_ids=array_column($new_account_details,'fk_user_id');

        $user_type=array_column($new_account_details,'user_type');


        $this->write_db->trans_start();

        switch($user_type[0]){
            case 1:
               //Delete user in context_center_user
               $this->write_db->where(['fk_user_id'=>$user_ids[0]]);

               $this->write_db->delete('context_center_user');

               break;

            case 2: 
               //Delelet user in context_cluster_user
               $this->write_db->where(['fk_user_id'=>$user_ids[0]]);

               $this->write_db->delete('context_cluster_user');

               break;

            case 3: 
               //Delete user from context_cohort_user
               $this->write_db->where(['fk_user_id'=>$user_ids[0]]);

               $this->write_db->delete('context_cohort_user');

               break;

            case 4: 
               //Delete user from context_country_user [country admins]
               $this->write_db->where(['fk_user_id'=>$user_ids[0]]);

               $this->write_db->delete('context_country_user');

               break;

            case 5:
                //Delete user from context_country_user [Other national staffs]
                $this->write_db->where(['fk_user_id'=>$user_ids[0]]);

                $this->write_db->delete('context_country_user');
                
                break;
        }

        //Delete user from department_user table
        $this->write_db->where(['fk_user_id'=>$user_ids[0]]);

        $this->write_db->delete('department_user');

        //Delete user from user table
        $this->write_db->where(['user_id'=>$user_ids[0]]);

        $this->write_db->delete('user');

        //Soft delete on user_account_activation
        $put_delete_at_marker['deleted_at']=date('Y-m-d');

        $put_delete_at_marker['fk_user_id']=0;

        $put_delete_at_marker['user_account_activation_reject_reason']=$userRejectionReson;

        $this->write_db->where(['user_account_activation_id'=>$user_activation_id]);

        $this->write_db->update('user_account_activation',$put_delete_at_marker);

        $this->write_db->trans_complete();

        if ($this->write_db->affected_rows() == '1') {

            return 1;

        } else {
            // any transaction error?
            if ($this->write_db->trans_status() === FALSE) {

                return 0;
            }

            return 1;
        }
    }

    public function detail_tables()
    {
    }

    public function detail_multi_form_add_visible_columns()
    {
    }
}
