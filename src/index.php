<!DOCTYPE html>
<html>

<head>
    <title>Display JSON Data</title>
</head>

<body>




    <?php


    use Saloon\Http\Auth\AccessTokenAuthenticator;
    use TagMyDoc\SharePoint\SharePointClient;

    include('config.php');

    require __DIR__ . '/../vendor/autoload.php';

    function get_token(): false|string|null
    {
        return @file_get_contents(__DIR__ . '/../storage/token') ?: null;
    }
    function store_token(string $token): void
    {
        file_put_contents(__DIR__ . '/../storage/token', $token);
    }

    //$client = new SharePointClient  ('a1b259ca-22bc-4d80-99f5-a32b6a3cc40c', '7bn8Q~VOIR5eTr.3_YrTZdBFUKbUSV9h~H13Xb77','1a17fb93-b9e8-433d-9418-56455ea5573a');
    // technupur
    // $client = new SharePointClient('682fb38b-0315-46ef-b0de-e627c9f7dc80', '--_8Q~tRbj-FndfdOmsFSXoLPtkkq9GN7NZeHawe','cfd08a2e-4e1b-46c7-ac26-fb947caf2345');

    $client = new SharePointClient($clientId, $clientSecret, $tenantId);


    $token = get_token();

    if ($token === null) {
        $token = $client->getAccessToken()->serialize();
        store_token($token);
    }

    $auth = AccessTokenAuthenticator::unserialize($token);
    $client->authenticate($auth);
    echo $token;

    $localDirectory = __DIR__ . '/../src/LocalDrive';


    $tokendelta =  @file_get_contents(__DIR__ . '/../storage/deltaToken') ?: null;

    if ($tokendelta === null) {
        delta($client, $driveId);
    } else {
        deltaByToken($client, $driveId, $tokendelta);
    }

    //First Time Delta Call
    function delta($client, $driveId)
    {
        $response = $client->drive($driveId)->delta();
        echo $response;
        $data = json_decode($response, true);
        $tokendelta = substr($data['@odata.deltaLink'], 124, 151); // Extract from position 3 to 38
        file_put_contents(__DIR__ . '/../storage/deltaToken', $tokendelta);
    }


    //Delta By Token
    function deltaByToken($client, $driveId, $tokendelta)
    {
        try {
            $response = $client->drive($driveId)->delta($tokendelta);
            $data = json_decode($response, true);

            echo $response;
            //if new item has created/upload

            // Check if the 'value' array exists in the JSON data
            // if (isset($data['value']) && is_array($data['value'])) {
            //     // Start iterating from the second element (index 1)
            //     for ($i = 1; $i < count($data['value']); $i++) {
            //         $item = $data['value'][$i];

            //         // Check if 'id' and 'name' keys exist in the current item
            //         if (isset($item['id']) && isset($item['name'])) {
            //             $itemid = $item['id'];
            //             $itemname = $item['name'];

            //             // If the operation was successful, display a success message
            //             //echo "Delta successfully for item: $itemname (id: $itemid)\n";
            //             $localDirectory = __DIR__ . '/../src/LocalDrive';
            //             // Call the downloadItemById function with extracted values
            //            // downloadItemByPath($client, $driveId, $itemname, $itemid);
            //             downloadItemById($client, $driveId, $itemname, $itemid);
            //         } else {
            //             echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //         }
            //     }
            // } else {
            //     echo "Error: 'value' array not found in the JSON response.\n";
            // }


            //if item has changed
            //   if (isset($data['value']) && is_array($data['value'])) {
            //                 // Start iterating from the second element (index 1)
            //                 for ($i = 1; $i < count($data['value']); $i++) {
            //                     $item = $data['value'][$i];

            //                     // Check if 'id' and 'name' keys exist in the current item
            //                     if (isset($item['createdDateTime']) && isset($item['lastModifiedDateTime'])) {
            //                         $createdDateTime = $item['createdDateTime'];
            //                         $lastModifiedDateTime = $item['lastModifiedDateTime'];
            //                         $itemid = $item['id'];
            //                         $itemname = $item['name'];

            //             // Convert the date and time to a string
            //     $createdDateTimeString = date('Y-m-d H:i:s', strtotime($createdDateTime));
            //     $lastModifiedDateTimeString = date('Y-m-d H:i:s', strtotime($lastModifiedDateTime));

            //     // Print the results
            //     echo "Created DateTime: $createdDateTimeString\n";
            //     echo "Last Modified DateTime: $lastModifiedDateTimeString\n";

            //     if($createdDateTimeString !== $lastModifiedDateTimeString){
            //         //echo "Not equal";
            //         $localPath = __DIR__ . '\LocalDrive/4567.txt';
            //         //updateItem($client, $driveId, $itemid, $itemname,$localPath);
            //         downloadItemById($client, $driveId, $itemname, $itemid);

            //     }

            //                         // If the operation was successful, display a success message
            //                         //echo "Delta successfully for item: $createdDateTime (id: $itemid)\n";
            //                         //echo "Delta successfully for item: $lastModifiedDateTime (id: $itemid)\n";
            //                         $localDirectory = __DIR__ . '/../src/LocalDrive';
            //                         // Call the downloadItemById function with extracted values

            //                        // downloadItemById($client, $driveId, $itemname, $itemid);
            //                     } else {
            //                         echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //                     }
            //                 }
            //             } else {
            //                 echo "Error: 'value' array not found in the JSON response.\n";
            //             }

            //if item has deleted
            // if (isset($data['value']) && is_array($data['value'])) {
            //     // Start iterating from the second element (index 1)
            //     for ($i = 1; $i < count($data['value']); $i++) {
            //         $item = $data['value'][$i];

            //         // Check if 'id' and 'name' keys exist in the current item
            //         if (!(isset($item['createdDateTime']) && isset($item['lastModifiedDateTime']))) {
            //             $itemid = $item['id'];
            //             //$itemname = $item['name'];
            //             $itemName='123.txt';

            // $ID= $item['id'];
            // if ($item['folder']) {
            //     $Folder='Folder';
            //     echo "Item ID: $ID\n";
            //     echo "Item Type: $Folder\n";
                
            // }
            // else{
            //     $File='File';
            //     echo "Item ID: $ID\n";
            //      echo "Item Type: $File\n";
                 
            // }

            //             $localDirectory = __DIR__ . '/../src/LocalDrive';
            //            // deleteItemlocally($client, $driveId, $itemid, $itemName, $localDirectory);
            //         } else {
            //             echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //         }
            //     }
            // } else {
            //     echo "Error: 'value' array not found in the JSON response.\n";
            // }

        } 
        catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }



    // function deltaByToken($client, $driveId, $tokendelta)
    // {
    //     try {
    //         $response = $client->drive($driveId)->delta($tokendelta);
    //         //echo $response;
    //         $data = json_decode($response, true);
    //         $itemid = $data['id'];
    //         $itemname = $data['name'];
    //         //echo $itemid;
    //         //echo $itemname;

    //         // If the operation was successful, display a success message
    //         echo "Delta successfully: " . $response;
    //     } catch (Exception $e) {
    //         // If there was an error, display an error message
    //         echo "Error: " . $e->getMessage();
    //     }
    //     // $data = json_decode($response, true);
    //     // $itemname = $data['name'];
    //     // $itemId = $data['id'];
    //     //downloadItemById($client, $driveId, $itemname, $itemid);
    //     //downloadItemByPath($client, $driveId, $itemname);  
    //     downloadItemById($client, $driveId, $itemname, $itemid);     
    //     //downloadItemById($client, $driveId, $itemname, $itemId);

    // }

    //Download Item By Path
    // function downloadItemByPath($client, $driveId, $itemname)
    // {
    //     $response = $client
    //         ->drive($driveId)
    //         ->downloadItemByPath($itemname);

    //     //// Define the local directory where you want to save the Item
    //     $localDirectory = __DIR__ . '/../src/LocalDrive';

    //     $imageContent = $response;
    //     $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . $itemname;
    //     file_put_contents($localFilePath, $imageContent);

    //     if (file_put_contents($localFilePath, $imageContent) !== false) {
    //         echo 'Item saved successfully to ' . $localFilePath;
    //     } else {
    //         echo 'Failed to save the Item.';
    //     }
    // }

    //Download Item on Local Directory By Name
    function downloadItemByPath($client, $driveId, $itemname, $itemId)
    {

        // Define the local directory where you want to save the item
        $localDirectory = __DIR__ . '/../src/LocalDrive';

        // Define the local file/folder path
        $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . $itemname;

        // Check if the item (file or folder) already exists locally
        if (file_exists($localFilePath)) {
            echo "Item already exists at: $localFilePath\n";
        } else {
            // Get information about the item
            $itemInfo = $client->drive($driveId)->getItemByPath($itemname);

            //if ($itemInfo !== false) {
            $data = json_decode($itemInfo, true);
            if ($data['folder']) {
                // If the item is a folder, create the local folder
                if (mkdir($localFilePath, 0777, true)) {
                    echo "Folder created successfully at: $localFilePath\n";
                } else {
                    echo "Failed to create folder at: $localFilePath\n";
                }

                // Recursively download the contents of the folder
                $children = $client->drive($driveId)->listById($itemId);

                foreach ($children as $child) {
                    downloadItemByPath($client, $driveId, $child['name'], $child['id']);
                }
            } else {
                // If the item is a file, download and save it
                $response = $client->drive($driveId)->downloadItemByPath($itemname);
                if ($response !== false) {
                    if (file_put_contents($localFilePath, $response) !== false) {
                        echo "File saved successfully to $localFilePath\n";
                    } else {
                        echo "Failed to save the file to $localFilePath\n";
                    }
                } else {
                    echo "Failed to download the file.\n";
                }
            }

            //}
            //}
            // else {
            //     echo "Failed to get item information.\n";
            // }
        }
    }

    //Download Item on Local Directory By Id
    function downloadItemById($client, $driveId, $itemname, $itemId)
    {

        // Define the local directory where you want to save the item
        $localDirectory = __DIR__ . '/../src/LocalDrive';

        // Define the local file/folder path
        $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . $itemname;

        // Check if the item (file or folder) already exists locally
        if (file_exists($localFilePath)) {
            echo "Item already exists at: $localFilePath\n";
        } else {
            // Get information about the item
            $itemInfo = $client->drive($driveId)->getItemById($itemId);

            //if ($itemInfo !== false) {
            $data = json_decode($itemInfo, true);
            $Name= $data['name'];
            $ID= $data['id'];
            if ($data['folder']) {
                $Folder='Folder';
                echo "Item Name: $Name\n";
                echo "Item Content: $Name\n";
                echo "Item Type: $Folder\n";
                echo "SharePoint ID: $ID\n";
            }
            else{
                $File='File';
                 echo "Item Name: $Name\n";
                 echo "Item Content: $Name\n";
                 echo "Item Type: $File\n";
                 echo "SharePoint ID: $ID\n";
            }
          


            if ($data['folder']) {
                // If the item is a folder, create the local folder
                if (mkdir($localFilePath, 0777, true)) {
                    echo "Folder created successfully at: $localFilePath\n";
                } else {
                    echo "Failed to create folder at: $localFilePath\n";
                }

                // Recursively download the contents of the folder
                $children = $client->drive($driveId)->listById($itemId);

                foreach ($children as $child) {
                    downloadItemById($client, $driveId, $child['name'], $child['id']);
                }
            } else {
                // If the item is a file, download and save it
                $response = $client->drive($driveId)->downloadItemById($itemId);
                if ($response !== false) {
                    if (file_put_contents($localFilePath, $response) !== false) {
                        echo "File saved successfully to $localFilePath\n";
                    } else {
                        echo "Failed to save the file to $localFilePath\n";
                    }
                } else {
                    echo "Failed to download the file.\n";
                }
            }

            //}
            //}
            // else {
            //     echo "Failed to get item information.\n";
            // }
        }
    }


    // function downloadItemById($client, $driveId, $itemname, $itemId)
    // {
    //     // Define the local directory where you want to save the item
    //     $localDirectory = __DIR__ . '/../src/LocalDrive';

    //     // Define the local file/folder path
    //     $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . $itemname;

    //     // Check if the item (file or folder) already exists locally
    //     if (file_exists($localFilePath)) {
    //         echo "Item already exists at: $localFilePath\n";
    //     } else {
    //         // Download the item from SharePoint
    //         $response = $client->drive($driveId)->downloadItemById($itemId);

    //         if ($response !== false) {
    //             // Save the item to the local directory
    //             if (file_put_contents($localFilePath, $response) !== false) {
    //                 echo "Item saved successfully to $localFilePath\n";
    //             } else {
    //                 echo "Failed to save the item.\n";
    //             }
    //         } else {
    //             echo "Failed to download the item.\n";
    //         }
    //     }
    // }


    //Download Item By Id
    // function downloadItemById($client, $driveId, $itemname, $itemId)
    // {
    //     $response = $client
    //         ->drive($driveId)
    //         ->downloadItemById($itemId);

    //     //Define the local directory where you want to save the Item
    //     $localDirectory = __DIR__ . '/../src/LocalDrive';

    //     $imageContent = $response;
    //     $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . $itemname;
    //     file_put_contents($localFilePath, $imageContent);

    //     if (file_put_contents($localFilePath, $imageContent) !== false) {
    //         echo 'Item saved successfully to ' . $localFilePath;
    //     } else {
    //         echo 'Failed to save the Item.';
    //     }
    // }

    //Get Item By Id from SharePoint Directory
    function getItemById($client, $driveId, $itemId)
    {
        try {
            $response = $client->drive($driveId)->getItemById($itemId);
            // If the operation was successful, display a success message
            echo "Item retrieved successfully: " . $response;
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }

    //Get Item By Path from SharePoint Directory
    function getItemByPath($client, $driveId, $itemPath)
    {
        try {
            $response = $client->drive($driveId)->getItemByPath($itemPath);
            // If the operation was successful, display a success message
            echo "Item retrieved successfully: " . $response;
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }

    //Get Items from SHarePoint Directory
    function getItems($client, $driveId)
    {
        try {
            $response = $client->drive($driveId)->getItems();
            // If the operation was successful, display a success message
            echo "Item Retrieved successfully: " . $response;
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }

    //Delete Item on SharePoint
    //Deleting Item (First Upload a item and then use its id for deletion)
    // function deleteItem($client, $driveId, $itemId)
    // {
    //     try {
    //         $response = $client->drive($driveId)->deleteItem($itemId);
    //         // If the operation was successful, display a success message
    //         echo "Item Deleted successfully: " . $response;
    //     } catch (Exception $e) {
    //         // If there was an error, display an error message
    //         echo "Error: " . $e->getMessage();
    //     }
    // }


    //Delete Item from both SharePoint Directory and Local Directory by Path
    // function deleteItem($client, $driveId, $itemId,$itemName, $localDirectory)
    // {
    //     // Delete the item on SharePoint
    //     $response = $client->drive($driveId)->deleteItemByPath($itemId);

    //     // Check if the SharePoint item was deleted successfully
    //     if ($response) {
    //         echo "SharePoint Item Deleted Successfully!\n";

    //         // Construct the local item path based on the item's name
    //         //$itemName = basename($itemId);
    //         $localItemPath = $localDirectory . '/' . $itemName;

    //         // Check if the local item exists and delete it
    //         if (file_exists($localItemPath)) {
    //             if (is_dir($localItemPath)) {
    //                 // Delete the directory and its contents recursively
    //                 $success = deleteDirectory($localItemPath);
    //                 if ($success) {
    //                     echo "Local Directory Deleted Successfully at $localItemPath\n";
    //                 } else {
    //                     echo "Failed to delete Local Directory at $localItemPath\n";
    //                 }
    //             } else {
    //                 // Delete a file
    //                 if (unlink($localItemPath)) {
    //                     echo "Local File Deleted Successfully at $localItemPath\n";
    //                 } else {
    //                     echo "Failed to delete Local File at $localItemPath\n";
    //                 }
    //             }
    //         } else {
    //             echo "Local Item does not exist at $localItemPath\n";
    //         }
    //     } else {
    //         echo "Failed to delete SharePoint Item\n";
    //     }
    // }

    // // Recursive function to delete a directory and its contents
    // function deleteDirectory($dir)
    // {
    //     if (!file_exists($dir)) {
    //         return true;
    //     }

    //     if (!is_dir($dir)) {
    //         return unlink($dir);
    //     }

    //     foreach (scandir($dir) as $item) {
    //         if ($item == '.' || $item == '..') {
    //             continue;
    //         }

    //         if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
    //             return false;
    //         }
    //     }

    //     return rmdir($dir);
    // }


    //Delete Item from both sharePoint Directory and Local Directory By Id
    // function deleteItem($client, $driveId, $itemId, $itemName, $localDirectory)
    // {
    //     // Delete the item on SharePoint
    //     $response = $client->drive($driveId)->deleteItem($itemId);
    //     echo $response;
    //     // Check if the SharePoint item was deleted successfully
    //     if ($response) {
    //         echo "SharePoint Item Deleted Successfully!\n";

    //         // Construct the local item path based on the item's name
    //         ///$itemName = basename($itemId);
    //         $localItemPath = $localDirectory . '/' . $itemName;

    //         // Check if the local item exists and delete it
    //         if (file_exists($localItemPath)) {
    //             if (unlink($localItemPath)) {
    //                 echo "Local Item Deleted Successfully at $localItemPath\n";
    //             } else {
    //                 echo "Failed to delete Local Item\n";
    //             }
    //         } else {
    //             echo "Local Item does not exist at $localItemPath\n";
    //         }
    //     } else {
    //         echo "Failed to delete SharePoint Item\n";
    //     }
    // }


    //Delete Item from LOcal Directory By Path
    function deleteItemlocally($client, $driveId, $itemId, $itemName, $localDirectory)
    {

        // Construct the local item path based on the item's name
        ///$itemName = basename($itemId);
        $localItemPath = $localDirectory . '/' . $itemName;

        // Check if the local item exists and delete it
        if (file_exists($localItemPath)) {
            if (unlink($localItemPath)) {
                echo "Local Item Deleted Successfully at $localItemPath\n";
                
            } else {
                echo "Failed to delete Local Item\n";
            }
        } else {
            echo "Local Item does not exist at $localItemPath\n";
        }
    }



    //Move Item (First Upload a item and then use its id and parent id from
    //where you want to move a item)
    function moveItem($client, $driveId, $itemId, $parentId)
    {
        try {
            $response = $client->drive($driveId)->moveItem($itemId, $parentId);
            // If the operation was successful, display a success message
            echo "Item Moved successfully: " . $response;
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //Copy Item (First Upload a item and then use its id and parent id from
    //where you want to copy a item)
    // function copyItem($client, $driveId, $itemId, $parentId)
    // {
    //     try {
    //         $response = $client->drive($driveId)->copyItem($itemId, $parentId);
    //         // If the operation was successful, display a success message
    //         echo "Item Copied successfully: " . $response;
    //     } catch (Exception $e) {
    //         // If there was an error, display an error message
    //         echo "Error: " . $e->getMessage();
    //     }
    // }


    //Update Item on SharePoint and Local Directory
    function updateItem($client, $driveId, $itemId, $itemname, $localPath)
    {
        try {
            //echo $localPath;
            // Update the item on SharePoint
            $response = $client->drive($driveId)->updateItem(
                $itemId,
                [
                    'name' => $itemname
                ]
            );

            // If the operation was successful, display a success message
            echo "Item Updated successfully on SharePoint: " . $response;

            // Update the local directory
            if (file_exists($localPath)) {
                $newLocalPath = dirname($localPath) . '/' . $itemname;
                //echo $newLocalPath;
                if (rename($localPath, $newLocalPath)) {
                    echo "Local file/directory updated successfully.";
                } else {
                    echo "Failed to update local file/directory.";
                }
            } else {
                echo "Local file/directory not found.";
            }
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //Update Item on SHarePoint

    // function updateItem($client, $driveId, $itemId, $itemname)
    // {
    //     try {
    //         $response = $client->drive($driveId)->updateItem(
    //             $itemId,
    //             [
    //                 'name' => $itemname
    //             ]
    //         );

    //         // If the operation was successful, display a success message
    //         echo "Item Updated successfully: " . $response;
    //     } catch (Exception $e) {
    //         // If there was an error, display an error message
    //         echo "Error: " . $e->getMessage();
    //     }
    // }


    //List Items By Id

    function listItemById($client, $driveId, $itemId)
    {
        try {
            $response = $client->drive($driveId)->listById($itemId);
            // If the operation was successful, display a success message
            echo "Item Listed successfully: " . $response;
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //List Items By Path
    function listItemByPath($client, $driveId, $itemPath)
    {
        try {
            $response = $client->drive($driveId)->listByPath($itemPath);
            // If the operation was successful, display a success message
            echo "Item Listed successfully: " . $response;
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //List Items
    function listItems($client, $driveId)
    {
        try {
            $response = $client->drive($driveId)->listItems();
            // If the operation was successful, display a success message
            echo "Item Listed successfully: " . $response;
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //Create Folder
    function createFolder($client, $driveId, $itemPath, $localDirectory)
    {
        // Create the folder on SharePoint
        $response = $client->drive($driveId)->createFolder($itemPath);

        // Check if the SharePoint folder was created successfully
        if ($response) {
            echo "SharePoint Folder Created Successfully!\n";
            // Create the folder locally
            // $folderName = $response->getFolderName(); // Replace with the actual method or property
            //$localFolder = $localDirectory . '/' . $folderName;

            $data = json_decode($response, true);
            $itemname = $data['name'];


            $localFolder = $localDirectory . '/' . $itemname;

            if (mkdir($localFolder)) {
                echo "Local Folder Created Successfully at $localFolder\n";
            } else {
                echo "Failed to create Local Folder\n";
            }
        } else {
            echo "Failed to create SharePoint Folder\n";
        }
    }


    //Upload Item on SharePoint to Root and Download in Local Directory By Id
    function uploadItem($client, $driveId, $itemName, $parentId)
    {
        try {
            $response = $client->drive($driveId)->uploadItem($itemName, $itemName, $parentId);
            $data = json_decode($response, true);
            $itemid = $data['id'];
            $itemname = $data['name'];
            // If the operation was successful, display a success message
            echo "Item Upload successfully: " . $response;
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
        //getItemById($client, $driveId, $itemid);
        //getItemByPath($client, $driveId, $itemname);
        downloadItemById($client, $driveId, $itemname, $itemid);
        //downloadItemByPath($client, $driveId, $itemname);
        //createFolder($client, $driveId, $itemname);
        //createFolder($client, $driveId, $itemName,$localDirectory);
        //deleteItem($client, $driveId, $itemid);
    }

    //Upload Item to Path on SharePoint By Path and Download in Local Directory by Id
    function uploadItemtoPath($client, $driveId, $itemName, $parentName)
    {
        try {
            $response = $client->drive($driveId)->uploadItemToPath($itemName, $itemName, $parentName);
            $data = json_decode($response, true);

            $itemid =  $data['id'];
            $itemname = $data['name'];
            $itemContent = $itemName;
            $parentname = $parentName;


            //echo "Item Name: $itemname \n";

            //echo "Item Content: $itemContent \n";
            //echo "Item Location: $parentname \n";
            // echo "Item Name: $itemname \n";



            // If the operation was successful, display a success message
            echo "Item Upload successfully: " . $response;
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
        // getItemById($client, $driveId, $itemid);
        //getItemByPath($client, $driveId, $itemname);
        downloadItemById($client, $driveId, $itemname, $itemid);
        //downloadItemByPath($client, $driveId, $itemname);
        //createFolder($client, $driveId, $itemname);
        //createFolder($client, $driveId, $itemName,$localDirectory);
        //moveItem($client, $driveId, $itemid,$parentId);
        //copyItem($client, $driveId, $itemid,$parentId);
    }


    // $itemname='folderfolder33';
    // $itemoldname='folderfolder22';
    // $itemId = '01FJOJ76GM23VY2NICABGIPIE6YUJHLVW6';

    // $localPath = __DIR__ . '\LocalDrive/'.$itemoldname;
    // echo $localPath;

    //updateItem($client, $driveId, $itemId,$itemname, $localPath);



    //$itemid = 'Newfolder(3)';
    //$parentId = '01FJOJ76F6Y2GOVW7725BZO354PWSELRRZ';
    //$parentName = 'folderfolder33';
    //$itemName = '123.txt';
    // $itemname='folderfolder55';


    //$itemname='test.txt';
    //$itemId = '01FJOJ76HVOEY4F75Z4RHZRVXGKDCPEOZW';

    //delta($client, $driveId);
    //deltaByToken($client, $driveId, $tokendelta);
    //copyItem($client, $driveId, $itemid, $parentId);
    //moveItem($client, $driveId, $itemid,$parentId);
    //downloadFolderByPath($client, $driveId, $itemname);
    //downloadFolder($client, $driveId, $itemname,$localDirectory);
    //createFolder($client, $driveId, $itemname,$localDirectory);
    //downloadItemByPath($client, $driveId, $itemName);
    //uploadItem($client, $driveId, $itemName,$parentId);
    //uploadItemtoPath($client, $driveId, $itemName,$parentName);
    //deleteItem($client, $driveId, $itemid,$localDirectory);
    //listItemById($client, $driveId, $itemId);
    //listItemByPath($client, $driveId, $itemPath);
    //listItems($client, $driveId);
    //updateItem($client, $driveId, $itemId, $itemname);
    //getItemById($client, $driveId, $itemId);
    //getItems($client, $driveId);
    //updateItem($client, $driveId, $itemId, $itemname, $localPath);
    //copyItem($client, $driveId, $itemId, $parentId, $localDirectory);

    function downloadFolder($client, $driveId, $itemname, $localDirectory)
    {
        // List the contents of the folder
        $folderContents = $client->drive($driveId)->delta();

        //$data = json_decode($folderContents, true);
        //$fodleelle = $data['parentReference'];
        echo $folderContents;
        if (!empty($folderContents)) {
            // Create the local folder to store the downloaded files
            $localFolderPath = $localDirectory . '/' . basename($itemname);
            if (!is_dir($localFolderPath)) {
                mkdir($localFolderPath, 0755, true);
            }

            foreach ($folderContents as $item) {
                if ($item['folder'] && $item['name'] != '.' && $item['name'] != '..') {
                    // If it's a folder, recursively download it
                    downloadFolder($client, $driveId, $item['name'], $localFolderPath);
                } elseif ($item['file']) {
                    // If it's a file, download it
                    $localFilePath = $localFolderPath . '/' . $item['name'];
                    $fileContent = $client->drive($driveId)->downloadItemByPath($item['name']);
                    file_put_contents($localFilePath, $fileContent);
                    echo "Downloaded file: $localFilePath\n";
                }
            }
            echo "Downloaded folder: $localFolderPath\n";
        } else {
            echo "Folder is empty or does not exist: $itemname\n";
        }
    }



    function moveFileLocally($sourcePath, $destinationPath)
    {
        try {
            // Use the rename function to move the file
            if (rename($sourcePath, $destinationPath)) {
                echo "File moved successfully.\n";
            } else {
                echo "Failed to move file.\n";
            }
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error moving file: " . $e->getMessage();
        }
    }





    //   $response = $client
    //  ->drive($driveId)
    //  ->request('https://5jd7y6.sharepoint.com/sites/SPFX3/');
    //  echo $response;

    //01FJOJ76F6Y2GOVW7725BZO354PWSELRRZ
    //Get Item By Id
    // $response = $client
    // ->drive($driveId)
    // ->getItemById('DDD8DF03-889A-42C8-912E-AD79065AB488'); 
    // echo $response;


    //// 817FBE15-48E8-42A5-BC3B-67E38EE00C98

    //Get Item By Path
    // $response = $client
    // ->drive($driveId)
    // ->getItemByPath('Folder For Testing'); 
    // echo $response;


    // //Get Items
    // $response = $client
    // ->drive($driveId)
    // ->getItems(); 
    // echo $response;


    //////  $localDirectory = 'C:\xampp\htdocs\sharepoint-sdk-example-main\src\LocalDrive';

    //Download Item By Path
    //  $response = $client
    //  ->drive($driveId)
    //  ->downloadItemByPath('Folder For Testing');   

    // //// Define the local directory where you want to save the image
    //     $localDirectory = __DIR__ . '/../src/LocalDrive';

    //     $imageContent = $response;
    //     $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . 'Folder For Testing';
    //     file_put_contents($localFilePath, $imageContent);

    //     if (file_put_contents($localFilePath, $imageContent) !== false) {
    //                 echo 'Image saved successfully to ' . $localFilePath;
    //              } else {
    //                 echo 'Failed to save the image.';
    //              }

    //06DE7584-6D93-4339-9E34-CED9AE2C9CDB

    //Download Item By Id
    //  $response = $client
    //  ->drive($driveId)
    //  ->downloadItemById('06DE7584-6D93-4339-9E34-CED9AE2C9CDB');   

    // //Define the local directory where you want to save the image
    //     $localDirectory = __DIR__ . '/../src/LocalDrive';

    //     $imageContent = $response;
    //     $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . '06DE7584-6D93-4339-9E34-CED9AE2C9CDB';
    //     file_put_contents($localFilePath, $imageContent);

    //     if (file_put_contents($localFilePath, $imageContent) !== false) {
    //                 echo 'Image saved successfully to ' . $localFilePath;
    //              } else {
    //                 echo 'Failed to save the image.';
    //              }


    //Creating Folder on Drive
    //              $response = $client
    //  ->drive($driveId)
    //  ->createFolder("Testing Folder2");
    //              echo $response;

    //upload item
    //      $response = $client
    //      ->drive($driveId)
    //    ->uploadItem
    //    ("DeletingFile4.txt",
    //    "Testing For Deleting Item2",
    //    "01FJOJ76F6Y2GOVW7725BZO354PWSELRRZ");
    //     echo $response;

    //Upload item to path
    //     $response = $client
    //      ->drive($driveId)
    //    ->uploadItemToPath
    //    ("TestingFileUpload11.txt",
    //    "Testing For Uploading Item 1",
    //    "Testing Folder");
    //     echo $response;

    //Deleting Item (First Upload a item and then use its id for deletion)
    // $response = $client
    // ->drive($driveId)
    // ->deleteItem('2DA211FE-36F8-4B99-A9B4-BE1ABA5EAAD8'); 
    // echo $response;


    //  $response = $client
    //     ->drive($driveId)
    //     ->deleteItemByPath('5.txt'); 
    //     echo $response;

    //Move Item (First Upload a item and then use its id and parent id from
    //where you want to move a item)
    // $response = $client
    // ->drive($driveId)
    // ->moveItem('817FBE15-48E8-42A5-BC3B-67E38EE00C98','01FJOJ76F6Y2GOVW7725BZO354PWSELRRZ'); 
    // echo $response;

    //Copy Item (First Upload a item and then use its id and parent id from
    //where you want to copy a item)
    // $response = $client
    // ->drive($driveId)
    // ->copyItem('6F8573F0-0441-445B-BC24-8A20C83445C1','01FJOJ76F6Y2GOVW7725BZO354PWSELRRZ'); 
    // echo $response;

    //List Items By Id
    // $response = $client
    // ->drive($driveId)
    // ->listById('01FJOJ76A2TUDVG2UK2JEY25PEFIDDUQGE'); 
    // echo $response;

    //List Items By Path
    // $response = $client
    // ->drive($driveId)
    // ->listByPath('Testing Folder2'); 
    // echo $response;

    // //List Items
    // $response = $client
    // ->drive($driveId)
    // ->listItems(); 
    // echo $response;

    //Update Item
    // $response = $client
    //     ->drive($driveId)
    //     ->updateItem('817FBE15-48E8-42A5-BC3B-67E38EE00C98', [
    //         'name' => 'new-file-name33.txt'
    //     ]);
    // echo $response;

    //First Time Delta Call
    // $response = $client
    // ->drive($driveId)
    // ->delta(); 
    // echo $response;

    //After Changes(Modification) delta call for track changes
    // $response = $client
    // ->drive($driveId)
    // ->delta('NDslMjM0OyUyMzE7MztkYWQwYzFjYy1kMTFjLTRmOTQtODEwMS0wYmIwMzU3MjE3YWM7NjM4MzE4MzYyOTkzMDMwMDAwOzIwNzQzMDI0NzslMjM7JTIzOyUyMzA7JTIz'); 
    // echo $response;



    // $response = $client
    // ->drive('b!A1_K8Zkwa0ikSugI16DH_QsyIfJNq29CitDnlep5wSHMwdDaHNGUT4EBC7A1ches')
    // ->getItemByPath('Folder For Testing'); 
    // echo $response;
    //   		// $foldername=$_POST['$response'];
    // if(!is_dir($foldername)) mkdir($foldername);
    // foreach($_FILES['files']['name'] as $i => $name)
    // {
    //     if(strlen($_FILES['files']['name'][$i]) > 1)
    //     {  move_uploaded_file($_FILES['files']['tmp_name'][$i],$foldername."/".$name);
    //     }
    // }
    // echo "Folder is successfully uploaded";









    //   if(isset($_POST['upload']))
    //   {
    //   	if($_POST['foldername'] != "")
    //   	{
    //   		$foldername=$_POST['foldername'];
    //   		if(!is_dir($foldername)) mkdir($foldername);
    //   		foreach($_FILES['files']['name'] as $i => $name)
    // 		{
    //   		    if(strlen($_FILES['files']['name'][$i]) > 1)
    //   		    {  move_uploaded_file($_FILES['files']['tmp_name'][$i],$foldername."/".$name);
    //   		    }
    //   		}
    //   		echo "Folder is successfully uploaded";
    //   	}
    //   	else
    //   	    echo "Upload folder name is empty";
    //   }







    // var_dump($response->json());


    //$result = $graphServiceClient->drives()->byDriveId('drive-id')->items()->byDriveItemId('driveItem-id')->delta()->get()->wait();





    //01FJOJ76A36PK2DDGVZRBIDX7AIWW3G53A  parent id
    //817FBE15-48E8-42A5-BC3B-67E38EE00C98 item id


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

    ?>
</body>

</html>