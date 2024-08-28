<?php 
$deposit_in_transit = $this->financial_report_model->list_oustanding_cheques_and_deposits([$office_id], $reporting_month, 'income', 'cash_contra', 'bank');
?>

<table class="table table-striped tbl_cleared_transit_deposit_connector" id='tbl_transit_deposit'>
    <thead>
        <tr>
            <th colspan="3" style="font-weight: bold;" ><?=get_phrase('list_of_transit_deposit', 'List of In Transit Deposit');?></th>
        </tr>
        <tr>
            <th><?= get_phrase('date'); ?></th>
            <th><?= get_phrase('description'); ?></th>
            <th><?= get_phrase('amount'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($deposit_in_transit as $deposit_in_transit_row) { ?>
            <tr>
                <td><?= $deposit_in_transit_row['voucher_date']; ?></td>
                <td><?= $deposit_in_transit_row['voucher_description']; ?></td>
                <td class='td_row_amount'><?= number_format($deposit_in_transit_row['voucher_detail_total_cost'], 2); ?></td>
            </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan='2'><?= get_phrase('total'); ?></td>
            <td class='td_effects_total total_dt'><?= number_format(array_sum(array_column($deposit_in_transit, 'voucher_detail_total_cost')), 2); ?></td>
        </tr>
    </tfoot>
</table>

<script>

</script>