<?php 

$centers_with_missing_last_month_mfr = $this->financial_report_model->last_month_submitted_financial_reports();

// print_r($centers_with_missing_last_month_mfr);
?>

<div class="row">
    <div class="col-xs-12">
        <table class="table table-striped" id="datatable">
            <thead>
                <tr>
                    <th colspan="3"><?=get_phrase('fcp_missing_last_month_report','FCPs with Missing Last Month MFR');?></th>
                </tr>
                <tr>
                    <th><?=get_phrase('fcp_code','FCP Code')?></th>
                    <th><?=get_phrase('fcp_name','FCP Name');?></th>
                    <th><?=get_phrase('cluster_name','Cluster Name')?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($centers_with_missing_last_month_mfr as $office){?>
                    <tr>
                        <td><?=$office['office_code'];?></td>
                        <td><?=$office['office_name'];?></td>
                        <td><?=$office['cluster_name'];?></td>
                    </tr>
                <?php }?>
            </tbody>
        </table>
    </div>
</div>

<script>
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
    });
</script>
