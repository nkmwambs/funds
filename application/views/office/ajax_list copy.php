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
</head>

<body>


<button class="tablink" onclick="openPage('fcp_offices', this, 'blue')" id="defaultOpen">FCP Offices</button>

<button class="tablink" onclick="openPage('cluster_offices', this, 'black')">Cluster Offices</button>
<button class="tablink" onclick="openPage('base_or_regions', this, 'orange')">Bases or Regions</button>
  <!-- Display country when user <> supper admin -->
<?php if($this->session->system_admin){ ?>

    <button class="tablink" onclick="openPage('country', this, 'brown')">Country</button>

<?php } ?>


<div id="fcp_offices" class="tabcontent">
<div class='col-xs-12 form-group'>

   <div class='col-xs-4'>
    <!-- Populate clusters for enabling mass update of moving fcp to clusters -->
      <select  id="cluster" name="header[fk_cluster_id]" class="form-control master input_office fk_user_id select2 select2-offscreen visible">
       <option value='0'><b><?=get_phrase('select_cluster');?></b></option>
       
       <?php 
         $cluster_ids=array_column($cluster_offices, 'office_id');

         $cluster_names=array_column($cluster_offices, 'office_name');

         $cluster_office_ids_and_cluster_names=array_combine($cluster_ids, $cluster_names);

         foreach($cluster_office_ids_and_cluster_names as $key=>$cluster_names){ ?>
           
             <option value='<?= $key;?>'><?=$cluster_names;?></option>

         <?php } ?>
      
      
      </select>
  </div>

  
  <div class='col-xs-2'>
     <button disabled id = 'click_move_fcps' class='btn btn-primary btn-click_move_fcps'><?=get_phrase('click_move_fcps')?></button>
  </div>
  

  <div id='update_msg' class='col-xs-4 '>
   
  </div>
</div>


  <!-- FCP Office -->
 
  <div class="row">
    <div class="col-xs-12">
       
        <table id='center_table' class="table table-striped">
            <thead>
                <th><?=get_phrase('mass_update');?></th>
                <th><?=get_phrase('action');?></th>
                <th><?=get_phrase('office_code');?></th>
                <th><?=get_phrase('office_name');?></th>
                <th><?=get_phrase('office_start_date');?></th>
                <th><?=get_phrase('cluster');?></th>
                <th><?=get_phrase('base_/_region_/_province');?></th>
                <!-- Display country when user <> supper admin -->
                <?php if($this->session->system_admin){ ?>

                    <th><?=get_phrase('country');?></th>

               <?php } ?>
                
            </thead>
            <tbody>
                <?php 
                if(!empty($fcp_offices)){
                foreach($fcp_offices as $office){

                    $label = 'Suspend';
                    $color = 'btn-danger';
                    if($office['office_is_suspended'] == 1){
                      $label = 'Unsuspend';
                      $color = 'btn-success';
                    }
                    //Remove 'Context for office' from the cluster, region/cohort, and country names

                    $cohort=explode('Context for office',$office['context_cohort_name']);

                    $country=explode('Context for office',$office['context_country_name']);

                    $cluster=explode('Context for office',$office['context_cluster_name']);

                    $office['context_cluster_name']=count($cluster)>1?$cluster[1]:$office['context_cluster_name'];

                    $office['context_cohort_name']=count($cohort)>1?$cohort[1]:$office['context_cohort_name'];

                    $office['context_country_name']=count($country)>1?$country[1]:$office['context_country_name'];
                    
                    
                    ?>
                    <tr>
                        <td>
                        <div class='form-group'>
                          <input class="checkbox" type="checkbox" onclick="check_or_uncheck_checkbox()" name="office_ids[]"  id="<?=$office['office_id'];?>">

                       </div>

                        </td>
                        <td>
                        <?php if($this->user_model->check_role_has_permissions(ucfirst($this->controller), 'update')){?>
                          <div data-office_id = "<?=$office['office_id'];?>" data-suspension_status = "<?=$office['office_is_suspended'];?>" class="btn <?=$color;?> suspend"><?=$label;?>
                          </div><a href="<?=base_url();?>office/edit/<?=hash_id($office['office_id'],'encode');?>" class="btn btn-default btn-icon"><i class="fa fa-pencil"></i> <?=get_phrase('edit');?></a></td>
                        <?php }?>
                        <td><?=$office['office_code'];?></td>
                        <td><?=$office['office_name'];?></td>
                        <td><?=$office['office_start_date'];?></td>
                        <td><?=$office['context_cluster_name'];?></td>
                        <td><?=$office['context_cohort_name']?></td>
                        

                         <!-- Display country when user <> supper admin -->
                        <?php if($this->session->system_admin){ ?>

                            <td><?=$office['context_country_name'];?></td>

                        <?php } ?>
                    </tr>
                <?php }}?>
            </tbody>
        </table>
    </div>
