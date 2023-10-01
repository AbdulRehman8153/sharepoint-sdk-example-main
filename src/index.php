<?php

use Saloon\Http\Auth\AccessTokenAuthenticator;
use TagMyDoc\SharePoint\SharePointClient;

require __DIR__ . '/../vendor/autoload.php';

function get_token(): false|string|null
{
    return @file_get_contents(__DIR__ . '/../storage/token') ?: null;
}
function store_token(string $token): void
{
    file_put_contents(__DIR__ . '/../storage/token', $token);
}

$client = new SharePointClient  ('a1b259ca-22bc-4d80-99f5-a32b6a3cc40c', '7bn8Q~VOIR5eTr.3_YrTZdBFUKbUSV9h~H13Xb77','1a17fb93-b9e8-433d-9418-56455ea5573a');
// technupur
// $client = new SharePointClient('682fb38b-0315-46ef-b0de-e627c9f7dc80', '--_8Q~tRbj-FndfdOmsFSXoLPtkkq9GN7NZeHawe','cfd08a2e-4e1b-46c7-ac26-fb947caf2345');

$token = get_token();

if ($token === null) {
    $token = $client->getAccessToken()->serialize();
    store_token($token);
}

$auth = AccessTokenAuthenticator::unserialize($token);
$client->authenticate($auth);
 echo $token;

 //Download Item By Path
//  $response = $client
//  ->drive('b!A1_K8Zkwa0ikSugI16DH_QsyIfJNq29CitDnlep5wSHMwdDaHNGUT4EBC7A1ches')
//  ->downloadItemByPath('react.txt');   

 //////  $localDirectory = 'C:\xampp\htdocs\sharepoint-sdk-example-main\src\LocalDrive';

// Define the local directory where you want to save the image
    // $localDirectory = __DIR__ . '/../src/LocalDrive';

    // $imageContent = $response;
    // $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . 'react.txt';
    // file_put_contents($localFilePath, $imageContent);

    // if (file_put_contents($localFilePath, $imageContent) !== false) {
    //             echo 'Image saved successfully to ' . $localFilePath;
    //          } else {
    //             echo 'Failed to save the image.';
    //          }

//Creating Folder on Drive
//              $response = $client
//  ->drive('b!A1_K8Zkwa0ikSugI16DH_QsyIfJNq29CitDnlep5wSHMwdDaHNGUT4EBC7A1ches')
//  ->createFolder("Testing Folder2");
//              echo $response;

//upload item
//      $response = $client
//      ->drive('b!A1_K8Zkwa0ikSugI16DH_QsyIfJNq29CitDnlep5wSHMwdDaHNGUT4EBC7A1ches')
//    ->uploadItem
//    ("TestingFile.txt",
//    "Testing For Uploading Item",
//    "01FJOJ76F6Y2GOVW7725BZO354PWSELRRZ");
//     echo $response;


//Upload item to path
//     $response = $client
//      ->drive('b!A1_K8Zkwa0ikSugI16DH_QsyIfJNq29CitDnlep5wSHMwdDaHNGUT4EBC7A1ches')
//    ->uploadItemToPath
//    ("TestingFileUpload1.txt",
//    "Testing For Uploading Item 1",
//    "Testing Folder");
//     echo $response;


$response = $client
->drive('b!A1_K8Zkwa0ikSugI16DH_QsyIfJNq29CitDnlep5wSHMwdDaHNGUT4EBC7A1ches')
->deleteItem('react.txt'); 



 // $flag = @mkdir($save_path . "/src/LocalDrive/" . $response,0777,true);
 // echo $flag ;
 //var_dump($response->json());

//echo ($response);
//  header('Content-Type: application/octet-stream');
//  header('Content-Disposition: attachment; filename="react.png"');
//  header('Content-Length: ' . strlen($response));
 //echo $response;

// $response = $client
//     ->drive('b!A1_K8Zkwa0ikSugI16DH_QsyIfJNq29CitDnlep5wSHMwdDaHNGUT4EBC7A1ches')
//     ->getItemById('01FJOJ76HYJQJSVE3L25CYF22TD2SGR7YB');

// var_dump($response->json());


// $response = $client
//     ->drive('b!A1_K8Zkwa0ikSugI16DH_QsyIfJNq29CitDnlep5wSHMwdDaHNGUT4EBC7A1ches')
//     ->downloadItemByPath('react.png');

// var_dump($response->json());


// $sites = $client->sites()->get();

// // Loop through sites and find the one you want
// foreach ($sites as $site) {
//     if ($site['name'] === 'YourSiteName') {
//         $driveId = $site['drive']['id'];
//         break;
//     }
// }






    

// Check if the download was successful
// if ($response->getStatusCode() == 200) {
//     // Get the binary content of the image
//     $imageContent = $response->getBody();

//     // Create the full path to the local file
//     $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . 'react.png';

//     // Save the image content to the local file
//     if (file_put_contents($localFilePath, $imageContent) !== false) {
//         echo 'Image saved successfully to ' . $localFilePath;
//     } else {
//         echo 'Failed to save the image.';
//     }
// } else {
//     echo 'Failed to download the image from SharePoint.';
// }
