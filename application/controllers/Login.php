<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

    
    require_once('vendor/autoload.php');


// $dotenv = Dotenv\Dotenv::createImmutable(FCPATH);
// $dotenv->safeLoad();

//define('DS', DIRECTORY_SEPARATOR);

/*
 *	@author 	: Nicodemus Karisa
 *	date		: 6th June, 2018
 *	AFR Staff Recognition system
 *	https://www.compassion.com
 *	NKarisa@ke.ci.org
 */

//require_once('saml2/libautoload.php');

/**
 *  @package grants <Finance management system for NGOs>
 *	@author  Karisa-<nkarisa@ke.ci.org> and Onduso <londuso@ke.ci.org>
 *	@date	20th June, 2018
 *  @method void __construct() main method, first to be executed and initializes variables.
 *  @method void index().
 *  @method void create_account(): calls the create_account view in the general folder.
 *  @method string password_salt(): Add the salt on the password.
 *  @method void create_password(): calls the create_password view and sets user data.
 *  @method change_password_from_md5_to_sha256() is an temporal method that is used to migrate the MD5 passwords to complex SHA256 algorithm
 *  @method change_password(): used to update or change existing user password.
 *  @method void create_account(): allows new user of the system create an account and exposes  the create_account form.
 *  @method void get_country_language(): returns language id.
 *  @method void get_offices(): return an array of offices like fcp/cluster/region.
 *  @method void get_user_departments(): returns departments based on selected office context e.g. fcp/cluster.
 *  @method void get_user_roles(): returns roles based on context definiation and account system id
 *  @method void get_country_currency(): returns currency id.
 *  @method void verify_password_complexity(): validates the if password requirements are met.
 *  @method void verify_valid_email(): checks if email an is a correct formated email.
 *  @method void email_exists(): check if email exists.
 *  @method insert_into_context_user_table(): saves data in context tables as context_center_user.
 *  @method void save_create_account_data(): save form data to database .
 *  @method void get_user_activator_ids(): returns array of user_ids.
 *  @method void save_data_in_department_user(): saves department user data.
 *  @method void save_data_in_user_account_activation(): saves user activation data in user_account_activation.
 *  @method string get_office_name(): get office name of the user.
 *  @method process_reCAPTCHA(): processes google reCAPTCHA to avoid spaming
 *	@see https://techsysnow.com
 */


class Login extends CI_Controller
{

    public $auth;
    public $controller;
    public $write_db = null;
    public $read_db = null;

    function __construct()
    {
        parent::__construct();

        $this->write_db = $this->load->database('write_db', true); // Master DB on Port 3306
        $this->read_db = $this->grants_model->read_database_connection();

        // To be placed in all controller constructors but a remedy has been met in the htaccess Files block
        $this->output->set_header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        $this->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
        $this->output->set_header("Cache-Control: post-check=0, pre-check=0", false);
        $this->output->set_header("Pragma: no-cache");

        $this->controller = $this->session->default_launch_page;

        $this->load->model('office_model');
    }


    public function index()
    {

        // Check if there are global language pack with all languages

        $this->check_global_language_pack_state();

        if (!$this->db->table_exists('setting') || $this->db->get('setting')->num_rows() == 0) {
            $this->load->model('setting_model');

            // Create all tables in the database
            $this->grants_model->initialize_db_schema();

            // Populate setting table
            $this->setting_model->intialize_table();
        }

        if ($this->session->userdata('user_login') == 1) {
            //Create missing library and models files for the loading object/ controller
            if (parse_url(base_url())['host'] == 'localhost' && $this->session->system_admin) {
                $this->grants->create_missing_system_files_from_json_setup();
            }

            if ($this->session->system_admin) {
                $this->system_setup_check();
            }

            // Create mandatory role_permission for default launch page  
            $this->create_default_launch_page_role_permissions();

            // $this->read_db->select(array('user_first_time_login'));
            // $user_first_time_login = $this->read_db->get_where('user', array('user_id' => $this->session->user_id))->row()->user_first_time_login;

            redirect(base_url() . strtolower($this->session->default_launch_page) . '/list');
            

        } elseif ($this->session->has_userdata('update_user_password') && $this->session->update_user_password) {
            redirect(base_url() . 'login/create_password');
        }

        $this->load->view('general/login');
    }

    function check_global_language_pack_state()
    {
        // Get all languages
        $this->read_db->select(array('language_code'));
        $all_languages = $this->read_db->get("language")->result_array();

        $path = APPPATH . 'language' . DIRECTORY_SEPARATOR . 'global' . DIRECTORY_SEPARATOR;

        if (!file_exists($path)) {

            if (mkdir($path)) {
                foreach ($all_languages as $language) {
                    $this->language_library->create_language_files($language['language_code'], 'global');
                }
            }
        }
    }

