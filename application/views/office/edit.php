<?php 
extract($result);

extract($result['office_record_to_edit']);

//print_r($result['office_record_to_edit']);

// echo '<br>';
// echo '<br>';
// print_r($result['account_systems']);

// echo '<br>';
// echo '<br>';
// print_r($result['country_currency']);
// echo '<br>';
// echo '<br>';
//print_r($this->office_model->get_office_context_users(746,1));


?>

<style>
{
.has-error {border:1px solid rgb(185, 74, 72) !important;}
}

</style>    

<div class='row'>
<div class='col-xs-12'>

<div class="panel panel-default" data-collapsed="0">
    <div class="panel-heading">
        <div class="panel-title">
            <i class="entypo-plus-circled"></i>
            <?php echo get_phrase('edit_office'); ?>
        </div>
    </div>

    <div class="panel-body" style="max-width:50; overflow: auto;">
        <?php echo form_open("", array('id' => 'frm_office', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

        <div class='form-group'>
            <div class='col-xs-12 user_message'>

            </div>
        </div>

        <div class='form-group'>
            <div class='col-xs-12' style='text-align:center;'>
                <button class='btn btn-default btn-save'><?=get_phrase('save_changes');?></button>
                <button class='btn btn-default btn-cancel'><?=get_phrase('cancel');?></button>
            </div>
        </div>

        <div class='form-group'>
            <label class='col-xs-2 control-label'>Office Name</label>
            <div class='col-xs-4'>
                <!-- <?=$this->grants->header_row_field('office_name'); ?> -->

                <textarea   id="office_name" required="required" class="form-control master input_office office_name " name="header[office_name]" placeholder="Enter Office Name" ><?=$office_name;?></textarea>

            </div>

            <label class='col-xs-2 control-label'>Office Description</label>
            <div class='col-xs-4'>

                <textarea id="office_description" required="required" class="form-control master input_office office_description " name="header[office_description]" placeholder="Enter Office Description"><?=trim($office_description);?></textarea>
            </div>
        </div>

        <div class='form-group'>
            <label class='col-xs-2 control-label'>Office Code</label>
            <div class='col-xs-4'>
                <!-- Hidden Office Id -->
                <input class='hidden' name='header[office_id]' id="office_id"   value="<?=$office_id;?>" type="text">

                <input id="office_code" maxlength="45" value="<?=$office_code;?>" required="required" type="text" class="form-control master input_office office_code" name="header[office_code]" placeholder="Enter Office Code">
            </div>

            <?php 
                 //Disable Start
                 $journal_records=$this->office_model->get_vouchers_for_office_to_edit($office_id);
                 
                 $hide_office_start_date_if_journal_exists='';//sizeof($journal_records)>0?'hidden':'';


            ?>

            <label class='col-xs-2 control-label'>Office Start Date</label>
            <div class='col-xs-4'>
                
                <?php
                 if(sizeof($journal_records)>0){ ?>
                   <input id="office_start_date" value="<?=$office_start_date;?>"  type="text" class="form-control master input_office office_start_date" name="header[office_start_date]" readonly>
                <?php }else{ ?>
                    <input  id="office_start_date" value="<?=$office_start_date;?>" data-format="yyyy-mm-dd" required="required" readonly type="text" class="form-control master datepicker input_office office_start_date" name="header[office_start_date]" placeholder="Enter Office Start Date">
                <?php } ?>
                
            </div>

        </div>

        <div class='form-group'>
            <label class='col-xs-2 control-label'>Context Definition</label>
            <div class='col-xs-4'>
                <input class='hidden' id='hold_context_defination_id' name='header[hold_context_defination_id]' value='<?=$fk_context_definition_id?>' />
                <select readonly='readonly' onchange="onchange_fk_context_definition_id(this)" id="fk_context_definition_id" name="header[fk_context_definition_id]" class="form-control master input_office fk_context_definition_id select2 select2-offscreen visible" required="required" tabindex="-1">
                
                <option value="<?=$fk_context_definition_id;?>"><?=ucfirst($context_definition_name);?></option>
                <!-- <?php 
                foreach($defination_contexts as $key=>$defination_context){ 

                if($key!=$fk_context_definition_id){
                    
                    if(ucfirst($defination_context)!='Region' && ucfirst($defination_context)!='Global'){ ?>

                        <option value='<?=$key?>'><?=ucfirst($defination_context);?></option>
                    
                
                <?php } }}?> -->
                </select>

            </div>

            <label class='col-xs-2 control-label'>Reporting Context</label>
            <div class='col-xs-4' id='div_office_context'>
                <select class='form-control select2' id='office_context' name='header[office_context]'>

                <?php 
                
                    $reporting_office_id='';

                    $reporting_office_name='';

                    $clusters=[];
                    //Check if center or cluster or region to assign the correct reporting office for center is cluster, for cluster is region and for region is country
                    switch($fk_context_definition_id){
                    case 1:
                        $reporting_office_id=$fk_context_cluster_id;

                        $reporting_office_name=explode('Context for office',$context_cluster_name)[1];

                        $offices_to_add_dropdown=$this->office_model->get_clusters_or_cohorts_or_countries('context_cluster','context_cluster_id','context_cluster_name',false, false);

                        break;

                    case 2:
                        $reporting_office_id=$fk_context_cohort_id;

                        $reporting_office_name=explode('Context for office',$context_cohort_name)[1];

                        $offices_to_add_dropdown=$this->office_model->get_clusters_or_cohorts_or_countries('context_cohort','context_cohort_id','context_cohort_name', false, false);

                        break;

                    case 3:
                        $reporting_office_id=$fk_context_country_id;

                        $reporting_office_name=explode('Context for office',$context_country_name)[1];

                        $offices_to_add_dropdown=$this->office_model->get_clusters_or_cohorts_or_countries('context_country','context_country_id','context_country_name',false, false);

                        break;
                    case 4:
                        $reporting_office_id=$fk_context_region_id;

                        $reporting_office_name=explode('Context for office',$context_region_name)[1];

                        break;

                    case 5:
                        $reporting_office_id=$fk_context_global_id;

                        $reporting_office_name=explode('Context for office',$context_global_name)[1];

                        break;


                    }
                ?>
                <option value="<?=$reporting_office_id;?>"><?=$reporting_office_name;?></option>
                <?php 
                    //And the other reporting offices on the dropdown after default one
                    foreach($offices_to_add_dropdown as $key=>$reporting_office){ 
                    if($key!=$reporting_office_id){?>

                        <option value="<?=$key;?>"><?=explode('Context for office',$reporting_office)[1];?></option>

                    <?php } } ?>

                </select>

            </div>

        </div>

        
        <div class='form-group'>
            <label class='col-xs-2 control-label'>Is Office Active?</label>
            <div class='col-xs-4'>
                <?= $this->grants->header_row_field('office_is_active', $office_is_active); ?>
            </div>

            <label class="col-xs-2 control-label <?=!$this->session->system_admin? "hidden":" ";?>">Office Accounting System</label>
            <div class='col-xs-4'>
                <?php 
                    //This piece of code was added by Onduso [3/8/2022]
                    if(!$this->session->system_admin){ ?>
                    <input class='hidden' type="text" id="fk_account_system_id" name="header[fk_account_system_id]" value="<?=$this->session->user_account_system_id;?>"/>
                    <?php } else{ ?>


                    <select onchange="onchange_fk_context_definition_id(this)" id="fk_account_system_id" name="header[fk_account_system_id]" class="form-control master input_office fk_account_system_id select2 select2-offscreen visible" required="required" tabindex="-1">
                
                    <option value="<?=$fk_account_system_id;?>"><?=ucfirst($account_system_name);?></option>

                    <?php foreach($account_systems as $key=>$account_system){ 
                        if($fk_account_system_id!=$key){ ?>
                        <option value="<?=$key;?>"><?=ucfirst($account_system);?></option>
                        <?php }} ?>
                
                    </select>
                <?php  }//End of addition ?>
                
            </div>
        </div>

        <div class='form-group'>
            <label  id='fk_user_id_label' class='col-xs-2 control-label hidden'><?=get_phrase('activate_office_staffs');?></label>
            
            <div class='col-xs-4'>
            <select onchange="onchange_fk_context_definition_id(this)" id="fk_user_id" name="header[fk_user_id][]" class="form-control master input_office fk_user_id select2 select2-offscreen visible hidden"  tabindex="-1" multiple>
               <?php
               $office_staffs=$this->office_model->get_office_context_users($office_id,$fk_context_definition_id);

               //print_r($office_staffs['user_names']);

               foreach($office_staffs['user_names'] as $key=>$office_staff){?>
                   <option value='<?=$key?>'><?=$office_staff;?></option>

               <?php } ?>
            </select>
            </div>
        </div>

        <div class='form-group'>
            <label class='col-xs-2 control-label hidden'>Office is ReadOnly</label>
            <div class='col-xs-4'>
                
                    <!-- This piece of code was added by Onduso [3/8/2022] -->
                
                    <input class='hidden' type="text" id="office_is_readonly" name="header[office_is_readonly]" value="<?=$office_is_readonly;?>"/>
                
                    <!-- End of addition -->
            
            </div>
        </div>

        <div class=' <?=!$this->session->system_admin? "hidden":" ";?> form-group'>
            <label class='col-xs-2 control-label'>Office Currency</label>
            <div class='col-xs-4'>
            <?php 
                    //This piece of code was added by Onduso [3/8/2022]
                    if(!$this->session->system_admin){ ?>
                    <input type="text" id="fk_country_currency_id" name="header[fk_country_currency_id]" value="<?=$fk_country_currency_id;?>"/>
                    <?php } else{ ?>


                        <select onchange="onchange_fk_context_definition_id(this)" id="fk_country_currency_id" name="header[fk_country_currency_id]" class="form-control master input_office fk_country_currency_id select2 select2-offscreen visible" required="required" tabindex="-1">

                        <option value="<?=$fk_country_currency_id;?>"><?=$this->office_model->currency_name($fk_country_currency_id);?></option>

                        <?php foreach($country_currency as $key=>$currency){ 
                        if($fk_country_currency_id!=$key){ ?>
                            <option value="<?=$key;?>"><?=ucfirst($currency);?></option>
                        <?php }} ?>

                        </select>
                <?php  }//End of addition ?>
            
            </div>
        </div>


        <div class='form-group'>
            <div class='col-xs-12' style='text-align:center;'>
                <button class='btn btn-default btn-save'><?=get_phrase('save_changes')?></button>
                <button class='btn btn-default btn-cancel'><?=get_phrase('cancel');?></button>
                
            </div>
        </div>


        </form>
    </div>
</div>

<script>

    $('#office_is_active').on('click', function(){

        if($(this).val()==1){
            $('#fk_user_id_label').removeClass('hidden');

            $('#fk_user_id').removeClass('hidden');

        }else{
            $('#fk_user_id_label').addClass('hidden');

            $('#fk_user_id').addClass('hidden');

            $('#fk_user_id').addClass('required');
        }

       

    });

    $(document).ready(function(){
        //Modify office is readonly when fk_context_defination_id =1 i.e. center
        if($('#fk_context_definition_id').val()==1){
            $('#office_is_readonly').attr('value',0);
        }
    });


    //On change populate the reporting office dropdown
    $("#fk_context_definition_id").on('change', function() {
        
        //Added by Onduso [03/08/2022] to resolve missing property error if context definition is not selected.
        //$(".btn-save,.btn-save-new").removeClass('disabled');

        var url = "<?= base_url(); ?>office/get_ajax_responses_for_context_definition";
        var data = {
            'context_definition_id': $(this).val()
        };

        $.ajax({
            url: url,
            data: data,
            type: "POST",
            success: function(response) {

                var obj = JSON.parse(response);
                //console.log(obj.office_context);

                $("#div_office_context").html(obj.office_context);
                $("#div_office_context").find('select').removeClass('select2');

                //Code added by Onduso: Get the <select> tag and add the select2() method when dynamically creating an drop element; Code 
                $("select").select2();
                
            }
        });
    });

    $(".btn-save").on('click', function(ev) {
        
        
        //Validate Form

        if(validate_form()==true) {
            
            alert('Complete the required fields');
            
            return false;
        
        }


      //Edit the records

        var url = "<?= base_url(); ?>office/edit_office/";

        var data = $("#frm_office").serializeArray();
        var btn = $(this);

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function(response) {

                console.log(JSON.parse(response));

                //return false;
                alert(response);

                if (btn.hasClass('btn-save')) {
                    location.href = document.referrer
                } else {
                    reset_form();
                }
            }
        });

        ev.preventDefault();
    });

    $(".btn-reset").on('click', function(ev) {
        reset_form();

        ev.preventDefault();
    });

    //Validate the inputs before posting
function validate_form() {

    any_field_empty=false;

    //$('#office_context').select2('data');

    var data = $(document).find(".select2 option:selected").text();

    $("[required=required]").each(function(){

        if ($(this).val().trim() == '') {

            $(this).css('border-color', 'red');

        any_field_empty = true;
    }

    });

    return any_field_empty;
}


    function reset_form() {
        $('input').val(null);
        $("#fk_context_definition_id").val(null).attr('selected', true);



        $("#fk_account_system_id").val(0).prop('selected', true);
        $("#office_description").val(null);

        $("#office_context").empty().prop('disabled', 'disabled');

        $('#unit').val('21');

    }


    function onchange_fk_context_definition_id(elem) {

    }

    function onchange_office_context(elem) {

    }

    function onchange_fk_account_system_id(elem) {

    }
</script>