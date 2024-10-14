<style>
    .control-label {
        text-align: left;
    }

    .center {
        text-align: center;
    }
</style>


<?php
extract($result);
?>


<div class="row">
    <div class="col-xs-12">
        <?= Widget_base::load('position', 'position_1'); ?>
    </div>
</div>

<div class='row' id="main_row">
    <div class='col-xs-12 split_screen'>
        <div class="panel panel-default" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="entypo-plus-circled"></i>
                    <?php echo get_phrase('transaction_voucher'); ?>
                </div>
            </div>

            <div class="panel-body" style="max-width:50; overflow: auto;">
                <?php echo form_open("", array('id' => 'frm_voucher', 'class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data')); ?>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-insert'><?= get_phrase('insert_voucher_detail_row'); ?></div>
                        <div class='btn btn-default btn-save'><?= get_phrase('save'); ?></div>
                        <div class='btn btn-default btn-save-new'><?= get_phrase('save_and_new'); ?></div>
                        <div class='btn btn-default btn-retrieve-request <?= $office_has_request ? null : 'hidden'; ?>'><?= get_phrase('show_or_hide_requests'); ?> &nbsp; <span class='badge badge-secondary requests_badge'>0</span></div>
                    </div>
                </div>

                <div class = "form-group">
                    <label class='control-label col-xs-2'><?= get_phrase('funder'); ?></label>
                    <div class='col-xs-3'>
                        <select class='form-control required' id='funder' name='fk_funder_id'>
                            <option value=""><?= get_phrase('select_funder'); ?></option>
                            <?php foreach ($user_funder as $funder) { ?>
                                <option value="<?= $funder['funder_id']; ?>"><?= $funder['funder_name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <label class='control-label col-xs-2'><?= get_phrase('office'); ?></label>
                    <div class='col-xs-3'>
                        <select class='form-control required' id='office' name='fk_office_id'>
                            <option value=""><?= get_phrase('select_office'); ?></option>
                            
                        </select>
                    </div>
                </div>

                <div class='form-group'>
                    <label class='control-label col-xs-2 date-field'><?= get_phrase('transaction_date'); ?></label>
                    <div class='col-xs-2 date-field'>
                        <input id="transaction_date" type='text' name='voucher_date' onkeydown="return false" class='form-control required' autocomplete="off" />
                    </div>

                    <label class='control-label col-xs-2'><?= get_phrase('voucher_number'); ?></label>
                    <div class='col-xs-2'>
                        <input type='text' onkeydown="return false" class='form-control required' name='voucher_number' id="voucher_number" autocomplete="off"/>
                    </div>

                    <label class='control-label col-xs-2'><?= get_phrase('voucher_type'); ?></label>
                    <div class='col-xs-2'>
                        <select class='form-control required' disabled="disabled" name='fk_voucher_type_id' id='voucher_type'>
                            <option value=""><?= get_phrase('select_voucher_type'); ?></option>

                        </select>
                    </div>

                </div>

                <div class='form-group'>

                    <span class='hidden'>
                        <label class='control-label col-xs-2'><?= get_phrase('bank_account'); ?></label>
                        <div class='col-xs-2'>
                            <select class="form-control required account_fields" disabled="disabled" name='fk_office_bank_id' id='bank'>
                                <option value=""><?= get_phrase('select_bank_account'); ?></option>
                            </select>
                        </div>
                    </span>

                    <span class='hidden'>
                        <label class='control-label col-xs-2'><?= get_phrase('cheque_number'); ?></label>
                        <div class='col-xs-2'>
                            <!-- <input type='text' name='voucher_cheque_number' id='cheque_number' disabled='disabled' class='form-control required account_fields' /> -->
                            <select class='form-control required account_fields' name='voucher_cheque_number' id='cheque_number' disabled='disabled'>
                                <option value=''><?= get_phrase('select_cheque_number'); ?></option>
                            </select>
                        </div>
                    </span>

                    <span class='hidden'>
                        <label class='control-label col-xs-2'><?= get_phrase('cash_account'); ?></label>
                        <div class='col-xs-2'>
                            <select class="form-control required account_fields" disabled="disabled" name='fk_office_cash_id' id='cash_account'>
                                <option value=""><?= get_phrase('select_cash_account'); ?></option>
                            </select>
                        </div>
                    </span>

                    <span class='hidden'>
                        <label class='control-label col-xs-2'><?= get_phrase('receiving_account'); ?></label>
                        <div class='col-xs-2'>
                            <select name='cash_recipient_account' disabled="disabled" id='cash_recipient_account' class='form-control required account_fields'>
                            </select>
                        </div>
                    </span>


                </div>

                <div class='form-group'>
                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('bank_balance'); ?></label>
                        <div class='col-xs-2'><input id='bank_balance' class='form-control' value='0' name='bank_balance' onkeydown="return false" /></div>
                    </span>
                    <!--  Total Unapproved and Approved vouchers-->
                    <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('total_cash_balance', 'Total Cash Bal.'); ?></label>
                        <div class='col-xs-2'><input id='unapproved_and_approved_vouchers_cash_balance' name='unapproved_and_approved_vouchers' class='form-control' value='0' onkeydown="return false" /></div>
                    </span>

                    <!-- <span class='hidden'>
                        <label class='control-label col-xs-1'><?= get_phrase('journal_cash_balance', 'Journal Cash Bal.'); ?></label>
                        <div class='col-xs-2'><input id='approved_vouchers_cash_balance' class='form-control' value='0' onkeydown="return false" /></div>
                    </span> -->

                </div>

                <div class='form-group'>
                    <label class='col-xs-1'><?= get_phrase('payee/_vendor'); ?></label>
                    <div class='col-xs-11'>
                        <input type='text' id='voucher_vendor' name='voucher_vendor' class='form-control required' autocomplete="off" />
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-1'><?= get_phrase('address'); ?></label>
                    <div class='col-xs-11'>
                        <input type='text' id='voucher_vendor_address' name='voucher_vendor_address' class='form-control required' autocomplete="off" />
                    </div>
                </div>

                <div class='form-group'>
                    <label class='col-xs-1'><?= get_phrase('description'); ?></label>
                    <div class='col-xs-11'>
                        <input type='text' id='voucher_description' name='voucher_description' class='form-control required' autocomplete="off" />
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-insert'><?= get_phrase('insert_voucher_detail_row'); ?></div>
                        <div class='btn btn-default btn-save'><?= get_phrase('save'); ?></div>
                        <div class='btn btn-default btn-save-new'><?= get_phrase('save_and_new'); ?></div>
                        <div class='btn btn-default btn-retrieve-request <?= $office_has_request ? null : 'hidden'; ?>'><?= get_phrase('show_or_hide_requests'); ?> &nbsp; <span class='badge badge-secondary requests_badge'>0</span></div>
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12'>
                        <table class='table table-striped' id='tbl_voucher_body'>
                            <thead>
                                <tr>
                                    <th><?= get_phrase('action'); ?></th>
                                    <th><?= get_phrase('quantity'); ?></th>
                                    <th><?= get_phrase('description'); ?></th>
                                    <th><?= get_phrase('unit_cost'); ?></th>
                                    <th><?= get_phrase('total_cost'); ?></th>

                                    <?php
                                    $toggle_accounts_by_allocation = $this->config->item("toggle_accounts_by_allocation");

                                    if ($toggle_accounts_by_allocation) {
                                    ?>
                                        <th><?= get_phrase('allocation_code'); ?></th>
                                        <th><?= get_phrase('account'); ?></th>
                                    <?php } else { ?>
                                        <th><?= get_phrase('account'); ?></th>
                                        <th><?= get_phrase('allocation_code'); ?></th>
                                    <?php } ?>

                                    <th class="<?= $office_has_request ? null : 'hidden'; ?>"><?= get_phrase('request_number'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="tbl_tbody">
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan='6'><?= get_phrase('total'); ?></td>
                                    <td><input type='text' id='voucher_total' class='form-control' readonly /></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class='form-group'>
                    <div class='col-xs-12 center'>
                        <div class='btn btn-default btn-reset'><?= get_phrase('reset'); ?></div>
                        <div class='btn btn-default btn-insert'><?= get_phrase('insert_voucher_detail_row'); ?></div>
                        <div class='btn btn-default btn-save'><?= get_phrase('save'); ?></div>
                        <div class='btn btn-default btn-save-new'><?= get_phrase('save_and_new'); ?></div>
                        <div class='btn btn-default btn-retrieve-request <?= $office_has_request ? null : 'hidden'; ?>'><?= get_phrase('show_or_hide_requests'); ?> &nbsp; <span class='badge badge-secondary requests_badge'>0</span></div>
                    </div>
                </div>

                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-xs-12">
                <?php //echo Widget_base::load('upload');
                ?>
            </div>
        </div>


    </div>
</div>