    /**
     * ajax_login(): This method authenticate user to login in
     * @author Karisa and Onduso 
     * @access public
     * @return void
     */
    public function ajax_login(): void
    {
        $response = array();

        //Recieving post input of email, password from ajax request
        $email = $_POST["email"];
        // $password = !isset($_POST["password"])?$this->db->get_where('setting',array('type'=>'setup_password'))->description:$_POST["password"];
        $password = $_POST["password"];
        $response['submitted_data'] = $_POST;

        //Validating login
        $login_status = $this->validate_login(strtolower(trim($email)), $password);

        //Check if new user
        //if()new_user

        $response['login_status'] = $login_status;

        if ($login_status == 'success') {

            $response['redirect_url'] = '';
        }


        //Replying ajax request with validation response
        echo json_encode($response);
    }

        
    /**
     * validate_login(): This method validates email and password
     * @author Karisa and Onduso 
     * @access public
     * @return string
     * @param string $email, string $password ,bool $is_user_switch
     */
    function validate_login(string $email = '', string $password = '', bool $is_user_switch = false): string
    {

        //Convert email to lower case and Enforce user to change password from MD5 to SHA256

        // This code is becoming obsolete since it was developed to resolved a specific problem which is not full resolved.
        // --> start of obsolete code
        $email = strtolower($email);
        $this->read_db->where(array('user_email' => $email, 'user_is_active' => 1, 'md5_migrate' => 0));
        $md5_old_user_password = $this->read_db->get('user');


        if ($md5_old_user_password->num_rows() > 0 && !$is_user_switch) {
            //Change password to SHA256 and create sessions
            $this->change_password_from_md5_to_sha256($email);
            $row = $md5_old_user_password->row();

            //Check if user has ever logged in or not
            if ($row->user_first_time_login == 0) {
                $this->session->set_userdata('update_user_password', true);
                $this->session->set_userdata('user_id', $row->user_id);
                return 'success';
            }

            return $this->create_user_session($row, $is_user_switch);
        }
        // <-- end of obselete code

        //Hash password with sha256 and Salt
        $hashed = $this->password_salt($password);
        $maintenance_mode = $this->read_db->get_where('setting', array('type' => 'maintenance_mode'))->row()->description;
        $credential = array('user_email' => $email, "user_is_active" => 1, "user_password" => $hashed);

        if ($password == '' && $is_user_switch) {
            $credential = array('user_email' => $email, "user_is_active" => 1);
        }

        // Checking login credential for admin
        $query = $this->read_db->get_where('user', $credential);


        if ($query->num_rows() == 0) {
            return 'invalid';
        }else{
            
            $user_object = $query->row();
            $user_first_time_login = $user_object->user_first_time_login;
            $user_id = $user_object->user_id;
            $user_access_count = $user_object->user_access_count;

            if ($user_first_time_login == 0 && !$is_user_switch) {
                $this->session->set_userdata('update_user_password', true);
                $this->session->set_userdata('user_id', $user_id);
                return 'success';
            } elseif ($maintenance_mode == 0 || ($maintenance_mode == 1 && $query->row()->user_is_system_admin == 1)) {
                $row = $query->row();
                $is_user_session_created = $this->create_user_session($row, $is_user_switch);
                $is_user_session_created ? $this->update_login_history($user_id, $user_access_count) : 'invalid';
                return $is_user_session_created;
            }
        }

        return 'invalid';
    }

    private function update_login_history($user_id, $user_access_count){
        $update_data['user_last_login_time'] = date('Y-m-d H:i:s');
        $update_data['user_access_count'] = $user_access_count + 1;
        $this->write_db->where(array('user_id' => $user_id));
        $this->write_db->update('user', $update_data);
    }

    /**
     * password_salt
     * 
     * @author nkarisa <nakrisa@ke.ci.org>
     * @package Core
     * @subpackage Authentication
     * @version 2.0
     * @access private
     * 
     * Used to salt the password with a key derived from the AWS Parameter store or .env file. In the .env file the key is known as PASSWORD_SALT
     * Note that the .env file MUST be gitignored and MUST not be shared in public repositories.
     * 
     * When a user fails to get the parameter store salt code then they will get the code from the .env file.
     * To get the value of the PASSWORD_SALT for .env file set up, contact the DevOps team.
     * 
     * @param String $password - This is the unsalted password
     * 
     * @return String - It returns the salted password
     **/

    private function password_salt(String $password):String{
        // This construct was built to prevent system admin being forced going to aws while developing on localhost without internet
        // System admins need to have the env file set with a key PASSWORD_SALT and use the value given by the system administrator
        
        $salt = 'none';

        try {
            $salt = $this->aws_parameter_library->get_parameter_value('sha256-password-salt');
        } catch (\Throwable $th) {
            $dotenv = Dotenv\Dotenv::createImmutable(FCPATH);
            $dotenv->safeLoad();
            $salt = $_ENV['PASSWORD_SALT']; 
        }
        
        $hashed    = hash('sha256', $password . $salt);
        return $hashed;
    }

