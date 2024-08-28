<?php 

    extract($result);
?>

<style>
    .header_item{
        font-weight:bold;
    }

    .cancel-role{
        background-color:white;
        color:black;
        border-radius:10px;
        border:1px white solid;
        cursor:pointer;
    }
</style>

<div class='row'>
  <div class='col-xs-12'>
    <?php
        if($this->grants->show_add_button('permission') && $this->user_model->check_role_has_permissions('permission','create')){
            echo add_record_button('permission', false,null,false);
        }
    ?>   
  </div>
</div>

<hr/>

<div class='row'>
    <div class='col-xs-12'>

    <div class="panel panel-default" data-collapsed="0">
       	    <div class="panel-heading">
           	    <div class="panel-title" >
           		    <i class="entypo-plus-circled"></i>
					    <?php echo get_phrase('permissions');?>
           	    </div>
            </div>
	    
            <div class="panel-body"  style="max-width:50; overflow: auto;">	

            
                <table class='table'>
                    <?php
                        foreach($permission_labels as $permission_label_id=>$permission_label){
                    ?>
                        <thead>
                        <tr>
                            <th colspan='4' style='text-align:center;font-weight:bold;font-size:11pt;'>
                                <?=ucfirst($permission_label);?> <?=get_phrase('permissions');?>
                            </th>
                        </tr>
                        <tr>
                            <th class='header_item'><?=get_phrase('permissions');?></th>
                            <th class='header_item'>Access Type</th>
                            <th class='header_item'>Access Item</th>
                            <th class='header_item'><?=get_phrase('roles');?></th>
                        </tr>
                        </thead>
                        <tbody>
                       
                        <?php 
                            if(array_key_exists($permission_label_id,$permissions)){
                                foreach($permissions[$permission_label_id] as $permission_record){
                        ?>
                                <tr>
                                    <td><?=$permission_record['permission_name'];?>
                                    </td><td><?=$permission_record['permission_type'] == 1?get_phrase('page_access'):get_phrase('field_access');?></td>
                                    <td><?=$permission_record['menu_name']?></td>
                                    <td>
                                        <i id='<?=$permission_record['permission_id'];?>' class='fa fa-plus add_role' label='<?=get_phrase('add_a_role');?>' style='cursor:pointer;'></i></br>
                                        <div class='role_dropdown'></div></br>
                                        <?php 
                                            if(array_key_exists($permission_record['permission_id'],$roles_permission)){
                                                
                                                foreach($roles_permission[$permission_record['permission_id']] as $role_permission){
                                                    $role_label_color = "primary";

                                                    if($role_permission['role_permission_is_active'] == 0){
                                                        $role_label_color = "danger";
                                                    }
                                        ?>  
                                                <span id="<?=$permission_record['permission_id'];?>-<?=$role_permission['role_id'];?>" class='label label-<?=$role_label_color;?>'>
                                                    <?=$role_permission['role_name'];?> &nbsp; &nbsp; <i class='fa fa-times cancel-role'></i>
                                                </span></br>
                                        <?php 
                                                }
                                            }
                                        
                                        ?>
                                    </td>
                                </tr>
                        <?php 
                                }
                            }
                        ?>

                        </tbody>
                    <?php 
                        }
                    ?>
                </table>
            </div>    
        </div>
    </div>
</div>    

<script>
$('.cancel-role').on('click',function(){
    //$(this).closest('span').remove();
    remove_role_label(this);
});

$(".add_role").on('click',function(){
    var permission_id = $(this).attr('id');
    var elem  = $(this);
    var url = "<?=base_url();?>permission_label/get_available_roles_for_permission/";
    var data = {'permission_id':permission_id};

    $.ajax({
        url:url,
        data:data,
        type:"POST",
        success:function(response){
            //alert(response);
            elem.siblings().next('.role_dropdown').html(response);
        }
    });

});

$(document).on('change','.available-roles',function(){
    var selected_option_text = $(this).find('option:selected').text();
    var selected_option_value = $(this).val();
    var elem_id = $(this).attr('id');
    var elem = $(this);
    
    var url = "<?=base_url();?>permission_label/create_a_new_role_permission";
    var data = {'role_id':selected_option_value,'permission_id':elem_id,'role_name':selected_option_text};

    $.ajax({
        url:url,
        data:data,
        type:"POST",
        success:function(response){
            if(response){
                elem.find('option:selected').remove();
                elem.closest('td').append('<span id="'+elem_id+'-'+selected_option_value+'" class="label label-primary">' + selected_option_text + ' &nbsp; &nbsp; <i class="fa fa-times cancel-role" onclick="remove_role_label(this);"></i></span></br>');
            }else{
                alert("Role permission assignment failure");
            }
            
        }
    });
});

function remove_role_label(elem){

    var role_permission_is_active = 0; 

    // This piece has to be always called before data obj is created. It sets the role_permission_is_active
   if($(elem).closest('span').hasClass('label-primary')){
    $(elem).closest('span').removeClass('label-primary').addClass('label-danger');
    $(elem).removeClass('fa-times').addClass('fa-check');
   }else{
    $(elem).closest('span').removeClass('label-danger').addClass('label-primary');
    $(elem).removeClass('fa-check').addClass('fa-times');
    role_permission_is_active = 1;
   }

   var id = $(elem).closest('span').attr('id');
   var split_id = id.split('-');

   var url = "<?=base_url();?>permission_label/update_role_permission";
   var data = {'role_id':split_id[1],'permission_id':split_id[0],'role_permission_is_active':role_permission_is_active};

   $.ajax({
        url:url,
        data:data,
        type:"POST",
        success:function(response){
            alert(response);
        }
   });

}
</script>