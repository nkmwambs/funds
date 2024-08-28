<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


if (!function_exists('draw_list_of_claims')) {
    function draw_list_of_claims($reimbursement_claim_data, $reimbursement_claim_attachments, $status_data, $item_status, $initial_status, $item_initial_item_status_id, $item_max_approval_status_ids, $table_id, $has_permission_for_add_claim_button)
    {
        $CI = &get_instance();

?>
        <table id='<?= $table_id; ?>'class="table table-striped">
            <thead>
                <th><?= get_phrase('action'); ?></th>
                <th class="absorbing-column"><?= 'Uploads' ?> </th>
                <th><?= get_phrase('reimbursement_trk_no', 'Track number'); ?></th>
                <th><?= get_phrase('voucher', 'voucher_number'); ?></th>
                <th><?= get_phrase('childNo', 'Child Number'); ?></th>
                <th><?= get_phrase('fcpNo', 'FCP Code'); ?></th>
                <th><?= get_phrase('claim_type', 'Claim Type'); ?></th>
                <th><?= get_phrase('funding_type', 'Funding Type'); ?></th>
                <th><?= get_phrase('connect_incident_id', 'Connect Incident ID'); ?></th>

                <th><?= get_phrase('treatment_date', 'Treatment/Transaction Date'); ?></th>
                <th><?= get_phrase('diagnosis', 'Diagnosis/Description'); ?></th>
                <th><?= get_phrase('reimbursement_amount', 'Total Amount'); ?></th>
                <th><?= get_phrase('caregiver_contribution', 'caregiver_contribution'); ?></th>
                <th><?= get_phrase('amount_reimbursed', 'Amount Reimbursed'); ?></th>
                <th><?= get_phrase('fcp_cluster', 'Cluster'); ?></th>


            </thead>
            <tbody>
                <?php

                //Get Intial status
                //$initial_status = $this->grants_model->initial_item_status('medical_claim');

                /*$action = approval_action_button($this->controller,$item_status, $voucher_id
                $voucher_status, $item_initial_item_status_id, $item_max_approval_status_ids);*/
                $support_docs_flag = '';

                $medical_claim_type_font_style = "color:black; font-style:oblique;
                                                  font-size:medium; font-family:Verdana, Geneva, Tahoma, sans-serif";


                $track_number_style = '';

                $medtfi_font_style = "color:green;
                                      font-style:oblique;
                                      font-size:medium;
                                      font-family:Verdana, Geneva, Tahoma, sans-serif";


                $hvc_cpr_font_style = "color:brown;
                                       font-style:oblique;
                                       font-size:medium; font-family:Verdana, Geneva, Tahoma, sans-serif";

                $civ_medical_font_style = "color:blue;
                                           font-style:oblique;
                                           font-size:medium; font-family:Verdana, Geneva, Tahoma, sans-serif";


                foreach ($reimbursement_claim_data as $data) {

                    $app_type = $data['reimbursement_app_type_name'];

                    if ($app_type === 'MED-TFI') {

                        $tr_style = $medtfi_font_style;
                        $track_number_style = $medtfi_font_style;
                    } elseif ($app_type === 'HVC-CPR') {
                        $tr_style = $hvc_cpr_font_style;
                        $track_number_style = $hvc_cpr_font_style;
                    } elseif ($app_type === 'CIV-MEDICAL') {
                        $tr_style = $civ_medical_font_style;

                        $track_number_style = $civ_medical_font_style;
                    } else {
                        $tr_style = $medical_claim_type_font_style;

                        $track_number_style = $medical_claim_type_font_style;
                    }


                ?>
                    <tr style="<?= $tr_style; ?>">
                        <!-- Count of claims , action buttons -->

                        <td nowrap='nowrap'>

                            <?php
                            $status_id = $data['fk_status_id'];

                            $status_backflow_sequence=$status_data['item_status']
                                                      [$status_id]['status_backflow_sequence'];

                            $status_button_label = $status_data['item_status'][$status_id]['status_button_label'];

                            $status_decline_button_label = $status_data['item_status']
                                                           [$status_id]['status_decline_button_label'];

                            //$item_initial_item_status_id = $item_initial_item_status_id;

                            //$item_max_approval_status_ids = $item_max_approval_status_ids;

                            ?>

                            <div>
                                <?php

                                echo approval_action_button('reimbursement_claim', $item_status, $data['reimbursement_claim_id'], $status_id, $item_initial_item_status_id, $item_max_approval_status_ids, true);
                                ?>
                            </div>
                            <?php
                            // Comment Area
                            
                            if ($status_decline_button_label != ''  || $status_button_label === 'Reinstate') {
                              
                                
                            ?>
                           
                                <i style='cursor:alias; color:brown; font-size:20pt;'
                                   id="trigger_comment_area_<?= $data['reimbursement_claim_id'] ?>"
                                   data-reimbursement_id_comment_btn='<?= $data['reimbursement_claim_id'] ?>'
                                   class='fa fa-comment trigger_comment_area'>
                                </i>

                                <div class="hidden"
                                     id='claim_decline_reason_div_<?= $data['reimbursement_claim_id'] ?>'>
                                     <textarea data-reimbursement_id_txt_area='<?= $data['reimbursement_claim_id'] ?>'
                                      id="claim_decline_reason_<?= $data['reimbursement_claim_id'] ?>"
                                      class='claim_decline_reason'></textarea>
                                </div>

                                <div class='hidden' id='saved_comments_div_<?= $data['reimbursement_claim_id'] ?>'>

                                </div>

                            <?php } ?>

                            <input id='support_documents_need_flag_<?=$data['reimbursement_claim_id']?>'
                                   name='flag' class='hidden'
                                   data-suppoort_doc_hidden_field='<?= $data['support_documents_need_flag'] ?>' />

                        </td>

                        <!-- Cordion for uploads and downloads -->
                        <td>

                            <p>
                                <a type="button" data-toggle="collapse"
                                  data-target="#receipts_<?= $data['reimbursement_claim_id'] ?>"
                                  aria-expanded="false" aria-controls="collapseExample">
                                    <i class="fa fa-plus-circle  plus" style="font-size:20px;color:green"></i>
                                </a>
                            </p>
                            <div class="collapse" id="receipts_<?= $data['reimbursement_claim_id'] ?>">
                                <div class="card card-body">

                                    <p>

                                        <button id="receipt_upload_btn_<?= $data['reimbursement_claim_id']; ?>"
                                         style="font-size:18px"
                                         data-store_voucher_number='<?= $data['fk_voucher_detail_id'] ?>'
                                         data-document_type='receipts'
                                         data-reimbursement_claim_id='<?= $data['reimbursement_claim_id'] ?>'
                                         class='btn reciepts <?=
                                         in_array($data['fk_status_id'],$CI->general_model->get_max_approval_status_id('reimbursement_claim')) || $status_id != $initial_status && $status_backflow_sequence == 0 || !$has_permission_for_add_claim_button ? 'disabled' : '' ?>'>
                                         <i class="fa fa-upload"></i><?= get_phrase('receipts'); ?>
                                        </button>
                                    </p>

                                    <!-- Dropzone  For receipts -->

                                    <div id='upload_receipt_<?= $data['reimbursement_claim_id']; ?>'
                                         class="col-xs-12 hidden" style="margin-bottom:20px;">
                                        <form id="drop_receipts_<?= $data['reimbursement_claim_id']; ?>"
                                              class="dropzone">
                                            <div class="fallback">
                                                <input id="receipt_upload_area_<?= $data['reimbursement_claim_id']; ?>"
                                                       name="file" type="file" multiple />
                                            </div>
                                        </form>
                                    </div>
                                    <p>
                                    <table id="tbl_render_uploaded_receipts_<?= $data['reimbursement_claim_id'] ?>">

                                        <!-- Populate attachments of Receipts from S3 -->
                                        <tbody>


                                            <?php
                                            $pattern_receipt = "/receipts/";

                                            foreach ($reimbursement_claim_attachments as $reimbursement_claim_attachment) {

                                                $attachment_url = $reimbursement_claim_attachment['attachment_url'];

                                                $reimbursement_claim_id  = $data['reimbursement_claim_id'];

                                                //Check if the primary_id matches with the medical claim Id and if is a receipt. If so create the s3_preassigned_url
                                                if ($reimbursement_claim_attachment['attachment_primary_id'] == $reimbursement_claim_id  && preg_match($pattern_receipt, $attachment_url) == 1) {

                                                    $objectKey = $attachment_url . '/' . $reimbursement_claim_attachment['attachment_name'];
                                                    $url = $CI->config->item('upload_files_to_s3') ? $CI->grants_s3_lib->s3_preassigned_url($objectKey) : $CI->attachment_library->get_local_filesystem_attachment_url($objectKey);

                                            ?>
                                                    <tr>

                                                        <!-- //also enable deletion if 'status_backflow_sequence'=1 -->
                                                        <td>
                                                            <i id='<?=$reimbursement_claim_attachment['attachment_id']?>'
                                                               class="btn fa fa-trash delete_attachment <?= $status_id == $initial_status || $status_backflow_sequence == 1 ? '' : 'disabled'; ?>" aria-hidden="true">
                                                        </td>
                                                        <td><a target='__blank' href='<?= $url; ?>'><?= $reimbursement_claim_attachment['attachment_name']; ?></a></td>

                                                    </tr>

                                            <?php } else {


                                                    continue;
                                                }

                                                $support_docs_flag = $data['support_documents_need_flag'];

                                                if ($reimbursement_claim_attachment['attachment_primary_id'] == $reimbursement_claim_id  && preg_match($pattern_receipt, $attachment_url) == 1 && $support_docs_flag == 0) {

                                                    //Toggle to enable the button when Receipts have been upload

                                                    disbale_ready_to_submit_btn($reimbursement_claim_id);
                                                }
                                            } ?>

                                        </tbody>

                                    </table>
                                    </p>

                                    <?php
                                    //Display upload documents if the support_documents_need_flag setting is 1 and 0
                                    if ($data['support_documents_need_flag'] == 1) { ?>

                                        <button id="support_docs_upload_btn_<?= $data['reimbursement_claim_id']; ?>" style="font-size:18px" data-store_voucher_number='<?= $data['fk_voucher_detail_id'] ?>' data-document_type='support_documents' data-reimbursement_claim_id='<?= $data['reimbursement_claim_id'] ?>' class=' btn docs <?= in_array($data['fk_status_id'], $CI->general_model->get_max_approval_status_id('reimbursement_claim')) || $status_id != $initial_status && $status_backflow_sequence == 0 ? 'disabled' : '' ?>'><i class="fa fa-upload"></i><?= get_phrase('support_docs'); ?> </button>

                                        <!-- Dropzone  For Support Documents-->

                                        <div id='upload_support_docs_<?= $data['reimbursement_claim_id']; ?>' class="col-xs-12 hidden" style="margin-bottom:20px;">
                                            <form id="drop_support_documents_<?= $data['reimbursement_claim_id']; ?>" class="dropzone">
                                                <div class="fallback">
                                                    <input id="support_document_upload_area_<?= $data['reimbursement_claim_id']; ?>" name="file" type="file" multiple />
                                                </div>
                                            </form>
                                        </div>
                                        <p>

                                        <table id="tbl_render_uploaded_docs_<?= $data['reimbursement_claim_id'] ?>">
                                            <!-- Populate attachments of Support Documents from S3 -->
                                            <tbody>
                                                <?php foreach ($reimbursement_claim_attachments as $reimbursement_claim_attachment) {

                                                    $reimbursement_claim_id = $data['reimbursement_claim_id'];

                                                    $attachment_url = $reimbursement_claim_attachment['attachment_url'];
                                                    $pattern = "/support_documents/";

                                                    //Check if the primary_id matches with the medical claim Id and if is a support_documents. If so create the s3_preassigned_url
                                                    if ($reimbursement_claim_attachment['attachment_primary_id'] == $reimbursement_claim_id  && preg_match($pattern, $attachment_url) == 1) {

                                                        $objectKey = $attachment_url . '/' . $reimbursement_claim_attachment['attachment_name'];
                                                        $url = $CI->config->item('upload_files_to_s3') ? $CI->grants_s3_lib->s3_preassigned_url($objectKey) : $CI->attachment_library->get_local_filesystem_attachment_url($objectKey);

                                                ?>

                                                        <tr>
                                                            <td><i id='<?= $reimbursement_claim_attachment['attachment_id'] ?>' class="btn fa fa-trash delete_attachment <?= $status_id == $initial_status || $status_backflow_sequence == 1 ? '' : 'disabled'; ?>" aria-hidden="true"></td>
                                                            <td><a target='__blank' href='<?= $url; ?>'><?= $reimbursement_claim_attachment['attachment_name']; ?></a></td>

                                                        </tr>

                                                <?php } else {

                                                        continue;
                                                    }

                                                    if ($support_docs_flag == 1 && $reimbursement_claim_attachment['attachment_primary_id'] == $reimbursement_claim_id) {

                                                        //Toggle to enable the button when Receipts/ Support Document have been upload
                                                        disbale_ready_to_submit_btn($reimbursement_claim_id);
                                                    }
                                                }  ?>


                                            </tbody>


                                        </table>
                                        </p>

                                    <?php } ?>

                                </div>
                            </div>

                        </td>
                        <!-- Track Number -->
                        <td nowrap='nowrap'>
                            <?php
                            $primary_key = $data['reimbursement_claim_id'];

                            echo '<a href="' . base_url() . lcfirst($CI->controller) . '/view/' . hash_id($primary_key) . '" style="' . $track_number_style . '" >' . $data['reimbursement_claim_track_number'] . '</a>';

                            ?>

                        </td>
                        <!-- Claim name,connect incident id, claim count, treatment date, dignosis, caregiver contrbn, reimbursed, status name -->
                        <?php
                        $child_number = $data['reimbursement_claim_beneficiary_number'];
                        $voucher_number=$data['voucher_number'];

                        $fcp_number = substr($child_number, 1, 6);
                        ?>
                        <td><?= $voucher_number; ?></td>
                        <td><?= $child_number; ?></td>
                        <td><?= $fcp_number; ?></td>
                        <?php
                          $amount=$data['reimbursement_claim_amount_reimbursed']+$data['reimbursement_claim_caregiver_contribution'];
                        ?>
                        <td><?= $data['reimbursement_app_type_name'] ?></td>
                        <td><?= $data['reimbursement_funding_type_name'] ?></td>
                        <td><?= $data['reimbursement_claim_incident_id'] ?></td>
                        <td><?= $data['reimbursement_claim_treatment_date'] ?></td>
                        <td><?= $data['reimbursement_claim_diagnosis'] ?></td>
                        <td><?= $amount; ?></td>
                        <td><?= $data['reimbursement_claim_caregiver_contribution'] ?></td>
                        <td><?= $data['reimbursement_claim_amount_reimbursed'] ?></td>
                        <td><?= $data['office_name'] ?></td>

                    </tr>

                <?php
                }
                ?>

            </tbody>
        </table>
<?php }
}

