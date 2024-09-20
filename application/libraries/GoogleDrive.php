<?php

require_once FCPATH . 'vendor/autoload.php'; // Required if using Composer

class GoogleDrive
{
    protected $client;

    public function __construct()
    {
        $CI =& get_instance();
        $CI->config->load('googledrive');
        
        $this->client = new Google_Client();
        $this->client->setClientId($CI->config->item('client_id'));
        $this->client->setClientSecret($CI->config->item('client_secret'));
        $this->client->setRedirectUri($CI->config->item('redirect_uri'));
        $this->client->addScope($CI->config->item('scopes'));
    }

    public function getAuthUrl()
    {
        return $this->client->createAuthUrl();
    }

    public function authenticate($code)
    {
        $this->client->fetchAccessTokenWithAuthCode($code);
        return $this->client->getAccessToken();
    }

    public function setAccessToken($token)
    {
        $this->client->setAccessToken($token);
    }

    public function uploadFile($filePath, $fileName)
    {
        $driveService = new Google_Service_Drive($this->client);

        $fileMetadata = new Google_Service_Drive_DriveFile([
            'name' => $fileName,
        ]);

        $fileContent = file_get_contents($filePath);

        $file = $driveService->files->create($fileMetadata, [
            'data' => $fileContent,
            'mimeType' => 'application/octet-stream',
            'uploadType' => 'multipart',
        ]);

        return $file;
    }

    public function listFiles()
    {
        $driveService = new Google_Service_Drive($this->client);
        $files = $driveService->files->listFiles();
        return $files;
    }
}
