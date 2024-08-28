 <style>
     @media print {
         .no-print {
             display: none;
         }
     }

     @media print {
         a[href]:after {
             content: none !important;
         }
     }

     @media print {

         /* on modal open bootstrap adds class "modal-open" to body, so you can handle that case and hide body */
         body.modal-open {
             visibility: hidden;
         }

         body.modal-open .modal .modal-header,
         body.modal-open .modal .modal-body {
             visibility: visible;
             /* make visible modal body and header */
         }
     }

     .modal-dialog {
         min-width: 80%;
         max-height: 100%;
         margin: 0;
         padding: 0;
     }

     .modal-content {
         height: 60%;
         max-height: 60%;
         height: auto;
         border-radius: 0;

     }

     .modal-body {
         overflow-y: auto;
         height: 540px;
     }
 </style>

 <script type="text/javascript">
     function showAjaxModal(url) {
         // SHOWING AJAX PRELOADER IMAGE
         jQuery('#modal_ajax .modal-body').html('<div style="text-align:center;margin-top:200px;"><img src="<?php echo base_url(); ?>assets/uploads/preloader4.gif" /></div>');

         // LOADING THE AJAX MODAL
         jQuery('#modal_ajax').modal('show', {
             backdrop: 'true'
         });

         // SHOW AJAX RESPONSE ON REQUEST SUCCESS
         $.ajax({
             url: url,
             success: function(response) {
                 jQuery('#modal_ajax .modal-body').html(response);
             }
         });
     }
 </script>

 <!-- (Ajax Modal)-->
 <div class="modal fade" id="modal_ajax" data-backdrop="static" data-keyboard="false">
     <div class="modal-dialog">
         <div class="modal-content">

             <div class="modal-header">
                 <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title"><?php echo $system_name; ?></h4>
             </div>

             <div class="modal-body" id="pop_modal_body">



             </div>

             <div class="modal-footer">
                 <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                 <button type="button" class="btn btn-default" onclick="js:window.print()">Print</button>
             </div>
         </div>
     </div>
 </div>




 <script type="text/javascript">
     function confirm_modal(delete_url) {
         jQuery('#modal-4').modal('show', {
             backdrop: 'static'
         });
         document.getElementById('delete_link').setAttribute('href', delete_url);
     }

     function confirm_action(url) {
         jQuery('#modal-5').modal('show', {
             backdrop: 'static'
         });
         document.getElementById('perform_link').setAttribute('href', url);
     }

     function confirm_dialog(url, reload = false) {
         BootstrapDialog.confirm('<?php echo get_phrase("Are_you_sure_you_want_to_perform_this_action?"); ?>', function(result) {
             if (!result) {
                 BootstrapDialog.show({
                     title: 'Information',
                     message: '<?php echo get_phrase('process_aborted'); ?>'
                 });
             } else {

                 $.ajax({
                     url: url,
                     beforeSend: function(request) {
                         BootstrapDialog.show({
                             title: 'Information',
                             message: '<?php echo get_phrase('wait_confirmation_message', 'Please wait until you receive confirmation'); ?>'
                         });
                     },
                     success: function(response) {

                         BootstrapDialog.show({
                             title: 'Alert',
                             message: response
                         });

                         if (reload === true) {
                             window.location.reload();
                         }

                     }
                 });
             }

         });
     }
 </script>

 <!-- (Normal Modal)-->
 <div class="modal fade" id="modal-4">
     <div class="modal-dialog">
         <div class="modal-content" style="margin-top:100px;">

             <div class="modal-header">
                 <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title" style="text-align:center;">Are you sure to delete this information ?</h4>
             </div>


             <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                 <a href="#" class="btn btn-danger" id="delete_link"><?php echo get_phrase('delete'); ?></a>
                 <button type="button" class="btn btn-info" data-dismiss="modal"><?php echo get_phrase('cancel'); ?></button>
             </div>
         </div>
     </div>
 </div>

 <!-- (Confirm Modal)-->
 <div class="modal fade" style="position: absolute;top:0px;bottom:0px;" id="modal-5">
     <div class="modal-dialog">
         <div class="modal-content" style="margin-top:100px;">

             <div class="modal-header">
                 <button id="" type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                 <h4 class="modal-title" style="text-align:center;">Are you sure you want to perform this action?</h4>
             </div>


             <div class="modal-footer" style="margin:0px; border-top:0px; text-align:center;">
                 <a href="#" class="btn btn-danger" id="perform_link"><?php echo get_phrase('Ok'); ?></a>
                 <button id="" type="button" class="btn btn-info" data-dismiss="modal"><?php echo get_phrase('cancel'); ?></button>
             </div>
         </div>
     </div>
 </div>

 <!--Bootstrap select intitialization-->

 <script>
     $('.datatable').DataTable({
         dom: 'lBfrtip',
         buttons: [
             'copyHtml5',
             'excelHtml5',
             'csvHtml5',
             'pdfHtml5',
         ],
         "pagingType": "full_numbers",
         'stateSave': true
     });

     $('.datatable_details').DataTable({
         dom: 'Bfrtip',
         fixedHeader: true,
         stateSave: true,
         buttons: [{
                 extend: 'excelHtml5',
                 text: '<?= get_phrase('export_in_excel'); ?>',
                 className: 'btn btn-default btn_export',
                 exportOptions: {
                     columns: 'th:not(:first-child)',
                 }
             },
             {
                 extend: 'pdfHtml5',
                 className: 'btn btn-default btn_export',
                 text: '<?= get_phrase('export_in_pdf'); ?>',
                 orientation: 'landscape',
                 exportOptions: {
                     columns: 'th:not(:first-child)'
                 }
             }
         ],
         "pagingType": "full_numbers"
     });


     function go_back() {
         window.history.back();
     }

     function go_forward() {
         window.history.forward();
     }

     $(document).ready(function() {
         //$('select').select2();
         if (location.hash) {
             $("a[href='" + location.hash + "']").tab("show");
         }
         $(document.body).on("click", "a[data-toggle]", function(event) {
             location.hash = this.getAttribute("href");
         });

         $(window).on("popstate", function() {
             var anchor = location.hash || $("a[data-toggle='tab']").first().attr("href");
             $("a[href='" + anchor + "']").tab("show");

         });



     });



     $(".list_delete_link").click(function(ev) {
         var cnf = confirm('Are you sure you want to delete this record');
         var delete_link = $(this);

         if (!cnf) {
             alert('Delete action aborted!');
         } else {

             $.ajax({
                 url: delete_link.attr('href'),
                 type: "GET",
                 beforeSend: function() {

                 },
                 success: function(response) {
                     alert(response);
                     delete_link.closest('tr').remove();
                 },
                 error: function() {

                 }
             });

         }
         ev.preventDefault();
     });

     function pre_record_post() {}

     function on_record_post() {}

     function post_record_post() {}

     function pre_row_insert() {}

     function on_row_insert() {}

     function post_row_insert() {}

     function pre_row_delete() {}

     function on_row_delete() {}

     function post_row_delete() {}

     //  function custom_confirm(phrase_key, phrase_translation = "", callback){
     //      const url = '<?= base_url(); ?><?= strtolower($this->controller); ?>/get_phrase_wrapper'
     //      const data = {
     //          phrase_key,
     //          phrase_translation
     //         }


     //     $.post(url, data, function (phrase){
     //         callback(phrase)
     //     })
     //  }

     // function confirmResponse(phrase){
     //     let cmfm = confirm(phrase)

     //     if(!cmfm){
     //         return false;
     //     }

     //     return true;
     // }
 </script>

 <style>
     .dt-buttons {
         margin-top: 10px;

     }

     #overlay {
         position: fixed;
         /* Sit on top of the page content */
         display: none;
         /* Hidden by default */
         width: 100%;
         /* Full width (cover the whole page) */
         height: 100%;
         /* Full height (cover the whole page) */
         top: 0;
         left: 0;
         right: 0;
         bottom: 0;
         background-color: rgba(0, 0, 0, 0.5);
         /* Black background with opacity */
         z-index: 2;
         /* Specify a stack order in case you're using a different order for other elements */
         cursor: pointer;
         /* Add a pointer on hover */
     }

     #overlay img {
         position: absolute;
         top: 50%;
         left: 50%;
     }
 </style>

 <div id="overlay"><img src='<?php echo base_url() . "uploads/preloader4.gif"; ?>' /></div>

 <script>
     $(document).ajaxSend(function() {
         $("#overlay").css("display", "block");
     });

     $(document).ajaxSuccess(function() {
         $("#overlay").css("display", "none");
     });

     $(document).ajaxError(function(xhr) {
         alert('Error has occurred');
     });


     $('a').on('click', function(ev) {

         var url_path = window.location.pathname;

         var split_path = url_path.split("/");

         var str = "";
         $.each(split_path, function(i, el) {
             if (i > 1) {
                 if (i == 2) {
                     str += capitalize(el) + "/";
                 } else {
                     str += el + "/";;
                 }
             }

         });

         //var newUrl = "<?= base_url(); ?>"+str;

         //$(this).prop('href',newUrl);

     });

     const capitalize = (s) => {
         if (typeof s !== 'string') return ''
         return s.charAt(0).toUpperCase() + s.slice(1)
     }


     // Use in the list page approval button e.g. in budget schedule
     $(document).on('click', '.item_action', function() {

         var item_id = $(this).data('item_id');
         var next_status = $(this).data('next_status');
         var current_status = $(this).data('current_status');
         var table = $(this).data('table');
         let confirmation_required = $(this).data('confirmation');
         // alert(item_id);
         // alert(next_status);
         // alert(current_status);
         var data = {
             'item_id': item_id,
             'next_status': next_status,
             'current_status': current_status
         };

         if (confirmation_required) {
             let cnf = confirm('Are you sure you want to perfom this action?')

             if (!cnf) {
                 alert('Action aborted')
                 return false;
             }
         }

         var url = "<?= base_url(); ?>" + table + "/update_item_status/" + table;
         // alert(url);
         var btn = $(this);
         var td = btn.parent();
         $.post(url, data, function(response) {
             //alert(response);
             //action_button = JSON.parse(response);
             //btn.html(action_button.button_label);
             //btn.parent().html(response);
             btn.parent().html(response);
             btn.addClass('disabled');
             btn.toggleClass('btn-info', 'btn-success');
             btn.siblings().remove();
             btn.closest('tr').find('.action_td .dropdown ul').html("<li><a href='#'><?= get_phrase('no_action'); ?></a></li>");

             try {
                 datatable.draw();


             } catch (e) {
                 if (e instanceof ReferenceError) {
                     console.log('DataTable API not set')
                 }
                 //location.reload();
             }

             //get uploaded documents
             var attachment_url = '<?= base_url() . $this->controller ?>/get_uploaded_S3_documents/';

             $.get(attachment_url, function(re) {

                 //Draw html table and populate it with uploaded docs from S3

                 let uploded_docs = JSON.parse(re);

                 // console.log(uploded_docs);

                 // draw_and_populate_table(uploded_docs);

                 let controler = '<?= $this->controller; ?>';

                 draw_and_populate_table(uploded_docs, controler);


             });
         });


     });

     function create_favorite_menu_items(items) {

         let elements = '<ul>';

         $.each(items, function(i, elem) {
             elements += "<li><a href='<?= base_url(); ?>" + i + "/list'>" + elem + "</a></li>"
         })

         elements += '</ul>';

         $("#fav_menu_items").html(elements);
     }

     $('.reset_date_selection').on('click', function() {
         const form_group = $(this).closest('.form-group')
         $(form_group).find('.datepicker').val('')
     })

     // $(document).ready(function() {
     //     const user_has_consented = '<?= $this->session->data_privacy_consented; ?>'
     //     // alert(user_has_consented)
     //     if (!user_has_consented) {
     //         $('.menu_tab:not(.dashboard)').hide();
     //     }
     // })

     // Selecting multiple option at once
    $('.select2').each(function(i, elem) {
        if($(elem).attr('multiple')){
            const select_holder = $(elem).closest('.form-group').find('.col-xs-9')
            
            select_holder.removeClass('col-xs-9')
            select_holder.addClass('col-xs-8')

            $(elem).closest('.form-group').append(`<div class = "col-xs-1"><div class = 'btn btn-default select2-select-all select' >Select All</div></div>`)
            
            $('.select2-select-all').on('click', function () {
                const selectAllBtn = $(this)
                const parent = selectAllBtn.parent().parent()
                let btnLabel = 'Select All'
                const select = parent.find('.select2')
                const options = parent.find('.select2 > option')

                // alert(options.length)
                if(options.length == 1){
                    alert('<?=get_phrase('no_items_to_select','There are not items available to be selected');?>');
                    return false;
                }else if(options.length > 50){
                    alert('<?=get_phrase('has_exceeded_max_options','The options to be selected exceeds 100 items, only 100 items will be selected')?>');
                }

                if(selectAllBtn.hasClass('select')){
                    btnLabel = 'Unselect All'
                    selectAllBtn.removeClass('select')
                    selectAllBtn.addClass('unselect')
               
                    $.each(options, function (i, elem) {
                        if(i < 101 && $(elem).val() > 0){
                            $(elem).prop('selected','selected')
                        }
                    })
                    select.trigger("change");

                }else{
                    btnLabel = 'Select All'
                    selectAllBtn.removeClass('unselect')
                    selectAllBtn.addClass('select')

                    options.removeAttr("selected");
                    select.trigger("change");
                }
                
                selectAllBtn.html(btnLabel)
            })
        }
    })
 </script>