    /**
     * create_user_session(): This method creates sessions
     * @author Karisa and Onduso 
     * @access public
     * @return string
     * @param array $row ,bool $is_user_switch
     */
    function create_user_session(object $row, $is_user_switch = false): string
    {
        $this->load->model('Approve_item_model');

        if ($is_user_switch && !$this->session->has_userdata('primary_user_data')) {
            // Session for the primary user
            $this->session->set_userdata('primary_user_data', ['user_id' => $this->session->user_id, 'user_name' => $this->session->name]);
        }

        if ($this->session->has_userdata('user_id')) {

            $sesion_keys = [
                "user_id",
                "name",
                "role_id",
                "role_ids",
                "role_permissions",
                "system_admin",
                "user_locale",
                "user_currency_id",
                "user_currency_code",
                "user_account_system",
                "user_account_system_id",
                "base_currency_id",
                "departments",
                "context_associations",
                "context_definition",
                "context_offices",
                "hierarchy_offices",
                "role_is_department_strict",
                "breadcrumb_list",
                "default_launch_page",
                'user_menu',
                '$is_user_switch'
            ];

            $this->session->unset_userdata($sesion_keys);
        }

       
        $this->session->set_userdata('is_user_switch', $is_user_switch); 

        $this->session->set_userdata('package', 'Grants'); // To be changed when changing Apps

        $this->session->set_userdata('user_login', '1');
        $this->session->set_userdata('user_id', $row->user_id);
        $this->session->set_userdata('name', $row->user_firstname . ' ' . $row->user_lastname);
        $this->session->set_userdata('role_id', $row->fk_role_id); // To be retired in favour of role_ids session
        $this->session->set_userdata('role_ids', array_keys($this->user_model->user_role_ids($row->user_id, $row->fk_role_id)));


        $this->session->set_userdata(
            'role_permissions',
            $is_user_switch ?
                array_merge($this->user_model->get_user_permissions($this->session->role_ids), ['User_switch' => [1 => ['read' => 'read_user_switch']]]) :
                $this->user_model->get_user_permissions($this->session->role_ids)
        );

        $this->session->set_userdata('system_admin', $row->user_is_system_admin);

        $this->session->set_userdata('context_manager', $row->user_is_context_manager);

        $this->session->set_userdata('user_locale', $this->db->get_where(
            'language',
            array('language_id' => $row->fk_language_id)
        )->row()->language_code);


        // $this->session->set_userdata('language', 'swahili');


        $this->session->set_userdata('user_currency_id', $this->db->get_where(
            'country_currency',
            array('country_currency_id' => $row->fk_country_currency_id)
        )->row()->country_currency_id);

        $this->session->set_userdata('user_currency_code', $this->db->get_where(
            'country_currency',
            array('country_currency_id' => $row->fk_country_currency_id)
        )->row()->country_currency_code);

        $account_system = $this->db->get_where('account_system', array('account_system_id' => $row->fk_account_system_id))->row();

        $this->session->set_userdata('user_account_system', $account_system->account_system_code);

        $this->session->set_userdata('user_account_system_id', $row->fk_account_system_id);

        $this->load->model('account_system_setting_model');

        $this->session->set_userdata('system_settings', 
        $this->account_system_setting_model->get_account_system_settings($row->fk_account_system_id));

        $this->session->set_userdata(
            'base_currency_id',
            $this->db->get_where('setting', array('type' => 'base_currency_code'))->row()->description
        );

        $this->read_db->where(array('type' => 'environment'));
        $environment = $this->read_db->get('setting')->row()->description;

        $this->session->set_userdata('env', $environment);

        /**
         * These are Center Group Hierarchy related sessions
         */

        // This session carries the ids of the departments related to the current user.
        // A user may or may not have a department. A user can have multiple departments

        $this->session->set_userdata(
            'departments',
            $this->user_model->user_department($row->user_id)
        );

        $this->session->set_userdata(
            'context_definition',
            $this->user_model->get_user_context_definition($row->user_id)
        );

        $this->session->set_userdata(
            'data_privacy_consented',
            $this->user_model->data_privacy_consented($row->user_id, $is_user_switch)
        );

        $this->session->set_userdata(
            'is_user_switch',
            $is_user_switch
        );

        
        // log_message('error', json_encode($is_user_switch));
        // This method returns office ids the user has an association with in his/her context 
        // A user can have multiple offices associated to him or her e.g. A user of context definition of a country
        // can be associated to multiple countries.  

        $this->session->set_userdata(
            'context_offices',
            $this->user_model->get_user_context_offices($row->user_id)
        );

        // This method crreates an array of all office ids in the entire context hierachy of the user. 
        // If the context of the user is country called Kenya and Uganda, this 
        // methods gives all offices related to kenya and Uganda from 
        // the cohort level (immediate next level to a country) to the center level  

        $this->session->set_userdata(
            'hierarchy_offices',
            $this->user_model->user_hierarchy_offices($row->user_id)
        );

        // $this->update_user_offices($row->user_id, $this->user_model->user_hierarchy_offices($row->user_id));


        $this->session->set_userdata(
            'role_is_department_strict',
            $this->user_model->check_role_department_strictness($this->session->role_id)
        );


        $approve_item_model = $this->Approve_item_model->approveable_items();

        $this->session->set_userdata(
            'approveable_items',
            $approve_item_model
        );


        /**
         * Breadcrumb and default page sessions
         */
        $this->session->set_userdata('breadcrumb_list', array());
        $default_launch_page = $this->user_model->default_launch_page($row->user_id);
        $this->session->set_userdata('default_launch_page', $default_launch_page);

        $this->session->set_userdata('role_status', $this->user_model->actionable_role_status(array_keys($this->user_model->user_role_ids($row->user_id, $row->fk_role_id)))); //$this->status_model->actionable_role_status($this->session->role_ids)

        // This is a testing session. By default set to empty array
        //$this->session->set_userdata('testing',array());

        $this->create_missing_language_files();

        if ($is_user_switch) {
            $this->load->library('autoloaded/menu_library');
            $this->menu_library->navigation();
        }

        return 'success';
    }

