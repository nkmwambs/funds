<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
  extract($result);
  //print_r($this->session->context_offices);
  //echo count((array)$this->voucher_model->get_office_last_voucher(9));
  //print_r($this->voucher_model->get_approveable_item_last_status(3));
?>

<div class="row">
	<div class="col-md-12">
		<div class="panel panel-default" data-collapsed="0">
        	<div class="panel-heading">
            	<div class="panel-title" >
            		<i class="entypo-plus-circled"></i>
					<?php echo get_phrase('add_'.$this->controller);?>
            	</div>
            </div>
			         <div class="panel-body">

              <?php echo form_open(base_url().$this->controller.'/'.$this->action , array('id'=>'add_form','class' => 'form-horizontal form-groups-bordered', 'enctype' => 'multipart/form-data'));?>
                <div class="form-group">
                    <div class="col-xs-12">
                      <?=Widget_base::load('position','position_4');?>
                    </div>
                  </div>
              <?php                  
                  foreach ($fields as $column => $field) {

                    if( strpos($column,'_id') == true ||
                        strpos($column,'_track_number') == true ||
                        strpos($column,'_created_date') == true ||
                        strpos($column,'_last_modified_date') == true ||
                        strpos($column,'_last_modified_by') == true ||
                        strpos($column,'_created_by') == true ||
                        $column == 'approval_name' ||
                        $column == 'status_name' 
                        //strpos($column,'_name') == true
                    ){
                      continue;
                    }
                ?>
                  <div class="form-group">
                    <label for="" class="control-label col-xs-3"><?=ucwords(str_replace("_"," ",$column));?></label>
                    <div class="col-xs-9">
                      <?php
                        //echo $this->grants->header_row_field($column);
                        echo $field;
                      ?>

                    </div>
                  </div>
                <?php
                  }
                 ?>

                 <div class="form-group">
                   <div class="col-xs-12" style="text-align:center;">
                       <div class="btn btn-default insert_row"><?=get_phrase('insert_'.$this->controller.'_detail_row');?></div>
                       <div class="btn btn-default reset"><?=get_phrase('reset');?></div>
                       <button class="btn btn-default save back"><?=get_phrase('save');?></button>
                       <button class="btn btn-default save_new"><?=get_phrase('save_and_new');?></button>
                   </div>
                 </div>

                 <div class="form-group">
                    <div class="col-xs-12">
                      <?=Widget_base::load('position','position_5');?>
                      <?=$this->voucher_library->list_project_allocation_without_office_bank_linkage();?>
                    </div>
                  </div>

                <div class="form-group">
                  <div class="col-xs-12">
                    <table class="table table-bordered detail">
                      <thead>
                        <tr>
                          <th><?=get_phrase('action');?></th>
                        <?php

                          foreach ($detail_table as $detail_table_key) {
                            if(
                              $this->grants->is_primary_key_field($dependant_table,$detail_table_key) ||
                              $this->grants->is_history_tracking_field($dependant_table,$detail_table_key) ||
                              $this->grants->is_name_field($this->controller,$detail_table_key) ||
                              $this->grants->is_name_field($dependant_table,$detail_table_key)
                            ) 
                              continue;
                        ?>
                            <th class="th_data" id="<?="th_".$detail_table_key;?>"><?=ucwords(str_replace("_"," ",$detail_table_key));?></th>
                        <?php
                          }
                         ?>
                       </tr>
                      </thead>

                      <tbody></tbody>
                      <tfoot class="hidden">
                          <tr>
                            <td colspan="<?=count($detail_table);?>"><?=get_phrase('grand_total')?></td>
                            <td><input type="number" readonly="readonly" class="form-control" id="grand_total" value="0" /></td>
                          </tr>
                      </tfoot>
                    </table>
                  </div>
                </div>

                <div class="form-group">
                    <div class="col-xs-12">
                      <?=Widget_base::load('position','position_6');?>
                    </div>
                  </div>


                <div class="form-group">
                  <div class="col-xs-12"  style="text-align:center;">
                    <div class="btn btn-default insert_row"><?=get_phrase('insert_'.$this->controller.'_detail_row');?></div>
                    <div class="btn btn-default reset"><?=get_phrase('reset');?></div>
                    <button class="btn btn-default save back"><?=get_phrase('save');?></button>
                    <button class="btn btn-default save_new"><?=get_phrase('save_and_new');?></button>
                  </div>
                </div>

              </form>
        </div>
      </div>
    </div>
  </div>

  <script type="text/javascript">

  // $(document).on('.datepicker','click',function(){
  //   $(this).datepicker();
  // });

    $('.insert_row').click(function(ev){

      pre_row_insert();
      
      var url = "<?=base_url();?><?=$this->controller;?>/detail_row";
      
      var fields = [];

      $(".th_data").each(function(i,el){
        fields[i] = $(this).attr('id').replace('th_','');
      });

      var data = {fields};
      
      insert_row(url,data);

      post_row_insert();

    });

  function insert_row(url,data){

    $.ajax({
      url:url,
      data:data,
      type:"POST",
      beforeSend:function(){
        $('#overlay').css('display','block');
      },
      success:function(response){
          $('#overlay').css('display','none');
          var obj = JSON.parse(response);

          var detail_row = "<tr><td><div class='btn btn-danger' onclick='delete_row(this);'><?=get_phrase('delete');?></div></td>";

          $.each(obj,function(i,el){
              detail_row += "<td class='td_"+i+"'>"+el+"</td>";
          });

            detail_row += "</tr>";
        $('.detail tbody').append(detail_row);
        on_row_insert();
      },
      error:function(){

      }
    });
  }  

  function delete_row(el){
    pre_row_delete();
    var cnfm = confirm('<?=get_phrase("are_you_sure_you_want_to_delete_this_row?");?>');

    if(!cnfm){
      alert('<?=get_phrase("proccess_aborted!");?>');
    }else{
      $(el).closest('tr').remove();
      on_row_delete();
    }

    post_row_delete();

  }

  $('.form-control').keydown(function(){
    $(this).removeAttr('style');
  });

  $(".save, .save_new").on('click',function(ev){
    
    var elem = $(this);

      //Check if all required fields are filled
  var empty_fields_count = 0;
  $('.form-control').each(function(i,el){
    if($(el).val() == '' && !$(el).hasClass('hidden')){
      $(el).css('border','1px solid red');
      empty_fields_count++;
    }
  });

  if(empty_fields_count>0){
    alert('1 or more required fields are empty');
  }else{

    pre_record_post();

    var url = "<?=base_url().$this->controller;?>/<?=$this->action;?>";

    if('<?=$this->uri->segment(3,0);?>' !== 0){
      url = "<?=base_url().$this->controller;?>/<?=$this->action;?>/<?=$this->uri->segment(3,0);?>";
    }

    var data = $(this).closest('form').serializeArray();

    $.ajax({
      url:url,
      data:data,
      type:"POST",
      beforeSend:function(){

      },
      success:function(response){
        alert(response);
        
        on_record_post();
        //If Save , use the browser history and go back
        if(elem.hasClass('back')){
          //window.history.back(1); 
          location.href = document.referrer         
        } else{
          document.getElementById('add_form').reset();
          //Remove all rows of the detail table
          $(".detail tbody").empty();
        }
      },
      error:function(){

      }
    });

    post_record_post();
  }  
    ev.preventDefault();
  });

$(".reset").on('click',function(){
  location.reload();
});
  </script>
