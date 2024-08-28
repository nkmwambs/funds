<script>
  $('.form-control').keydown(function() {
    $(this).removeAttr('style');
  });

  $(".save, .save_new").on('click', function(ev) {

    var elem = $(this);


    //Check if all required fields are filled
    var empty_fields_count = 0;
    var code_field_or_element='';
    $('.form-control').each(function(i, el) {

      //Code added by Onduso
      var code_elem=$(el).attr('id');

      //check if we have code
      let code_str_exist=code_elem.split('_');

      if(code_str_exist.includes('code')){
        code_field_or_element=code_elem;
      }

      //End of addition by Onduso
      if ($(el).hasClass('select2') && $(el).is('select')) {
        // alert($(el).val())
        // To be completed later. Check if select2 is empty
        if (!$(el).val() && $(el).attr('required')) {
          $(el).closest('div').css('border', '1px solid red');
          empty_fields_count++;
        } else {
          $(el).closest('div').removeAttr('style');
        }

      } else {
        if ($(el).val().trim() == '' && $(el).attr('required')) {
          $(el).css('border', '1px solid red');
          empty_fields_count++;
        }
      }
    });
    
    if (empty_fields_count > 0) {
      alert('1 or more required fields are empty');
      // console.log($(this).closest('form').serializeArray())
      return false;
    } else {

      pre_record_post();

      let url = "<?= base_url() . $this->capped_controller; ?>/<?= $this->action; ?>";

      if ('<?= hash_id($this->uri->segment(3, 0), 'decode'); ?>' !== 0) {
        url = "<?= base_url() . $this->capped_controller; ?>/<?= $this->action; ?>/<?= $this->uri->segment(3, 0); ?>";
      }

      var data = $(this).closest('form').serializeArray();
      // console.log(data);
      // return false;
      $.ajax({
        url: url,
        data: data,
        type: "POST",
        beforeSend: function() {

        },
        success: function(response) {

          //Check if the dulicate columns for some functions exists [Added or modified By Onduso 28/7/2022]
          var convert_response_json = JSON.parse(response);

          if (convert_response_json['flag'] == false) {

            $('#'+code_field_or_element).css('border', '1px solid red');
            
            alert(convert_response_json['message']);
           
            return false;
          } 
          else {

            on_record_post();

            alert(convert_response_json['message']);

            // alert(convert_response_json['table'] + ' ' + convert_response_json['header_id'])

            //If Save , use the browser history and go back
            if (elem.hasClass('back')) {
              //window.history.back(1); 
              if (typeof alt_referrer === 'undefined') {
                  location.href = document.referrer
              }else{
                  window.location.replace(alt_referrer + '/' + convert_response_json['header_id'] + '/' + convert_response_json['table']);
              }
            } else {
              document.getElementById('add_form').reset();
            }

          }
          //End of added code on 28/07/2022

        },
        error: function() {

        }
      });

      post_record_post();
    }
    ev.preventDefault();
  });

  // $('.date_check').on('click', function () {
  //    const id = $(this).attr('id');
  //    const date_input_id =  id.split('-')[1];
  //    //alert("#"+date_input_id);
  //    $("#opening_outstanding_cheque_date").html('');
  // });
</script>