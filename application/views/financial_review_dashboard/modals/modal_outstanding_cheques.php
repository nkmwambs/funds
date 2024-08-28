<?php 
$outstanding_cheques = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'expense', 'bank_contra', 'bank');
log_message('error', json_encode($outstanding_cheques));
?>

<table class="table table-striped tbl_cleared_outstanding_cheque_connector" id='tbl_outstanding_cheque'>
    <thead>
        <tr>
            <th colspan="4" style="font-weight: bold;"><?=get_phrase('list_outstanding_cheques', 'List of Outstanding Cheques');?></th>
        </tr>
        <tr>
            <th><?= get_phrase('date'); ?></th>
            <th><?= get_phrase('description'); ?></th>
            <th><?= get_phrase('cheque_number'); ?></th>
            <th><?= get_phrase('amount'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $oustanding_state_color = "danger";
 
        if(sizeof($outstanding_cheques)>0){

       
        foreach ($outstanding_cheques as $outstanding_cheque) { 
               $style=$outstanding_cheque['voucher_id']==0?"color:#FF0000":''; 
            ?>

                <td><?= $outstanding_cheque['voucher_date']; ?></td>
                <td><?= $outstanding_cheque['voucher_description']; ?></td>
                <td><?= $outstanding_cheque['voucher_cheque_number']; ?></td>
                <!-- <td><?= $outstanding_cheque['office_bank_name']; ?></td> -->

                <td class='td_row_amount'><?= number_format($outstanding_cheque['voucher_detail_total_cost'], 2); ?></td>
            </tr>
        <?php } 
        }
        else{
            $no_data=get_phrase('no_outstanding cheques');
        }
        ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan='3'><?= get_phrase('total'); ?></td>

            <td class='td_effects_total total_oc' id='total_oc_from_list'><?= empty($outstanding_cheques)?$no_data: number_format(array_sum(array_column($outstanding_cheques, 'voucher_detail_total_cost')), 2); ?></td>
        </tr>
    </tfoot>
</table>
