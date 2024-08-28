<?php 

    $default_option = 0;
    $active_controller = $this->CI->controller;
    $get_max_approval_status_id = $this->CI->general_model->get_max_approval_status_id(strtolower($active_controller)); 

    $this->CI->read_db->select(array('status_id','status_name'));
    $this->CI->read_db->join('approval_flow','approval_flow.approval_flow_id=status.fk_approval_flow_id');
    $this->CI->read_db->join('approve_item','approve_item.approve_item_id=approval_flow.fk_approve_item_id');
    $this->CI->read_db->where(array('approval_flow.fk_account_system_id'=>$this->CI->session->user_account_system_id));
    $this->CI->read_db->where( array('approve_item_name'=>strtolower($active_controller)));
    
    //$this->CI->read_db->where(array('status_id'=>$get_max_approval_status_id));

    $status_array = $this->CI->read_db->get('status');

   
?>
    
    <div class="col-xs-offset-3 col-xs-6">
        <select name="page_view" class="form-control select2" id="status_id">
            <option value="0"><?=get_phrase('select_filter');?></option>
            <?php 
                if($status_array->num_rows()>0){
                    $list_of_status = $status_array->result_object();

                    foreach($list_of_status as $status){
                      ?>
                        <option value="<?=hash_id($status->status_id,'encode');?>" 
                        
                        <?php 
                            if (    
                                    $status->status_id == $get_max_approval_status_id
                                         
                                ) 
                                {
                                    echo "selected";
                                }
                                    
                        ?> 
                        
                        >
                        <?=$status->status_name;?>
                        </option>
                      <?php  
                    }
                }
            ?>
        </select>
    </div>
    <div class="col-xs-3"> 
        <button href="#" id="btn_filter" name="btn_page_view" value="btn_page_view" class="btn btn-default"><?=get_phrase('filter');?></button>
    </div>  

    <script>
    $("#btn_filter").on('click',function(){
        var status_id = $("#status_id").val();
        
        if(status_id != 0){
            location.href = "<?=base_url();?><?=$active_controller;?>/list/"+status_id;
        }else{
            alert('Choose a valid status');
        }
        
    });
    </script>  
