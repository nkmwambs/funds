<style>
.control-label{
    text-align:left;
}

.center{
    text-align:center;
}
</style>

<?php 
   //print_r($this->voucher_model->get_request_to_voucher_conversion_approval_status(9));
?>

    <div class='col-xs-12 split_screen'>
        <div class="panel panel-default" data-collapsed="0">
       	    <div class="panel-heading">
           	    <div class="panel-title" >
           		    <i class="entypo-plus-circled"></i>
					    <?php echo get_phrase('expense_request_form');?>
           	    </div>
            </div>
	    
            <div class="panel-body"  style="max-width:50; overflow: auto;">	
                <?php echo form_open("" , array('id'=>'frm_request','class' => 'form-horizontal form-groups-bordered validate', 'enctype' => 'multipart/form-data'));?>
                    
                    <div class='form-group'>
                        <div class='col-xs-12 center'>
                            <div class='btn btn-default btn-reset'><?=get_phrase('reset');?></div>
                            <div class='btn btn-default btn-insert'><?=get_phrase('insert_request_detail_row');?></div>
                            <!-- <input type="submit" class='btn btn-default btn-save' value="<?=get_phrase('save');?>" /> -->
                            <div class='btn btn-default btn-save'><?=get_phrase('save');?></div>
                            <div class='btn btn-default btn-save-new'><?=get_phrase('save_and_new');?></div>
                        </div>
                    </div>

                    <div class='form-group'>
                        <label class='control-label col-xs-2'><?=get_phrase('office');?></label>
                        <div class='col-xs-3'>
                            <select class='form-control' id='office' name='fk_office_id'>
                                <option value=''><?=get_phrase('select_office');?></option>
                                <?php foreach($this->session->hierarchy_offices as $office){?>
                                        <option value="<?=$office['office_id'];?>"><?=$office['office_name'];?></option>
                                <?php }?>
                            </select>
                        </div>

                        <label class='control-label col-xs-2'><?=get_phrase('request_date');?></label>
                        <div class='col-xs-3'>
                            <input id="request_date" type='text' name='request_date' value="<?=date('Y-m-d');?>" data-end-date='<?=date('Y-m-d');?>' readonly data-format='yyyy-mm-dd' class='form-control datepicker' />
                        </div>

                    </div>

                    <div class='form-group'>
                        <label class='control-label col-xs-2'><?=get_phrase('request_type');?></label>
                        <div class='col-xs-3'>
                            <select class='form-control account_fields' name='fk_request_type_id' id='request_type_id'>
                                <option value=""><?=get_phrase('select_request_type');?></option>
                                
                            </select>
                        </div>


                        <label class='control-label col-xs-2'><?=get_phrase('department');?></label>
                        <div class='col-xs-3'>
                            <select class="form-control account_fields" name='fk_department_id' id='department_id'>
                                    <option value=""><?=get_phrase('select_department');?></option>
                            </select>
                        </div>

                    </div>

                    <div class='form-group'>
                        <div class='col-xs-12'>
                            <textarea placeholder="Enter descripition here" name='request_description' id="request_description" class='form-control'></textarea>
                        </div>
                    </div>

                    <div class='form-group'>
                        <div class='col-xs-12 center'>
                            <div class='btn btn-default btn-reset'><?=get_phrase('reset');?></div>
                            <div class='btn btn-default btn-insert'><?=get_phrase('insert_request_detail_row');?></div>
                            <!-- <input type="submit" class='btn btn-default btn-save' value="<?=get_phrase('save');?>" /> -->
                            <div class='btn btn-default btn-save'><?=get_phrase('save');?></div>
                            <div class='btn btn-default btn-save-new'><?=get_phrase('save_and_new');?></div>
                           
                        </div>
                    </div>

                    <div class='form-group'>
                        <div class='col-xs-12'>
                            <table class='table table-striped' id='tbl_request_body'>
                                <thead>
                                    <tr>
                                        <th><?=get_phrase('action');?></th>
                                        <th><?=get_phrase('quantity');?></th>
                                        <th><?=get_phrase('description');?></th>
                                        <th><?=get_phrase('unit_cost');?></th>
                                        <th><?=get_phrase('total_cost');?></th>
                                        <th><?=get_phrase('allocation_code');?></th>
                                        <th><?=get_phrase('account');?></th>
                                        <th><?=get_phrase('attachments');?></th>

                                    </tr>
                                </thead>
                                <tbody id="tbl_request_body">
                                </tbody>  
                                <tfoot>
                                    <tr>
                                        <td colspan='7'><?=get_phrase('total');?></td>
                                        <td><input type='text' id='request_total' class='form-control' readonly /></td>
                                    </tr>
                                </tfoot>      
                            </table>        
                        </div>
                    </div>  

                    <div class='form-group'>
                        <div class='col-xs-12 center'>
                            <div class='btn btn-default btn-reset'><?=get_phrase('reset');?></div>
                            <div class='btn btn-default btn-insert'><?=get_phrase('insert_request_detail_row');?></div>
                            <!-- <input type="submit" class='btn btn-default btn-save' value="<?=get_phrase('save');?>" /> -->
                            <div class='btn btn-default btn-save'><?=get_phrase('save');?></div>
                            <div class='btn btn-default btn-save-new'><?=get_phrase('save_and_new');?></div>
                           
                        </div>
                    </div>

                </form>

            </div>

            <!-- <div class="row">
                <div class="col-xs-12">
                 <?=Widget_base::load('upload');?>
                </div>
            </div> -->

        </div>    
    </div>

    

