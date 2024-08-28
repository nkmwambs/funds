<?php
extract($result);
// print_r($transfer_request);
?>

<div class="row">
  <div class="col-xs-12">
      <?=Widget_base::load('comment');?>
  </div>
</div>

<div class="row">
    <div class="col-sm-12">

        <div class="panel panel-primary" data-collapsed="0">
            <div class="panel-heading">
                <div class="panel-title">
                    <i class="fa fa-search"></i>
                    <?php echo get_phrase('funds_transfer_request'); ?>
                </div>
            </div>
            <div class="panel-body" style="max-width:50; overflow: auto;">
                <div id="print_view">
                <a href="<?= base_url(); ?>funds_transfer/list" class="btn btn-primary hidden-print" id="list_transfer"><?=get_phrase('list_fund_transfer_requests');?></a>
                <hr />

                <table class="table table-striped ">
                    <thead>
                        <tr>
                            <th style="text-align:center;" colspan="3">
                                
                                <?php
                                    $status_data = $this->general_model->action_button_data('funds_transfer');
                                    extract($status_data); 
                                    echo approval_action_button($this->controller,$item_status, $transfer_request['funds_transfer_id'], $transfer_request['funds_transfer_status_id'], $item_initial_item_status_id, $item_max_approval_status_ids);
                                ?>
                            </th>
                        </tr>
                        <tr>
                            <td colspan="3" style="text-align:center;">
                                <?= $transfer_request['office_name']; ?> <br />
                                <?=get_phrase('funds_transfer_request');?>
                            </td>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span style="font-weight:bold;"><?=get_phrase('request_date');?>:</span> <?= $transfer_request['raise_date']; ?></td>
                            <td colspan="2"><span style="font-weight:bold;"><?=get_phrase('voucher_number');?></span> <?= !$transfer_request['voucher_number'] ? "Not yet assigned" : $transfer_request['voucher_number']; ?></td>
                        </tr>
                        <tr>
                            <td><span style="font-weight:bold;"><?=get_phrase('source');?></span> <?= $transfer_request['office_name']; ?></td>
                            <td colspan="2"><span style="font-weight:bold;"><?=get_phrase('request_raised_by');?></span> <?= $transfer_request['requestor']; ?></td>
                        </tr>
                        <tr>
                            <td><span style="font-weight:bold;"><?=get_phrase('request_status');?>:</span> <?=$transfer_request['request_status'];?></td>
                            <td colspan="2"><span style="font-weight:bold;"><?=get_phrase('funds_transfer_type');?>:</span> <?= ucwords(str_replace('_',' ',transfer_types()[$transfer_request['funds_transfer_type']])); ?></td>
                        </tr>
                        <tr>
                            <td colspan="3">
                                <span style="font-weight:bold;"><?=get_phrase('request_description');?>:</span>
                                <?= $transfer_request['description']; ?>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='3'></td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold;"><?=get_phrase('transfer_order');?></td>
                            <td style="font-weight:bold;"><?=get_phrase('allocation_code');?></td>
                            <td style="font-weight:bold;"><?=get_phrase('account');?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:bold;"><?=get_phrase('transfer_source');?></td>
                            <td><?=$transfer_request['source_allocation'];?></td>
                            <td><?=$transfer_request['source_account'];?></td>

                        </tr>

                        <tr>
                            <td style="font-weight:bold;"><?=get_phrase('transfer_destination');?></td>
                            <td><?=$transfer_request['destination_allocation'];?></td>
                            <td><?=$transfer_request['destination_account'];?></td>

                        </tr>

                        <tr>
                            <td style="font-weight:bold;"><?=get_phrase('transfered_amount');?></td>
                            <td colspan="2"><?= number_format($transfer_request['amount'],2); ?></td>
                        </tr>

                        <tr>
                            <td colspan="3"></td>
                        </tr>

                        <tr>
                            <td><span style="font-weight:bold;"><?=get_phrase('request_created_by');?>: </span> <?= $transfer_request['requestor']; ?></td>
                            <td><?=get_phrase('date');?>: <?=$transfer_request['funds_transfer_raise_date'];?> </td>
                            <td><?=get_phrase('signature');?>: </td>
                        </tr>
                        <tr>
                            <td><span style="font-weight:bold;"><?=get_phrase('request_approved_by');?> (<?=get_phrase('ASM_approver');?>): </span> </td>
                            <td><?=get_phrase('date');?>: </td>
                            <td><?=get_phrase('signature');?>: </td>
                        </tr>
                        <tr>
                            <td><span style="font-weight:bold;"><?=get_phrase('request_verified_by');?> (<?=get_phrase('final_system_verifier');?>): </span> <?=$transfer_request['funds_transfer_approved_by'];?></td>
                            <td><?=get_phrase('date');?>: <?=$transfer_request['funds_transfer_approval_date'];?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    </div>
</div>

<script>
 function PrintElem(elem)
    {
        $(elem).printThis({ 
		    debug: false,              
		    importCSS: true,             
		    importStyle: true,         
		    printContainer: false,       
		    loadCSS: "", 
		    pageTitle: "<?php echo get_phrase('funds_transfer_request');?>",             
		    removeInline: false,        
		    printDelay: 333,            
		    header: null,             
		    formValues: true          
		});
    }
</script>