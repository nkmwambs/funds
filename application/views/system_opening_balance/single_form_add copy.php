
<style>
  .header_label {
    font-weight: bold;
    text-align: center;
  }

  .row {
    margin-bottom: 25px;
  }
</style>

<?php

$income_accounts = [
  1 => 'R100 - Support Funds',
  2 => 'R200 - Gift Funds'
];

$cash_boxes = [
  1 => 'Petty Cash Box',
  2 => 'Mobile Money',
  3 => 'Vendor Box'
];
?>

<?php echo form_open("", array('id' => 'system_opening_balance', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

<div class="row">
  <div class="col-xs-12 header_label">
    <div id="reset_cash_balance" class='btn btn-danger'><?= get_phrase('reset_cash_balance'); ?></div>
  </div>
</div>
<hr />

<div class="row">
  <div class="col-xs-6 header_label">
    <?= get_phrase('bank_balance') ?>
  </div>
  <div class="col-xs-6 header_label">
    <?= get_phrase('cash_balance') ?>
  </div>
</div>

<hr />

<div class = 'row'>
  <div class = 'col-xs-6'>
    <div class="form-group">
      <label class="control-label col-xs-6"><?= get_phrase('choose_bank_account'); ?></label>
      <div class="col-xs-6">
        <select class="form-control" id="office_bank_id" name="office_bank_id">
          <option value=""><?= get_phrase('select_a_bank_account'); ?></option>

          <option value='1'>Tested CDC - Standard Chartered Bank Account</option>
          <option value="2">Tested CDC - ECO Bank Account</option>

        </select>
      </div>
    </div>

    <div class="form-group">
        <label class="control-label col-xs-6"><?= get_phrase('book_bank_closing_balance'); ?> <span style='color:brown;font-weight:bold;'>(A)</span></label>
        <div class="col-xs-6">
          <input type="number" class="form-control proof_of_cash bank_reconciliation_fields" id="book_bank_balance" name="book_bank_balance" value="0" />
        </div>
    </div>

  </div>

  <div class = 'col-xs-6'>
      <?php foreach ($cash_boxes as $cash_box_id => $cash_box) { ?>
        <div class='form-group'>
          <label class='control-label col-xs-6'><?= $cash_box; ?></label>
          <div class="col-xs-6">
            <input type="number" class="form-control cash_balance proof_of_cash" value="0" name="cash_balance[<?= $cash_box_id; ?>]" />
          </div>
        </div>
      <?php } ?>
  </div>
</div>

<hr />

<div class='row'>
  <div class='col-xs-12'>
    <div class='form-group'>
      <label class='control-label col-xs-6'><?= get_phrase('total_cash'); ?> <span style='color:brown;font-weight:bold;'>(B)</span></label>
      <div class="col-xs-6">
        <input type="number" class="form-control" onkeypress="return false;" name="total_cash" id="total_cash" value="0" />
      </div>
    </div>
  </div>
</div>

<hr />

<div class="row">
  <div class="col-xs-12">
    <table class="table table-stripped" id="fund_balance_table">
      <thead>
        <tr>
          <th class="header_label" colspan="7"><?= get_phrase('fund_balance'); ?></th>
        </tr>
        <tr>
          <th colspan="7" style="text-align:center;">
            <div id="insert_fund_balance" class="btn btn-success"><?= get_phrase('insert_fund_balance_row'); ?></div>
            <div id="reset_fund_balance" class="btn btn-danger"><?= get_phrase('reset_fund_balance_report'); ?></div>
          </th>
        </tr>
        <tr>
          <th><?= get_phrase('action'); ?></th>
          <th><?= get_phrase('project'); ?></th>
          <th><?= get_phrase('account'); ?></th>
          <th><?= get_phrase('month_opening'); ?></th>
          <th><?= get_phrase('month_income'); ?></th>
          <th><?= get_phrase('month_expense'); ?></th>
          <th><?= get_phrase('closing_balance'); ?></th>
        </tr>
      </thead>
      <tbody>

      </tbody>
      <tfoot>
        <tr>
          <td style="font-weight: bold;" colspan='6'>
            <?= get_phrase('total_fund_balance'); ?> <span style='color:brown;font-weight:bold;'>(C)</span>
          </td>
          <td><input class="form-control" type='number' onkeypress="return false;" name='total_fund_balance' id='total_fund_balance' value="0" /></td>
        </tr>
        <tr>
          <td style="font-weight: bold;" colspan='5'><?= get_phrase('proof_of_cash_check'); ?></td>
          <td>
            <i class='label label-danger' id="proof_of_cash_check"><?= get_phrase('proof_of_cash_error'); ?></i>
            <span style='color:brown;font-weight:bold;'>D = (B = C)</span>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<hr />

<div class='row'>
  <div class="col-xs-6 header_label"><?= get_phrase('outstanding_cheques'); ?></div>
  <div class="col-xs-6 header_label"><?= get_phrase('deposits_in_transit'); ?></div>
</div>

<hr />

<div class='row'>
  <div class="col-xs-6">
    <table class='table table-stripped' id='outstanding_cheque_table'>
      <thead>
        <tr>
            <th colspan="5" style="text-align:center;">
              <div id="insert_outstanding_cheque" class="btn btn-success"><?= get_phrase('insert_outstanding_cheque_row'); ?></div>
              <div id="reset_outstanding_cheque" class="btn btn-danger"><?= get_phrase('reset_outstanding_cheque_report'); ?></div>
          </th>
        </tr>
        <tr>
          <th><?= get_phrase('action'); ?></th>
          <th><?= get_phrase('transaction_date'); ?></th>
          <th><?= get_phrase('cheque_number'); ?></th>
          <th><?= get_phrase('description'); ?></th>
          <th><?= get_phrase('amount'); ?></th>
        </tr>
      </thead>
      <tbody>
      </tbody>
      <tfoot>
        <tr>
            <td colspan="4" style = 'font-weight:bold;'><?=get_phrase('total_outstanding_cheques');?> <span style='color:brown;font-weight:bold;'>(E)</span></td>
            <td><input type = 'number' class = "form-control"  onkeypress="return false;" name = 'total_outstanding_cheque'  id = "total_outstanding_cheque" /></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="col-xs-6">
    <table class='table table-stripped' id='deposit_in_transit_table'>
      <thead>
        <tr>
            <th colspan="4" style="text-align:center;">
              <div id="insert_deposit_in_transit" class="btn btn-success"><?= get_phrase('insert_deposit_in_transit_row'); ?></div>
              <div id="reset_deposit_in_transit" class="btn btn-danger"><?= get_phrase('reset_deposit_in_transit_report'); ?></div>
          </th>
        </tr>
        <tr>
          <th><?= get_phrase('action'); ?></th>
          <th><?= get_phrase('transaction_date'); ?></th>
          <th><?= get_phrase('description'); ?></th>
          <th><?= get_phrase('amount'); ?></th>
        </tr>
      </thead>
      <tbody>
      </tbody>
      <tfoot>
        <tr>
            <td colspan="3" style = 'font-weight:bold;'><?=get_phrase('total_deposit_in_transit');?> <span style='color:brown;font-weight:bold;'>(F)</span></td>
            <td><input type = 'number' class = "form-control"  onkeypress="return false;" name = 'total_deposit_in_transit'  id = "total_deposit_in_transit" /></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<hr/>

<div class = 'row'>
  <div class = 'col-xs-6 header_label'><?=get_phrase('reconciliation_statement');?></div>

  <div class = 'col-xs-6 header_label'><?=get_phrase('bank_statements');?></div>
</div>

<hr/>

<div class = 'row'>
  <div class = 'col-xs-6'>

        <div class = 'form-group'>
          <label class = 'control-label col-xs-6'><?=get_phrase('statement_date');?></label>
          <div class = "col-xs-6">
            <input type="text" class="form-control datepicker reconciliation_statement" id = 'statement_date' name="statement_date" data-format = 'yyyy-mm-dd' onkeydown="return false;"  />
          </div>
        </div>

        <div class = 'form-group'>
          <label class="control-label col-xs-6"><?= get_phrase('statement_balance'); ?> <span style='color:brown;font-weight:bold;'>(G)</span></label>
          <div class="col-xs-6">
            <input type="number" class="form-control reconciliation_statement bank_reconciliation_fields" id="statement_balance" name="statement_balance" value="0" />
          </div>
        </div>


        <div class = 'form-group'>
          <label class="control-label col-xs-6"><?= get_phrase('reconciled_statement_balance'); ?> <span style='color:brown;font-weight:bold;'>H = (G + F - E)</span></label>
          <div class="col-xs-6">
            <input type="number" class="form-control reconciliation_statement" onkeypress="return false;" id="reconciled_statement_balance" name="reconciled_statement_balance" value="0" />
          </div>
        </div>

        <div class = 'form-group'>
          <label class="control-label col-xs-6"><?= get_phrase('bank_reconciled_difference'); ?> <span style='color:brown;font-weight:bold;'>J = A - H</span></label>
          <div class="col-xs-6">
            <input type="number" class="form-control reconciliation_statement" onkeypress="return false;" id="bank_reconciled_difference" name="bank_reconciled_difference" value="0" />
          </div>
        </div>

        <div class = 'form-group'>
            <div class = 'col-xs-12' style = 'text-align:center;'>
                <i class = 'label label-danger' id = 'reconciliation_error'><?=get_phrase('bank_reconciliation_error');?></i> <span style='color:brown;font-weight:bold;'>I = (H = A)</span>
            </div>
        </div>
  </div>

  <div class = 'col-xs-6'>
    <div class = 'form-group'>
        <div class = 'col-xs-12'>
            <input type="file" class="form-control" name="upload_bank_statement_file" multiple />
        </div>
    </div>
  </div>
</div>

<hr />

<div class="row">
  <div class = 'col-xs-12 header_label'>
        <div id = "save" class = 'btn btn-success'><?=get_phrase('save');?></div>
        <div id = "reset" class = 'btn btn-danger'><?=get_phrase('reset');?></div>
  </div>
</div>
</form>


<?php
include "view_script.php";
?>