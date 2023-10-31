<?php

// require_once 'vendor/autoload.php';
// require_once 'config.php'; //Config file for credentials


use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Saloon\Http\Auth\AccessTokenAuthenticator;
use TagMyDoc\SharePoint\SharePointClient;


include 'config.php';

require __DIR__ . '/../vendor/autoload.php';

global $clientId;
global $clientSecret;
global $tenantId;


$client = new SharePointClient($clientId, $clientSecret, $tenantId);

$token = $client->getAccessToken()->serialize();
$auth = AccessTokenAuthenticator::unserialize($token);
$client->authenticate($auth);



//Store Logs
function store_log($messagelog)
{
    $logFilePath = __DIR__ . '/../src/log.log';
    $logFile = fopen($logFilePath, 'a');

    if ($logFile) {
        date_default_timezone_set('Asia/Karachi');
        $message = $messagelog . date('d-m-Y h:i:s A') . ".\n";

        fwrite($logFile, $message);
        fclose($logFile);
    } else {
        //echo "Unable to open or create the log file.";
    }
}


//Error Logs
function store_error_log($messagelog)
{
    $logFilePath = __DIR__ . '/../src/error.log';
    $logFile = fopen($logFilePath, 'a');

    if ($logFile) {
        
        date_default_timezone_set('Asia/Karachi');
        $message = $messagelog . date('d-m-Y h:i:s A') . ".\n";

        fwrite($logFile, $message);
        fclose($logFile);
    } else {
        //echo "Unable to open or create the log file.";
    }
}


function disable_Warnings(){
     // Save the current error reporting level
     $previousErrorReporting = error_reporting();

     // Disable warnings
     error_reporting($previousErrorReporting & ~E_WARNING);  
     // Restore the previous error reporting level
    error_reporting($previousErrorReporting);
}

//First Time Delta Call
//Give Information of All Files/Folders in JSON
function delta()
{
    global $client;
    global $driveId;
    $response = $client->drive($driveId)->delta();

    // Save the new response to the file
    $filePath = __DIR__ . '/../storage/deltaResponse';
    file_put_contents($filePath, $response);

    $data = json_decode($response, true);

    $deltaLink = $data['@odata.deltaLink'];
    $parts = explode("token=", $deltaLink); // Split the URL based on "token="
    if (count($parts) > 1) {
        $tokendelta = $parts[1]; // Get the second part, which is the token value
    } else {
        $tokendelta = ""; // Handle the case where "token=" is not found in the URL
    }
    // Save the token to another file
    $tokenFilePath = __DIR__ . '/../storage/deltaToken';
    file_put_contents($tokenFilePath, $tokendelta);

   
}


class ClsHelper
{
    public static function downloadAttachment($path, $fileName, $access_token)
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


