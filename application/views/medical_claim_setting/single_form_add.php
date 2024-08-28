<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php
extract($result);
// print_r($admin_settings);
?>

<div class='row'>
    <div class='col-xs-12'>

        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('Medical_claim_settings'); ?>
                </div>
            </div>


            <div class="panel-body" style="max-width:50; overflow: auto;">

                <form id='add_medical_claim_form' , class='form-horizontal form-groups-bordered validate' , name='add_medical_claim_form'>

                    <div class='form-group'>
                        <label class='col-xs-4 control-label'><?= get_phrase('medical_claim_setting_type') ?></label>
                        <div class='col-xs-8'>


                            <select class="form-control select2 required" id="medical_claim_setting_type" name="medical_claim_setting_type">

                                <option value='0'>Select Type</option>  
                                   
                                  <?php foreach ($admin_settings as $admin_setting_id => $admin_setting) { ?>

                                    <option value='<?= $admin_setting_id; ?>'><?= $admin_setting; ?></option>

                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <div class='form-group'>
                        <label class='col-xs-4 control-label'><?= get_phrase('medical_claim_name') ?></label>
                        <div class='col-xs-8'>
                            <input type="text" name='medical_claim_name' class="form-control required" id="medical_claim_name" aria-describedby="basic-addon3">
                        </div>
                    </div>

                    <div class='form-group'>
                        <label class='col-xs-4 control-label'><?= get_phrase('medical_claim_setting_value') ?></label>
                        <div class='col-xs-8'>
                            <input type="number" name='medical_claim_setting_value' class="form-control required" id="medical_claim_setting_value" aria-describedby="basic-addon3">
                        </div>
                    </div>


                    <div class='form-group'>
                        <div class='col-xs-12' style='text-align:center;'>
                            <button type='' id='btn_save' class='btn btn-default btn_save'>Save</button>
                            <button type='' id='btn_save_and_new' class='btn btn-default btn_save_and_new'>Save and New</button>
                            <button type='' id='btn_reset' class='btn btn-default btn-reset'>Reset</button>
                        </div>

                    </div>

                    <!-- overlay -->
                    <div class='hidden' id="overlay"><img src='<?= base_url() . "uploads/preloader3.gif"; ?>' /></div>

                </form>
            </div>
        </div>



        <script>
            $(".btn_save, .btn_save_and_new").on('click', function(e) {


                
                var redirect_url = '<?= base_url(); ?>Medical_claim_setting/list'

                //Check if the record medical claim setting exists before adding
                let medical_admin_setting_id = parseInt($('#medical_claim_setting_type').val());

                let record_exists_url = '<?= base_url() ?>medical_claim_setting/check_if_record_exists/' + medical_admin_setting_id;

                
                
                $.get(record_exists_url, function(response_message) {

                  
                    let message = 'Duplicate entry';

                    let res=JSON.parse(response_message);

                    if (res.value > 0) {

                        alert(message);
                        window.location.replace(redirect_url);
                       // return false;
                    } else {
                       
                        //Check if to redirect to list or remain on add form
                        var go_to_list_of_medical_claims = true

                        if ($(this).hasClass('btn_save_and_new')) {

                            go_to_list_of_medical_claims = false;

                        }

                        if (validate_form()) return false;

                        let url = '<?= base_url(); ?>medical_claim_setting/save_claim_settings';

                        let data = {
                            'medical_claim_setting_type': $('#medical_claim_setting_type').val(),
                            'medical_claim_name': $('#medical_claim_name').val(),
                            'medical_claim_setting_value': $('#medical_claim_setting_value').val(),
                        }

                        $.post(url, data, function(response) {

                            var res__result=JSON.parse(response);
                            //alert(res__result.result);

                            console.log(res__result.result); 

                            if (res__result.result == 1) {
                                alert('Record added Successfully');

                                if (go_to_list_of_medical_claims) {

                                    window.location.replace(redirect_url);
                                } else {
                                    //Clear the add form
                                    reset_form();
                                }
                            } else {
                                alert('Record Addition Failed');


                            }
                           // e.preventDefault();

                        });


                    }

                });



            });

            function reset_form() {
                $('input').val(null);
                $("#medical_claim_setting_type").select2("val", "");
                $("#medical_claim_name").select2("val", "");
                $("#medical_claim_setting_value").select2("val", "");

            }

            //Validate the inputs before posting
            function validate_form() {

                var any_field_empty = false

                $(".required").each(function() {

                    if ($(this).val().trim() == '') {

                        $(this).css('border-color', 'red');
                        any_field_empty = true;

                    } else {
                        $(this).css('border-color', '');

                        //Select2 implementation
                        if ($(this).hasClass('select2') && $(this).val() != 0) {
                            $(this).siblings(".select2-container").css('border', '');

                            any_field_empty = false;
                        }

                    }

                });

                return any_field_empty;
            }
        </script>