    // function update_user_offices($user_id, $user_offices){
        
    //     $this->write_db->where(array('user_id' => $user_id));
    //     $this->write_db->update('user',['user_offices' => json_encode($user_offices)]);
    // }


    function system_setup_check()
    {

        $system_setup_state_obj = $this->write_db->get_where(
            'setting',
            array('type' => 'system_setup_completed')
        );

        $system_setup_state = 0;

        if ($system_setup_state_obj->num_rows() == 0) {
            // Use the write db for read queries due to slave replication delays
            $this->db = $this->load->database('write_db', true);
        } else {
            $system_setup_state = $system_setup_state_obj->row()->description;
        }


        if ($system_setup_state == 0) {

            // Check if context tables exists or create if missing
            $this->grants_model->create_context_tables();

            $db_tables = $this->grants_model->get_all_tables();

            // Empty all tables are reset by using truncate
            foreach ($db_tables as $db_table) {

                if ($db_table == 'setting') continue;

                // Disable foreign keys check
                $this->db->query('SET foreign_key_checks = 0');
                $this->db->truncate($db_table);
                // Enable foreign keys check after truncating
                $this->db->query('SET foreign_key_checks = 1');

                // Reset the auto-increment field to 1
                $reset_auto_increment = "ALTER TABLE " . $db_table . " AUTO_INCREMENT = 1";
                $this->db->query($reset_auto_increment);
            }

            // Insert approve items
            foreach ($db_tables as $db_table) {
                if (!$this->db->table_exists($db_table) && $db_table == 'setting') continue;
                $this->grants_model->insert_missing_approveable_item($db_table);
            }

            // Insert records for system required tables        
            $are_tables_populated = $this->grants_model->populate_initial_table_data();

            // Set user_id session for super user
            $this->session->set_userdata('user_id', 1);

            $tables_that_dont_require_history_fields = $this->config->item('table_that_dont_require_history_fields');

            foreach ($db_tables as $db_table) {

                if (!in_array($db_table, $tables_that_dont_require_history_fields)) {

                    if (!$this->db->table_exists($db_table)) continue;

                    // Create mandatory fields in a table i.e. created_date, last_modified_date, created_by, created_by, fk_status_id and fk_approval_id
                    $this->grants_model->mandatory_fields($db_table);

                    //$this->grants_model->insert_missing_approveable_item($db_table);

                    // Insert approve item and status of a selected table
                    $this->grants_model->insert_status_if_missing($db_table);
                }
            }

            //Update system_setup_completed setting to true if all tables are set up
            if ($are_tables_populated) {
                $setting_data['description'] = 1;
                $this->write_db->where(array('type' => 'system_setup_completed'));
                $this->write_db->update('setting', $setting_data);
            }

            // Create upload folders
            $this->grants->create_resource_upload_directory_structure();
        } else {

            $db_table = 'funds_transfer';

            $this->load->library('autoloaded/menu_library');

            $menus = $this->menu_library->getMenuItems();

            $this->menu_model->upsert_menu($menus);

            $this->grants_model->insert_missing_approveable_item($db_table);

            $this->grants_model->mandatory_fields($db_table);
        }
    }

    function create_default_launch_page_role_permissions()
    {

        $default_page = $this->config->item('default_launch_page'); // Ex. Dashboard

        $this->db->join('permission', 'permission.permission_id=role_permission.fk_permission_id');
        $this->db->join('menu', 'menu.menu_id=permission.fk_menu_id');
        $role_permission_obj = $this->db->get_where(
            'role_permission',
            array(
                'menu.menu_derivative_controller' => $default_page,
                'role_permission.fk_role_id' => $this->session->role_id
            )
        );

        $role_name = $this->db->get_where(
            'role',
            array('role_id' => $this->session->role_id)
        )->row()->role_name;

        if ($role_permission_obj->num_rows() == 0) {

            $this->db->join('menu', 'menu.menu_id=permission.fk_menu_id');
            $permission_obj = $this->db->get_where(
                'permission',
                array('menu_derivative_controller' => $default_page)
            );

            $role_permission_data['role_permission_name'] = "Read permission for " . $default_page . " by " . $role_name;
            $role_permission_data['role_permission_is_active'] = 1;
            $role_permission_data['fk_role_id'] = $this->session->role_id;
            $role_permission_data['fk_permission_id'] = $permission_obj->row()->permission_id;

            $role_permission_data_to_insert = $this->grants_model->merge_with_history_fields('role_permission', $role_permission_data, false);

            $this->write_db->insert('role_permission', $role_permission_data_to_insert);
        }
    }


