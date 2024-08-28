<?php
    $bank_statements_uploads = $this->financial_report_model->_bank_statements_uploads([$office_id], $reporting_month);
?>


        <table class="table table-striped" id="tbl_list_statements">
            <thead>
                <tr><th style="font-weight: bold;"><?=get_phrase('list_bank_statements','List of Bank Statement');?></th></tr>
                <tr>
                    <th><?=get_phrase('file_name');?></th>
                    <th><?=get_phrase('file_size');?></th>
                    <th><?=get_phrase('last_modified_date');?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($bank_statements_uploads as $bank_statements_upload){
                      if(!isset($bank_statements_upload['attachment_url'])) continue;
                    ?>
                    <tr>
            
                        <?php 
                            $objectKey = $bank_statements_upload['attachment_url'].'/'.$bank_statements_upload['attachment_name'];
                            $url = $this->config->item('upload_files_to_s3')?$this->grants_s3_lib->s3_preassigned_url($objectKey):$this->attachment_library->get_local_filesystem_attachment_url($objectKey);
                        ?>
                        <td><a target='__blank' href='<?=$url;?>'><?=$bank_statements_upload['attachment_name'];?></a></td>
                        <td><?=formatBytes($bank_statements_upload['attachment_size']);?></td>
                        <td><?=$bank_statements_upload['attachment_last_modified_date'];?></td>
                    </tr>
                <?php }?>
            </tbody>
        </table>


<script>

</script>