    //Delete File/Folder on SharePoint Directory By File/Folder Name
    public static function deleteItemSharePoint($itemName)
    {
        global $client;
        global $driveId;
        
        try {
            disable_Warnings();
            $itemIdNew = '';
            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            $mappingDatabase = json_decode($mappingFile, true);

            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                // Start iterating from the second element (index 1)
                for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                    if (isset($mappingDatabase['value'][$k])) {
                    $itemDatabase = $mappingDatabase['value'][$k];
                    $itemNameNew = $itemName;
                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($itemDatabase['name']) && $itemDatabase['name'] === $itemNameNew) {
                        $itemIdNew = $itemDatabase['id'];
                    } else {
                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
                }
            } else {
                //echo "Error: 'value' array not found in the JSON response.\n";
            }

            $response = $client->drive($driveId)->deleteItem($itemIdNew);
            // If the operation was successful, display a success message
            //echo "Item Deleted successfully on SharePoint: " . $itemName;
            //echo $response;
            $messagelog =  "Item Deleted successfully on SharePoint: $itemName\n";
            store_log($messagelog);
            delta();
        } catch (Exception $e) {
            // If there was an error, display an error message
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);

        }
    }

    //Rename File/Folder on SharePoint By File/Folder Old Name and Updated Name
    public static function updateItemSharePoint($itemOldName, $itemUpdatedName)
    {
        global $client;
        global $driveId;
        try {
            disable_Warnings(); 
            $itemId = '';
            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            $mappingDatabase = json_decode($mappingFile, true);

            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                // Start iterating from the second element (index 1)
                for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                    if (isset($mappingDatabase['value'][$k])) {
                    $itemDatabase = $mappingDatabase['value'][$k];

                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($itemDatabase['name']) && $itemDatabase['name'] === $itemOldName) {
                        $itemId = $itemDatabase['id'];
                    } else {
                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
                }
            } else {
                //echo "Error: 'value' array not found in the JSON response.\n";
            }
            $response = $client->drive($driveId)->updateItem(
                $itemId,
                [
                    'name' => $itemUpdatedName
                ]
            );
           // echo $response;
            // If the operation was successful, display a success message
            //echo "Item Updated successfully on SharePoint: " . $itemUpdatedName;
            $messagelog =  "Item Updated successfully on SharePoint: $response\n";
            store_log($messagelog);
            delta();
        } catch (Exception $e) {
            // If there was an error, display an error message
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //Upload File/Folder on SharePoint in Specific Folder by File/Folder Name and
    // its Content(if it is a file) and Specific Folder Name/Path
    //itemName (Name of file you want to upload) 
    //itemContent (Content if it is file)
    //parentName (where to upload File, if you want to upload on Root Path then parentName
    //is Empty otherwise give path of specific Folder where to upload)
    function uploadItemtoPathSharePoint($itemName,$itemContent, $parentName)
    {
        global $client;
        global $driveId;
        try {
            disable_Warnings();
            $response = $client->drive($driveId)->uploadItemToPath($itemName, $itemContent, $parentName);
            $data = json_decode($response, true);
            // If the operation was successful, display a success message
            //echo "Item Upload successfully on SharePoint: " . $itemName;
            $messagelog =  "Item Upload successfully on SharePoint: $response\n";
            store_log($messagelog);
            delta();
        } catch (Exception $e) {
            // If there was an error, display an error message
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
        
    }

    //Create Folder on SharePoint directory By Folder Name at Root Path
    function createFolderSharePoint($itemName)
    {

        global $client;
        global $driveId;
        try {
         disable_Warnings();
        // Create the folder on SharePoint
        $response = $client->drive($driveId)->createFolder($itemName);

        // Check if the SharePoint folder was created successfully
        if ($response) {
            
            $messagelog =  "Folder Created Successfully on SharePoint: $response\n";
            store_log($messagelog);
            delta();
        } else {
            
            $messagelog = "Failed to create folder on SharePoint at: $response\n";
            store_error_log($messagelog);
        }
    } catch (Exception $e) {
        // If there was an error, display an error message
        $errorlog = "Error: " . $e->getMessage();
        store_error_log($errorlog);
    }
    }

    //Move File/Folder on SharePoint 
    //itemName(Which File/Folder you want to move)
    //parentName (where to move that File/Folder)
    function moveItemSharePoint($itemName, $parentName)
    {
        global $client;
        global $driveId;
        try {
            disable_Warnings();
            $itemId='';
            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            $mappingDatabase = json_decode($mappingFile, true);

            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                // Start iterating from the second element (index 1)
                for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                    if (isset($mappingDatabase['value'][$k])) {
                    $itemDatabase = $mappingDatabase['value'][$k];
                    
                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($itemDatabase['name']) && $itemDatabase['name'] === $itemName) {
                        $itemId = $itemDatabase['id'];
                    } else {
                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
                }
            } else {
                //echo "Error: 'value' array not found in the JSON response.\n";
            }


            $parentId='';
            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            $mappingDatabase = json_decode($mappingFile, true);

            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                // Start iterating from the second element (index 1)
                for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                    if (isset($mappingDatabase['value'][$k])) {
                    $itemDatabase = $mappingDatabase['value'][$k];
                   
                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($itemDatabase['name']) && $itemDatabase['name'] === $parentName) {
                        $parentId = $itemDatabase['id'];
                    } else {
                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
                }
            } else {
               // echo "Error: 'value' array not found in the JSON response.\n";
            }

            $response = $client->drive($driveId)->moveItem($itemId, $parentId);
            // If the operation was successful, display a success message
           
            $messagelog =  "Item Moved successfully on SharePoint:  $response\n";
            store_log($messagelog);
            delta();
        } catch (Exception $e) {
            // If there was an error, display an error message
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);

        }
    }


    //Copy File/Folder on SharePoint 
    //itemName(Which File/Folder you want to copy)
    //parentName (where to Copy that File/Folder )
    function copyItemSharePoint($itemName, $parentName)
    {
        global $client;
        global $driveId;
        try {
            disable_Warnings(); 
            $itemId='';
            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            $mappingDatabase = json_decode($mappingFile, true);

            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                // Start iterating from the second element (index 1)
                for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                    if (isset($mappingDatabase['value'][$k])) {
                    $itemDatabase = $mappingDatabase['value'][$k];
                    
                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($itemDatabase['name']) && $itemDatabase['name'] === $itemName) {
                        $itemId = $itemDatabase['id'];
                    } else {
                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
                }
            } else {
                //echo "Error: 'value' array not found in the JSON response.\n";
            }


            $parentId='';
            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            $mappingDatabase = json_decode($mappingFile, true);

            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                // Start iterating from the second element (index 1)
                for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                    if (isset($mappingDatabase['value'][$k])) {
                    $itemDatabase = $mappingDatabase['value'][$k];
                   
                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($itemDatabase['name']) && $itemDatabase['name'] === $parentName) {
                        $parentId = $itemDatabase['id'];
                    } else {
                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
                }
            } else {
                //echo "Error: 'value' array not found in the JSON response.\n";
            }


            $response = $client->drive($driveId)->copyItem($itemId, $parentId);
            // If the operation was successful, display a success message
            //echo "Item Copied successfully on SharePoint: " . $itemName;
            $messagelog =  "Item Copied successfully on SharePoint:  $response\n";
            store_log($messagelog);
            delta();
        } catch (Exception $e) {
            // If there was an error, display an error message
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }



}
