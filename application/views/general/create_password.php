<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
} ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <?php
    $system_name = $this->db->get_where('setting', array('type' => 'system_name'))->row()->description;
    $system_title = $this->db->get_where('setting', array('type' => 'system_title'))->row()->description;
    ?>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Grants Management System" />
    <meta name="author" content="" />

    <title><?php echo get_phrase('reset_password'); ?> | <?php echo $system_title; ?></title>


    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/font-icons/entypo/css/entypo.css">
    <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-core.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-theme.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-forms.css">
    <link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css" />

    <script src="<?php echo base_url(); ?>assets/js/jquery-1.11.0.min.js"></script>

    <!--[if lt IE 9]><script src="<?php echo base_url(); ?>assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
                <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
                <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    <link rel="shortcut icon" href="<?php echo base_url(); ?>assets/images/favicon.ico">

</head>

<body class="page-body login-page login-form-fall" data-url="http://neon.dev">


    <!-- This is needed when you send requests via Ajax -->
    <script type="text/javascript">
        var baseurl = '<?php echo base_url(); ?>';
    </script>

    <div class="login-container">

        <div class="login-header login-caret">

            <div class="login-content" style="width:100%;">

                <a href="<?php echo base_url(); ?>" class="logo">
                    <img src="uploads/logo.png" height="60" alt="" />
                </a>

                <p class="description">
                <h2 style="color:#cacaca; font-weight:100;">
                    <?php echo $system_name; ?>
                </h2>
                </p>
                <p class="description"><?= get_phrase('change_your_password'); ?>.</p>

                <!-- overlay -->
                <div class='hidden' id="overlay"><img src='<?php echo base_url() . "uploads/preloader4.gif"; ?>' /></div>
            </div>

        </div>

        <div class="login-progressbar">
            <div></div>
        </div>

        <div class="login-form">

            <div class="login-content">

                <div id='same_password' class="hidden">
                    <h3 style='color:red;'><?= get_phrase('new_password_entered_is_same_as_old_one'); ?></h3>

                </div>
                <form method="post" role="form" id="form_new_password">


                    <div class="form-steps">

                        <div class="step current" id="step-1">
                            <?php
                            if ($this->session->user_id == '') { ?>

                                <!-- <div class="form-group">
                                    <div class="input-group">
                                        <div class="input-group-addon">
                                            <i class="entypo-mail"></i>
                                        </div>

                                        <input type="email" class="form-control" name="email" id="email" placeholder="<?= get_phrase('enter_email'); ?>" autocomplete="off" />
                                         <div id='email_password_div' style="color:red; size:20px; font-family:Georgia, 'Times New Roman', Times, serif;">
                                    </div>
                                </div> -->

                                <!-- Email -->
                                <div class="form-group">
                                    <div class='input-group'>
                                        <span class='input-group-addon'>
                                            <i style="color:red; " class='fa fa-asterisk'></i>

                                        </span>
                                        <input style="background-color:white; color:black;" type="text" class="form-control required" name="email" id="email" placeholder="Your Email" autocomplete="off" />
                                        <i id='email_txt' class="bi bi-envelope" style="margin-left: -30px; color:black; font-size:20px"></i>

                                        <div id='email_password_div' style="color:red; size:20px; font-family:Georgia, 'Times New Roman', Times, serif;">

                                        </div>

                                    </div>
                                </div>

                            <?php } ?>


                            <!-- <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <i class="entypo-lock"></i>
                                    </div>

                                    <input type="password" class="form-control" name="new_password" id="new_password" placeholder="<?= get_phrase('enter_new_password'); ?>" autocomplete="off" />

                                    
                                </div>
                            </div> -->

                            <!-- Password -->
                            <div class="form-group ">
                                <div class='input-group'>
                                    <span class='input-group-addon'>
                                        <i style="color:red; " class='fa fa-asterisk'></i>

                                    </span>
                                    <input style="background-color:white; color:black;" type="password" class="form-control required" name="password" id="password" placeholder="Password" autocomplete="off" />

                                    <i class="bi bi-eye-slash" id="togglePassword" style="margin-left: -30px; cursor: pointer; color:black; font-size:20px"></i>
                                    <div id='weak_password_div' style="color:red; size:20px; font-family:Georgia, 'Times New Roman', Times, serif;">
                                        <!-- Populated by ajax -->
                                    </div>

                                </div>
                            </div>


                            <div class="form-group">
                                <button id='change_password' type="submit" class="btn btn-info btn-block btn-login">
                                    <?php echo get_phrase('submit'); ?>
                                    <i class="entypo-right-open-mini"></i>
                                </button>
                            </div>

                        </div>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <!-- Bottom Scripts -->
    <script src="<?php echo base_url(); ?>assets/js/gsap/main-gsap.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/jquery-ui/js/jquery-ui-1.10.3.minimal.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/bootstrap.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/joinable.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/resizeable.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/neon-api.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/jquery.validate.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/neon-forgotpassword.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/jquery.inputmask.bundle.min.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/neon-custom.js"></script>
    <script src="<?php echo base_url(); ?>assets/js/neon-demo.js"></script>

