<?php 

defined('BASEPATH') OR exit('No direct script access allowed');

$config['client_id'] = 'YOUR_CLIENT_ID';
$config['client_secret'] = 'YOUR_CLIENT_SECRET';
$config['redirect_uri'] = 'http://your-app-url.com/google/callback';
$config['scopes'] = [
    'https://www.googleapis.com/auth/drive.file',
];