    /**
     * get_offices(): return an array of offices like fcp/cluster/region
     * @author Onduso 
     * @access public 
     * @return void
     * @param int $account_system_id, int $context_definition_id
     */
    public function get_offices(int $account_system_id, int $context_definition_id): void
    {
        echo json_encode($this->login_model->get_offices($account_system_id, $context_definition_id));
    }

    /**
     * get_country_language(): returns language id
     * @author Onduso 
     * @access public 
     * @return void
     * @param int $account_system_id
     */
    public function get_country_language(int $account_system_id): void
    {

        echo json_encode($this->login_model->get_country_language($account_system_id));
    }

    /**
     * get_country_currency(): returns currency id
     * @author Onduso 
     * @access public 
     * @return void
     * @param int $account_system_id
     */
    public function get_country_currency(int $account_system_id): void
    {

        echo json_encode($this->login_model->get_country_currency($account_system_id));
    }

    /**
     * verify_password_complexity(): validates the if password requirements are met
     * @author Onduso 
     * @access public 
     * @return void
     */

    function verify_password_complexity()
    {
        //Get password inputted and check for password complexity.
        $password = $this->input->post('password');

        $un_allowed_password = [];

        if (strlen($password) < 8) {
            $un_allowed_password[] = "Password must be more than 8 characters!";
        }

        if (!preg_match("#[0-9]+#", $password)) {
            $un_allowed_password[] = "Password must include at least one number!";
        }


        if (!preg_match('/(?=.*[a-z])(?=.*[A-Z])/', $password)) {
            $un_allowed_password[] = "Password must have a lower and caps letters!";
        }

        if (!preg_match('@[^\w]@', $password)) {
            $un_allowed_password[] = "Password must include at least one a special character!";
        }

        echo json_encode($un_allowed_password);
        
        
    }
      /**
     * process_reCAPTCHA(): processes google reCAPTCHA to avoid spaming
     * @author Onduso 
     * @access public 
     * @return void
     */
    public function process_reCAPTCHA():void
    {
        $recaptcha = $this->input->post('g-recaptcha-response');

         // Secret key from google console LOCAL DEVELOPMENT KEY

        //Production/Local key change accordingly
        $secret_key =$this->aws_parameter_library->get_parameter_value('google-reCAPTCHA-secret-key-prod');

      
        // Hitting request to the URL, Google responds with success or error scenario
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret='. $secret_key . '&response=' . $recaptcha;

        // Making request to verify captcha
        $response = file_get_contents($url);

        // Google response json
        $response = json_decode($response);

        // Checking, if response is true or not
        // log_message('error',$response);
        if ($response->success == true) {
            echo 1;
        } else {
            echo 0;
        }
    }
    /**
     * get_user_activator_ids(): returns array of user_ids
     * @author Onduso 
     * @access public 
     * @Dated: 16/8/2023
     * @return void
     * @param int $user_type,int $office_id, int $country_id
     */
    public function get_user_activator_ids(int $user_type, int $office_id, int $country_id): void
    {
        //  $user_type=$this->input->post('user_type');

        //  $office_id=$this->input->post('user_office');

        echo json_encode($this->login_model->get_user_activator_ids($user_type, $office_id, $country_id));
    }
    /**
     * save_create_account_data(): save form data to database 
     * @author Onduso 
     * @access public 
     * @Dated: 15/8/2023
     * @return void
     */
    public function save_create_account_data(): void
    {

        $this->write_db->trans_begin();
        //Save in User Table
        $email = strtolower($this->input->post('email'));
        $user_name = explode('@', $email)[0];
        $first_name = $this->input->post('first_name');
        $surname = $this->input->post('surname');

        $user_office = $this->input->post('user_office');

        $plain_text_password = $this->input->post('password');

        //Hash password
        $hashed_password = $this->password_salt($plain_text_password);
        $user_type = $this->input->post('user_type');

        $last_insert = $this->save_data_in_user_table($first_name, $surname, $email, $user_name, $user_type, $hashed_password);

        //Save in Department user Table
        $department_name = 'Department for' . ' ' . $first_name . ' ' . $surname;

        $this->save_data_in_department_user($department_name, $last_insert);

        //Save data in context_user tables
        $designation = $this->input->post('user_designation');

        switch ($user_type) {
            case 1:
                $context_data = $this->insert_into_context_user_table($first_name, $surname, $user_office, $designation, $last_insert, 'context_center', 'context_center_id');
                //Insert Data in context_user table
                $this->write_db->insert('context_center_user',  $context_data);
                break;
            case 2:
                $context_data = $this->insert_into_context_user_table($first_name, $surname, $user_office, $designation, $last_insert, 'context_cluster', 'context_cluster_id');
                //Insert Data in context_user table
                $this->write_db->insert('context_cluster_user',  $context_data);
                break;
            case 3:
                $context_data = $this->insert_into_context_user_table($first_name, $surname, $user_office, $designation, $last_insert, 'context_cohort', 'context_cohort_id');
                //Insert Data in context_user table
                $this->write_db->insert('context_cohort_user',  $context_data);
                break;
            case 4:
                $context_data = $this->insert_into_context_user_table($first_name, $surname, $user_office, $designation, $last_insert, 'context_country', 'context_country_id');
                $this->write_db->insert('context_country_user',  $context_data);
                //Insert Data in context_user table
                break;
            case 5:
                $context_data = $this->insert_into_context_user_table($first_name, $surname, $user_office, $designation, $last_insert, 'context_country', 'context_country_id');
                $this->write_db->insert('context_country_user',  $context_data);
                //Insert Data in context_user table
                break;
        };

        //Save Data in the user_account_activation table

        $user_activation_name = $first_name . ' ' . $surname;

        $this->save_data_in_user_account_activation($user_activation_name, $last_insert, $user_office);


        // Commit or rollback if any issue in either user, context related tables and department_user table
        if ($this->write_db->trans_status() == false) {

            $this->write_db->trans_rollback();

            echo "Account Not Created contact the system administration";
        } else {

            $this->write_db->trans_commit();

            echo "Account Created System Administrator will activate soon";
        }
    }
    /**
     * save_data_in_user_table(): save user data in user table. 
     * @author Onduso 
     * @access private 
     * @return string
     * @dated: 18/08/2023
     * @param string $first_name, string $surname, string $email, string $user_name, int $user_type, string $hashed_password
     */
    private function save_data_in_user_table(string $first_name, string $surname, string $email, string $user_name, int $user_type, string $hashed_password): int
    {

        //5 = other national office staffs e.g health specialist
        //4 =contry admins
        $user_is_context_manager=0;

        if($user_type==4){
            $user_is_context_manager=1;
        }

        if($user_type==5){
            
            $user_type=4;
        }
        
        $user_data['user_firstname'] = $first_name;
        $user_data['user_lastname'] = $surname;
        $user_data['user_email'] = $email;
        $user_data['user_name'] = $user_name;
        $user_data['fk_context_definition_id'] = $user_type;
        $user_data['user_password'] = $hashed_password;
        $user_data['user_is_context_manager'] = $user_is_context_manager;
        $user_data['user_is_system_admin'] = 0;
        $user_data['fk_language_id'] = $this->input->post('country_language');
        $user_data['fk_country_currency_id'] = $this->input->post('country_currency');
        $user_data['user_is_active'] = 0;
        $user_data['fk_role_id'] = $this->input->post('user_role');
        $user_data['fk_account_system_id'] = $this->input->post('user_country');
        $user_data['user_first_time_login'] = 0;
        $user_data['md5_migrate'] = 1;
        $user_data['user_track_number '] = $this->grants_model->generate_item_track_number_and_name('user')['user_track_number'];
        $user_data['user_created_date'] = date('Y-m-d');

        $this->write_db->insert('user', $user_data);

        return $this->write_db->insert_id();
    }