</body>

</html>

<script>
    //Password eye Toggling
    const togglePassword = document.querySelector("#togglePassword");
    const password = document.querySelector("#password");
    passwordEyeToggling(togglePassword, password);

    // prevent form submit
    const form = document.querySelector("form");
    form.addEventListener('submit', function(e) {
        e.preventDefault();
    });

    //Make Password to show visible text or hashed
    function passwordEyeToggling(togglePassword, password) {

        togglePassword.addEventListener("click", function() {
            // toggle the type attribute
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            // toggle the icon
            this.classList.toggle("bi-eye");
        });
    }

    //Email verification
    $(document).on('change', '#email', function() {
        let data = {
            email: $(this).val(),
        };

        let url = '<?= base_url() ?>login/verify_valid_email';

        $.post(url, data, function(res) {

            if (parseInt(res) == 0) {
                //Hide create password button and Show email_password_div
                $('#change_password').hide();
                $('#email_password_div').html('Invalid Email');

            } else {
                //Show create password button and Hide email_password_div
                $('#change_password').show();
                $('#email_password_div').html('');

            }

        });


    });


    // Verify Password complexity
    $(document).on('change', '#password', function() {


        let data = {
            password: $(this).val(),
        }

        let url = '<?= base_url() ?>login/verify_password_complexity';

        $.post(url, data, function(response) {

            let validPasswordArr = JSON.parse(response);

            let arrayLength = validPasswordArr.length;

            if (arrayLength > 0) {

                //Hide Create Button
                $('#change_password').hide();

                //Unhide Labels
                let buildWrongPasswardDivString = '';

                for (let index = 0; index < arrayLength; index++) {

                    buildWrongPasswardDivString += validPasswordArr[index] + '<br>';

                }

                $('#weak_password_div').html(buildWrongPasswardDivString);
            } else {
                //Unhide Create Button
                $('#change_password').show();

                //Hide Labels
                $('#weak_password_div').html('');


            }

        });

    });

    $('#change_password').on('click', function(ev) {

        var password = $('#password').val();
        var user_email = $('#email').val();

        if (password != '') {
            $('#overlay').removeClass('hidden');
        } else {
            alert('<?= get_phrase('enter_new_password'); ?>');

            return false;
        }



        //Add data

        var data = {
            new_password: password,
            email: user_email,
        }
        var url = '<?= base_url(); ?>login/change_password';

        $.ajax({
            url: url,
            data: data,
            type: "POST",
            beforeSend: function() {
                $('#overlay').css('display', 'block');
            },
            success: function(response) {

                $('#overlay').css('display', 'none');
                if (response == 1) {
                    $('#same_password').addClass('hidden');

                    alert('<?= get_phrase('you_are_being_taken_to_login_page_to_login_with_this_new_password') ?>');

                    window.location.href = '<?= base_url() . 'login/logout' ?>';


                } else if (response == -1) {
                    $('#same_password').removeClass('hidden');

                    return false;
                } else {
                    alert('Something wrong with new password');

                    return false;
                }

            },
            error: function() {

            }
        });


        ev.preventDefault();
    });
</script>