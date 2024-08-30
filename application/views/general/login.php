<!DOCTYPE html>
<html lang="en">

<head>
	<?php
	$system_name	=	$this->db->get_where('setting', array('type' => 'system_name'))->row()->description;
	$system_title	=	$this->db->get_where('setting', array('type' => 'system_title'))->row()->description;
	?>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">

	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="Neon Admin Panel" />
	<meta name="author" content="" />

	<title>Login | <?php echo $system_title; ?></title>


	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/js/jquery-ui/css/no-theme/jquery-ui-1.10.3.custom.min.css">
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/font-icons/entypo/css/entypo.css">
	<!-- <link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Noto+Sans:400,700,400italic"> -->
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/bootstrap.css">
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-core.css">
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-theme.css">
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/neon-forms.css">
	<link rel="stylesheet" href="<?php echo base_url(); ?>assets/css/custom.css">


	<script src="<?php echo base_url(); ?>assets/js/jquery-1.11.0.min.js"></script>

	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
	<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />


	<!--[if lt IE 9]><script src="<?php echo base_url(); ?>assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

	<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
		<script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
		<script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
	<![endif]-->
	<link rel="shortcut icon" href="<?php echo base_url(); ?>assets/images/favicon.ico">

	<style>
		#outage_message {
			font-size: 16pt;
			font-weight: bolder;
			background-color: red;
			color: white;
			padding: 10px;
			border-radius: 10px;
		}

		;

		.input-icons i {
			position: absolute;
		}

		;

		.input-icons {
			width: 100%;
			margin-bottom: 10px;
		}

		;

		.icon {
			padding: 10px;
			min-width: 40px;
		}

		;

		.input-field {
			width: 100%;
			padding: 10px;

		}

		;

		input,
		input[type=password] {
			width: 150px;
			height: 20px;
		}

		#toggle_pwd {
			margin-left: -30px;
			cursor: pointer;
		}

		#email_txt {
			margin-left: -30px;
		}
	</style>

</head>

<body class="page-body login-page login-form-fall" data-url="http://neon.dev">


	<!-- This is needed when you send requests via Ajax -->
	<script type="text/javascript">
		var baseurl = '<?php echo base_url(); ?>';
	</script>

	<div class="login-container">

		<div class="login-header login-caret">

			<div class="login-content" style="width:100%;">

				<!-- <a href="<?php echo base_url(); ?>" class="logo">
				<img src="uploads/logo.png" height="60" alt="" />
			</a> -->

				<p class="description">
				<h2 style="color:#cacaca; font-weight:100;">
					<?php echo $system_name; ?>
				</h2>
				</p>

				<!-- progress bar indicator -->
				<div class="login-progressbar-indicator">
					<h3>43%</h3>
					<span>logging in...</span>
				</div>
			</div>

		</div>

		<div class="login-progressbar">
			<div></div>
		</div>
		<?php
		if ($this->db->get_where('setting', array('type' => 'maintenance_mode'))->row()->description == 1) {
		?>
			<div class="row">
				<div class="col-xs-12 text-center">
					<span id="outage_message">The system is under maintenance schedule. You will be contacted by your Country Administrators once the system is back</span>
				</div>
			</div>
		<?php
		}
		?>

		<div class="login-form">

			<div class="login-content">

				<div class="form-login-error">
					<h3>Invalid login</h3>
					<p>If active in System: Please enter correct email and password!</p>
					<p>If Account is New: Please Contact Admin to activate your account!</p>
				</div>

				<form method="post" role="form" id="form_login">

					<div class="form-group">

						<div class="input-group">
							<div class="input-group-addon">
								<i class=""></i>
							</div>

							<?php
							$setup_email = "";
							$disable_password = "";
							$user_obj = $this->db->get('user');
							if ($user_obj->num_rows() == 1) {
								$setup_email = $this->db->get_where('setting', array('type' => 'system_email'))->row()->description;
								//$disable_password = "hide";
							}
							?>

							<input type="text" value="" class="form-control input-field col-xs-12" name="email" id="email" placeholder="Your Email" autocomplete="off" data-mask="email" />
							<i id='email_txt' class="fa fa-envelope icon"></i>

						</div>

					</div>

					<div class="form-group <?= $disable_password; ?>">

						<div class="input-group">
							<div class="input-group-addon">
								<i class=""></i>
							</div>

							<input type="password" class="form-control input-field" name="password" id="password" placeholder="Password" autocomplete="off" />
							<i id="toggle_pwd" class="fa fa-fw fa-eye field_icon"></i>
						</div>

					</div>

					<div class="form-group">
						<button id='btn-login' type="submit" class="btn btn-primary btn-block btn-login">
							<i class=""></i>
							Login
						</button>

					</div>



				</form>

				<!-- Create Account and Forgot Password -->
				<div class="login-bottom-links">
					<!-- <div>
						<a href="<?php echo base_url(); ?>login/create_account" class="link" style="color:yellow;">
							<?php echo get_phrase('create_account'); ?>
						</a>
					</div> -->

					<div>
						<a href="<?php echo base_url(); ?>login/forgot_password" class="link">
							<?php echo get_phrase('forgot_your_password'); ?> ?
						</a>
					</div>


				</div>


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
	<script src="<?php echo base_url(); ?>assets/js/neon-login.js"></script>
	<script src="<?php echo base_url(); ?>assets/js/neon-custom.js"></script>
	<script src="<?php echo base_url(); ?>assets/js/neon-demo.js"></script>

	<script type="text/javascript">
		$(function() {
			$("#toggle_pwd").click(function() {
				$(this).toggleClass("fa-eye fa-eye-slash");
				var type = $(this).hasClass("fa-eye-slash") ? "text" : "password";
				$("#password").attr("type", type);
			});
		});

	</script>

</body>

</html>