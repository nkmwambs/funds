<?php 

$fund_balance_report = $this->financial_review_dashboard_model->_fund_balance_report([$office_id], $reporting_month);

?>
<table class="table table-striped" id="fund_balance_table">
        <thead>
        <tr>
            <th colspan="5" style="font-weight: bold;"><?=get_phrase('fund_balance_report', 'Fund Balance Report');?></th>
        </tr>
            <tr>
                <th class='row_header'><?=get_phrase('fund');?></th>
                <th class='row_header'><?=get_phrase('opening_balance');?></th>
                <th class='row_header'><?=get_phrase('month_income');?></th>
                <th class='row_header'><?=get_phrase('month_expense');?></th>
                <th class='row_header'><?=get_phrase('closing_balance');?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($fund_balance_report as $fund_account_info){?>
                <tr>
                    <td><?=$fund_account_info['account_name'];?></td>
                    <td class="currency fund_month_opening_balance"><?=number_format($fund_account_info['month_opening_balance'],2);?></td>
                    <td class="currency fund_month_income"><?=number_format($fund_account_info['month_income'],2);?></td>
                    <td class="currency fund_month_expense"><?=number_format($fund_account_info['month_expense'],2);?></td>
                    <td class="currency fund_month_closing_balance">0.00</td><!--Value calculate with JS in the view file-->
                </tr>
            <?php }?>
        </tbody>
        <tfoot>
            <tr>
                <td class='row_total'>Total</td>
                <td class='row_total' id="total_fund_month_opening_balance"><?=number_format(array_sum(array_column($fund_balance_report,'month_opening_balance')),2);?></td>
                <td class='row_total' id="total_fund_month_income"><?=number_format(array_sum(array_column($fund_balance_report,'month_income')),2);?></td>
                <td class='row_total' id="total_fund_month_expense"><?=number_format(array_sum(array_column($fund_balance_report,'month_expense')),2);?></td>
                <td class='row_total code_proof_of_cash' id="total_fund_month_closing_balance">0.00</td>
            </tr>
        </tfoot>
        </table>

<script>

    compute_fund_balance_totals()

    function compute_fund_balance_totals(){
        $('#fund_balance_table tbody tr').each(function(i, el) {
            let opening_balance = parseFloat($(el).find('.fund_month_opening_balance').html().split(',').join(""));
            let month_income = parseFloat($(el).find('.fund_month_income').html().split(',').join(""));
            let month_expense = parseFloat($(el).find('.fund_month_expense').html().split(',').join(""));
            let closing_opening_balance = (opening_balance + month_income) - month_expense;

            $(this).find('.fund_month_closing_balance').html(accounting.formatNumber(closing_opening_balance, 2));
        });

        let sum_opening_balance = parseFloat($('#total_fund_month_opening_balance').html().split(',').join(""));
        let sum_month_income = parseFloat($('#total_fund_month_income').html().split(',').join(""));
        let sum_month_expense = parseFloat($('#total_fund_month_expense').html().split(',').join(""));

        $("#total_fund_month_closing_balance").html(accounting.formatNumber((sum_opening_balance + sum_month_income - sum_month_expense), 2));
        // $("#total_fund_month_closing_balance").append(" <span class='label label-info'>1</span>");
        $(".row_total, .row_header").css('font-weight', 'bold');
    }
</script>