</div>
</div>

<div id="cluster_offices" class="tabcontent">
  
  <!-- Cluster Office -->
  <div class="row">
    <div class="col-xs-12">

        <table id='cluster_table' class="table table-striped">
            <thead>
                <th><?=get_phrase('action');?></th>
                <th><?=get_phrase('office_code');?></th>
                <th><?=get_phrase('office_name');?></th>
                <th><?=get_phrase('office_start_date');?></th>
                <th><?=get_phrase('base_/_region_/_province');?></th>
                <!-- Display country when user <> supper admin -->
                <?php if($this->session->system_admin){ ?>

                    <th><?=get_phrase('country');?></th>

               <?php } ?>
                
            </thead>
            <tbody>
                <?php 
                if(!empty($cluster_offices)){
                foreach($cluster_offices as $office){

                    //Remove 'Context for office' from the cluster, region/cohort, and country names
                    $cohort=explode('Context for office',$office['context_cohort_name']);

                    $country=explode('Context for office',$office['context_country_name']);

                    $office['context_cohort_name']=count($cohort)>1?$cohort[1]:$office['context_cohort_name'];

                    $office['context_country_name']=count($country)>1?$country[1]:$office['context_country_name'];

                    ?>
                    <tr>
                        <td><a href="<?=base_url();?>office/edit/<?=hash_id($office['office_id'],'encode');?>" class="btn btn-default btn-icon"><i class="fa fa-pencil"></i> <?=get_phrase('edit');?></a></td>
                        <td><?=$office['office_code'];?></td>
                        <td><?=$office['office_name'];?></td>
                        <td><?=$office['office_start_date'];?></td>
                        <td><?=$office['context_cohort_name'];?></td>
                        

                         <!-- Display country when user <> supper admin -->
                        <?php if($this->session->system_admin){ ?>

                            <td><?=$office['context_country_name'];?></td>

                        <?php } ?>
                    </tr>
                <?php }}?>
            </tbody>
        </table>
    </div>
</div>
</div>

<div id="base_or_regions" class="tabcontent">
  
  <!--Region Office -->
  <div class="row">
    <div class="col-xs-12">

        <table id='cohort_table' class="table table-striped">
            <thead>
                <th><?=get_phrase('action');?></th>
                <th><?=get_phrase('office_code');?></th>
                <th><?=get_phrase('office_name');?></th>
                <th><?=get_phrase('office_start_date');?></th>
                
                <!-- Display country when user <> supper admin -->
                <?php if($this->session->system_admin){ ?>

                    <!-- <th><?=get_phrase('base_/_region_/_province');?></th> -->
                    <th><?=get_phrase('country');?></th>

               <?php } ?>
                
            </thead>
            <tbody>
                <?php 
                  if(!empty($cohort_offices)){
                  foreach($cohort_offices as $office){

                    //Remove 'Context for office' from the cluster, region/cohort, and country names
                    
                    $explode_country=explode('Context for office',$office['context_country_name']);
                    ?>
                    <tr>
                        <td><a href="<?=base_url();?>office/edit/<?=hash_id($office['office_id'],'encode');?>" class="btn btn-default btn-icon"><i class="fa fa-pencil"></i> <?=get_phrase('edit');?></a></td>
                        <td><?=$office['office_code'];?></td>
                        <td><?=$office['office_name'];?></td>
                        <td><?=$office['office_start_date'];?></td>
                        
                        

                         <!-- Display country when user <> supper admin -->
                        <?php if($this->session->system_admin){ ?>

                            <!-- <td><?=$explode_country[1];?></td> -->
  
                            <td><?=$explode_country[1];?></td>

                        <?php } ?>
                    </tr>
                <?php }}?>
            </tbody>
        </table>
    </div>
</div>
</div>


