<?php

class Ajax_model extends CI_Model{
    
    var $table = '';
	var $column_order = array(); //set column field database for datatable orderable
	var $column_search = array(); //set column field database for datatable searchable 
    var $order = array(); // default order 
    
    function __construct(){
		$this->table = $this->controller;
		
        parent::__construct();
		$this->load->database();   
		
		$this->column_order = $this->grants->toggle_list_select_columns();
		
		$this->column_search = $this->grants->toggle_list_select_columns();
		
		$this->order = array($this->table.'_id' => 'asc'); // default order 
		
    }

    private function _get_datatables_query()
	{	
		
		//Custom page view filters are applied here (Applies for status as at now - plan to expand it to other fields)
		//$this->grants->where_condition('page_view',$this->table,$this->input->post('page_view'));

		//Loading default center records
		$this->grants->list_table_where();
		
		//This is a join statement
		$this->grants->create_table_join_statement($this->table,$this->grants->lookup_tables($this->table));	
		
		$this->db->from($this->table);

		$i = 0;
	
		foreach ($this->column_search as $item) // loop column 
		{
			if($_POST['search']['value']) // if datatable send POST for search
			{
				
				if($i===0) // first loop
				{
					$this->db->group_start(); // open bracket. query Where with OR clause better with bracket. because maybe can combine with other WHERE with AND.
					$this->db->like($item, $_POST['search']['value']);
				}
				else
				{
					$this->db->or_like($item, $_POST['search']['value']);
				}

				if(count($this->column_search) - 1 == $i) //last loop
					$this->db->group_end(); //close bracket
			}
			$i++;
		}
		
		if(isset($_POST['order'])) // here order processing
		{
			$this->db->order_by($this->column_order[$_POST['order']['0']['column']], $_POST['order']['0']['dir']);
		} 
		else if(isset($this->order))
		{
			$order = $this->order;
			$this->db->order_by(key($order), $order[key($order)]);
		}
	}

	function get_datatables()
	{
		$this->_get_datatables_query();
		if($_POST['length'] != -1)
		$this->read_db->limit($_POST['length'], $_POST['start']);
		$query = $this->read_db->get();
		return $query->result();
	}	

	function count_filtered()
	{
		$this->_get_datatables_query();
		$query = $this->read_db->get();
		return $query->num_rows();
	}

	public function count_all()
	{
		$this->db->from($this->table);
		return $this->db->count_all_results();
    }
    
}