<script>

$(document).ready(function(){
    $('.btn-insert').hide();
    $('.btn-save').hide();
    $('.btn-save-new').hide();
});

function unhide_all_hidden_button(){
    $('.btn-insert').show();
    $('.btn-save').show();
    $('.btn-save-new').show()
}

$("#request_type_id").on('change',function(){
    let url = "<?=base_url();?>Request/get_request_department";
    let data  = {'request_type_id':$(this).val()};

    $.post(url,data,function(response){
        var department = JSON.parse(response); 

        var select_department_option = "<option value=''><?=get_phrase('select_department');?></option>";

             if(department.length > 0){
                 $.each(department,function(i,el){
                     select_department_option += "<option value='" + department[i].department_id + "'>" + department[i].department_name +"</option>";
                 });
             }

        $("#department_id").html(select_department_option);
    });
});

$("#office").on('change',function(ev){
    
    if(!$(this).val()){
        alert('Select a valid office');        
        return false;
    }

    let url = "<?=base_url();?>Request/get_request_type";
    $.post(url,{'office_id':$(this).val()},function(response){
        
        unhide_all_hidden_button();
        
        var request_type = JSON.parse(response);        

        var select_option = "<option value=''><?=get_phrase('select_request_type');?></option>";

            if(request_type.length > 0){
                $.each(request_type,function(i,el){
                    select_option += "<option value='" + request_type[i].request_type_id + "'>" + request_type[i].request_type_name +"</option>";
                });
            }



        $("#request_type_id").html(select_option);
    });
});

$(".btn-insert").on('click',function(){
    var tbl_body_rows = $("#tbl_request_body tbody tr");
    //alert(tbl_body_rows.length);
    if(tbl_body_rows.length == 0){
        
        updateAllocationField();
        disabled_account_fields();
    }else{
        copyRow();   
    } 
});

function disabled_account_fields(){
    $('.account_fields').each(function(i,elem){
        //$(elem).attr("style", "pointer-events: none;");
        if($(elem).val() != ''){
            if($(elem).is('select')){
                $(elem).find('option:not(:selected)').prop('disabled', true);
            }else{
                $(elem).prop('readonly','readonly');
            }
        }
    });
}

function removeRow(rowCellButton){
    var row = $(rowCellButton).closest('tr');
    var tbl_body_rows = $("#tbl_request_body tbody tr");


    // Remove extra rows
    var count_body_rows = tbl_body_rows.length;

    if(count_body_rows > 1){
        row.remove();
        updateTotalCost();
    }else{
        alert('You can\'t remove all rows');
    }


}


$(document).on('change','.allocation',function(){
    var office_id = $("#office").val();
    var allocation_id = $(this).val();
    var data = {'office_id':office_id,'allocation_id':allocation_id};
    var row = $(this).closest('tr');
    var url = "<?=base_url();?>Request/get_request_accounts";

    $.post(url,data,function(response){
        var account_select_option = "<option value=''>Select an account</option>";
        var response_accounts = JSON.parse(response);

         if(response_accounts.length > 0){
            $.each(response_accounts,function(i,el){
                account_select_option += "<option value='" + response_accounts[i].expense_account_id + "'>" + response_accounts[i].expense_account_name + "</option>";
            });
        }

         //$(".account").html(account_select_option);
         row.find(".account").html(account_select_option);

    });
});

function updateAllocationField(expense_account_id = "", project_allocation_id = ""){
    
    var office_id = $("#office").val();
    var request_date = $("#request_date").val();
    var request_type_id = $("#request_type_id").val();
    var data = {'office_id':office_id,'request_date':request_date,'request_type_id':request_type_id};

    var url = "<?=base_url();?>Request/get_request_allocation";

    $.post(url,data,function(response){
        var allocation_select_option = "<option value=''>Select an allocation code</option>";

        var response_allocation = JSON.parse(response);

        insertRow();

        if(response_allocation.length > 0){
            $.each(response_allocation,function(i,el){
                allocation_select_option += "<option value='" + response_allocation[i].project_allocation_id + "'>" + response_allocation[i].project_allocation_name + "</option>";
            });
        }

        $(".allocation").html(allocation_select_option);
    });

}

// function copyRow(){
    
//     var tbl_body = $("#tbl_request_body tbody");

//     var original_row = tbl_body.find('tr').clone()[0];

//     tbl_body.append(original_row);
// }