<div id="country" class="tabcontent">
 
  <!-- Country Office -->
  <div class="row">
    <div class="col-xs-12">

        <table id='country_table' class="table table-striped">
            <thead>
                
                <th><?=get_phrase('action');?></th>
                <th><?=get_phrase('office_code');?></th>
                <th><?=get_phrase('office_name');?></th>
                <th><?=get_phrase('office_start_date');?></th>
                
                
                <!-- Display country when user <> supper admin -->
                <?php if($this->session->system_admin){ ?>

                    <th><?=get_phrase('region');?></th>

               <?php } ?>
                
            </thead>
            <tbody>
                <?php 
                if($this->session->system_admin){
                  foreach($country_offices as $office){

                    //Remove 'Context for office' from the cluster, region/cohort, and country names
                    //$explode_cluster=explode('Context for office',$office['context_cluster_name']);

                    // $explode_cohort=explode('Context for office',$office['context_cohort_name']);
                    
                    $explode_region=explode('Context for office',$office['context_region_name']);
                    ?>
                    <tr>
                        
                        <td><a href="<?=base_url();?>office/edit/<?=hash_id($office['office_id'],'encode');?>" class="btn btn-default btn-icon"><i class="fa fa-pencil"></i> <?=get_phrase('edit');?></a></td>
                        <td><?=$office['office_code'];?></td>
                        <td><?=$office['office_name'];?></td>
                        <td><?=$office['office_start_date'];?></td>
                        
                        

                         <!-- Display country when user <> supper admin -->
                        <?php if($this->session->system_admin){ ?>

  
                            <td><?=$explode_region[1];?></td>

                        <?php } ?>
                    </tr>
                <?php }
              }?>
            </tbody>
        </table>
    </div>
</div>
</div>


</body>



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
     // console.log(res);

      if(res==1){

        //$('#update_msg').html('<h3 style="color:green;">FCP cluster updated</h3>');
        //reload the table
        //console.log(get_office_ids());
        draw_table(true);

      }else{
        //$('#update_msg').html('<h3 style="color:res;">FCP cluster Failed to update</h3>');
      }

    });

  }
  
});

//Draw the table for clustes, FCPs and Cohorts
function draw_table(success_msg){
  
 let url='<?=base_url()?>/office/reload_fcps_after_switching_clusters';


 let tr_and_tds='';

 $.get(url,function(res){
   //console.log(JSON.parse(res));

   let offices=JSON.parse(res);
  

   for (index = 0; index < offices.length; index++) {

    let cluster_arr=offices[index].context_cluster_name.split('Context for office');

    let cohort_arr=offices[index].context_cohort_name.split('Context for office');

    let cluster_name =cluster_arr.length>1?cluster_arr[1]:offices[index].context_cluster_name;

    let cohort_name=cohort_arr.length>1?cohort_arr[1]:offices[index].context_cohort_name;

    let style_for_updated_rec='';
    let cluster_updated_str='';

    //Highlight the color for updated record and append the word '[Cluster Updated]'
    if(success_msg==true && get_office_ids().includes(offices[index].office_id)){

      style_for_updated_rec='style="color:green;"';

      cluster_updated_str="[New Cluster For:";

    }else if(success_msg==false && get_office_ids().includes(offices[index].office_id)){

      style_for_updated_rec='style="color:red;"';

      cluster_updated_str='[CLUSTER NOT UPDATED]';
    }
   
    var cluster=cluster_name+' '+cluster_updated_str+ offices[index].office_code+']';

     tr_and_tds=tr_and_tds+'<tr '+style_for_updated_rec+'>';
     tr_and_tds=tr_and_tds+"<td><div class='form-group'><input id="+ offices[index].office_id+" class='checkbox' type='checkbox' onclick='check_or_uncheck_checkbox()' name='office_ids[]'></div></td>";
     tr_and_tds=tr_and_tds+"<td><a href=<?=base_url();?>office/edit/<?=hash_id($office['office_id'],'encode')?> class='btn btn-default btn-icon'><i class='fa fa-pencil'></i><?=get_phrase('edit')?></a></td>";
     tr_and_tds=tr_and_tds+'<td>'+offices[index].office_code;+'</td>';
     tr_and_tds=tr_and_tds+'<td>'+offices[index].office_name;+'</td>';
     tr_and_tds=tr_and_tds+'<td>'+offices[index].office_start_date;+'</td>';
     tr_and_tds=tr_and_tds+'<td>'+cluster+'</td>';
     tr_and_tds=tr_and_tds+'<td>'+cohort_name;+'</td>';
     tr_and_tds=tr_and_tds+'</tr>';
        

   }

   let table=$('#center_table tbody');

   table.html();

   table.html(tr_and_tds)


 });

}

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

//Data tables
$(document).ready( function () {
    $('#center_table').DataTable();
    $('#cluster_table').DataTable();
    $('#cohort_table').DataTable();
    $('#country_table').DataTable();
} );
  
//TAB CODE
function openPage(pageName,elmnt,color) {

  var i, tabcontent, tablinks;

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
}

// Get the element with id="defaultOpen" and click on it
document.getElementById("defaultOpen").click();

$(document).on('click','.suspend', function () {
  const btn = $(this)
  const suspension_status = btn.data('suspension_status');
  const office_id = btn.data('office_id')
  const data = {office_id, suspension_status}
  const url = '<?=base_url();?>office/suspend_office'

  const cnf =confirm('<?=get_phrase('confirm_suspension','Are you sure you want to perform this action?');?>');

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
</script>