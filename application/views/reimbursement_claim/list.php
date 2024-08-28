<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
extract($result);

extract($status_data);
//print_r($reimbursement_claim_data);
?>

<style>
    table {
        table-layout: auto;
        border-collapse: collapse;
        width: 100%;
    }

    /* table td {
        border: 1px solid #ccc;
    } */

    table .absorbing-column {
        width: 15%;
    }

    /*Comment */
    /* .text-hidden {
  transform: scaleX(0);
  transform-origin: 0% 40%;
  transition: all .5s ease;
}

.text {
  transform: scaleX(1);
} */
</style>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<div class="row hidden" id='add_claim_button_id' style="margin-bottom:85px;">
    <div class="col-xs-12" style="text-align:center;">


        <?php
        $has_permission_for_add_claim_button = false;

        $display_filter_records_area = true;

        if ($show_add_button && $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'create')) {

            $has_permission_for_add_claim_button = true;

            $display_filter_records_area = false;

            echo add_record_button($this->controller, $has_details_table, null, $has_details_listing, $is_multi_row);
        }
        ?>

    </div>

</div>
<!-- Filter Records Area -->
<div id='filter_records' class='hidden'>

    <?php echo form_open("", array(
        'id' => 'frm_filter',
        'class' => 'form-horizontal form-groups-bordered validate',
        'enctype' => 'multipart/form-data'
    )); ?>
    <div class='row'>

        <div class='col-xs-12'>
            <div class='form-group'>
                <!-- Cluster Name -->
                <label class='col-xs-2 control-label '>Filter by Cluster</label>
                <div class='col-xs-4'>

                    <select class='form-control required select2' name='fk_cluster_id[]' id='fk_cluster_id' multiple>

                        <?php
                        foreach ($clusters as $office_id => $cluster) { ?>

                            <option value='<?= $office_id; ?>'><?= $cluster; ?></option>

                        <?php }  ?>

                    </select>

                </div>
            </div>
        </div>
    </div>
    <!-- Status Name -->
    <div class='row'>
        <div class='col-xs-12'>
            <div class='form-group'>
                <label class='col-xs-2 control-label '>Filter by Status</label>
                <div class='col-xs-4'>
                    <select class='form-control required select2' name='status_id[]' id='status_id' multiple>

                        <?php

                        foreach ($item_status as $key => $status) { ?>

                            <option value='<?= $key ?>'><?= $status['status_name']; ?></option>

                        <?php }  ?>

                    </select>
                </div>
            </div>
        </div>

    </div>
    <!-- Button -->
    <div> &nbsp;</div>
    <div class='col-xs-12'>
        <div class='form-group'>
            <div class='col-xs-12' style='text-align:center;'>

                <input class='btn btn-primary btn-filter' id='filter_id' type="submit" value="Filter">
            </div>
        </div>
    </div>
    </form>
</div>
<!-- List Records Area -->
<div id='div_intial_records_b4_filter'>
    <?php


    draw_list_of_claims($reimbursement_claim_data, $reimbursement_claim_attachments, $status_data, $item_status, $initial_status, $item_initial_item_status_id, $item_max_approval_status_ids, 'myTable_default', $has_permission_for_add_claim_button);
    ?>