function copyRow(){
    
    var tbl_body = $("#tbl_request_body tbody");

    var original_row = tbl_body.find('tr').clone()[0];

    tbl_body.append(original_row);

    $.each(tbl_body.find("tr:last").find('input'),function(i,el){
        let resatable_fields = ['quantity','description','unitcost'];
        var elem = $(el);

        resatable_fields.forEach(function(fieldClass,index){
            if(elem.hasClass(fieldClass)){
                elem.removeAttr('readonly');
            }
            
            if(elem.hasClass('number-fields')){
                elem.val(0);
            }else{
                elem.val("");
            }
        });
    });
}

function insertRow(){
    var tbl_body = $("#tbl_request_body tbody");

    var cell = actionCell(); 
    cell += quantityCell(); 
    cell += descriptionCell();
    cell += unitCostCell(); 
    cell += totalCostCell();
    cell += allocatioCodeCell();  
    cell += accountCell(); 
    cell += fileUploadCell();

    tbl_body.append("<tr>"+cell+"</tr>");
}

function replaceValue(numberField){
    $(numberField).val("");
}

function computeTotalCost(numberField){
    
    var activeCell = $(numberField);
    var row = $(numberField).closest('tr');

    var quantity = 0;
    var unitcost = 0;
    var totalcost = 0;

    if(activeCell.hasClass('quantity')){
        quantity = activeCell.val();

        if(quantity == "" || quantity == null){
            row.find('.quantity').val(0);
        }

        unitcost = row.find('.unitcost').val();
    }else{
        unitcost = activeCell.val();

        if(unitcost == "" || unitcost == null){
            row.find('.unitcost').val(0);
        }

        quantity = row.find('.quantity').val();
    }

    totalcost = quantity * unitcost;

    row.find('.totalcost').val(totalcost);

    $("#request_total").val(sumRequestDetailTotalCost());
        
}

function updateTotalCost(){
    $("#request_total").val(sumRequestDetailTotalCost());
}

function sumRequestDetailTotalCost(){
   
   var sum = 0;

   $.each($('.totalcost'),function(i,el){
       sum += parseFloat($(el).val());
   });
   
   return sum;
}

function clearRow(el){
    $(el).closest('tr').find(".body-input").val(null);
    $(el).closest('tr').find(".number-fields").val(0);
}

$(".btn-reset").on('click',function(){
    resetRequest();
});

function resetRequest(){
    var tbl_body_rows = $("#tbl_request_body tbody tr");

    // Remove extra rows
    var count_body_rows = tbl_body_rows.length;

    if(count_body_rows > 1){
        tbl_body_rows.each(function(i,el){
            if(i != 0){
                el.remove();
            }
        });
    }

    // Empty the cells
    $(".body-input").val(null);
    $(".number-fields").val(0);
    $("#request_type_id").html('<option value="">Select a request type</option>');;
    $("#department_id").html('<option value="">Select a department</option>');
    $("#request_description").val(null);
    $("#request_total").val(0);
    $("#office").val('');
    
}

function actionCell(){
    return "<td><div class='btn btn-danger action' onclick='removeRow(this);'>Remove Row</div> &nbsp; <span onclick='clearRow(this);' class='fa fa-trash'></span> </td>";
}

function quantityCell(value = 0){
    return "<td><input name='request_detail_quantity[]' type='number' class='form-control body-input number-fields quantity' onclick='replaceValue(this);' onchange='computeTotalCost(this);' value='" + value + "' id=''/></td>";
}

function descriptionCell(value = ''){
    return "<td><input  name='request_detail_description[]' type='text' class='form-control body-input description' value='" + value + "' id=''/></td>"; 
}

function unitCostCell(value = 0){
    return "<td><input  name='request_detail_unit_cost[]' type='number' class='form-control body-input number-fields unitcost' onclick='replaceValue(this);' onchange='computeTotalCost(this);'  value='" + value + "' id=''/></td>";
}

function totalCostCell(value = 0){
    return "<td><input name='request_detail_total_cost[]' type='number' class='form-control body-input number-fields totalcost' value='" + value + "' id='' readonly='readonly'/></td>";
}

function accountCell(value = 0){
    return "<td><select name='fk_expense_account_id[]' class='form-control body-input account' id=''></select></td>";
}

function allocatioCodeCell(value = 0){
    return "<td><select name='fk_project_allocation_id[]' class='form-control body-input allocation' id=''></select></td>";
}

function fileUploadCell(value = 0){
    return "<td><input name='file_upload[]' class='form-control body-input file_upload' type='file' /></td>";
}

function saveRequest(){
    var url = "<?=base_url();?>Request/insert_new_request";
    var data = $("#frm_request").serializeArray();
    //var files = $('.file_upload');
    //var form = $("#frm_request");
    //var data = new FormData(form);

    $.ajax({
        url:url,
        type:"POST",
        data:data,
        beforeSend:function(){

        },
        success:function(response){
            alert(response);
        },
        error:function(){
            alert('Error occurred');
        }

    });
}


$(".btn-save").on('click',function(){
    saveRequest();
    location.href = document.referrer 
});

$(".btn-save-new").on('click',function(){
    saveRequest();
    resetRequest();
});
</script>