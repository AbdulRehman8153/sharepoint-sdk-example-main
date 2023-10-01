<?php
require_once 'vendor/autoload.php';
require_once 'config.php'; //Config file for credentials

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

class ClsHelper
{
public static function downloadAttachment($path,$fileName, $access_token)
    {
        try {


            $objResult = new Result();
            $client = new Client();
            $headers = [
                'Accept' => 'application/json;odata=verbose',
                'Content-Type' => 'application/json;odata=verbose',
                'Authorization' => 'Bearer ' . $access_token
            ];

            $request = new Request('GET', $path, $headers);
            // echo 'https://' . Config::$tanatURL . '/sites/' . Config::$siteName . '/_api/Web/GetFileByServerRelativeUrl(\'/sites/' . Config::$siteName . '/' . $libraryName . '/' . $attachmentName . '\')/$value';
            //  $res = $client->sendAsync($request)->wait();
            $res = $client->send($request);
            // echo $res->getBody();

            // Get the response body stream

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            header('Content-Length: ' . strlen($res->getBody()));
            echo $res->getBody();

            //code to save file in the folder  
// $dirname = uniqid();
//         $newFolderName = $dirname . "_" . date('Y_m_d_H_i_s_m');
//         $folderPath = Config::$fileDownloadPath . "/" . $newFolderName;
//         $fileUrl = "/"."files/" . $newFolderName . "/" . $attachmentName;
//         mkdir($folderPath);
//         $folderPath = $folderPath . "/" . $attachmentName;     
            // $outputStream = fopen($folderPath, 'a');
            // fwrite($outputStream, $res->getBody());
            // fclose($outputStream);

            $objResult->status = 1;
            $objResult->result = "File downloaded successfully";
            return $objResult;

        } catch (Exception $e) {
            $objResult->status = 0;
            $objResult->result = $e->getTraceAsString();
            return $objResult;
        }

    }
}
?>