</div>
<!-- SCRIPTS -->
<script>
    //Pull the comments and textarea to enter
    $(document).on('click', ".trigger_comment_area", function() {
        //$(".text-hidden").toggleClass("text");

        const id = $(this).data('reimbursement_id_comment_btn');


        $('#claim_decline_reason_div_' + id).removeClass('hidden');
        $('#saved_comments_div_' + id).removeClass('hidden');

        //
        if ($(this).hasClass('fa-comment')) {

            $(this).removeClass('fa-comment');
            $(this).addClass('fa fa-close');

            //Display comment
            draw_comment_table(id);

        } else {
            $(this).removeClass('fa fa-close');
            $(this).addClass('fa fa-comment');

            $('#claim_decline_reason_div_' + id).addClass('hidden');
            $('#saved_comments_div_' + id).addClass('hidden');

        }

    });
    //Save Comments
    $(document).on('change', ".claim_decline_reason", function(event) {
        const comment = $(this).val().trim();
        const reimbursement_id_txt_area = $(this).data("reimbursement_id_txt_area");

        if (comment == '') {
            alert('<?= get_phrase("empty_reimbursement_comment", "No comment entered"); ?>');

            return false;
        }
        const data = {
            fk_reimbursement_claim_id: reimbursement_id_txt_area,
            reimbursement_comment_detail: comment
        }

        //console.log(data);

        const url = '<?= base_url() ?>reimbursement_claim/add_reimbursement_comment';

        $.post(url, data, function(response) {
            //    console.log(response);
            if (parseInt(response) > 0) {
                alert('<?= get_phrase("reimbursement_comment_saved", "Comment Saved Successfully"); ?>');

                //Clear the Textarea
                $('#claim_decline_reason_' + reimbursement_id_txt_area).val('');

                $('#saved_comments_div_' + reimbursement_id_txt_area).html('');

                draw_comment_table(reimbursement_id_txt_area)


            } else {
                alert('<?= get_phrase("reimbursement_comment_not_saved", "Comment NOT Saved"); ?>');
            }
        });



        event.preventDefault();


    });



    //$("#claim_decline_reason").

    $(document).on('click', '#filter_id', function(event_d) {

        let status = $('#status_id').val();
        let cluster_name = $('#fk_cluster_id').val();

        var data = {
            'fk_cluster_id': cluster_name,
            'status_id': status
        };

        // console.log(data);

        var url = '<?= base_url() ?>reimbursement_claim/filter_claims/';
        $.post(url, data, function(response) {

            //Clear the HTML to remove the initial data with the datatable plugin
            $('#div_intial_records_b4_filter').html('');

            $('#div_intial_records_b4_filter').html(response);

            //Disable the return claim if no comments exists
            disable_status_btn();

            //Reinitialize the datatable but make sure you use it late binding of JQuery
            $('#myTable_filtered').DataTable({
                dom: 'lBfrtip',
                buttons: [
                    'copyHtml5',
                    'excelHtml5',
                    'csvHtml5',
                    'pdfHtml5',
                ],

            });

        });

        event_d.preventDefault();
    });

    //Unhide the add claim button if user has permission
    $(document).ready(function() {

        //Show add meddical buttons
        let has_permission_for_add_claim_button = '<?= $has_permission_for_add_claim_button; ?>';

        if (has_permission_for_add_claim_button) {
            $('#add_claim_button_id').removeClass('hidden');
        }
        //Show Filters area
        let display_filter_records_area = '<?= $display_filter_records_area; ?>';
        if (display_filter_records_area) {
            $('#filter_records').removeClass('hidden');
        }
    });

    //Hide and show the plus or minus fa icon
    $('.plus').on('click', function() {

        if ($(this).hasClass('fa-plus-circle')) {
            $(this).removeClass('fa-plus-circle');
            $(this).addClass('fa-minus-circle');
        } else {
            $(this).removeClass('fa-minus-circle');
            $(this).addClass('fa-plus-circle');

        }

    });

    $(document).ready(function() {
        Dropzone.autoDiscover = false;
    });

    //Disable Return Claim Button
    $(document).ready(function() {

        disable_status_btn();

        var table = $('#myTable_default').DataTable();

        var buttonToDisable = $('.item_action');

        table.on('page.dt', function() {

            //var table = $('#example').DataTable();

            var info = table.page.info();

            // Go to page 2
            table.page(info.page).draw(false);

            // Get the elements on page 2
            var page2Elements = table.rows({
                page: 'current'
            }).data();

            // Loop through the elements and do something with them
            page2Elements.each(function(row, index) {
                //console.log(row);

                var item = $('.item_action');

                let reimbursement_id_txt_area = $(item).data('item_id');

                console.log(reimbursement_id_txt_area);

                if ($(item).hasClass('btn-danger')) {

                    draw_comment_table(reimbursement_id_txt_area);

                }
            });

        });
    });

    function disable_status_btn() {

        let elem = $(".item_action");


        $.each(elem, function(i, e) {

            let reimbursement_id_txt_area = $(this).data('item_id');

            if ($(this).hasClass('btn-danger')) {

                draw_comment_table(reimbursement_id_txt_area);

            }


        });
    }

    //Approve medical claims
    $(".item_action").on('click', function() {
        var reimbursement_claim_id = $(this).attr('id');
        var next_status = $(this).data('next_status');
        var data = {
            'reimbursement_claim_id': reimbursement_claim_id,
            'next_status': next_status
        };
        var url = "<?= base_url(); ?>reimbursement_claim/update_medical_claim_status";
        var btn = $(this);

        $.post(url, data, function(response) {
            action_button = JSON.parse(response);
            //console.log(action_button);
            btn.html(action_button.button_label);
            btn.addClass('disabled');
            btn.siblings().remove();
            // btn.closest('tr').find('.action_td .dropdown ul').html("<li><a href='#'><?= get_phrase('no_action'); ?></a></li>");

            //Disable Receipts and Support Documents and Delete button after submitting the docs

            $('#receipt_upload_btn_' + reimbursement_claim_id).addClass('disabled');
            $('#support_docs_upload_btn_' + reimbursement_claim_id).addClass('disabled');
            $('#drop_receipts_' + reimbursement_claim_id).hide();
            $('#drop_support_documents_' + reimbursement_claim_id).hide();


            //Disable the Delete icon/btn once you submit the medical claim. [Reciepts]
            var reciepts_table_trs = $("#tbl_render_uploaded_receipts_" + reimbursement_claim_id).find("tbody>tr");

            reciepts_table_trs.each(function() {
                //find the td with i tag that houses the delete btn
                $(this).children('td:first').children('i').addClass('disabled');
            });

            //Disable the Delete icon/btn once you submit the medical claim. [Support Docs]
            var support_documents_table_trs = $("#tbl_render_uploaded_docs_" + reimbursement_claim_id).find("tbody>tr");

            support_documents_table_trs.each(function() {
                //find the td with i tag that houses the delete btn
                $(this).children('td:first').children('i').addClass('disabled');
            });

        });
    });

    // Upload Reciepts and Support Documents

    $('.reciepts, .docs').on('click', function() {

        //get data from form
        let document_type = $(this).data('document_type');
        let voucher_id = $(this).data('store_voucher_number');
        var reimbursement_claim_id = $(this).data('reimbursement_claim_id');

        // alert(reimbursement_claim_id);

        //Unhide the file upload area html form for RECEIPTS of SUPPORT DOCUMENTS
        if ($('#upload_receipt_' + reimbursement_claim_id).hasClass('hidden') && $(this).hasClass('reciepts')) {

            $('#upload_receipt_' + reimbursement_claim_id).removeClass('hidden');

        } else if ($('#upload_support_docs_' + reimbursement_claim_id).hasClass('hidden') && $(this).hasClass('docs')) {

            $('#upload_support_docs_' + reimbursement_claim_id).removeClass('hidden');

        } else if (!$('#upload_support_docs_' + reimbursement_claim_id).hasClass('hidden') && $(this).hasClass('docs')) {

            $('#upload_support_docs_' + reimbursement_claim_id).addClass('hidden');

        } else if (!$('#upload_receipt_' + reimbursement_claim_id).hasClass('hidden') && $(this).hasClass('reciepts')) {

            $('#upload_receipt_' + reimbursement_claim_id).addClass('hidden');
        }

        //Check if not receipts switch to support documents

        let dropzone_form_id_receipt_or_support_docs = "#drop_receipts_" + reimbursement_claim_id;

        var tbl_tag_id = '#tbl_render_uploaded_receipts_' + reimbursement_claim_id;

        let search_str_attachment_url = 'receipts';

        if ($(this).hasClass('docs')) {

            dropzone_form_id_receipt_or_support_docs = '#drop_support_documents_' + reimbursement_claim_id;
            tbl_tag_id = '#tbl_render_uploaded_docs_' + reimbursement_claim_id;

            search_str_attachment_url = 'support_documents';
        }

        //Populate the docs and receipts from attachment table

        //Ajax to upload to AWS S3
        var myDropzone = new Dropzone(dropzone_form_id_receipt_or_support_docs, {

            url: "<?= base_url() ?>reimbursement_claim/upload_reimbursement_claims_documents",
            paramName: "file", // The name that will be used to transfer the file
            params: {
                'document_type': document_type,
                'reimbursement_claim_id': reimbursement_claim_id,
                'store_voucher_number': voucher_id,
            },
            maxFilesize: 50, // MB
            uploadMultiple: true,
            parallelUploads: 5,
            maxFiles: 5,
            acceptedFiles: 'image/*,application/pdf',
        });


        myDropzone.on("complete", function(file) {
            myDropzone.removeAllFiles();
        });

        myDropzone.on('error', function(file, response) {
            console.log(response);
        });

        myDropzone.on("success", function(file, response) {

            console.log(response);
            if (response == 0) {
                alert('Error in uploading files');
                return false;
            }

            //Render the uplaod file once uploaded


            var table_tbody = $(tbl_tag_id + " tbody");

            var medical_id = tbl_tag_id.split("_")[4];

            let receipts_and_support_docs_uploaded = false;

            let document_type = tbl_tag_id.split("_")[3]

            if (document_type == 'docs') {
                document_type = 'support_documents';
            }

            //Get the documents that you have just uploaded and pull medical id
            var obj = JSON.parse(response);

            $.each(obj, function(i, elem) {

                //Once the documents are uploaded enable the 'Ready To Submit' btn

                medical_id = elem.attachment_primary_id;

                let ready_submit_btn = $('#' + medical_id);

                //let support_docs_flag = ready_submit_btn.next().data('suppoort_doc_hidden_field');

                let input_support_docs_flag = $('#support_documents_need_flag_' + medical_id).data('suppoort_doc_hidden_field');

                //let support_docs_flag = ready_submit_btn.next().data('suppoort_doc_hidden_field');

                let support_docs_flag = input_support_docs_flag

                //console.log(input_support_docs_flag);

                let url = '<?= base_url() ?>reimbursement_claim/get_medical_claim_attachment_by_Id/' + medical_id + '/' + document_type + '/' + support_docs_flag;

                var rebuild_table = '';

                $.get(url, function(res) {

                    let attachment_obj = JSON.parse(res);

                    $.each(attachment_obj, function(index, el) {

                        //Check if the receipts and support documents
                        if (el.receipt_or_support_doc_flag == 'true') {

                            receipts_and_support_docs_uploaded = true;
                        }
                        //Rebuiding table with new uplaoded documents
                        rebuild_table = rebuild_table + '<tr><td><i id=' + el.attachment_id + ' class="btn fa fa-trash delete_attachment aria-hidden="true"></i></td><td><a target= "__blank" href=' + el.attachment_url + '>' + el.attachment_name + '</a></td></tr>'

                    });

                    if (receipts_and_support_docs_uploaded == true) {
                        //Eanble the ready to submit button
                        ready_submit_btn.removeClass('disabled');

                    } else {
                        //Disable the ready to submit button
                        ready_submit_btn.addClass('disabled');
                    }
                    //Populate the tbody
                    table_tbody.html('');

                    table_tbody.html(rebuild_table);
                });

            });

        });

    });

    //Delete Attached_receipts and support docs

    $(document).on('click', '.delete_attachment', function() {

        //Reload the td
        var table_id = $(this).parent().parent().parent().parent().attr('id');

        /*Split the id to get numeral value at index 4 which a table id id="tbl_render_uploaded_receipts_79" e.g. 79 and 
          Document type which is either Receipts of Docs  at index 3      
        */

        var medical_id = table_id.split("_")[4];

        let document_type = table_id.split("_")[3]

        let ready_submit_btn = $('#' + medical_id);

        let support_docs_flag = ready_submit_btn.next().data('suppoort_doc_hidden_field');

        //Ajax call to delete receipts and supporting documenets
        let attachment_id = $(this).attr('id');

        url = '<?= base_url(); ?>reimbursement_claim/delete_reciept_or_support_docs/' + attachment_id;

        $.post(url, function(response) {

            $message = 'Deletion Failed';

            if (response == true) {

                $message = 'Attachments Deleted';

                //Rewrite the upload table
                var table_tbody = $('#' + table_id + '  tbody');

                if (document_type == 'docs') {
                    document_type = 'support_documents';
                }

                attachment_urls = '<?= base_url(); ?>/reimbursement_claim/get_medical_claim_attachment_by_Id/' + medical_id + '/' + document_type + '/' + support_docs_flag;

                //After Delete Redraw the table to list the remaining documents= 'Receipts and or Support_ documents'
                $.get(attachment_urls, function(response2) {

                    let attachments_after_delete = JSON.parse(response2);

                    table_tbody.html('');

                    let build_tbody_for_receipts_or_support_docs = '';

                    $.each(attachments_after_delete, function(index, element) {

                        if (element.attachment_url.includes('support_documents')) {

                            build_tbody_for_receipts_or_support_docs = build_tbody_for_receipts_or_support_docs + '<tr><td><i id=' + element.attachment_id + ' class="btn fa fa-trash delete_attachment aria-hidden="true"></i></td><td><a target= "__blank" href=' + element.attachment_url + '>' + element.attachment_name + '</a></td></tr>';

                        } else if (element.attachment_url.includes('receipts')) {

                            build_tbody_for_receipts_or_support_docs = build_tbody_for_receipts_or_support_docs + '<tr><td><i id=' + element.attachment_id + ' class="btn fa fa-trash delete_attachment aria-hidden="true"></i></td><td><a target= "__blank" href=' + element.attachment_url + '>' + element.attachment_name + '</a></td></tr>'
                        }
                    });

                    //Re-Draw the tbody for receipts table with id

                    table_tbody.html(build_tbody_for_receipts_or_support_docs);

                    //Check if the all documents/or receipts have been deleted and if so Disable the Ready Submit Button
                    if (build_tbody_for_receipts_or_support_docs == '') {

                        let ready_submit_btn = $('#' + medical_id);
                        ready_submit_btn.addClass('disabled');
                    }

                });
            }
            alert($message);
        });
    });

    $('#myTable_default').DataTable({
        dom: 'lBfrtip',
        buttons: [
            'copyHtml5',
            'excelHtml5',
            'csvHtml5',
            'pdfHtml5',
        ],
        "pagingType": "full_numbers",
        'stateSave': true
    });

    //Delete Reimbursement Comment
    $(document).on('click', '.delete_comment', function() {

        const reimbursement_comment_id = $(this).data('comment_id');

        let reimbursement_claim_id = $(this).data('claim_id');

        const data = {
            reimbursement_comment_id: reimbursement_comment_id
        }

        //console.log(reimbursement_comment_id);

        const comment_url = '<?= base_url() ?>reimbursement_claim/delete_reimbursement_comment';

        $.post(comment_url, data, function(res) {

            const confirm_deletion = confirm('<?= get_phrase('confirm_delete', 'Want to Delete Comment?'); ?>');

            if (confirm_deletion) {
                if (res == 1) {
                    alert('<?= get_phrase('comment_deletion_success', "Comment Deleted"); ?>');

                    draw_comment_table(reimbursement_claim_id);

                } else {
                    alert('<?= get_phrase('comment_deletion_failure', "Comment Not delete"); ?>');

                    return false;
                }

            }

        });





    });

    //Comment table code
    function draw_comment_table(reimbursement_id_txt_area) {

        //Mark disbled
        $('#decline_btn_' + reimbursement_id_txt_area).addClass('disabled');

        let url_get = "<?= base_url() ?>reimbursement_claim/get_reimbursement_comments/" + reimbursement_id_txt_area;

        $.get(url_get, function(response_comments) {

            const reimbursement_comments = JSON.parse(response_comments);

            //console.log(reimbursement_comments);

            //Repopulate the table
            if (reimbursement_comments != 0) {
                let table_html = '';


                $('#decline_btn_' + reimbursement_id_txt_area).removeClass('disabled');


                table_html = table_html + "<table class='table table-striped'><tbody>";



                $.each(reimbursement_comments, function(index, elem) {
                    table_html = table_html + "<tr>";
                    table_html = table_html + "<td >";
                    table_html = table_html + "<i style='cursor:alias;' class='<?= !$has_permission_for_add_claim_button ? 'fa fa-trash' : ''; ?>  delete_comment' data-comment_id='" + reimbursement_comments[index].reimbursement_comment_id + "' data-claim_id='" + reimbursement_comments[index].fk_reimbursement_claim_id + "'></i>";
                    table_html = table_html + "</td>";
                    table_html = table_html + "<td >";
                    table_html = table_html + reimbursement_comments[index].reimbursement_comment_detail + " [Created By:" + reimbursement_comments[index].user_lastname + "| On:" + reimbursement_comments[index].reimbursement_comment_created_date + "]";
                    table_html = table_html + "</td>";

                    table_html = table_html + "</tr>";



                });

                table_html = table_html + "</tbody></table>";

                //    $('#trigger_comment_area_'+reimbursement_id_txt_area).html('');

                //    let comment_icon="<i  style='cursor:alias; font-size:20pt;' id='trigger_comment_area_'"+reimbursement_id_txt_area+"' data-reimbursement_id_comment_btn='"+reimbursement_id_txt_area+"' class='fa fa-comment trigger_comment_area' ></i>";

                //    $('#trigger_comment_area_'+reimbursement_id_txt_area).html(comment_icon);

                $('#saved_comments_div_' + reimbursement_id_txt_area).html(table_html);



            } else {
                $('#saved_comments_div_' + reimbursement_id_txt_area).html('');
            }


        });


    }
</script>