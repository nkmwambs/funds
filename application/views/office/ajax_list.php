<?php 

  // echo json_encode($result['offices']);
  
  $width=33;
  if($this->session->system_admin){
    $width=25;
  }
?>
<style>
* {box-sizing: border-box}

/* Set height of body and the document to 100% */
body, html {
  height: 100%;
  margin: 0;
  font-family: Arial;
}

/* Style tab links */
.tablink {
  background-color: #555;
  color: white;
  float: left;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  font-size: 17px;
  width: <?=$width.'%';?>;
}

.tablink:hover {
  background-color: #777;
}

/* Style the tab content (and add height:100% for full page content) */
.tabcontent {
  color: black;
  display: none;
  padding: 100px 20px;
  height: 100%;
}

#fcp_offices {background-color: white;}

#cluster_offices {background-color: white;}
#base_or_regions {background-color: white;}
#country {background-color: white;}
</style>


<button class="tablink" onclick="openPage('fcp_offices', this, 'blue')" id="defaultOpen">FCP Offices</button>
<button class="tablink" onclick="openPage('cluster_offices', this, 'black')">Cluster Offices</button>
<button class="tablink" onclick="openPage('base_or_regions', this, 'orange')">Bases or Regions</button>
<?php if($this->session->system_admin){ ?>
  <button class="tablink" onclick="openPage('country', this, 'brown')">Country</button>
<?php }

$contents = [
  'fcp_offices' => ['table_columns' => ['mass_update','action','office_track_number','office_code','office_name','office_start_date','cluster','base_/_region_/_province',$this->session->system_admin ? 'country' : '',]],
  'cluster_offices' => ['table_columns' => ['office_track_number','office_code','office_name','office_start_date','base_/_region_/_province',$this->session->system_admin ? 'country' : '',]],
  'base_or_regions' => ['table_columns' => ['office_track_number','office_code','office_name','office_start_date',$this->session->system_admin ? 'country' : '',]],
  'country' => ['table_columns' => ['office_track_number','office_code','office_name','office_start_date',$this->session->system_admin ? 'region' : '',]]
];
?>

<?php foreach($contents as $tabcontent => $content){ ?>
<div id="<?=$tabcontent;?>" class="tabcontent">
<!-- This is the selector for clusters - Only visible when fcp table is loaded: start -->
  <?php if($tabcontent == 'fcp_offices'){?>
    <div class='col-xs-12 form-group'>
      <div class='col-xs-4'>
        <!-- Populate clusters for enabling mass update of moving fcp to clusters -->
          <select  id="cluster" name="header[fk_cluster_id]" class="form-control master input_office fk_user_id select2 select2-offscreen visible">
          <option value='0'><b><?=get_phrase('select_cluster');?></b></option>
          
          <?php 
            $cluster_ids = array_column($cluster_offices, 'office_id');
            $cluster_names = array_column($cluster_offices, 'office_name');
            $cluster_office_ids_and_cluster_names = array_combine($cluster_ids, $cluster_names);

            foreach($cluster_office_ids_and_cluster_names as $key=>$cluster_names){ ?>
                <option value='<?= $key;?>'><?=$cluster_names;?></option>
            <?php } ?>
          </select>
      </div>

      <div class='col-xs-2'>
        <button disabled id = 'click_move_fcps' class='btn btn-primary btn-click_move_fcps'><?=get_phrase('click_move_fcps')?></button>
      </div>
      <div id='update_msg' class='col-xs-4 '></div>
    </div>
  <?php }?>

  <!-- This is the selector for clusters: End -->

  <div class="row">
    <div class="col-xs-12">
        <table id='tbl_<?=$tabcontent;?>' class="datatable table table-striped office_table nowrap">
            <thead>
                <?php 
                    foreach($content['table_columns'] as $column){
                      if($column != ""){
                ?>
                  <th><?=get_phrase($column);?></th>
                <?php 
                      }
                  } 
                ?>
            </thead>
            <tbody></tbody>
        </table>
    </div>
  </div>
</div>

<?php }?>

<script>
//Enable/disable 'click move fcps' button

