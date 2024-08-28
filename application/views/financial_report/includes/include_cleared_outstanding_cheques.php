<?php
//print_r($clear_outstanding_cheques);
?>
<table class="table table-striped tbl_outstanding_cheque_connector" id="tbl_cleared_outstanding_cheque">
    <thead>
        <tr>
            <th class = 'no-print'><?= get_phrase('action'); ?></th>
            <th><?= get_phrase('date'); ?></th>
            <th><?= get_phrase('description'); ?></th>
            <th><?= get_phrase('cheque_number'); ?></th>
            <th><?= get_phrase('voucher_number'); ?></th>
            <th><?= get_phrase('voucher_vendor'); ?></th>
            <th><?= get_phrase('amount'); ?></th>
        </tr>
    </thead>
    <tbody>
    

        <?php 
        // print_r($clear_outstanding_cheques);

        $no_data=get_phrase('no_cleared_outstanding cheques');
        
        if(sizeof($clear_outstanding_cheques)>0){

        foreach ($clear_outstanding_cheques as $clear_outstanding_cheque) { 

            $style=$clear_outstanding_cheque['voucher_id']==0?"color:#FF0000":''; 
            ?>
            <tr style ="<?=$style;?>">
                <?php
                //echo($clear_outstanding_cheque['voucher_is_reversed']);
                $clear_outstanding_cheque_state_color = "danger";

                $disable_undo_if_mfr_submitted='disabled';
                
                //$oustanding_state_disabled = "";
                $clear_outstanding_cheque_state_clear_class = 'to_clear';
                $clear_outstanding_cheque_state_label = get_phrase('unclear');
                if ($clear_outstanding_cheque['voucher_cleared'] == 1) {
                    $clear_outstanding_cheque_state_color = "success";
                    //$oustanding_state_disabled = "disabled";
                    $oustanding_state_clear_class = '';
                }
                ?>
                <td class = 'no-print'>

                <?php if (($clear_outstanding_cheque['voucher_id'] >0)||($clear_outstanding_cheque['voucher_id']==0 && $clear_outstanding_cheque['voucher_cleared']==1 && $clear_outstanding_cheque['bounce_flag']==0) ) { ?>
                    <div data-data-opening_deposit_transit_id="0" data-opening_outstanding_cheque_id="<?= isset($clear_outstanding_cheque['opening_outstanding_cheque_id']) ? $clear_outstanding_cheque['opening_outstanding_cheque_id'] : 0; ?>" id="<?= $clear_outstanding_cheque['voucher_id']; ?>" class='btn btn-<?= $clear_outstanding_cheque_state_color; ?> clear_btn <?= $allow_mfr_reconciliation && $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'update') ? '' : 'disabled'; ?> <?= $clear_outstanding_cheque_state_clear_class; ?> cleared_outstanding_cheque cleared_effect state_<?= $clear_outstanding_cheque['voucher_cleared']; ?> <?= isset($clear_outstanding_cheque['voucher_is_reversed']) &&  $clear_outstanding_cheque['voucher_is_reversed'] ? 'hidden' : '' ?>'>
                        <?= $clear_outstanding_cheque_state_label; ?>
                    </div>
                    <?php }
                    elseif($clear_outstanding_cheque['voucher_id']==0 && $clear_outstanding_cheque['bounce_flag']==1){?>
                       <div data-data-opening_deposit_transit_id="0" data-opening_outstanding_cheque_id="<?= isset($clear_outstanding_cheque['opening_outstanding_cheque_id']) ? $clear_outstanding_cheque['opening_outstanding_cheque_id'].'_unbounce' : 0; ?>" id="<?= $clear_outstanding_cheque['voucher_id']; ?>" class=' <?=$financial_report_submitted==true?$disable_undo_if_mfr_submitted:'';?> btn btn-<?= $oustanding_state_color; ?> <?= $allow_mfr_reconciliation && $this->user_model->check_role_has_permissions(ucfirst($this->controller), 'update') ? '' : 'disabled'; ?> cancel_btn <?= $oustanding_state_clear_class; ?> outstanding_cheque cleared_effect state_<?= $clear_outstanding_cheque['voucher_cleared']; ?>'>
                            <?= get_phrase('undo'); ?>
                            <i class='fa fa-undo' style='cursor:pointer;'></i>
                        </div>
                    <?php } ?>
                    
                </td>
                <?php
                  //Voucher_id
                  $voucher_id=$clear_outstanding_cheque['voucher_id'];
                ?>
                <td><?= $clear_outstanding_cheque['voucher_date']; ?></td>
                <td><?= $clear_outstanding_cheque['voucher_description']; ?></td>
                <td><?= $clear_outstanding_cheque['voucher_cheque_number']; ?></td>
                <td><a href="<?=base_url()?>voucher/view/<?=hash_id($voucher_id)?>"><?= isset($clear_outstanding_cheque['voucher_number'])?$clear_outstanding_cheque['voucher_number']:0; ?></a></td>
                <td><?= isset($clear_outstanding_cheque['voucher_vendor'])?$clear_outstanding_cheque['voucher_vendor']:$clear_outstanding_cheque['voucher_description']; ?></td>
                <!-- <td><?= $clear_outstanding_cheque['office_bank_name']; ?></td> -->
                <td class='td_row_amount'><?= number_format($clear_outstanding_cheque['voucher_detail_total_cost'], 2); ?></td>
            </tr>
        <?php } 
           }
           ?>
       
    </tbody>
    <tfoot>
        <tr>
            <td colspan='6'><?= get_phrase('total'); ?></td>
            <td class='td_effects_total'><?=  count($clear_outstanding_cheques)<1?$no_data:  number_format(array_sum(array_column($clear_outstanding_cheques, 'voucher_detail_total_cost')), 2); ?></td>
        </tr>
    </tfoot>
</table>