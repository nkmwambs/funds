<?php 
extract($result);

$columns = array_chunk(array_keys($header),$this->config->item('master_table_columns'),true);
?>


<div class="row">
  <div class="col-xs-12">
      <?=Widget_base::load('comment');?>
  </div>
</div>

<div class="row">
  <div class="col-xs-12" id='print_pane'>
    <table class="table">
      <thead>
        <tr>
          <th colspan="<?=$this->config->item('master_table_columns');?>" style="text-align:center;"><?=get_phrase($this->uri->segment(1).'_master_record');?>
          </th>
        </tr>

        <tr>
          <th colspan="<?=$this->config->item('master_table_columns');?>" style="text-align:center;">
          <?php 
              
                if($this->user_model->check_role_has_permissions(ucfirst($this->controller),'update'))
                {
                     echo Widget_base::load('button',get_phrase('edit'),$this->controller.'/edit/'.$this->id);
                }
  
                if($this->user_model->check_role_has_permissions(ucfirst($this->controller),'delete'))
                {
                    echo Widget_base::load('button',get_phrase('delete'),$this->controller.'/delete/'.$this->id);
                }              
              
              if(isset($action_labels['show_label_as_button']) && $action_labels['show_label_as_button']){ 
              
                  echo Widget_base::load('button',$action_labels['button_label'],$this->controller.'/approve/'.$this->id);
              }
              
              if(isset($action_labels['show_decline_button']) && $action_labels['show_decline_button']){
                   echo Widget_base::load('button',get_phrase('decline'),$this->controller.'/decline/'.$this->id);
              
               }

              

                echo Widget_base::load('button',get_phrase('print'),'#','btn_print','hidden-print');
               ?>     
                  
          </th>
        </tr>
      </thead>
      <tbody>


        <?php

            foreach ($columns as $row) {
          ?>
            <tr>
          <?php
              foreach ($row as $column) {
                $column_value = $header[$column];             
          ?>
                <td>
                  <span style="font-weight:bold;">
                    <?php
                        echo get_phrase($column);
                    ?>:</span> &nbsp;
                  <?php
                    echo $column_value;
                  ?>
                </td>
          <?php
              }
          ?>
              </tr>
          <?php
            }
          ?>
          
      </tbody>
    </table>
  </div>
</div>

<hr/>

<?php 
    foreach($result['detail'] as $table_name => $detail){
        extract($detail);
?>
<div class="row" style="margin-bottom:25;">
  <div class="col-xs-12" style="text-align:center;">
        <?php 
            
            if($show_add_button && $this->user_model->check_role_has_permissions(ucfirst($table_name),'create')){
                echo add_record_button($table_name, $has_details_table,$this->uri->segment(3,null),$has_details_listing, $is_multi_row);
              }
        ?>
    </div>
</div>


<div class="row">
    <div class="col-xs-12">
        <table class="table table-striped detail_datatable" id="<?=$table_name;?>">
            <thead>
                <tr>
                    <?php 
                        foreach($columns as $column){
                    ?>
                            <th><?=get_phrase($column);?></th>
                    <?php
                        }
                    ?>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<script>
        var url = "<?=base_url();?><?=$table_name;?>/show_list";
        var datatable = $("#<?=$table_name;?>").DataTable({
            dom: 'lBfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5',
            ],
            pagingType: "full_numbers",
            // stateSave:true,
            pageLength:10,
            order:[],
            serverSide:true,
            processing:true,
            language:{processing:'Loading ...'},
            destroy: true,
            ajax:{
                url:url,
                type:"POST",
                data: function(data){
		           		data['id'] = "<?=hash_id($this->id,'decode');?>";
                    }
            }
        });
   
</script>

<?php }?>

