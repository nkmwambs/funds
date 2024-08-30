<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
 //print_r($this->session->all_userdata());
//  echo json_encode($this->db->field_data('status'));

// echo  json_encode($this->session->data_privacy_consented);

if ($this->session->system_admin) {
}

extract($result);

$unique_identifier = $this->unique_identifier_model->get_account_system_unique_identifier($this->session->user_account_system_id);
$is_status_id_max = $this->general_model->is_status_id_max('user', $this->session->user_id);

if (!$this->session->data_privacy_consented && isset($unique_identifier) && !empty($unique_identifier)) {
   echo $personal_data_consent;
} else{
?>


    <div class="row">
        <?php
        //extract($result);
        //$user_context_definition_level = $this->session->context_definition['context_definition_level'];
        //if ($user_context_definition_level > 10) {
        ?>
            <!-- <div class="col-sm-3 col-xs-3">
                <div class="tile-stats tile-red">
                    <div class="icon"><i class="entypo-users"></i></div>
                    <div id="late_mfr" class="num metrics" data-start="0" data-end="0" data-postfix="" data-duration="1500" data-delay="0">0</div>

                    <h3><a href="#" onclick="showAjaxModal('<?= base_url(); ?>/dashboard/modal/modal_centers_with_missing_last_month_mfr');"><?= get_phrase('last_month_unsubmitted_reports'); ?></a></h3>
                    <p></p>
                </div>
            </div>

            <div class="col-sm-3 col-xs-3">
                <div class="tile-stats tile-green">
                    <div class="icon"><i class="entypo-users"></i></div>
                    <div id="stale_cheques" class="num metrics" data-start="0" data-end="0" data-postfix="" data-duration="1500" data-delay="0">0</div>

                    <h3><a href="#" onclick="showAjaxModal('<?= base_url(); ?>/dashboard/modal/modal_stale_cheques');"><?= get_phrase('stale_cheques'); ?></a></h3>
                    <p></p>
                </div>
            </div>


            <div class="col-sm-3 col-xs-3">
                <div class="tile-stats tile-cyan">
                    <div class="icon"><i class="entypo-users"></i></div>
                    <div id="overdue_deposit" class="num metrics" data-start="0" data-end="0" data-postfix="" data-duration="1500" data-delay="0">0</div>

                    <h3><a href="#" onclick="showAjaxModal('<?= base_url(); ?>/dashboard/modal/modal_overdue_transit_deposit');"><?= get_phrase('overdue_deposit_transit') ?></a></h3>
                    <p></p>
                </div>
            </div>
    </div> -->

<?php 
//} else { 
?>
    <div class="col-sm-12">
        <div class="well">
            <h1><?= date('F, d Y') ?></h1>
            <h3><?= get_phrase('dashboard_welcome', 'Welcome to the site'); ?> <strong><?= $this->session->name; ?></strong></h3>
        </div>
    </div>
<?php 
// } 
?>
</div>

<?php if ($this->session->system_admin) { ?>

    <!-- <div class="row">
        <div class="col-sm-3">
            <button id='update_project_ids' class='btn btn-danger'>Update Opening Fund Balance Project Ids</button>
        </div>
    </div> -->

<?php } ?>

<!-- <div class='row'>
    <div class='col-xs-12'>
      <div class='btn btn-default' id='btn_test'>Test</div>
    </div>
  </div> -->
<?php
    // print_r($this->grants->field_data('testing'));
}
?>

