    <?php 
        extract($transactions_summary);

        $transaction_types = [1 => 'Income', 2 => 'Bank Expenses', 3 => 'Cash Expenses', 4 => 'All Expenses'];

        if(count($transactions_summary['data']) == 0 ){
    ?>
        <div class = 'well'><?=get_phrase('missing_transaction_report','No data found for the transaction type in the given time or user is out of scope of a country')?></div>
    <?php
        }else{
    ?>

    <table class = 'table table-stripped datatable table-bordered'>
            <thead>
                <tr>
                    <th colspan = '<?=count($accounts) + 2;?>'>
                        <?=get_phrase('transaction_type');?> - <?=get_phrase(strtolower($transaction_types[$parameters['transaction_type']]));?>: <?=$parameters['start_date'];?> - <?=$parameters['end_date'];?>
                    </th>
                </tr>
                <tr>
                    <th rowspan = '2'><?=get_phrase('office')?></th>
                    <th colspan = '<?=count($accounts);?>' style = 'align:center;'><?=get_phrase('accounts_breakdown');?></th>
                    <th rowspan = '2'><?=get_phrase('total');?></th>
                </tr>
                <tr>
                    <?php 
                        foreach($accounts as $account){
                    ?>
                        <th><?=$account;?></th>
                    <?php
                        }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data as $office => $spread){?>
                    <tr>
                        <td><?=$office;?></td>
                        <?php 
                            $office_sum = 0;
                            foreach($accounts as $account_id => $account){
                                $amount = isset($spread[$account_id]['amount']) ? $spread[$account_id]['amount'] : 0;
                                $office_sum += $amount;
                        ?>
                            <td><?=number_format($amount,2);?></td>
                        <?php }?>
                        <td><?=number_format($office_sum,2);?></td>
                    </tr>
                <?php }?>
               
            </tbody>
    </table>

    <?php
        }
    ?>

    <script>
        $('.table').DataTable({
            dom: 'lBfrtip',
            buttons: [
                'copyHtml5',
                'excelHtml5',
                'csvHtml5',
                'pdfHtml5',
            ]
        });
    </script>