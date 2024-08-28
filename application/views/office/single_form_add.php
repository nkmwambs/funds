<?php 
extract($result);

// print_r($result)
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
                    <?php echo get_phrase('add_office'); ?>
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
                        <button class='btn btn-default btn-reset'>Reset</button>
                        <button class='btn btn-default btn-save disabled'>Save</button>
                        <button class='btn btn-default btn-save-new disabled'>Save and New</button>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'>Office Name</label>
                    <div class='col-xs-4'>
                        <?= $this->grants->header_row_field('office_name'); ?>
                    </div>

                    <label class='col-xs-2 control-label'>Office Description</label>
                    <div class='col-xs-4'>
                        <?= $this->grants->header_row_field('office_description'); ?>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'>Office Code</label>
                    <div class='col-xs-4'>
                        <?= $this->grants->header_row_field('office_code'); ?>
                    </div>

                    <label class='col-xs-2 control-label'>Office Start Date</label>
                    <div class='col-xs-4'>
                        <?= $this->grants->header_row_field('office_start_date'); ?>
                    </div>

                </div>

                <div class='form-group'>
                    <label class='col-xs-2 control-label'>Context Definition</label>
                    <div class='col-xs-4'>
                        <?= $this->grants->header_row_field('context_definition_name'); ?>
                    </div>

                    <label class='col-xs-2 control-label'>Reporting Context</label>
                    <div class='col-xs-4' id='div_office_context'>
                        <select class='form-control' disabled='disabled'></select>
                    </div>

                </div>

                
                <div class=' <?=!$this->session->system_admin? "hidden":" ";?> form-group'>
                    <label class='col-xs-2 control-label hidden'>Is Office Active?</label>
                    <div class='col-xs-4 hidden'>
                        <?= $this->grants->header_row_field('office_is_active', 1); ?>
                    </div>

                    <label class="col-xs-2 control-label <?=!$this->session->system_admin? "hidden":" ";?>">Office Accounting System</label>
                    <div class='col-xs-4'>
                        <?php 
                         //This piece of code was added by Onduso [3/8/2022]
                         if(!$this->session->system_admin){ ?>
                          <input type="text" id="fk_account_system_id" name="header[fk_account_system_id]" value="<?=$this->session->user_account_system_id;?>">
                         <?php } else{
                            echo $this->grants->header_row_field('account_system_name');
                         }
                         //End of addition
                         ?>
                        
                    </div>
                </div>

                <div class=' <?=!$this->session->system_admin? "hidden":" ";?> form-group'>
                    <label class='col-xs-2 control-label'>Office Currency</label>
                    <div class='col-xs-4'>
                    <?php 
                         //This piece of code was added by Onduso [3/8/2022]
                         if(!$this->session->system_admin){ ?>
                          <input type="text" id="fk_country_currency_id" name="header[fk_country_currency_id]" value="<?=$country_currency_id;?>">
                         <?php } else{
                            echo $this->grants->header_row_field('country_currency_name');
                         }
                         //End of addition
                    ?>
                    </div>
                </div>


                <div class='form-group'>
                    <div class='col-xs-12' style='text-align:center;'>
                        <button class='btn btn-default btn-reset'>Reset</button>
                        <button class='btn btn-default btn-save disabled'>Save</button>
                        <button class='btn btn-default btn-save-new disabled'>Save and New</button>
                    </div>
                </div>


                </form>
            </div>
        </div>

        <script>
            $("#fk_context_definition_id").on('change', function() {
                //alert('Hello');
                //Added by Onduso [03/08/2022] to resolve missing property error if context definition is not selected.
                $(".btn-save,.btn-save-new").removeClass('disabled');


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

            $(".btn-save,.btn-save-new").on('click', function(ev) {
                
                //Validate Form

                if(validate_form()==true) {
                    
                    alert('Complete the required fields');
                    
                    return false;
                
                }



                var url = "<?= base_url(); ?>office/create_new_office";
                var data = $("#frm_office").serializeArray();
                var btn = $(this);

                $.ajax({
                    url: url,
                    type: "POST",
                    data: data,
                    success: function(response) {

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

               // console.log($(this).val());

                
            //    if($(this).hasClass('office_context')){

                

            //     var reporting_context_value =$(this).find(':selected').text();

            //     if(reporting_context_value==''){
            //     //    $(this).next(".select2-container").css('border-color', 'red');

            //         $(this).next().find('.select2-selection').addClass('has-errorr');
            //        // $(this).trigger('change');
            //         console.log('Test');

            //         any_field_empty = true;
            //     }

            //    }

               if ($(this).val().trim() == '') {

                 $(this).css('border-color', 'red');

                any_field_empty = true;
            }
               


                // if ($(this).val().trim() == '') {

                //     $(this).css('border-color', 'red');

                //     any_field_empty = true;
                // }else if($(this).hasClass('office_context') && $(this).val() == 0){

                //     $(this).siblings(".select2-container").css('border-color', 'red');
                //     any_field_empty = true;
                // }
                // else{

                //    // $(this).css('border-color', '');
                // }

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