    /**
     * get_office_name(): get office name of the user; 
     * @author Onduso 
     * @access private 
     * @return string
     * @dated: 18/08/2023
     * @param int $user_office
     */
    private function get_office_name(int $user_office): string
    {

        $this->read_db->select(['office_name']);
        $this->read_db->where(['office_id' => $user_office]);
        $user_office_name = $this->read_db->get('office')->row()->office_name;

        return $user_office_name;
    }
    /**
     * save_data_in_user_account_activation(): saves user activation data in user_account_activation; 
     * @author Onduso 
     * @access private 
     * @return array
     * @param string $user_activation_name, int $last_inserted_id, int $user_office_id
     */
    private function save_data_in_user_account_activation(string $user_activation_name, int $last_inserted_id, int $user_office_id): void
    {

        $user_office = $this->get_office_name($user_office_id);

        $user_activator_ids['user_account_activation_name'] = $user_activation_name;
        $user_activator_ids['user_account_activation_track_number'] = $this->grants_model->generate_item_track_number_and_name('user_account_activation')['user_account_activation_track_number'];
        $user_activator_ids['user_activator_ids'] = $this->input->post('user_activator_ids');
        $user_activator_ids['fk_user_id'] = $last_inserted_id;
        $user_activator_ids['user_type'] = $this->input->post('user_type');
        $user_activator_ids['user_account_activation_created_date'] = date('Y-m-d');
        $user_activator_ids['user_works_for'] = $user_office;
        $this->write_db->insert('user_account_activation',  $user_activator_ids);
    }

    /**
     * save_data_in_department_user(): saves department user data; 
     * @author Onduso 
     * @access private 
     * @return array
     * @param string $department_name, int $last_inserted_id
     */
    private function save_data_in_department_user(string $department_name, int $last_inserted_id): void
    {

        $department_data['fk_department_id'] = $this->input->post('user_department');
        $department_data['department_user_track_number'] = $this->grants_model->generate_item_track_number_and_name('department_user')['department_user_track_number'];
        $department_data['department_user_name'] = $department_name;
        $department_data['fk_user_id'] = $last_inserted_id;
        $department_data['department_user_created_date'] = date('Y-m-d');

        $this->write_db->insert('department_user', $department_data);
    }