$('#cluster').on('change', function(){
  var move_fcps_btn=$('#click_move_fcps');
  if($(this).val()!=0){
    move_fcps_btn.removeAttr('disabled');
  }else{
    move_fcps_btn.attr('disabled','disabled');
  }
});

//Mass update FCP
$('#click_move_fcps').on('click', function(){
  let message='<?=get_phrase('Are_sure_you_want_to_change_the_cluster_of_the_selected_FCPs')?>';
  let office_ids=get_office_ids();

  //Check if checkbox is empty
   if(office_ids.length==0){
    alert('<?=get_phrase('You_have_to_select_atleast_an_FCP_and_cluster')?>');
    return false;
   }

  //Update fcp to clusters
  if(confirm(message) == true){
    let url='<?=base_url();?>office/mass_update_for_fcps';
    data={
      'cluster_office_id':$('#cluster').val(),
      'office_ids':get_office_ids(),
    }

    $.post(url,data,function(response){
      let res=JSON.parse(response);
      if(res==1){
        load_datatable('fcp_offices')
      }else{
        //$('#update_msg').html('<h3 style="color:res;">FCP cluster Failed to update</h3>');
      }
    });

  }
  
});

//Check or uncheck 
function check_or_uncheck_checkbox() {
  $(document).on("click", ".checkbox", function(event) {
      if($(this).is(":checked")){
        $(this).attr('checked',true);
      }else{
        $(this).attr('checked',false);
      }
    });
}

function get_office_ids() {
  // Populate the office ids of checked checkboxes
  var office_ids=[];

  $('.checkbox').each(function(){
    if($(this).is(":checked")){
        let office_id=$(this).attr("id");
        office_ids.push(office_id);
    }
  });

  return office_ids
  
}
  
//TAB CODE
function openPage(pageName,elmnt,color) {
  let i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");

  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  tablinks = document.getElementsByClassName("tablink");

  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].style.backgroundColor = "";
  }
  document.getElementById(pageName).style.display = "block";

  elmnt.style.backgroundColor = color;

  load_datatable(pageName)
}

// Get the element with id="defaultOpen" and click on it
// document.getElementById("defaultOpen").click();
$(document).ready(function() {
  document.getElementById("defaultOpen").click();
})

$(document).on('click','.suspend', function () {
  const btn = $(this)
  const suspension_status = btn.data('suspension_status');
  const office_id = btn.data('office_id')
  const data = {office_id, suspension_status}
  const url = '<?=base_url();?>office/suspend_office'

  const cnf = confirm('<?=get_phrase('confirm_suspension','Are you sure you want to perform this action?');?>');

  if(!cnf){
    alert('<?=get_phrase('process_aborted');?>');
    return false;
  }

  $.post(url, data, function (response) {
    // alert(response);
    if(suspension_status){
      btn.removeClass('btn-success')
      btn.addClass('btn-danger')
      btn.html('Suspend')
      btn.data('suspension_status',0)
    }else{
      btn.addClass('btn-success')
      btn.removeClass('btn-danger')
      btn.html('Unsuspend')
      btn.data('suspension_status',1)
    }
  })
})

// Server side loading implementation for FCP offices to improve loading performance

function load_datatable(pageName){
  
  let office_category = pageName // Can be fcp_offices, cluster_offices, base_or_regions
  let url = "<?=base_url();?><?=$this->controller;?>/show_list/";

  // alert(pageName)

  $(".datatable").dataTable().fnDestroy();

        const datatable = $("#tbl_" + pageName).DataTable({
            dom: 'lBfrtip',
            "bDestroy": true,
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5',
            ],
            pagingType: "full_numbers",
            stateSave:true,
            pageLength:10,
            order:[],
            serverSide:true,
            processing:true,
            language:{processing:'Loading ...'},
            ajax:{
                url:url,
                type:"POST",
                "data": function ( d ) {
                  d.office_category = office_category;
                }
            }
        });
  
}

   $("#datatable_filter").html(search_box());

    function search_box(){
    return '<?=get_phrase('search');?>: <input type="form-control" onchange="search(this)" id="search_box" aria-controls="datatable" />';
    }

    function search(el){
    datatable.search($(el).val()).draw();
    }
</script>