<script>
   

    // $('.metrics').html('<img height = "80px" width = "120px" src="<?php echo base_url(); ?>assets/uploads/loading-loader.gif" />');

    // function load_numeric_metric_tile(tile_id, metric_endpoint) {
    //     user_hash = '<?= hash_id($this->session->user_id, 'encode'); ?>'
    //     cacheKey = tile_id + '_' + user_hash;
    //     if (doesKeyExist(cacheKey)) {
    //         if (isDataValid(cacheKey)) { // 1 hour in milliseconds
    //             const cachedData = JSON.parse(localStorage.getItem(cacheKey));
    //             console.log(cachedData)
    //             $('#' + tile_id).html(cachedData.value.response_value)
    //         } else {
    //             fetch_numeric_metrics(tile_id, cacheKey, metric_endpoint)
    //         }
    //     } else {
    //         fetch_numeric_metrics(tile_id, cacheKey, metric_endpoint)
    //     }
    // }

    // async function fetch_numeric_metrics(tile_id, cacheKey, metric_endpoint) {
    //     try {

    //         // const count_of_late_mfr_url = '<?= base_url(); ?>dashboard/count_of_late_mfr'
    //         const response = await fetch(metric_endpoint)
    //             .then(response => response.json())
    //             .then(response_value => {
    //                 cacheData(cacheKey, {
    //                     data_life: getRandomTimeInterval(),
    //                     response_value
    //                 });
    //                 const cachedData = JSON.parse(localStorage.getItem(cacheKey));
    //                 // console.log(cachedData)
    //                 $('#' + tile_id).html(cachedData.value.response_value)
    //             })
    //     } catch (error) {
    //         $('#' + tile_id).html('<?= get_phrase('error_occurred', 'Error has occurred') ?>')
    //     }
    // }

    // function getRandomTimeInterval() {
    //     const minMinutes = 45; // Minimum time in minutes
    //     const maxMinutes = 60; // Maximum time in minutes

    //     // Generate a random number between 0 and 1
    //     const randomFraction = Math.random();

    //     // Calculate the random time interval in milliseconds
    //     const randomTimeInterval = (minMinutes + randomFraction * (maxMinutes - minMinutes)) * 60 * 1000;

    //     return Math.floor(randomTimeInterval); // Round down to the nearest millisecond
    // }

    // // Storing data in localStorage
    // function cacheData(key, value) {
    //     const now = new Date();
    //     const data = {
    //         value: value,
    //         timestamp: now.getTime(), // Store the current timestamp
    //     };

    //     localStorage.setItem(key, JSON.stringify(data));
    // }

    // // Checking if cached data is still valid (not expired)
    // function isDataValid(key) {
    //     const cachedData = localStorage.getItem(key);

    //     if (cachedData) {
    //         const data = JSON.parse(cachedData);
    //         const now = new Date().getTime();
    //         return now - data.timestamp < data.value.data_life;
    //     }

    //     return false; // Data doesn't exist or has expired
    // }

    // // Checking if a key exists in the cache
    // function doesKeyExist(key) {
    //     return localStorage.getItem(key) !== null;
    // }

    // $(document).ready(function() {
    //     load_numeric_metric_tile('late_mfr', '<?= base_url(); ?>dashboard/count_of_late_mfr')
    //     load_numeric_metric_tile('stale_cheques', '<?= base_url(); ?>dashboard/stale_cheques')
    //     load_numeric_metric_tile('overdue_deposit', '<?= base_url(); ?>dashboard/overdue_transit_deposit')
    // });

    $('#update_project_ids').on('click', function() {
        const url = '<?= base_url(); ?>opening_fund_balance/update_opening_fund_balance_project_id';

        $.get(url, function(response) {
            alert(response);
        });
    })

    $("#btn_test").on('click', function() {
        var url = "<?= base_url(); ?><?= $this->controller; ?>/custom_ajax_call";
        var data = {
            'ajax_method': 'testing',
            'return_as_json': false
        };

        $.post(url, data, function(response) {
            alert(response);
        });
    });

    $('#update_fund_balance_report').on('click', function() {
        const date_prompt = prompt('Please provide a date to initialize in format "YYYY-MM-DD":')
        const data = {
            date_prompt
        }
        const url = "<?= base_url(); ?>fund_balance_summary_report/initialize_month_fund_balances"

        $.post(url, data, function(response) {
            alert(response);
        })
    })
</script>