    /**
     * insert_into_context_user_table(): saves data in context tables as context_center_user 
     * @author Onduso 
     * @access private 
     * @return array
     * @param string $first_name, string $surname,int $user_office, int $designation, int $last_insert, string $context_table_name, string $context_column_name
     */
    private function insert_into_context_user_table(string $first_name, string $surname, int $user_office, int $designation, int $last_insert, string $context_table_name, string $context_column_name): array
    {
        $context_name = 'Office context' . 'for ' . $first_name . ' ' . $surname;
        //Get the context_id
        $this->read_db->where(['fk_office_id' => $user_office]);
        $context_center_id = $this->read_db->get($context_table_name)->row()->$context_column_name;

        $context_data[$context_table_name . '_user_track_number'] = $this->grants_model->generate_item_track_number_and_name($context_column_name . '_user')[$context_column_name . '_user_track_number'];
        $context_data[$context_table_name . '_user_name'] = $context_name;
        $context_data['fk_' . $context_table_name . '_id'] = $context_center_id;
        $context_data['fk_user_id '] = $last_insert;
        $context_data[$context_table_name . '_user_is_active'] = 1;
        $context_data[$context_table_name . '_user_created_by'] = $last_insert;
        $context_data[$context_table_name . '_user_created_date'] = date('Y-m-d');

        $context_data['fk_designation_id'] = $designation;

        return $context_data;
    }
    /**
     * email_exists(): check if email exists
     * @author Onduso 
     * @access public 
     * @return void
     */
    function email_exists(): void
    {

        $email = $this->input->post('email');

        $is_email_present = $this->login_model->email_exists($email);

        echo $is_email_present;
    }
    /**
     * verify_valid_email(): checks if email an is a correct formated email
     * @author Onduso 
     * @access public 
     * @return void
     */
    function verify_valid_email(): void
    {

        $email = $this->input->post('email');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * create_password(): calls the create_password view and sets user data
     * @author Onduso 
     * @access public 
     * @return void
     */
    public function create_password(): void
    {
        $this->session->set_userdata('update_user_password', false);
        $this->load->view('general/create_password');
    }

    /**
     * change_password_from_md5_to_sha256
     */
    function change_password_from_md5_to_sha256(String $email): void {

        $old_md5_password = $this->input->post('password');
        //Hash Password
        $hashed  = $this->password_salt($old_md5_password); 

        $data['user_password']=$hashed ;
        $data['md5_migrate']=1;
        $this->write_db->where(array('user_email'=>$email));
        $this->write_db->update('user',$data);
    }
    /**
     * change_password(): used to update or change existing user password.
     * @author Karisa 
     * @modified by Onduso  
     * @access public 
     * @return void
     */
    public function change_password(): void
    {

        $this->read_db->select(array('user_password'));
        if ($this->session->user_id == '') {
            $this->read_db->where(array('user_email' => $this->input->post('email')));
        } else {
            $this->read_db->where(array('user_id' => $this->session->user_id));
        }

        $old_password = $this->read_db->get('user')->row()->user_password;


        $new_password = $this->input->post('new_password');

        //Hash Password
        $hashed = $this->password_salt($new_password);

        //$data['user_password']=md5($new_password);
        $data['user_password'] = $hashed;

        $data['user_first_time_login'] = 1;

        $data['md5_migrate'] = 1;

        if (md5($data['user_password']) == md5($old_password)) {
            echo -1;
        } elseif ($data['user_password'] == $this->password_salt($old_password)) {
            echo -1;
        } else {

            $this->write_db->trans_start();
            if ($this->session->user_id == '') {
                $this->write_db->where(array('user_email' => $this->input->post('email')));
            } else {
                $this->write_db->where(array('user_id' => $this->session->user_id));
            }

            $this->write_db->update('user', $data);
            $this->write_db->trans_complete();


            if ($this->write_db->affected_rows() == '1') {
                // $this->session->set_userdata('update_user_password', false);
                echo 1;
            } else {
                // any trans error?
                if ($this->write_db->trans_status() === FALSE) {
                    echo 0;
                }
                echo 1;
            }
        }
    }

    function create_missing_language_files()
    {
        // Check if an account system language packs are available if not create them

        $this->load->library('language_library');

        $lang_file = $this->session->user_locale . '_lang.php';

        $lang_path = APPPATH . 'language' . DIRECTORY_SEPARATOR . $this->session->user_account_system . DIRECTORY_SEPARATOR;

        if (!file_exists($lang_path . $lang_file)) {

            $this->language_library->create_language_files($this->session->user_locale, $this->session->user_account_system);

            $default_language_code = $this->language_model->default_language()['language_code'];

            if (!file_exists($default_language_code . '_lang.php')) {
                $this->language_library->create_language_files($default_language_code, $this->session->user_account_system);
            }
        }
    }

    public function switch_user($user_id = '')
    {

        $user_id = $user_id == '' ? $this->input->post('user_id') : hash_id($user_id, 'decode');

        //$this->session->sess_destroy();

        $this->read_db->where(['user_id' => $user_id]);
        $user = $this->read_db->get('user')->row_array();

        $current_user_email = $this->read_db->get_where('user',array('user_id' => $this->session->user_id))->row()->user_email;

        $email = !isset($user['user_email']) ? $current_user_email : $user['user_email'] ;

        $login_status = $this->validate_login($email, '', true);

        if ($login_status) {

            redirect(base_url() . 'login', 'refresh');
        } else {
            redirect(base_url(), 'refresh');
        }
    }

    /*     * *DEFAULT NOR FOUND PAGE**** */

    function four_zero_four()
    {
        $this->load->view('four_zero_four');
    }

    function reset_password($hashed_user_id, $token){
        // Terminate all active user session access by this client
        $this->session->sess_destroy();

        $user_id = hash_id($hashed_user_id, 'decode');

        $this->read_db->select(array('user_password_reset_token'));
        $this->read_db->where(array('user_id' =>$user_id));
        $user_obj = $this->read_db->get('user');

        $is_password_reset_link_active = false;

        if($user_obj->num_rows() > 0){
            $token_data = $user_obj->row()->user_password_reset_token;

            if($token_data !=  NULL){
                $token_data_array = json_decode($token_data);
                $database_token = $token_data_array->token;
                $token_expiration_time = $token_data_array->expiration;

                if($token_expiration_time > time() && $database_token == $token){
                    $is_password_reset_link_active = true;
                }
            }
        }

        $view_page = 'general/reset_password';

        if(!$is_password_reset_link_active){
            $view_page = 'general/user_token_expiration';
        }

        $this->load->view($view_page);
    }

    // PASSWORD RESET BY EMAIL
    function forgot_password()
    {
        $this->load->view('general/forgot_password');
    }
    /**
     * create_account(): allows new user of the system create an account and exposes  the create_account form
     * @author Onduso 
     * @access public 
     * @return void
     */
    function create_account(): void
    {
        $this->load->view('general/create_account');
    }

    /**
     * get_user_departments_roles_and_designations(): returns departments based on selected office context e.g. fcp/cluster
     * @author Onduso 
     * @access public 
     * @return void
     * @param int $context_definition_id
     */
    public function get_user_departments_roles_and_designations(int $context_definition_id, string $table_name, string $elementId, int $countryID): void
    {

        $departments_roles_and_designations = $this->login_model->get_user_departments_roles_and_designations($context_definition_id, $table_name, $countryID);

        echo json_encode($departments_roles_and_designations);
    }

    public function ajax_reset_password(){
        $resp                   = array();
        $resp['status']         = 'false';
        //$reset_account_type     = '';
        //resetting user password here
        $new_password           =   $_POST["password"];
        $user_id = hash_id($_POST['user_id'], 'decode');

        // log_message('error', json_encode($user_id));

        // Checking credential for user
        $query = $this->read_db->get_where('user', array('user_id' => $user_id));

        $user = [];

        if ($query->num_rows() > 0) {
            $user = $query->row();

            $reset_data = array('user_password' => $this->password_salt($new_password), 'user_first_time_login' => 1, 'user_password_reset_token' => NULL);
            $this->write_db->where('user_id', $user_id);
            $this->write_db->update('user', $reset_data);
            $resp['status']         = 'true';

            $this->load->model('email_template_model');

            $tags['user'] =  $user->user_firstname . ' ' . $user->user_lastname;
            $tags['email'] = $user->user_email;
            $tags['password'] = $new_password;

            $email_subject = get_phrase('password_reset_notification');

            $email_body = file_get_contents(APPPATH . 'resources/email_templates/en/password_reset.txt'); // Template language should be from user session

            $mail_recipients['send_to'] = [$user->user_email]; // must be an array

            $this->email_template_model->log_email($tags, $email_subject, $email_body, $mail_recipients);

        }



        echo json_encode($resp);
    } 
    function ajax_forgot_password()
    {
        $resp                   = array();
        $resp['status']         = 'false';
        $email                  = $_POST["email"];
        //$reset_account_type     = '';
        //resetting user password here
        $new_password           =   substr(md5(rand(100000, 200000)), 0, 7);

        // Checking credential for user
        $query = $this->read_db->get_where('user', array('user_email' => $email));

        $user = [];

        if ($query->num_rows() > 0) {
            $user = $query->row();

            $this->write_db->where('user_email', $email);
            $this->write_db->update('user', array('user_password' => $this->password_salt($new_password), 'user_first_time_login' => 0));
            $resp['status']         = 'true';

            $this->load->model('email_template_model');

            $tags['user'] =  $user->user_firstname . ' ' . $user->user_lastname;
            $tags['email'] = $email;
            $tags['password'] = $new_password;

            $email_subject = get_phrase('password_reset_notification');

            $email_body = file_get_contents(APPPATH . 'resources/email_templates/en/password_reset.txt'); // Template language should be from user session

            $mail_recipients['send_to'] = [$email]; // must be an array

            $this->email_template_model->log_email($tags, $email_subject, $email_body, $mail_recipients);

            $resp['submitted_data'] = $_POST;
        }



        echo json_encode($resp);
    }


    function logout()
    {
        $this->session->sess_destroy();
        $this->session->set_flashdata('logout_notification', 'logged_out');
        redirect(base_url(), 'refresh');
    }
}