if (!function_exists('draw_diagnosis_area')) {
    function draw_diagnosis_area()
    {

        $CI = &get_instance();
        //Get sponship types
        $reimbursement_funding_type = $CI->reimbursement_claim_model->reimbursement_type();

        //Get diagnosis type
        $reimbursement_diagnosis_type = $CI->reimbursement_claim_model->reimbursement_diagnosis_type();

        $html_tag_diagnosis_area = "";

        //Funding Type

        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<label class='col-xs-2 control-label'>Funding Type</label><div class='col-xs-4'>";

        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<select class='form-control required' name='fk_reimbursement_funding_type_id' id='fk_reimbursement_funding_type_id'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value='0'>".get_phrase('funding_type', 'Select Funding Type') . "</option>";

        foreach ($reimbursement_funding_type as $key => $funding_type) {

            if($key==1){//Remove no funding type in dropdown
                continue;
            }
            $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value='" . $key . "'>" . $funding_type . "</option>";
        }
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . '</select></div>';
        //  Diagnosis Category
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<label class='col-xs-2 control-label '>Diagnosis Category</label>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<div class='col-xs-4'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<select class=' form-control  required select2' name='medical_claim_diagnosis_category' id='medical_claim_diagnosis_category'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . " <option value='0'>" . get_phrase('diagnosis_category', 'Diagnosis Category') . "</option>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . " </select> </div>";

        //Diagnosis Type
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<label class='col-xs-2 control-label '>Diagnosis Type</label>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<div class='col-xs-4'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<select class='form-control  required' name='reimbursement_claim_diagnosis_type' id='reimbursement_claim_diagnosis_type'>";
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value='0'>" . get_phrase('diagnosis_type', 'Diagnosis Type') . "</option>";
        //  $html_tag_diagnosis_area= $html_tag_diagnosis_area." <option value='Illness'>".get_phrase('Illness', 'Illness')."</option>";
        //  $html_tag_diagnosis_area= $html_tag_diagnosis_area." <option value='Injury'>".get_phrase('Injury', 'Injury')."</option> </select> </div>";
        foreach ($reimbursement_diagnosis_type as $id => $diagnosis_type) {
            $html_tag_diagnosis_area = $html_tag_diagnosis_area . "<option value=" . $id . ">" . $diagnosis_type . "</option>";
        }
        $html_tag_diagnosis_area = $html_tag_diagnosis_area . "</select> </div>";

        return $html_tag_diagnosis_area;
    }
}
