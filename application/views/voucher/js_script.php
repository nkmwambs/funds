<script>

    let toggle_accounts_by_allocation = '<?= $this->config->item("toggle_accounts_by_allocation"); ?>';
    const action = '<?= $this->action; ?>'

    $("#funder").on('change', function () {
        if ('<?= $this->action; ?>' != 'edit') {
            const funder_id = $(this).val();
            getTransactingOffices(funder_id);
        }
    });

    $("#office").on('change', function () {
        resetVoucherNumberAndDate();
    });

    $('#transaction_date').on('change', function () {
        getActiveVoucherTypes();
    })

    $(document).on('change', '#voucher_type', function () {
        showCashAccounts()
    });

    $("#bank").on('change', function () {
        if (action != 'edit') {
            validateAndLoadBankInfo();
        }
    })

    $("#cash_account").on('change', function () {
        showCashBalance()
    });

    $(document).on('change', "#cheque_number", function () {
        validateEfTNumbers()
        checkChequeValidity()
    });


    $(document).on('change', '.allocation', function () {
        if(toggle_accounts_by_allocation){
            getAccountForAllocation($(this))
        }
    });

    $(document).on('change', '.account', function () {
        if(!toggle_accounts_by_allocation){
            getAllocationForAccounts($(this));
        }
    });

    $(".btn-reset").on('click', function () {
        if ('<?= $this->action; ?>' != 'edit') {
            resetVoucher();
        } else {
            alert('You cannot reset a voucher in edit mode')
        }
    });

    $(".btn-save").on('click', function () {
        saveVoucher($(this));
    });

    $(".btn-save-new").on('click', function () {
        if (action != 'edit') {
            saveVoucher($(this));
        }
    });

    function load_approved_requests() {
        var office = $("#office").val();
        var url = "<?= base_url(); ?>Voucher/get_approve_request_details/" + office;

        $("#request_screen").html("");

        $.ajax({
            url: url,
            beforeSend: function () {

            },
            success: function (response) {

                if ($("#main_row").find('#request_screen').length == 0) {
                    $("#main_row").append("<div id='request_screen' class='col-xs-6'>" + response + "</div>");
                } else {
                    $("#request_screen").html(response);
                }

                $("#request_screen").css('overflow-x', 'auto');

                update_request_details_count_on_badge();
            },
            error: function () {
                alert("Error occurred!");
            }
        });
    }

    function showCashAccounts(cashAccountsCallBack = null) {

        remove_voucher_detail_rows();

        var office = $('#office').val();
        let funder_id = $('#funder').val()
        var voucher_type_id = $("#voucher_type").val();
        var active_request_url = "<?= base_url(); ?>Voucher/get_count_of_unvouched_request/" + office;
        var url = "<?= base_url(); ?>Voucher/check_voucher_type_affects_bank/" + office + "/" + funder_id + "/" + voucher_type_id;

        if (!voucher_type_id) {
            hide_buttons();
            $(".account_fields").closest('span').addClass('hidden');
            return false;
        }

        $("#bank_balance, #unapproved_and_approved_vouchers_cash_balance").closest('span').addClass('hidden');
        $("#bank_balance, #unapproved_and_approved_vouchers_cash_balance").val(0);

        $("#bank_balance, #approved_vouchers_cash_balance").closest('span').addClass('hidden');
        $("#bank_balance, #approved_vouchers_cash_balance").val(0);

        $("#cash_account").val(0);

        checkIfDateIsSelected() ? $.get(url, function (response) {

            var response_objects = JSON.parse(response);
            var response_office_cash = response_objects['office_cash'];
            var response_office_bank = response_objects['office_banks'];
            var response_is_transfer_contra = response_objects['is_transfer_contra'];
            var response_is_bank_payment = response_objects['is_bank_payment'];
            let response_is_cash_payment = response_objects['is_cash_payment'];
            var response_is_voucher_type_requires_cheque_referencing = response_objects['voucher_type_requires_cheque_referencing'];


            $.get(active_request_url, office, function (response) {
                var badges = $(".requests_badge");
                badges.html(response);

            })

            if (response_is_cash_payment) {
                $("#bank").closest('span').addClass('hidden');
                $("#bank option:first").attr('selected', 'selected');

                add_options_to_cash_select(response_office_cash);
            }


            if (response_office_cash.length > 0) {

                add_options_to_cash_select(response_office_cash);

                if (response_office_bank.length > 0) {
                    add_options_to_bank_select(response_office_bank, true);
                }

            } else if (response_office_bank.length > 0) {
                add_options_to_bank_select(response_office_bank);

                if (response_office_cash.length > 0) {
                    add_options_to_cash_select(response_office_cash, true);
                }

            }

            if (response_is_transfer_contra) {
                $("#cash_recipient_account").closest('span').removeClass('hidden');
                $("#cash_recipient_account").removeAttr('disabled');
                $("#cash_recipient_account").html('');
            } else {
                $("#cash_recipient_account").closest('span').addClass('hidden');
            }

            if (response_is_bank_payment) {
                $("#cheque_number").closest('span').removeClass('hidden');

                change_voucher_number_field_to_eft_number(response_is_voucher_type_requires_cheque_referencing);

            } else {
                $("#cheque_number").closest('span').addClass('hidden');
                $("#cheque_number option:first").attr('selected', 'selected');
            }

            if (voucher_type_id) {
                //update_request_details_count_on_badge();
                $(".btn-insert").show();
                $(".btn-retrieve-request").show();
            } else {
                hide_buttons();
            }

            if (typeof cashAccountsCallBack === "function") {
                cashAccountsCallBack()
            }

        }) : alert("Choose a valid date");

    }


    $(".btn-retrieve-request").on('click', function () {

        if ($(".split_screen").hasClass('col-xs-12')) {
            $(".split_screen").removeClass('col-xs-12').addClass('col-xs-6');

            var split_screen = $(".split_screen");

            load_approved_requests();

            remove_request_derived_voucher_details();

        } else {
            $("#request_screen").remove();
            $(".split_screen").removeClass('col-xs-6').addClass('col-xs-12');

            var tbl_body_rows = $("#tbl_voucher_body tbody tr");

            if (tbl_body_rows.length > 0) {
                $('.btn-save').show();
                $('.btn-save-new').show();
            }


        }

        $('.btn-save, .btn-save-new').show();
    });

    function remove_request_derived_voucher_details() {
        var tbl_voucher_body_rows = $("#tbl_voucher_body tbody tr");

        $.each(tbl_voucher_body_rows, function (i, el) {

            let row_request_id_input = $(el).find("td:last").find('input');

            if (parseInt(row_request_id_input.val()) > 0) {
                row_request_id_input.closest("tr").remove();

                //Adjust the voucher_total value
                let row_voucher_total = $(el).find('.totalcost').val();
                let voucher_total = $("#voucher_total").val();

                $update_voucher_total = parseFloat(voucher_total) - parseFloat(row_voucher_total);

                $("#voucher_total").val($update_voucher_total);
            }
        });
    }

    function hide_buttons() {
        $('.btn-insert').hide();
        $('.btn-save').hide();
        $('.btn-save-new').hide();
        $('.btn-retrieve-request').hide();
    }



    function populate_cash_transfer_recipient(elem) {
        var selected_option_index = elem.find(':selected').index();

        var elem_clone = elem.clone();
        elem_clone.find('option').eq(selected_option_index).remove();
        options = elem_clone.children();

        $("#cash_recipient_account").html(options);
        $("#cash_recipient_account").prop("selectedIndex", 0);

    }

    function showCashBalance() {
        const elem = $("#cash_account")
        office_id = $('#office').val();
        funder_id = $('#funder').val();
        office_cash_id = elem.val();

        populate_cash_transfer_recipient(elem);

        if (elem.val() != '') {
            $('#unapproved_and_approved_vouchers_cash_balance').closest('span').removeClass('hidden');
            $('#approved_vouchers_cash_balance').closest('span').removeClass('hidden');
            compute_cash_balance(office_id, funder_id, office_cash_id);

        } else {
            $('#unapproved_and_approved_vouchers_cash_balance').closest('span').addClass('hidden');
            $('#approved_vouchers_cash_balance').closest('span').closest('span').addClass('hidden');
        }
    }

    function compute_bank_balance(office_id, funder_id, office_bank_id) {
        const url = '<?= base_url(); ?>voucher/compute_bank_balance';
        const transaction_date = $('#transaction_date').val();
        const data = {
            office_id,
            funder_id,
            office_bank_id,
            transaction_date
        };

        $.post(url, data, function (bank_balance_response) {
            const bank_balance_amount = JSON.parse(bank_balance_response);
            bank_amount = bank_balance_amount.approved_and_unapproved_vouchers_bank_bal;
            let put_commas_on_bank_amount = bank_amount.toString().split(".");
            put_commas_on_bank_amount[0] = put_commas_on_bank_amount[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            $('#bank_balance').val(put_commas_on_bank_amount.join('.'));
        })
    }

    function compute_cash_balance(office_id, funder_id, office_cash_id) {
        // console.log(office_id, funder_id, office_cash_id)
        const url = '<?= base_url(); ?>voucher/compute_cash_balance';
        const transaction_date = $('#transaction_date').val();
        const data = {
            office_id,
            funder_id,
            office_cash_id,
            transaction_date
        };

        $.post(url, data, function (response) {
            const cash_balance = JSON.parse(response);
            cash_amount = cash_balance.approved_and_unapproved_vouchers_cash_bal;
            let put_commas_on_cash_amount = cash_amount.toString().split(".");
            put_commas_on_cash_amount[0] = put_commas_on_cash_amount[0].replace(/\B(?=(\d{3})+(?!\d))/g, ",");

            $('#unapproved_and_approved_vouchers_cash_balance').val(put_commas_on_cash_amount.join('.'));
            $('#approved_vouchers_cash_balance').val(cash_balance.approved_vouchers_journal_bal);
        })
    }

    function check_cheque_validity(callBack = null) {
        var office = $("#office").val();
        var bank = $("#bank").val();
        var cheque_number = $("#cheque_number").val();
        var url = "<?= base_url(); ?>voucher/check_cheque_validity";
        var data = {
            'office_id': office,
            'bank_id': bank,
            'cheque_number': cheque_number
        };


        if ($("#bank").val() == "") {
            alert("Choose a valid bank account");
            $('#cheque_number').val('');
            return false;
        }

        $.post(url, data, function (response) {
            var options = '<option value=""><?= get_phrase('select_cheque_number'); ?></option>';
            if (response == 0) {
                alert('The bank account selected lacks a cheque book');
            } else {
                var obj = JSON.parse(response);
                $.each(obj, function (i, elem) {
                    options += "<option value='" + elem.cheque_id + "'>" + elem.cheque_number + "</option>";
                });
            }

            $("#cheque_number").html(options);

            if (typeof callBack === 'function') {
                callBack()
            }
        });
    }

    function checkIfEftREfIsValid(office, bank, cheque_number, callBack = null) {

        var url = "<?= base_url(); ?>Voucher/check_eft_validity";
        var data = {
            'office_id': office,
            'bank_id': bank,
            'cheque_number': cheque_number
        };

        if ($("#bank").val() == "") {
            alert("Choose a valid bank account");
            $('#cheque_number').val('');
            return false;
        }

        $.post(url, data, function (response) {
            if (!response) {
                alert("The reference number given (" + cheque_number + ") is not valid");
                $("#cheque_number").val("");
            }

            if(typeof callBack === 'function'){
                callBack()
            }
        });
    }

    function validateEfTNumbers() {
        let office = $("#office").val();
        let bank = $("#bank").val();
        let cheque_number = $("#cheque_number").val();

        if ($("#cheque_number").is('input')) {
            checkIfEftREfIsValid(office, bank, cheque_number);
        }
    }

    function computeNextVoucherNumber(office_id) {

        var url = "<?= base_url(); ?>voucher/compute_next_voucher_number/";

        $.ajax({
            url: url,
            data: {
                'office_id': office_id
            },
            type: "POST",
            beforeSend: function () {

            },
            success: function (response) {
                //console.log(response);
                $("#voucher_number").val(response);
            },
            error: function () {
                alert('Error occurred');
            }
        });
    }

    function computeCurrentTransactingDate(office_id) {

        var url = "<?= base_url(); ?>voucher/get_office_voucher_date/";

        $.ajax({
            url: url,
            type: "POST",
            data: {
                'office_id': office_id
            },
            beforeSend: function () {

            },
            success: function (response) {
                $('.date-field').show();

                // console.log(response);

                let obj = JSON.parse(response);

                //$("#transaction_date").val(obj.next_vouching_date);
                $("#transaction_date").val();

                $('#transaction_date').datepicker({
                    format: 'yyyy-mm-dd',
                    startDate: obj.next_vouching_date,
                    endDate: obj.last_vouching_month_date
                });


            },
            error: function () {
                alert('Error occurred');
            }
        });
    }

    function checkIfBudgetIsPresent(office_id) {

        const url = '<?= base_url(); ?>budget/check_office_period_budget_exists/' + office_id
        let check = false

        $.get(url, function (resp) {
            if (!resp) {
                alert('<?= get_phrase('missing_period_budget', 'You have a missing or unapproved budget review for the period for the office and funder') ?>');
            }
            check = resp;
        })
        return check;
    }



    function resetVoucherNumberAndDate() {
        let clear_office_selector = false;
        const elem = $("#office")
        const office_id = elem.val()
        const funder_id = $('#funder').val()
        const url = '<?= base_url(); ?>budget/check_office_period_budget_exists/' + office_id + '/' + funder_id

        if ('<?= $this->action; ?>' == 'edit') {
            return false;
        }

        if (office_id == "") {
            resetVoucher(clear_office_selector);
            return false;
        }

        $.get(url, function (resp) {
            if (!resp) {
                alert('<?= get_phrase('missing_period_budget', 'You have a missing or unapproved budget review for the period for the office and funder') ?>');
            } else {
                resetVoucher(clear_office_selector);

                if (elem.val() == "") {
                    return false;
                }

                if ($(".split_screen").hasClass('col-xs-6')) load_approved_requests();

                computeNextVoucherNumber(elem.val());

                computeCurrentTransactingDate(elem.val());
            }
        })
    }

    function getActiveVoucherTypes(selectVoucherTypeCallCallBack = null) {

        var office_id = $("#office").val();
        let transaction_date = $('#transaction_date').val();

        // alert(office_id)

        var url = "<?= base_url(); ?>Voucher/get_active_voucher_types/" + office_id + "/" + transaction_date;

        $.ajax({
            url: url,
            success: function (response) {

                $("#voucher_type").removeAttr('disabled');

                var voucher_type_option = "<option value=''><?= get_phrase('select_a_voucher_type'); ?></option>";

                var response_voucher_type = JSON.parse(response);

                if (response_voucher_type.length > 0) {
                    $.each(response_voucher_type, function (i, el) {
                        voucher_type_option += "<option value='" + response_voucher_type[i].voucher_type_id + "'>" + response_voucher_type[i].voucher_type_name + "</option>";
                    });
                }

                $("#voucher_type").html(voucher_type_option);

                if (typeof selectVoucherTypeCallCallBack === "function") {
                    selectVoucherTypeCallCallBack()
                }
            }
        });

    }

    function checkIfDateIsSelected() {

        var checkIfDateIsSelected = true;

        if ($("#transaction_date").val() == "") {
            //alert("Choose a valid transaction date");
            $("#voucher_type").val("");
            checkIfDateIsSelected = false
        };

        return checkIfDateIsSelected;
    }

    function populate_select(obj, parent_elem, default_html_text = 'Select an option') {

        parent_elem.children().remove();

        var option_html = "<option value=''>" + default_html_text + "</option>";

        $.each(obj, function (i, elem) {
            option_html += "<option value='" + elem.item_id + "'>" + elem.item_name + "</option>";
        });

        parent_elem.append(option_html);
    }


    function checkChequeValidity() {
        let office_id = $("#office").val().trim();
        let bank_office_id = $("#bank").val().trim();
        let cheque_number = $("#cheque_number").val().trim();
        let url = "<?= base_url(); ?>Voucher/get_cheques_for_office/" + office_id + "/" + bank_office_id + "/" + cheque_number;

        $.get(url, function (response) {
            if (response) {
                alert('The cheque number is already used. Choose another one');

                check_cheque_validity();

            }

        });
    }

    function add_options_to_bank_select(response_office_bank, show_cash_accounts = false) {
        if (!show_cash_accounts) $("#cash_account").closest('span').addClass('hidden');

        $("#bank").closest('span').removeClass('hidden');
        $("#bank").removeAttr('disabled');
        $("#cheque_number").val("");
        //$("#cash_account").val("");
        populate_select(response_office_bank, $("#bank"), 'Select a bank account');
    }

    function add_options_to_cash_select(response_office_cash, show_bank_accounts = false) {
        if (!show_bank_accounts) $("#bank").closest('span').addClass('hidden');

        $("#cash_account").closest('span').removeClass('hidden');
        $("#cash_account").removeAttr('disabled');
        $("#cheque_number").val("");
        populate_select(response_office_cash, $("#cash_account"), 'Select a cash account');
    }


    function getTransactingOffices(funder_id, selectedOfficeIdCallBackParam = null, selectOfficeCallBack = null,) {
        let office_elem = $("#office")

        resetVoucher(false);

        $.ajax({
            url: "<?= base_url(); ?>/voucher/user_transacting_offices",
            type: "POST",
            success: function (response) {
                // console.log(response);
                let options = "<option value=''><?= get_phrase('select_office'); ?></option>"
                $.each(response, function (index, elem) {
                    options += "<option value='" + elem.office_id + "'>" + elem.office_name + "</option>"
                })
                office_elem.html(options)

                if (typeof selectOfficeCallBack === "function") {
                    selectOfficeCallBack(selectedOfficeIdCallBackParam);
                }
            }
        });
    }

    function load_bank_dependant_data(elem, callBack = null) {
        const office = $('#office').val();
        const bank = $("#bank").val();
        const funder = $('#funder').val();

        // Toogle Disable when a bank account is selected or not
        if ($(elem).val() != '') {
            // alert('Hellooo');
            $("#cheque_number").removeAttr('disabled');
            $("#cheque_number").removeAttr('readonly');

            $("#bank_balance").closest('span').removeClass('hidden');

            compute_bank_balance(office, funder, bank);

        } else {
            $("#cheque_number").val("");
            $("#cheque_number").prop('disabled', 'disabled');
            $("#bank_balance").closest('span').addClass('hidden');
        }

        // Populate transfer account list
        if (!$("#cash_recipient_account").closest('span').hasClass('hidden')) {
            populate_cash_transfer_recipient($(this));
        }

        if ($("#cheque_number").is('input') && $("#cheque_number").val() != "") {
            checkIfEftREfIsValid(office, bank, $("#cheque_number").val(), callBack);
        } else {
            check_cheque_validity(callBack);
        }

    }


    function validateAndLoadBankInfo() {
        const OfficeBankSelect = $("#bank")
        let office_id = $("#office").val();
        let funder_id = $("#funder").val();
        let voucher_type_id = $("#voucher_type").val();; // Can be expense, income, cash_contra or bank_contra
        let url = "<?= base_url(); ?>Voucher/check_voucher_type_affects_bank/" + office_id + "/" + funder_id + "/" + voucher_type_id;
        let office_bank_id = $(OfficeBankSelect).val();
        let transaction_date = $('#transaction_date').val();
        let extra_data = {
            'office_bank_id': office_bank_id
        };

        if (!office_bank_id) {
            return false;
        }

        load_bank_dependant_data(OfficeBankSelect)

        $.post(url, extra_data, function (response) {

            var response_objects = JSON.parse(response);
            var response_is_voucher_type_requires_cheque_referencing = response_objects['voucher_type_requires_cheque_referencing'];

            //Get the active cheque of an office;

            if (response_is_voucher_type_requires_cheque_referencing) {
                var url = "<?= base_url(); ?>Voucher/check_active_cheque_book_for_office_bank_exist/" + office_id + "/" + office_bank_id + "/" + transaction_date;
                $.get(url, function (response) {
                    // alert(response);
                    var response_obj = JSON.parse(response);
                    //Check if response =false and then redirect to the cheque form
                    if (!response_obj['is_active_cheque_book_existing']) {

                        alert('No active cheque book & you will be directed to add cheque book form');

                        var redirect_to_add_cheque_book_url = "<?= base_url(); ?>cheque_book/single_form_add";

                        window.location.replace(redirect_to_add_cheque_book_url);
                        //alert('Yes');
                    } else if (!response_obj['are_all_cheque_books_fully_approved']) {

                        alert('Your active cheque book is either unsubmitted, declined or reinstated and not approved. You will be redirect to the cheque book');

                        var redirect_to_add_cheque_book_url = "<?= base_url(); ?>cheque_book/view/" + response_obj['current_cheque_book_id']; //QnG6NpbmWr

                        window.location.replace(redirect_to_add_cheque_book_url);
                    }
                });
            }

        });
    }

    function change_voucher_number_field_to_eft_number(response_is_voucher_type_requires_cheque_referencing) {

        var cheque_number_div = $("#cheque_number").parent();

        if (response_is_voucher_type_requires_cheque_referencing == 0) {

            cheque_number_div.html('');
            const secondary_input = $('#secondary_input').clone();
            cheque_number_div.append(secondary_input);

            $('#secondary_input').prop('id', 'cheque_number');
            $('#cheque_number').prop('name', 'voucher_cheque_number');
            $('#cheque_number').prop('readonly', 'readonly');
            $('#cheque_number').addClass('account_fields');
            $('#cheque_number').addClass('eft');
            $('#cheque_number').removeClass('hidden');
            $('#cheque_number').removeClass('required');

            $("#cheque_number").parent().prev().html('<?= get_phrase("EFT_serial"); ?>');

        } else {


            cheque_number_div.html('');
            const secondary_select = $('#secondary_select').clone();
            cheque_number_div.append(secondary_select);

            //cheque_number_div.append($('#secondary_select'));
            $('#secondary_select').prop('id', 'cheque_number');
            $('#cheque_number').prop('name', 'voucher_cheque_number');
            $('#cheque_number').prop('readonly', 'readonly');
            $('#cheque_number').addClass('account_fields');
            $('#cheque_number').removeClass('hidden');
            $('#cheque_number').removeClass('required');

            $("#cheque_number").parent().prev().html('<?= get_phrase("cheque_number"); ?>');
        }
    }

    function create_office_cash_dropdown(response_office_cash) {
        var account_select_option = "<option value=''><?= get_phrase('select_cash_account'); ?></option>";

        if (response_office_cash.length > 0) {
            $.each(response_office_cash, function (i, el) {
                account_select_option += "<option value='" + response_office_cash[i].office_cash_id + "'>" + response_office_cash[i].office_cash_name + "</option>";
            });
        }

        $("#cash_account").html(account_select_option);
    }

    // Returns if a value is an object
    function isObject(value) {
        return value && typeof value === 'object' && value.constructor === Object ? true : false;
    }

    function create_accounts_select_options(response_accounts) {
        var account_select_option = "<option value=''><?= get_phrase('select_an_aaccount'); ?></option>";

        if (isObject(response_accounts) && response_accounts.length > 0) {
            $.each(response_accounts, function (i, el) {
                account_select_option += "<option value='" + response_accounts[i].account_id + "'>" + response_accounts[i].account_name + "</option>";
            });
        }

        $(".account").html(account_select_option);

    }

    function create_allocation_select_options(response_allocation, $allocation_id = 0, $account_id = 0) {
        var tbl_body = $("#tbl_voucher_body tbody");
        var allocation_select_option = "<option value=''><?= get_phrase('select_an_allocation_code'); ?></option>";

        if (response_allocation.length > 0) {

            $(".allocation").removeAttr('disabled');

            $.each(response_allocation, function (i, el) {
                if($allocation_id == response_allocation[i].project_allocation_id){
                    allocation_select_option += "<option value='" + response_allocation[i].project_allocation_id + "' selected >" + response_allocation[i].project_allocation_name + "</option>";
                    
                }else{
                    allocation_select_option += "<option value='" + response_allocation[i].project_allocation_id + "' >" + response_allocation[i].project_allocation_name + "</option>";
                }
            });
        }

        tbl_body.find(':last-child').find(".allocation").html(allocation_select_option);
    }

    function remove_voucher_detail_rows(min_rows = 0) {
        var tbl_body_rows = $("#tbl_voucher_body tbody tr");

        // Remove extra rows
        var count_body_rows = tbl_body_rows.length;

        //$(".btn-save, .btn-save-new").remove();

        if (count_body_rows > min_rows) {
            $(".btn-save, .btn-save-new").hide();
            $.each(tbl_body_rows, function (i, el) {
                if ($("#tbl_voucher_body tbody tr").length > min_rows) {
                    $(el).remove();
                }
            });
        }
    }

    function reset_account_fields() {
        $('.account_fields').each(function (i, elem) {
            $(elem).val('');
            $(elem).prop('disabled', 'disabled');
            $(elem).closest('span').addClass('hidden');
        });
    }

    function reset_particulars_fields() {
        $("#voucher_vendor").val("");
        $("#voucher_vendor_address").val("");
        $("#voucher_description").val("");
        $("#voucher_total").val("");
        $("#voucher_number").val("")
        $("#voucher_type").val("");
        $("#voucher_type").prop('disabled', 'disabled');
        $("#unapproved_and_approved_vouchers_cash_balance").closest('span').addClass('hidden')
        $("#bank_balance").closest('span').addClass('hidden')
        $("#transaction_date").val("")
        $('.date-field').hide();
    }

    function reset_voucher_identity_fields(clear_office_selector) {
        if (clear_office_selector) {
            $("#transaction_date").val("");
            $("#voucher_number").val('');
            $("#office").val('');
            $("#voucher_type").val("");
            $("#voucher_type").prop('disabled', 'disabled');
        }

    }

    function resetVoucher(clear_office_selector = true) {
        remove_voucher_detail_rows();
        reset_account_fields();
        reset_particulars_fields();
        reset_voucher_identity_fields(clear_office_selector);
        hide_buttons();

    }

    function copyRow() {

        var tbl_body = $("#tbl_voucher_body tbody");

        var original_row = tbl_body.find('tr').clone()[0];

        tbl_body.append(original_row);

        $.each(tbl_body.find("tr:last").find('input'), function (i, el) {
            let resatable_fields = ['quantity', 'description', 'unitcost'];
            var elem = $(el);

            resatable_fields.forEach(function (fieldClass, index) {
                if (elem.hasClass(fieldClass)) {
                    elem.removeAttr('readonly');
                }

                if (elem.hasClass('number-fields')) {
                    if (elem.hasClass('quantity')) {
                        elem.val(1);
                    } else {
                        elem.val(0);
                    }

                } else {
                    elem.val("");
                }
            });
        });
    }

    function insertRow(response_is_contra = false, data = {}) {
        // console.log(data.length)
        let tbl_body = $("#tbl_voucher_body tbody");
        let tbl_head = $("#tbl_voucher_body thead");
        let quantity = 1 
        let description = "";
        let unit_cost = 0;
        let total_cost = 0;
        let account_id = 0;
        let allocation_id = ""
        let request_id = 0

        if(Object.keys(data).length > 0 && data.constructor === Object){
            quantity = data.quantity 
            description = data.description
            unit_cost = data.unit_cost
            total_cost = data.total_cost
            account_id = data.account_id
            allocation_id = data.allocation_id
        }

        let cell = actionCell();
        cell += quantityCell(quantity);
        cell += descriptionCell(data.description);
        cell += unitCostCell(unit_cost);
        cell += totalCostCell(total_cost);

        if (toggle_accounts_by_allocation) {
            cell += allocationCodeCell(allocation_id);
            cell += accountCell(account_id);
        } else {
            cell += accountCell(account_id);
            cell += allocationCodeCell(allocation_code);
        }

        let office_has_request = '<?= $office_has_request; ?>';

        if (office_has_request) {
            cell += requestIdCell(request_id);
        }


        tbl_body.append("<tr>" + cell + "</tr>");
    }

    function voucher_has_request_details_inserted() {
        var request_number = $('.request_number');
        var voucher_has_request_details_inserted = false;


        $.each(request_number, function (i, elem) {
            if ($(elem).val() > 0) {
                voucher_has_request_details_inserted = true;
                return false;
            }
        });

        return voucher_has_request_details_inserted;

    }

    $(".btn-insert").on('click', function () {
        insertVoucherDetailRows()
    });

    function insertVoucherDetailRows() {

        $('.account_fields').each(function (i, elem) {
            //$(elem).attr("style", "pointer-events: none;");
            if ($(elem).val() != '' && action != 'edit') {
                if ($(elem).is('select')) {
                    $(elem).find('option:not(:selected)').prop('disabled', true);
                } else {
                    $(elem).prop('readonly', 'readonly');
                }
            }
        });

        var tbl_body_rows = $("#tbl_voucher_body tbody tr");

        if (tbl_body_rows.length == 0 || voucher_has_request_details_inserted() || action == 'edit') {

            var count_account_fields = 0;

            $(".account_fields").each(function (i, elem) {
                if (!$(elem).closest('span').hasClass('hidden') && $(elem).val() == '') {
                    count_account_fields++;
                    $(elem).css('border', '1px red solid');
                }
            });

            if (count_account_fields) {
                alert('Complete filling the required cash or bank account fields');
                return false;
            }

            updateAccountAndAllocationField();
        } else {
            copyRow();
        }

        $(".btn-save, .btn-save-new").show();

    }


    function removeRow(rowCellButton) {
        var row = $(rowCellButton).closest('tr');
        var tbl_body_rows = $("#tbl_voucher_body tbody tr");


        // Remove extra rows
        var count_body_rows = tbl_body_rows.length;

        if (count_body_rows > 1) {
            row.remove();
            updateTotalCost();
        } else {
            alert('You can\'t remove all rows');
        }


    }


    function computeTotalCost(numberField) {

        var activeCell = $(numberField);
        var row = $(numberField).closest('tr');

        var quantity = 0;
        var unitcost = 0;
        var totalcost = 0;

        if (activeCell.hasClass('quantity')) {
            quantity = activeCell.val();

            if (quantity == "" || quantity == null) {
                row.find('.quantity').val(0);
            }

            unitcost = row.find('.unitcost').val();
        } else {
            unitcost = activeCell.val();

            if (unitcost == "" || unitcost == null) {
                row.find('.unitcost').val(0);
            }

            quantity = row.find('.quantity').val();
        }
        //Remove commas added by Onduso
        unitcost = unitcost.replace(/,/g, "");
        quantity = quantity.replace(/,/g, "");

        totalcost = quantity * unitcost;

        //Add commas on totals Added by Onduso
        totalcost = totalcost.toLocaleString();

        row.find('.totalcost').val(totalcost);

        //Add commas
        let sum = sumVoucherDetailTotalCost().toLocaleString();

        $("#voucher_total").val(sum);

    }

    function updateTotalCost() {
        $("#voucher_total").val(sumVoucherDetailTotalCost());
    }

    function sumVoucherDetailTotalCost() {

        var sum = 0;

        $.each($('.totalcost'), function (i, el) {

            //Remove commas from computed totalcost : Add by Onduso
            let total = $(el).val();
            total = total.replace(/,/g, "");

            sum += parseFloat(total);
        });

        return sum;
    }

    function replaceValue(numberField) {
        $(numberField).val("");
    }

    function clearRow(el) {
        $(el).closest('tr').find(".body-input").val(null);
        $(el).closest('tr').find(".number-fields").val(0);
    }

    function getAllocationForAccounts(elem) {
        var office_id = $("#office").val(elem);
        var account_id = elem.val();
        var voucher_type_id = $("#voucher_type").val();
        var transaction_date = $("#transaction_date").val();

        var url = "<?= base_url(); ?>voucher/get_project_details_account/";

        var office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;

        //var toggle_accounts_by_allocation = '<?= $this->config->item("toggle_accounts_by_allocation"); ?>';

        if (!toggle_accounts_by_allocation) {

            $.ajax({
                url: url,
                data: {
                    'office_id': office_id,
                    'account_id': account_id,
                    'voucher_type_id': voucher_type_id,
                    'transaction_date': transaction_date,
                    'office_bank_id': office_bank_id
                },
                type: "POST",
                success: function (response) {

                    var response_allocation = JSON.parse(response);

                    var allocation_select_option = "<option value=''>Select an allocation code</option>";

                    if (response_allocation.length > 0) {
                        $(".allocation").removeAttr('disabled');
                        $.each(response_allocation, function (i, el) {
                            allocation_select_option += "<option value='" + response_allocation[i].project_allocation_id + "'>" + response_allocation[i].project_allocation_name + "</option>";
                        });
                    } else {
                        $(".allocation").prop('disabled', 'disabled');
                    }

                    $(".allocation").html(allocation_select_option);
                }
            });
        }
    }

    function getAccountForAllocation(elem, account_id = 0) {
        // console.log(account_id)
        var office_id = $("#office").val();
        var allocation_id = elem.val();
        var voucher_type_id = $("#voucher_type").val();
        var transaction_date = $("#transaction_date").val();
        var office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;
        var row = elem.closest('tr');

        var url = "<?= base_url(); ?>Voucher/get_accounts_for_project_allocation/";
        var data = {
            'office_id': office_id,
            'allocation_id': allocation_id,
            'voucher_type_id': voucher_type_id,
            'transaction_date': transaction_date,
            'office_bank_id': office_bank_id
        };

        if (toggle_accounts_by_allocation) {

            $.post(url, data, function (response) {

                var response_accounts = JSON.parse(response);
                //console.log(response_accounts);
                var accounts_select_option = "<option value=''>Select account</option>";

                array_size = Object.keys(response_accounts).length;

                if (array_size > 0) {
                    $(".account").removeAttr('disabled');
                    $.each(response_accounts, function (i, el) {
                        if(account_id > 0 && account_id == i){
                            accounts_select_option += "<option value='" + i + "' selected >" + response_accounts[i] + "</option>";
                        }else{
                            accounts_select_option += "<option value='" + i + "'>" + response_accounts[i] + "</option>";
                        }
                    });
                } else {
                    $(".account").prop('disabled', 'disabled');
                }

                row.find(".account").html(accounts_select_option);
            });
        }
    }

    function actionCell() {
        return "<td><div class='btn btn-danger action' onclick='removeRow(this);'>Remove Row</div> &nbsp; <span onclick='clearRow(this);' class='fa fa-trash'></span> </td>";
    }

    function quantityCell(value = 1) {
        return "<td><input name='voucher_detail_quantity[]'  type='text' class='form-control required body-input number-fields quantity' onclick='replaceValue(this);' onchange='computeTotalCost(this);' value='" + value + "' /></td>";
    }

    function descriptionCell(value = '') {
        // console.log(value);
        return "<td><input  name='voucher_detail_description[]' type='text' class='form-control required body-input description' value='" + value + "' autocomplete='off' /></td>";
    }

    function unitCostCell(value = '') {
        return "<td><input autocomplete='off' name='voucher_detail_unit_cost[]' type='text' class='form-control required body-input number-fields unitcost' onclick='replaceValue(this);'  onchange='computeTotalCost(this);'  value='" + value + "' /></td>";
    }

    function totalCostCell(value = 0) {
        return "<td><input name='voucher_detail_total_cost[]' type='text' class='form-control required body-input number-fields totalcost' value='" + value + "' readonly='readonly'/></td>";
    }

    function accountCell(value = 0) {
        if (toggle_accounts_by_allocation) {
            return "<td><select disabled='disabled' name='voucher_detail_account[]' class='form-control required body-input account' ></select></td>";
        } else {
            return "<td><select name='voucher_detail_account[]' class='form-control required body-input account' ></select></td>";
        }

    }

    function allocationCodeCell(value = 0) {
        if (toggle_accounts_by_allocation) {
            return "<td><select name='fk_project_allocation_id[]' class='form-control required body-input allocation'></select></td>";
        } else {
            return "<td><select disabled='disabled' name='fk_project_allocation_id[]' class='form-control required body-input allocation'></select></td>";
        }

    }

    function requestIdCell(value = 0) {
        return "<td><input name='fk_request_detail_id[]' type='number' class='form-control body-input number-fields request_number' value='" + value + "' readonly='readonly'/></td>";
    }

    function disable_elements_in_hidden_span() {
        var elem_in_hidden_span = $('span.hidden .account_fields');

        $.each(elem_in_hidden_span, function (i, elem) {
            if (!$(elem).attr('disabled')) {
                $(elem).prop('disabled', 'disabled');
            }
        });
    }

    function saveVoucher(clicked_btn) {
        //Check if the cheque_number_is_selected
        var cheque_number = $('#cheque_number').val();
        if (cheque_number != '' && action != 'edit') {
            var office_id = $('#office').val();
            let office_bank_id = $("#bank").val();
            let has_eft_class = $('#cheque_number').hasClass('eft') ? 1 : 0;
            var url = "<?= base_url(); ?>voucher/get_duplicate_cheques_for_an_office/" + office_id + '/' + cheque_number + '/' + office_bank_id + '/' + has_eft_class;

            $.get(url, function (response) {
                if (response == 0) {
                    post_voucher_transaction(clicked_btn)
                } else {
                    alert('<?= get_phrase('chq_duplicate', 'Selected Cheque number already used'); ?>');
                    //Repulated/referesh the cheque numbers if already used to avoid duplicate
                    check_cheque_validity();
                    $('#cheque_number').css('border', '1px solid red');
                    return false;
                }
            });
        } else {
            post_voucher_transaction(clicked_btn);
        }
    }

    function post_voucher_transaction(clicked_btn) {

        let voucher_id = 0;
        if (action == 'edit') {
            voucher_id = '<?= hash_id($this->id, 'decode'); ?>'
        }
        var url = action == 'edit' ? "<?= base_url(); ?>voucher/edit_voucher/" + voucher_id : "<?= base_url(); ?>voucher/insert_new_voucher";
        var data = $("#frm_voucher").serializeArray();

        var tbl_body_rows = $("#tbl_voucher_body tbody tr");
        let limit_check_url = '<?= base_url(); ?>voucher/cash_limit_exceed_check'
        let office_id = $('#office').val();
        let office_bank_id = $("#bank").val();
        let office_cash_id = $('#cash_account').val();
        let voucher_type_id = $('#voucher_type').val();
        let amount = $('#voucher_total').val();
        let transaction_date = $('#transaction_date').val();
        let unapproved_and_approved_vouchers = $('#unapproved_and_approved_vouchers_cash_balance').val();
        let bank_balance = $('#bank_balance').val();
        let cheque_number = $('#cheque_number').val();

        let limit_data = {
            office_id,
            office_bank_id,
            office_cash_id,
            voucher_type_id,
            amount,
            transaction_date,
            unapproved_and_approved_vouchers,
            bank_balance,
            cheque_number
        };

        // console.log(limit_data)

        $.post(limit_check_url, limit_data, function (limit_exceeded) {
            if (limit_exceeded == 1) {
                alert('<?= get_phrase('cash_limit_exceeded', "You have exceeded the bank or cash balance"); ?>');
                return false;
            }

            if (tbl_body_rows.length == 0) {
                alert("Please add voucher details before saving the voucher");
                return false;
            }

            // Make all select or inputs in hidden span be disabled
            disable_elements_in_hidden_span();


            if (!check_required_fields()) { //quantity
                alert('Empty required fields exists or "unit cost and cost fields have amount with more than 2 decimal places"');
            } else {
                $.ajax({
                    url: url,
                    type: "POST",
                    data: data,
                    success: function (response) {
                        //alert(response);

                        if (clicked_btn.hasClass('btn-save')) {

                            if (response == 1) {
                                alert("Voucher posted successfully");
                                const referrer = document.referrer;

                                //Delete Duplicate MFR in a month
                                transaction_date = $("#transaction_date").val();

                                office_id = $("#office").val();

                                var ur = '<?= base_url() ?>voucher/delete_duplicate_mfr/' + transaction_date + '/' + office_id;

                                $.get(ur, function (re_mf) {


                                    // console.log(re_mf)
                                });

                                //Delete Duplicate CJ in a month

                                var ur = '<?= base_url() ?>voucher/delete_duplicate_cj/' + transaction_date + '/' + office_id;

                                $.get(ur, function (re_cj) {


                                    // console.log(re_cj)
                                });


                                if (referrer.indexOf("book") > 0) {
                                    window.location.href = '<?= base_url(); ?>voucher/list'
                                } else {
                                    location.href = document.referrer
                                }
                            } else {
                                alert("Voucher posting failed");
                            }

                        } else {
                            resetVoucher();
                        }
                    }

                });
            }

        })

    }

    function check_required_fields() {
        return_flag = true;

        $(".required").each(function (i, el) {
            if (
                $(el).val() == "" &&
                !$(el).attr('disabled') &&
                !$(el).attr('readonly') &&
                $(el).hasClass('required')
            ) {
                //$(el).addClass('validate_error');
                return_flag = false;
                $(el).css('border', '1px red solid');
            }

            if ($(el).hasClass('quantity') && ($(el).val() == "" || $(el).val() <= 0 || isNaN($(el).val().replace(/\,/g, '')))) {
                return_flag = false;
                $(el).css('border', '1px red solid');
            }

            if ($(el).hasClass('unitcost') && ($(el).val() == "" || $(el).val() == 0 || $(el).val().replace(/\,/g, '') <= 0 || isNaN($(el).val().replace(/\,/g, ''))) || String($(el).val()).split(".")[1]?.length > 2) {

                return_flag = false;
                $(el).css('border', '1px red solid');
            }
        });

        return return_flag;
    }


    $(document).on('change', '.required', function () {
        if ($(this).attr('style')) {
            $(this).removeAttr('style');
        }
    });

    $(document).ready(function () {
        //get_approved_unvouched_request_details
        $('.date-field').hide();
        $('.btn-insert').hide();
        $('.btn-save').hide();
        $('.btn-save-new').hide();
        $('.btn-retrieve-request').hide();

        if ('<?= $this->action == 'edit'; ?>') {
            // $("#office").find('option:first').remove();
            setUpEditVoucher()
            $(".btn-save-new, .btn-reset").addClass('hidden');
        }
    });

    function setUpEditVoucher() {
        const {
            voucher_id,
            funder_id,
            office_id,
            voucher_number,
            voucher_date,
            voucher_type_id,
            office_bank_id,
            office_cash_id,
            voucher_vendor,
            voucher_vendor_address,
            voucher_description,
            voucher_cheque_number,
            voucher_type_is_cheque_referenced,
            voucher_type_account_code, 
            voucher_type_effect_code, 
        } = <?= json_encode($voucher_header_info); ?>

        const url = "<?= base_url(); ?>Voucher/get_voucher_accounts_and_allocation";


        if (funder_id > 0) {
            $('#funder').val(funder_id).trigger('change');
            removeAllOptionsExceptSelected($('#funder'))

            getTransactingOffices(funder_id, office_id, function (office_id) {
                $('#office').val(office_id).trigger('change');
                $('#voucher_number').val(voucher_number);
                $('.date-field').show();
                $('#transaction_date').val(voucher_date);
                removeAllOptionsExceptSelected($('#office'))

                getActiveVoucherTypes(function () {
                    $('#voucher_type').val(voucher_type_id).trigger('change');
                    removeAllOptionsExceptSelected($('#voucher_type'))
                    showCashAccounts(function () {
                        
                        if (office_cash_id) {
                            $('#cash_account').val(office_cash_id).trigger('change');
                        }

                        if (office_bank_id) {
                            $('#bank').val(office_bank_id).trigger('change');
                            removeAllOptionsExceptSelected($('#bank'))

                            load_bank_dependant_data($('#bank'), function () {
                                if($('#cheque_number').is('select')){
                                    $('#cheque_number option:first').remove();
                                    $('#cheque_number').prepend(`<option value="${voucher_cheque_number}">${voucher_cheque_number}</option>`)
                                    $('#cheque_number option:first').prop('selected', true);
                                }else{
                                    $('#cheque_number').val(voucher_cheque_number);
                                }
                                
                                createEditDetails(voucher_id)
                            })
                        } else {
                            createEditDetails(voucher_id)
                        }
                        
                        
                    })
                });

                $("#voucher_vendor").val(voucher_vendor)
                $("#voucher_vendor_address").val(voucher_vendor_address)
                $("#voucher_description").val(voucher_description)
            })
        }
    }


    function updateAccountAndAllocationField(callBack = null) {
        let office_id = $("#office").val();
        let funder_id = $("#funder").val();
        let transaction_date = $("#transaction_date").val();
        let voucher_type_id = $("#voucher_type").val(); // Can be expense, income, cash_contra or bank_contra
        let office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;
        let data = {
            office_id,
            funder_id,
            office_bank_id,
            voucher_type_id,
            transaction_date
        };
        let url = "<?= base_url(); ?>Voucher/get_voucher_accounts_and_allocation";
        
        $.ajax({
            url,
            method: "POST",
            data,
            beforeSend: function () {

            },
            success: function (response) {

                var account_select_option = "<option value=''>Select an account</option>";

                var allocation_select_option = "<option value=''>Select an allocation code</option>";

                var response_objects = response; //JSON.parse(response);

                //var response_accounts = response_objects['accounts'];
                var response_allocation = response_objects.project_allocation;
                //var toggle_accounts_by_allocation = '<?= $this->config->item("toggle_accounts_by_allocation"); ?>';
                var response_is_contra = response_objects.is_contra;

                //alert(response);
                insertRow(response_is_contra);

                //if(toggle_accounts_by_allocation){
                create_allocation_select_options(response_allocation, callBack);
                // } else {
                //     create_accounts_select_options(response_accounts);
                // }  

            },
            error: function () {
                alert('Error occurred');
            }
        });
    }

    function createEditDetails(voucher_id) {
        let office_id = $("#office").val();
        let funder_id = $("#funder").val();
        let transaction_date = $("#transaction_date").val();
        let voucher_type_id = $("#voucher_type").val(); // Can be expense, income, cash_contra or bank_contra
        let office_bank_id = !$("#bank").attr('disabled') ? $("#bank").val() : 0;
        let data = {
            office_id,
            funder_id,
            office_bank_id,
            voucher_type_id,
            transaction_date
        };

        const url = "<?= base_url(); ?>voucher/get_voucher_detail_to_edit/" + voucher_id;
        const tbl_tbody_rows = $("#tbl_tbody tr")
        $.post(url, data, function (accounts_details) {
            const details = accounts_details.voucher_details;
            // console.log(details)
            const tbody_rows = $(document).on("#tbl_tbody tr")
            let fieldsClassAndValues = {}
            details.forEach((element, rowIndex) => {
                const {
                    voucher_detail_quantity, 
                    voucher_detail_description, 
                    voucher_detail_unit_cost, 
                    voucher_detail_total_cost, 
                    project_allocation_id,
                    account_id
                } = element

                let account_select_option = "<option value=''>Select an account</option>";
                let allocation_select_option = "<option value=''>Select an allocation code</option>";
                let response_objects = accounts_details.accounts_and_allocation; 
                let response_allocation = response_objects.project_allocation;
                let response_is_contra = response_objects.is_contra;
                let data = {
                    quantity: voucher_detail_quantity,
                    description: voucher_detail_description,
                    unit_cost: voucher_detail_unit_cost,
                    total_cost: voucher_detail_total_cost,
                    allocation_id: project_allocation_id,
                    account_id: account_id
                }

                insertRow(response_is_contra, data);
                create_allocation_select_options(response_allocation, project_allocation_id);
                const allocation_elem = tbody_rows.find('.allocation');
                getAccountForAllocation(allocation_elem, account_id)
                
            })

            $(".btn-save, .btn-save-new").show();
        })
    }

    function removeAllOptionsExceptSelected(selectElement, isSelect2 = false) {
        let selectedValue = selectElement.val();

        // Remove all options except the selected one
        selectElement.find('option').each(function (index, elem) {
            if ($(elem).val() != selectedValue) {
                $(elem).remove();
            }
        });

        // Reinitialize Select2 to reflect the changes
        if (isSelect2) {
            selectElement.trigger('change').select2();
        }
    }

</script>

<input type="text" class="form-control hidden" name="secondary_input" id="secondary_input" value="" />
<select class='form-control hidden' name='secondary_select' id='secondary_select' disabled='disabled'>
    <option value=''><?= get_phrase('select_cheque_number'); ?></option>
</select>