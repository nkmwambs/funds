<style>
    #datatable thead th, tbody td {
      white-space: nowrap;
    }
  </style>

<div class="row" style="margin-bottom:25px;">
  <div class="col-xs-12" style="text-align:center;">

    <?php
    extract($result);
    if($show_add_button && $this->user_model->check_role_has_permissions(ucfirst($this->controller),'create')){
      echo add_record_button($this->controller, $has_details_table,null,$has_details_listing, $is_multi_row);
    }
    ?>
    <?=Widget_base::load('position','position_1');?>
  </div>
</div>

<table class="table table-striped" id="datatable">
    
    <thead>
        <!-- <th><?=get_phrase('action');?></th> -->
        <th><?=get_phrase('user_track_number');?></th>
        <th><?=get_phrase('user_firstname');?></th>
        <th><?=get_phrase('user_lastname');?></th>
        <th><?=get_phrase('user_email');?></th>
        <th><?=get_phrase('user_employment_date');?></th>
        <th><?=get_phrase('context_definition_name');?></th>
        <th><?=get_phrase('user_is_system_admin');?></th>
        <th><?=get_phrase('language_name');?></th>
        <th><?=get_phrase('user_is_active');?></th>
        <th><?=get_phrase('status_name');?></th>
        <th><?=get_phrase('role_name');?></th>
        <th><?=get_phrase('account_system_name');?></th>
        <th><?=get_phrase('user_first_time_login');?></th>
    </thead>
    <tbody>

    </tbody>
</table>

<script>
    //$(document).ready(function(){
        var url = "<?=base_url();?><?=$this->controller;?>/show_list";
        const datatable = $("#datatable").DataTable({
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
            ajax:{
                url:url,
                type:"POST",
            }
        });
    //});

    
   $("#datatable_filter").html(search_box());

    //});

    function search_box(){
    return '<?=get_phrase('search');?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
    }

    function search(el){
    datatable.search($(el).val()).draw();
    }
</script>