<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
    extract($result);    
?>

<div class="row" style="margin-bottom:85px;">
  <div class="col-xs-12" style="text-align:center;">

    <?php
    if($show_add_button && $this->user_model->check_role_has_permissions(ucfirst($this->controller),'create')){
      echo add_record_button($this->controller, $has_details_table,null,$has_details_listing, $is_multi_row);
    }
    ?>
    <?=Widget_base::load('position','position_1');?>
  </div>
</div>

<div id="load_data">
    <?php include "ajax_list.php";?>
</div>

<script>
    $(document).on('click',".expand",function(){
        var context_id = $(this).data('context_id');
        var context_definition_id = $(this).data('context_definition_id');
        var url = "<?=base_url();?>office/get_lower_offices";
        var data = {'context_id':context_id,'context_definition_id':context_definition_id};

        $.post(url,data,function(response){
            //alert(response);
            $("#load_data").html(response);
        });
    });
</script>

