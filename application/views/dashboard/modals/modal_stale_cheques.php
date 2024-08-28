<?php 

$stale_cheques = $this->financial_report_model->stale_cheques();

// print_r($stale_cheques);
?>

<div class="row">
    <div class="col-xs-12">
        <table class="table table-striped" id="datatable">
            <thead>
                <tr>
                    <th colspan="5"><?=get_phrase('stale_cheques','Stale Cheques');?></th>
                </tr>
                <tr>
                    <th><?=get_phrase('fcp_code','FCP Code')?></th>
                    <th><?=get_phrase('fcp_name','FCP Name');?></th>
                    <th><?=get_phrase('cheque_number','Cheque Number')?></th>
                    <th><?=get_phrase('transaction_date','Transaction Date')?></th>
                    <th><?=get_phrase('amount','Amount')?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    foreach($stale_cheques as $stale_cheque){
                        if($stale_cheque['amount'] == 0) continue;
                ?>
                    <tr>
                        <td><?=$stale_cheque['office_code'];?></td>
                        <td><?=$stale_cheque['office_name'];?></td>
                        <td><?=$stale_cheque['voucher_cheque_number'];?></td>
                        <td><?=$stale_cheque['voucher_date'];?></td>
                        <td><?=number_format($stale_cheque['amount'],2);?></td>
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
