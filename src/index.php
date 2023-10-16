
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

    // if ($token === null) {
    //     $token = $client->getRefreshToken()->serialize();
    //     store_token($token);
    // }


    //refreshAccessToken();

    $auth = AccessTokenAuthenticator::unserialize($token);
    $client->authenticate($auth);
    echo $token;

    //Local Directory Path
    $localDirectory = __DIR__ . '/../src/LocalDrive';

    //Get Token of Delta Response
    $tokendelta =  @file_get_contents(__DIR__ . '/../storage/deltaToken') ?: null;
    //$deltaResponse =  @file_get_contents(__DIR__ . '/../storage/deltaResponse.js') ?: null;


    //Store Logs
    function store_log($messagelog)
    {
        $logFilePath = __DIR__ . '/../src/log.log';
        $logFile = fopen($logFilePath, 'a');

        if ($logFile) {
            date_default_timezone_set('Asia/Karachi');
            $message = $messagelog . date('Y-m-d H:i:s') . ".\n";

            fwrite($logFile, $message);
            fclose($logFile);
        } else {
            echo "Unable to open or create the log file.";
        }
    }


    if ($tokendelta === null) {
        delta($client, $driveId);
    } else {
        deltaByToken($client, $driveId, $tokendelta);  
    }


    // delta($client, $driveId);

    //First Time Delta Call
    //Give Information of All Files/Folders in JSON
    function delta($client, $driveId)
    {
        $response = $client->drive($driveId)->delta();

        // Save the new response to the file
        $filePath = __DIR__ . '/../storage/deltaResponse';
        file_put_contents($filePath, $response);

        $data = json_decode($response, true);
        $tokendelta = substr($data['@odata.deltaLink'], 124, 151); // Extract from position 3 to 38

        // Save the token to another file
        $tokenFilePath = __DIR__ . '/../storage/deltaToken';
        file_put_contents($tokenFilePath, $tokendelta);

        //echo $response; // Optional: Display the new response
    }


    function for_Create_Item($client, $driveId, $data)
    {

        //if new item has created/uploaded
        //count($data['value'])
        // Check if the 'value' array exists in the JSON data
        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                $item = $data['value'][$i];

                // Check if 'id' and 'name' keys exist in the current item
                if (isset($item['id']) && isset($item['name'])) {
                    $itemid = $item['id'];
                    $itemname = $item['name'];
                    $createdDateTime = $item['createdDateTime'];
                    $lastModifiedDateTime = $item['lastModifiedDateTime'];

                    // Convert the date and time to a string
                    $createdDateTimeString = date('Y-m-d H:i:s', strtotime($createdDateTime));
                    $lastModifiedDateTimeString = date('Y-m-d H:i:s', strtotime($lastModifiedDateTime));

                    //     if ($createdDateTimeString === $lastModifiedDateTimeString) {

                    //         //$localDirectory = __DIR__ . '/../src/LocalDrive';

                    //        // downloadItemByIdLocally($client, $driveId, $itemname, $itemid, $localDirectory);
                    //         //delta($client, $driveId);

                    //     $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                    //     $mappingDatabase = json_decode($mappingFile, true);
                    //     $remoteItemId = $itemid;
                    //     if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                    //         // Start iterating from the second element (index 1)
                    //         for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
                    //             $itemDatabase = $mappingDatabase['value'][$j];
                    //             $remoteItemIdNew = $remoteItemId;
                    //             //echo $remoteItemIdNew;


                    //             // Check if 'id' and 'name' keys exist in the current item
                    //             if (isset($itemDatabase['id']) && $itemDatabase['id'] != $remoteItemIdNew )  {

                    //                 // if ($createdDateTimeString === $lastModifiedDateTimeString) {

                    //                      $localDirectory = __DIR__ . '/../src/LocalDrive';

                    //                      downloadItemByIdLocally($client, $driveId, $itemname, $itemid, $localDirectory);
                    //                 //     //delta($client, $driveId);
                    //                 // }

                    //             } else {
                    //                 //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    //             }

                    //         }
                    //     } else {
                    //         echo "Error: 'value' array not found in the JSON response.\n";
                    //     }
                    // }


                    if ($createdDateTimeString === $lastModifiedDateTimeString) {

                        $localDirectory = __DIR__ . '/../src/LocalDrive';
                        downloadItemByIdLocally($client, $driveId, $itemname, $itemid, $localDirectory);
                        //delta($client, $driveId);
                    }


                    // $localDirectory = __DIR__ . '/../src/LocalDrive';
                    // downloadItemByIdLocally($client, $driveId, $itemname, $itemid, $localDirectory);
                } else {
                    echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                }
            }
        } else {
            echo "Error: 'value' array not found in the JSON response.\n";
        }
    }

    function for_Rename_Item($client, $driveId, $data)
    {
        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                $item = $data['value'][$i];

                // Check if 'id' and 'name' keys exist in the current item
                if (isset($item['createdDateTime']) && isset($item['lastModifiedDateTime'])) {
                    $createdDateTime = $item['createdDateTime'];
                    $lastModifiedDateTime = $item['lastModifiedDateTime'];
                    $itemid = $item['id'];
                    $itemNewName = $item['name'];
                    $itemParentId = $item['parentReference']['id'];



                    // Convert the date and time to a string
                    $createdDateTimeString = date('Y-m-d H:i:s', strtotime($createdDateTime));
                    $lastModifiedDateTimeString = date('Y-m-d H:i:s', strtotime($lastModifiedDateTime));
                    if ($createdDateTimeString !== $lastModifiedDateTimeString) {

                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        $remoteItemId = $itemid;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
                                $itemDatabase = $mappingDatabase['value'][$j];
                                $remoteItemIdNew = $remoteItemId;
                                //echo $remoteItemIdNew;


                                // Check if 'id' and 'name' keys exist in the current item
                                if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

                                    $itemOldName = $itemDatabase['name'];
                                    //echo $itemnameDatabase;

                                    //$localPath = __DIR__ . '\LocalDrive/'. $itemnameDatabase;
                                    //updateItemLocally($client, $driveId, $itemid, $itemname, $localPath);
                                } else {
                                    //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                }
                            }
                        } else {
                            echo "Error: 'value' array not found in the JSON response.\n";
                        }

                        $itemOldNameOld = $itemOldName;
                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        $remoteItemParentId = $itemParentId;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                                $itemDatabase = $mappingDatabase['value'][$k];
                                $remoteItemParentIdNew = $remoteItemParentId;
                                //echo $remoteItemParentIdNew;


                                // Check if 'id' and 'name' keys exist in the current item
                                if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemParentIdNew) {

                                    $itemParentnameDatabase = $itemDatabase['name'];
                                    $itemParentWebUrl = $itemDatabase['webUrl'];
                                    //echo $itemParentWebUrl;

                                    // // Find the position of "Library1" in the URL
                                    $libraryPosition = strpos($itemParentWebUrl, "Library1");

                                    if ($libraryPosition !== false) {
                                        // Extract the value after "Library1" and everything after it
                                        $value = substr($itemParentWebUrl, $libraryPosition + strlen("Library1"));
                                        echo "Extracted value: " . $value;
                                    } else {
                                        echo "Value not found in the URL.";
                                    }

                                    echo "Extracted value: " . $value;

                                    if ($value === ' ') {
                                        $itemOldNameOldOld = $itemOldNameOld;
                                        //echo $itemnameDatabaseOriginalNew;
                                        $localPath = __DIR__ . '\LocalDrive/' . $itemOldNameOldOld;
                                        echo $localPath;
                                        updateItemLocally($client, $driveId, $itemid, $itemNewName, $localPath);
                                        //delta($client, $driveId);
                                    } else {


                                        $itemOldNameOldOld = $itemOldNameOld;
                                        //echo $itemnameDatabaseOriginalNew;
                                        $localPath = __DIR__ . '\LocalDrive' . $value . "/" . $itemOldNameOldOld;
                                        echo $localPath;
                                        updateItemLocally($client, $driveId, $itemid, $itemNewName, $localPath);
                                        // delta($client, $driveId);
                                    }
                                } else {
                                    //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                }
                            }
                        } else {
                            echo "Error: 'value' array not found in the JSON response.\n";
                        }
                    }
                    // delta($client, $driveId);
                } else {
                    echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                }
            }
        } else {
            echo "Error: 'value' array not found in the JSON response.\n";
        }
    }

    function for_delete_Item($client, $driveId, $data)
    {
        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                $item = $data['value'][$i];

                // Check if 'id' and 'name' keys exist in the current item
                if (isset($item['deleted']) && $item['deleted']['state'] === 'deleted') {

                    $itemid = $item['id'];
                    //$itemname = $item['name'];

                    $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                    $mappingDatabase = json_decode($mappingFile, true);
                    $remoteItemId = $itemid;
                    if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                        // Start iterating from the second element (index 1)
                        for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
                            $itemDatabase = $mappingDatabase['value'][$j];
                            $remoteItemIdNew = $remoteItemId;
                            //echo $remoteItemIdNew;
                            // Check if 'id' and 'name' keys exist in the current item
                            if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

                                $itemOldNameOld = $itemDatabase['name'];
                                $itemWebUrl = $itemDatabase['webUrl'];

                                // // Find the position of "Library1" in the URL
                                $libraryPosition = strpos($itemWebUrl, "Library1");

                                if ($libraryPosition !== false) {
                                    // Extract the value after "Library1" and everything after it
                                    $value = substr($itemWebUrl, $libraryPosition + strlen("Library1"));
                                    echo "Extracted value: " . $value;
                                } else {
                                    echo "Value not found in the URL.";
                                }

                                //echo "Extracted value: " . $value;

                                $localDirectory = __DIR__ . '\LocalDrive' . $value;
                                deleteItemlocally($client, $driveId, $remoteItemIdNew, $itemOldNameOld, $localDirectory);
                            } else {
                                //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                            }
                            //delta($client, $driveId);
                        }
                        delta($client, $driveId);
                    } else {
                        echo "Error: 'value' array not found in the JSON response.\n";
                    }
                } else {
                    echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                }
            }
        } else {
            echo "Error: 'value' array not found in the JSON response.\n";
        }
    }


    function for_moving_Item($client, $driveId, $data)
    {
        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                $item = $data['value'][$i];

                // Check if 'id' and 'name' keys exist in the current item
                if (isset($item['id']) && isset($item['name'])) {
                    //$webUrl = $item['webUrl'];
                    // $lastModifiedDateTime = $item['lastModifiedDateTime'];
                    $itemid = $item['id'];
                    $itemname = $item['name'];
                    $parentReferencecId = $item['parentReference']['id'];
                    //echo $webUrl;
                    //echo $itemid;
                    //echo $itemname;



                    $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                    $mappingDatabase = json_decode($mappingFile, true);
                    //$remoteItemWebUrl = $webUrl;
                    $remoteItemId = $itemid;
                    if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                        // Start iterating from the second element (index 1)
                        for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
                            $itemDatabase = $mappingDatabase['value'][$j];
                            //$remoteItemWebUrlNew = $remoteItemWebUrl;
                            $remoteItemIdNew = $remoteItemId;
                            //echo $remoteItemIdNew;
                            //echo $remoteItemWebUrlNew;

                            // Check if 'id' and 'name' keys exist in the current item
                            if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

                                $itemUrlDatabase = $itemDatabase['webUrl'];

                                // // Find the position of "Library1" in the URL
                                $libraryPosition = strpos($itemUrlDatabase, "Library1");

                                if ($libraryPosition !== false) {
                                    // Extract the value after "Library1" and everything after it
                                    $valueItem = substr($itemUrlDatabase, $libraryPosition + strlen("Library1"));
                                    echo "Extracted value: " . $valueItem;
                                } else {
                                    echo "Value not found in the URL.";
                                }

                                //echo $itemNameDatabase;
                                // $file=__DIR__ . '\LocalDrive/' . $itemname;
                                // $to = __DIR__ . '\LocalDrive/'. $itemNameDatabase;
                                // echo $file;
                                // echo $to;
                                // move_file($file, $to);

                                // if($itemNameDatabase === 'root'){
                                //     $file=__DIR__ . '\LocalDrive/' . $itemname;
                                //     $to = __DIR__ . '\LocalDrive/';
                                //     echo $file;
                                //     echo $to;
                                //     move_file($file, $to);
                                //     //recursive_files_copy($file, $to); 
                                // }
                                // else{
                                //     $file=__DIR__ . '\LocalDrive/' . $itemname;
                                //     $to = __DIR__ . '\LocalDrive/'. $itemNameDatabase;

                                //     echo $file;
                                //     echo $to;
                                //     //recursive_files_copy($file, $to); 
                                //     move_file($file, $to);
                                // }
                                //$itemnameDatabaseParent = '4000';

                            } else {
                                //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                            }
                        }
                    } else {
                        echo "Error: 'value' array not found in the JSON response.\n";
                    }

                    $valueItemnew = $valueItem;
                    $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                    $mappingDatabase = json_decode($mappingFile, true);
                    //$remoteItemWebUrl = $webUrl;
                    $remoteparentReferencecId = $parentReferencecId;
                    if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                        // Start iterating from the second element (index 1)
                        for ($j = 0; $j <= count($mappingDatabase['value']); $j++) {
                            $itemDatabase = $mappingDatabase['value'][$j];
                            //$remoteItemWebUrlNew = $remoteItemWebUrl;
                            $remoteremoteparentReferencecIdNew = $remoteparentReferencecId;
                            //echo $remoteItemIdNew;
                            //echo $remoteItemWebUrlNew;

                            // Check if 'id' and 'name' keys exist in the current item
                            if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteremoteparentReferencecIdNew) {

                                $itemUrlDatabaseParent = $itemDatabase['webUrl'];

                                // // Find the position of "Library1" in the URL
                                $libraryPosition = strpos($itemUrlDatabaseParent, "Library1");

                                if ($libraryPosition !== false) {
                                    // Extract the value after "Library1" and everything after it
                                    $valueParent = substr($itemUrlDatabaseParent, $libraryPosition + strlen("Library1"));
                                    echo "Extracted value: " . $valueParent;
                                } else {
                                    echo "Value not found in the URL.";
                                }


                                $file = __DIR__ . '\LocalDrive/' . $valueItemnew;
                                $to = __DIR__ . '\LocalDrive/' . $valueParent;
                                echo $file;
                                echo $to;
                                move_file_Locally($file, $to);

                                // if($itemNameDatabase === 'root'){
                                //     $file=__DIR__ . '\LocalDrive/' . $itemname;
                                //     $to = __DIR__ . '\LocalDrive/';
                                //     echo $file;
                                //     echo $to;
                                //     move_file($file, $to);
                                //     //recursive_files_copy($file, $to); 
                                // }
                                // else{
                                //     $file=__DIR__ . '\LocalDrive/' . $itemname;
                                //     $to = __DIR__ . '\LocalDrive/'. $itemNameDatabase;

                                //     echo $file;
                                //     echo $to;
                                //     //recursive_files_copy($file, $to); 
                                //     move_file($file, $to);
                                // }
                                //$itemnameDatabaseParent = '4000';

                            } else {
                                //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                            }
                        }
                    } else {
                        echo "Error: 'value' array not found in the JSON response.\n";
                    }
                } else {
                    echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                }
            }
        } else {
            echo "Error: 'value' array not found in the JSON response.\n";
        }
    }

    //Delta By Token
    //Track Changes of Files/Folders on SharePoint
    function deltaByToken($client, $driveId, $tokendelta)
    {
        try {
            $response = $client->drive($driveId)->delta($tokendelta);
            $data = json_decode($response, true);
            $deltaLink = $data['@odata.deltaLink'];
            $startIndex = strpos($deltaLink, "token='") + 7; // starting position of the token
            $endIndex = strpos($deltaLink, "')", $startIndex); // ending position of the token
            $tokendelta = substr($deltaLink, $startIndex, $endIndex - $startIndex);    
            // Save the token to another file
            $tokenFilePath = __DIR__ . '/../storage/deltaToken';
            file_put_contents($tokenFilePath, $tokendelta);
            
            echo $response;
            //if new item has created/uploaded
            //count($data['value'])
            // Check if the 'value' array exists in the JSON data
            // if (isset($data['value']) && is_array($data['value'])) {
            //     // Start iterating from the second element (index 1)
            //     for ($i = 1; $i <= count($data['value']); $i++) {
            //         $item = $data['value'][$i];

            //         // Check if 'id' and 'name' keys exist in the current item
            //         if (isset($item['id']) && isset($item['name'])) {
            //             $itemid = $item['id'];
            //             $itemname = $item['name'];
            //             $createdDateTime = $item['createdDateTime'];
            //             $lastModifiedDateTime = $item['lastModifiedDateTime'];

            //             // Convert the date and time to a string
            //             $createdDateTimeString = date('Y-m-d H:i:s', strtotime($createdDateTime));
            //             $lastModifiedDateTimeString = date('Y-m-d H:i:s', strtotime($lastModifiedDateTime));

            //         //     if ($createdDateTimeString === $lastModifiedDateTimeString) {

            //         //         //$localDirectory = __DIR__ . '/../src/LocalDrive';

            //         //        // downloadItemByIdLocally($client, $driveId, $itemname, $itemid, $localDirectory);
            //         //         //delta($client, $driveId);

            //         //     $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            //         //     $mappingDatabase = json_decode($mappingFile, true);
            //         //     $remoteItemId = $itemid;
            //         //     if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
            //         //         // Start iterating from the second element (index 1)
            //         //         for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
            //         //             $itemDatabase = $mappingDatabase['value'][$j];
            //         //             $remoteItemIdNew = $remoteItemId;
            //         //             //echo $remoteItemIdNew;


            //         //             // Check if 'id' and 'name' keys exist in the current item
            //         //             if (isset($itemDatabase['id']) && $itemDatabase['id'] != $remoteItemIdNew )  {

            //         //                 // if ($createdDateTimeString === $lastModifiedDateTimeString) {

            //         //                      $localDirectory = __DIR__ . '/../src/LocalDrive';

            //         //                      downloadItemByIdLocally($client, $driveId, $itemname, $itemid, $localDirectory);
            //         //                 //     //delta($client, $driveId);
            //         //                 // }

            //         //             } else {
            //         //                 //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //         //             }

            //         //         }
            //         //     } else {
            //         //         echo "Error: 'value' array not found in the JSON response.\n";
            //         //     }
            //         // }


            //             if ($createdDateTimeString === $lastModifiedDateTimeString) {

            //                 $localDirectory = __DIR__ . '/../src/LocalDrive';
            //                 downloadItemByIdLocally($client, $driveId, $itemname, $itemid, $localDirectory);
            //                 //delta($client, $driveId);
            //             }


            //             // $localDirectory = __DIR__ . '/../src/LocalDrive';
            //             // downloadItemByIdLocally($client, $driveId, $itemname, $itemid, $localDirectory);
            //         } else {
            //             echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //         }
            //     }
            // } else {
            //     echo "Error: 'value' array not found in the JSON response.\n";
            // }

            for_Create_Item($client, $driveId, $data);

            //if item has changed
            // if (isset($data['value']) && is_array($data['value'])) {
            //     // Start iterating from the second element (index 1)
            //     for ($i = 1; $i <= count($data['value']); $i++) {
            //         $item = $data['value'][$i];

            //         // Check if 'id' and 'name' keys exist in the current item
            //         if (isset($item['createdDateTime']) && isset($item['lastModifiedDateTime'])) {
            //             $createdDateTime = $item['createdDateTime'];
            //             $lastModifiedDateTime = $item['lastModifiedDateTime'];
            //             $itemid = $item['id'];
            //             $itemNewName = $item['name'];
            //             $itemParentId = $item['parentReference']['id'];



            //             // Convert the date and time to a string
            //             $createdDateTimeString = date('Y-m-d H:i:s', strtotime($createdDateTime));
            //             $lastModifiedDateTimeString = date('Y-m-d H:i:s', strtotime($lastModifiedDateTime));
            //             if ($createdDateTimeString !== $lastModifiedDateTimeString) {

            //                 $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            //                 $mappingDatabase = json_decode($mappingFile, true);
            //                 $remoteItemId = $itemid;
            //                 if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
            //                     // Start iterating from the second element (index 1)
            //                     for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
            //                         $itemDatabase = $mappingDatabase['value'][$j];
            //                         $remoteItemIdNew = $remoteItemId;
            //                         //echo $remoteItemIdNew;


            //                         // Check if 'id' and 'name' keys exist in the current item
            //                         if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

            //                             $itemOldName = $itemDatabase['name'];
            //                             //echo $itemnameDatabase;

            //                             //$localPath = __DIR__ . '\LocalDrive/'. $itemnameDatabase;
            //                             //updateItemLocally($client, $driveId, $itemid, $itemname, $localPath);
            //                         } else {
            //                             //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //                         }
            //                     }
            //                 } else {
            //                     echo "Error: 'value' array not found in the JSON response.\n";
            //                 }

            //                 $itemOldNameOld = $itemOldName;
            //                 $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            //                 $mappingDatabase = json_decode($mappingFile, true);
            //                 $remoteItemParentId = $itemParentId;
            //                 if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
            //                     // Start iterating from the second element (index 1)
            //                     for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
            //                         $itemDatabase = $mappingDatabase['value'][$k];
            //                         $remoteItemParentIdNew = $remoteItemParentId;
            //                         //echo $remoteItemParentIdNew;


            //                         // Check if 'id' and 'name' keys exist in the current item
            //                         if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemParentIdNew) {

            //                             $itemParentnameDatabase = $itemDatabase['name'];
            //                             $itemParentWebUrl = $itemDatabase['webUrl'];
            //                             //echo $itemParentWebUrl;

            //                             // // Find the position of "Library1" in the URL
            //                             $libraryPosition = strpos($itemParentWebUrl, "Library1");

            //                             if ($libraryPosition !== false) {
            //                                 // Extract the value after "Library1" and everything after it
            //                                 $value = substr($itemParentWebUrl, $libraryPosition + strlen("Library1"));
            //                                 echo "Extracted value: " . $value;
            //                             } else {
            //                                 echo "Value not found in the URL.";
            //                             }

            //                             echo "Extracted value: " . $value;

            //                             if ($value === ' ') {
            //                                 $itemOldNameOldOld = $itemOldNameOld;
            //                                 //echo $itemnameDatabaseOriginalNew;
            //                                 $localPath = __DIR__ . '\LocalDrive/' . $itemOldNameOldOld;
            //                                 echo $localPath;
            //                                 updateItemLocally($client, $driveId, $itemid, $itemNewName, $localPath);
            //                                 //delta($client, $driveId);
            //                             } else {


            //                                 $itemOldNameOldOld = $itemOldNameOld;
            //                                 //echo $itemnameDatabaseOriginalNew;
            //                                 $localPath = __DIR__ . '\LocalDrive' . $value . "/" . $itemOldNameOldOld;
            //                                 echo $localPath;
            //                                 updateItemLocally($client, $driveId, $itemid, $itemNewName, $localPath);
            //                                 // delta($client, $driveId);
            //                             }
            //                         } else {
            //                             //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //                         }
            //                     }
            //                 } else {
            //                     echo "Error: 'value' array not found in the JSON response.\n";
            //                 }
            //             }
            //            // delta($client, $driveId);
            //         } else {
            //             echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //         }
            //     }
            // } else {
            //     echo "Error: 'value' array not found in the JSON response.\n";
            // }

            for_Rename_Item($client, $driveId, $data);

            //if item has deleted 
            // if (isset($data['value']) && is_array($data['value'])) {
            //     // Start iterating from the second element (index 1)
            //     for ($i = 1; $i <= count($data['value']); $i++) {
            //         $item = $data['value'][$i];

            //         // Check if 'id' and 'name' keys exist in the current item
            //         if (isset($item['deleted']) && $item['deleted']['state'] === 'deleted') {

            //             $itemid = $item['id'];
            //             //$itemname = $item['name'];

            //             $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            //             $mappingDatabase = json_decode($mappingFile, true);
            //             $remoteItemId = $itemid;
            //             if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
            //                 // Start iterating from the second element (index 1)
            //                 for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
            //                     $itemDatabase = $mappingDatabase['value'][$j];
            //                     $remoteItemIdNew = $remoteItemId;
            //                     //echo $remoteItemIdNew;
            //                     // Check if 'id' and 'name' keys exist in the current item
            //                     if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

            //                         $itemOldNameOld = $itemDatabase['name'];
            //                         $itemWebUrl = $itemDatabase['webUrl'];

            //                         // // Find the position of "Library1" in the URL
            //                         $libraryPosition = strpos($itemWebUrl, "Library1");

            //                         if ($libraryPosition !== false) {
            //                             // Extract the value after "Library1" and everything after it
            //                             $value = substr($itemWebUrl, $libraryPosition + strlen("Library1"));
            //                             echo "Extracted value: " . $value;
            //                         } else {
            //                             echo "Value not found in the URL.";
            //                         }

            //                         //echo "Extracted value: " . $value;

            //                         $localDirectory = __DIR__ . '\LocalDrive' . $value;
            //                         deleteItemlocally($client, $driveId, $remoteItemIdNew, $itemOldNameOld, $localDirectory);
            //                     } else {
            //                         //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //                     }
            //                     //delta($client, $driveId);
            //                 }
            //                 delta($client, $driveId);
            //             } else {
            //                 echo "Error: 'value' array not found in the JSON response.\n";
            //             }
            //         } else {
            //             echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //         }
            //     }
            // } else {
            //     echo "Error: 'value' array not found in the JSON response.\n";
            // }

            for_delete_Item($client, $driveId, $data);

            //if item has Moved
            //   if (isset($data['value']) && is_array($data['value'])) {
            //     // Start iterating from the second element (index 1)
            //     for ($i = 1; $i <= count($data['value']); $i++) {
            //         $item = $data['value'][$i];

            //         // Check if 'id' and 'name' keys exist in the current item
            //         if (isset($item['id']) && isset($item['name'])) {
            //             //$webUrl = $item['webUrl'];
            //             // $lastModifiedDateTime = $item['lastModifiedDateTime'];
            //              $itemid = $item['id'];
            //              $itemname = $item['name'];
            //              $parentReferencecId = $item['parentReference']['id'];
            //                 //echo $webUrl;
            //                 //echo $itemid;
            //                 //echo $itemname;



            //                 $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            //                 $mappingDatabase = json_decode($mappingFile, true);
            //                 //$remoteItemWebUrl = $webUrl;
            //                 $remoteItemId = $itemid;
            //                 if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
            //                     // Start iterating from the second element (index 1)
            //                     for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
            //                         $itemDatabase = $mappingDatabase['value'][$j];
            //                         //$remoteItemWebUrlNew = $remoteItemWebUrl;
            //                         $remoteItemIdNew = $remoteItemId;
            //                         //echo $remoteItemIdNew;
            //                         //echo $remoteItemWebUrlNew;

            //                         // Check if 'id' and 'name' keys exist in the current item
            //                         if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

            //                             $itemUrlDatabase = $itemDatabase['webUrl'];

            //                             // // Find the position of "Library1" in the URL
            //                             $libraryPosition = strpos($itemUrlDatabase, "Library1");

            //                             if ($libraryPosition !== false) {
            //                                 // Extract the value after "Library1" and everything after it
            //                                 $valueItem = substr($itemUrlDatabase, $libraryPosition + strlen("Library1"));
            //                                 echo "Extracted value: " . $valueItem;
            //                             } else {
            //                                 echo "Value not found in the URL.";
            //                             }

            //                             //echo $itemNameDatabase;
            //                                 // $file=__DIR__ . '\LocalDrive/' . $itemname;
            //                                 // $to = __DIR__ . '\LocalDrive/'. $itemNameDatabase;
            //                                 // echo $file;
            //                                 // echo $to;
            //                                 // move_file($file, $to);

            //                             // if($itemNameDatabase === 'root'){
            //                             //     $file=__DIR__ . '\LocalDrive/' . $itemname;
            //                             //     $to = __DIR__ . '\LocalDrive/';
            //                             //     echo $file;
            //                             //     echo $to;
            //                             //     move_file($file, $to);
            //                             //     //recursive_files_copy($file, $to); 
            //                             // }
            //                             // else{
            //                             //     $file=__DIR__ . '\LocalDrive/' . $itemname;
            //                             //     $to = __DIR__ . '\LocalDrive/'. $itemNameDatabase;

            //                             //     echo $file;
            //                             //     echo $to;
            //                             //     //recursive_files_copy($file, $to); 
            //                             //     move_file($file, $to);
            //                             // }
            //                             //$itemnameDatabaseParent = '4000';

            //                         } else {
            //                             //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //                         }
            //                     }
            //                 } else {
            //                     echo "Error: 'value' array not found in the JSON response.\n";
            //                 }

            //                 $valueItemnew=$valueItem;
            //                 $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
            //                 $mappingDatabase = json_decode($mappingFile, true);
            //                 //$remoteItemWebUrl = $webUrl;
            //                 $remoteparentReferencecId = $parentReferencecId;
            //                 if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
            //                     // Start iterating from the second element (index 1)
            //                     for ($j = 0; $j <= count($mappingDatabase['value']); $j++) {
            //                         $itemDatabase = $mappingDatabase['value'][$j];
            //                         //$remoteItemWebUrlNew = $remoteItemWebUrl;
            //                         $remoteremoteparentReferencecIdNew = $remoteparentReferencecId;
            //                         //echo $remoteItemIdNew;
            //                         //echo $remoteItemWebUrlNew;

            //                         // Check if 'id' and 'name' keys exist in the current item
            //                         if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteremoteparentReferencecIdNew) {

            //                             $itemUrlDatabaseParent = $itemDatabase['webUrl'];

            //                             // // Find the position of "Library1" in the URL
            //                             $libraryPosition = strpos($itemUrlDatabaseParent, "Library1");

            //                             if ($libraryPosition !== false) {
            //                                 // Extract the value after "Library1" and everything after it
            //                                 $valueParent = substr($itemUrlDatabaseParent, $libraryPosition + strlen("Library1"));
            //                                 echo "Extracted value: " . $valueParent;
            //                             } else {
            //                                 echo "Value not found in the URL.";
            //                             }


            //                                 $file=__DIR__ . '\LocalDrive/' . $valueItem;
            //                                 $to = __DIR__ . '\LocalDrive/'. $valueParent;
            //                                 echo $file;
            //                                 echo $to;
            //                                 move_file_Locally($file, $to);

            //                             // if($itemNameDatabase === 'root'){
            //                             //     $file=__DIR__ . '\LocalDrive/' . $itemname;
            //                             //     $to = __DIR__ . '\LocalDrive/';
            //                             //     echo $file;
            //                             //     echo $to;
            //                             //     move_file($file, $to);
            //                             //     //recursive_files_copy($file, $to); 
            //                             // }
            //                             // else{
            //                             //     $file=__DIR__ . '\LocalDrive/' . $itemname;
            //                             //     $to = __DIR__ . '\LocalDrive/'. $itemNameDatabase;

            //                             //     echo $file;
            //                             //     echo $to;
            //                             //     //recursive_files_copy($file, $to); 
            //                             //     move_file($file, $to);
            //                             // }
            //                             //$itemnameDatabaseParent = '4000';

            //                         } else {
            //                             //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //                         }
            //                     }
            //                 } else {
            //                     echo "Error: 'value' array not found in the JSON response.\n";
            //                 }


            //         } else {
            //             echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
            //         }
            //     }
            // } else {
            //     echo "Error: 'value' array not found in the JSON response.\n";
            // }

            for_moving_Item($client, $driveId, $data);

        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }




    //Download File/Folder on Local Directory By File/Folder Id and Name and Path (where to download)
    function downloadItemByIdLocally($client, $driveId, $itemname, $itemId, $localDirectory)
    {
        // Define the local file/folder path
        $localFilePath = $localDirectory . '/' . $itemname;

        //echo $localFilePath;

        // Check if the item (file or folder) already exists locally
        if (file_exists($localFilePath)) {
            echo "Item already exists at: $localFilePath\n";
            return;
        } else {
            // Get information about the item
            $itemInfo = $client->drive($driveId)->getItemById($itemId);
            //echo $itemInfo;
            if ($itemInfo !== false) {
                $data = json_decode($itemInfo, true);
                $Name = $data['name'];
                $ID = $data['id'];

                if ($data['folder']) {
                    // If the item is a folder, create the local folder
                    if (mkdir($localFilePath, 0777, true)) {
                        echo "Folder created successfully at: $localFilePath\n";
                        $messagelog =  "Folder created successfully at: $localFilePath\n";
                        store_log($messagelog);

                        // Recursively download the contents of the folder
                        $children = $client->drive($driveId)->listById($itemId);
                        $data = json_decode($children, true);
                        //echo $data;
                        if (isset($data['value']) && is_array($data['value'])) {
                            // Iterate through the children items
                            foreach ($data['value'] as $child) {

                                downloadItemByIdLocally($client, $driveId, $child['name'], $child['id'], $localFilePath);
                            }
                        } else {
                            echo "Error: 'value' array not found in the JSON response.\n";
                        }
                    } else {
                        echo "Failed to create folder at: $localFilePath\n";
                    }
                } else {
                    // If the item is a file, download and save it
                    $response = $client->drive($driveId)->downloadItemById($itemId);
                    if ($response !== false) {
                        if (file_put_contents($localFilePath, $response) !== false) {
                            echo "File saved successfully to $localFilePath\n";

                            $messagelog =  "File created successfully at: $localFilePath\n";
                            store_log($messagelog);
                        } else {
                            echo "Failed to save the file to $localFilePath\n";
                        }
                    } else {
                        echo "Failed to download the file.\n";
                    }
                }
            } else {
                echo "Failed to get item information.\n";
            }
        }
    }


    //Get Single File/Folder By File/Folder Id from SharePoint Directory
    function getItemByIdSharePoint($client, $driveId, $itemId)
    {
        try {
            $response = $client->drive($driveId)->getItemById($itemId);
            // If the operation was successful, display a success message
            echo "Item retrieved successfully: " . $response;
            $messagelog =  "Item retrieved successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //Get Single File/Folder By File/Folder Path(Name) from SharePoint Directory
    function getItemByPathSharePoint($client, $driveId, $itemPath)
    {
        try {
            $response = $client->drive($driveId)->getItemByPath($itemPath);
            // If the operation was successful, display a success message
            echo "Item retrieved successfully: " . $response;
            $messagelog =  "Item retrieved successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }

    //Get All Files/Folders from SharePoint Directory
    function getItemsSharePoint($client, $driveId)
    {
        try {
            $response = $client->drive($driveId)->getItems();
            // If the operation was successful, display a success message
            echo "Items Retrieved successfully: " . $response;
            $messagelog =  "Items retrieved successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //Delete File/Folder on SharePoint Directory By File/Folder Id
    function deleteItemSharePoint($client, $driveId, $itemId)
    {
        try {
            $response = $client->drive($driveId)->deleteItem($itemId);
            // If the operation was successful, display a success message
            echo "Item Deleted successfully on SharePoint: " . $itemId;
            //echo $response;
            $messagelog =  "Item Deleted successfully on SharePoint: $itemId\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //Delete File/Folder from both SharePoint Directory and Local Directory by File/Folder Path(Name)
    //Delete File/Folder on SharePoint By File/Folder Name
    //Delete File/Folder on Local Directory File/Folder Name and Path (where file is located)

    // function deleteItemBothByName($client, $driveId, $itemId,$itemName, $localDirectory)
    // {
    //     // Delete the item on SharePoint
    //     $response = $client->drive($driveId)->deleteItemByPath($itemName);

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

    // Recursive function to delete a directory and its contents on Local Directory

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


    //Delete File/Folder from both SharePoint Directory and Local Directory By File/Folder Id
    //Delete File/Folder on SharePoint By File/Folder Id
    //Delete File/Folder on Local Directory File/Folder Name and Path (where file is located)

    // function deleteItemBoth($client, $driveId, $itemId, $itemName, $localDirectory)
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

    //$localDirectory = __DIR__ . '/../src/LocalDrive/';
    //$itemName='2(1).txt'
    //Delete File/Folder from Local Directory By File/Folder Path(Name)
    //deleteItemlocally($client, $driveId, $itemId, $itemName, $localDirectory);
    function deleteItemlocally($client, $driveId, $itemId, $itemName, $localDirectory)
    {
        ///$itemName = basename($itemId);
        //$localItemPath = $localDirectory . '/' . $itemName;
        $localItemPath = $localDirectory;
        echo $localItemPath;
        if (is_dir($localItemPath) === true) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($localItemPath), RecursiveIteratorIterator::CHILD_FIRST);

            foreach ($files as $file) {
                if (in_array($file->getBasename(), array('.', '..')) !== true) {
                    if ($file->isDir() === true) {
                        rmdir($file->getPathName());
                    } else if (($file->isFile() === true) || ($file->isLink() === true)) {
                        unlink($file->getPathname());
                    }
                }
            }
            echo "Local Item Deleted Successfully at $localItemPath\n";
            $messagelog =  "Local Item Deleted Successfully at: $localItemPath\n";
            store_log($messagelog);

            return rmdir($localItemPath);
        } else if ((is_file($localItemPath) === true) || (is_link($localItemPath) === true)) {
            echo "Local Item Deleted Successfully at $localItemPath\n";
            $messagelog =  "Local Item Deleted Successfully at: $localItemPath\n";
            store_log($messagelog);

            return unlink($localItemPath);
        }

        return false;
    }


    //Move File/Folder on SharePoint Directory by File/Folder Id and Parent Id
    //If we want to move in a specific folder then use its id (Parent Id) Otherwise
    //Parent Id is root id
    function moveItemSharePoint($client, $driveId, $itemId, $parentId)
    {
        try {
            $response = $client->drive($driveId)->moveItem($itemId, $parentId);
            // If the operation was successful, display a success message
            echo "Item Moved successfully on SharePoint: " . $response;
            $messagelog =  "Item Moved successfully on SharePoint:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //Move File/Folder on Local Directory By Source File Name and Destination File Name
    function move_file_Locally($file, $to)
    {
        $path_parts = pathinfo($file);
        $newplace = "$to/{$path_parts['basename']}";

        if (rename($file, $newplace)) {
            $messagelog = "File/Folder $file Moved successfully on Local Directory at: $newplace\n";
            store_log($messagelog);
            return $newplace;
        } else {
            return null;
        }
    }


    //Copy File/Folder on SharePoint Directory by File/Folder Id and Parent Id
    //If we want to move in a specific folder then use its id (Parent Id) Otherwise
    //Parent Id is root id
    function copyItemSharePoint($client, $driveId, $itemId, $parentId)
    {
        try {
            $response = $client->drive($driveId)->copyItem($itemId, $parentId);
            // If the operation was successful, display a success message
            echo "Item Copied successfully on SharePoint: " . $response;
            $messagelog =  "Item Copied successfully on SharePoint:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //Copy File/Folder on Local Directory By Source File Name and Destination File Name
    //Empty Folder not Copy
    function copyFilesLocally($source_dir, $destination_dir)
    {
        // Open the source folder / directory 
        $dir = opendir($source_dir);

        // Create a destination folder / directory if not exist 
        @mkdir($destination_dir);

        // Loop through the files in source directory 
        while ($file = readdir($dir)) {
            // Skip . and .. 
            if (($file != '.') && ($file != '..')) {
                // Check if it's folder / directory or file 
                if (is_dir($source_dir . '/' . $file)) {
                    // Recursively calling this function for sub directory  
                    copyFilesLocally($source_dir . '/' . $file, $destination_dir . '/' . $file);
                } else {
                    // Copying the files
                    copy($source_dir . '/' . $file, $destination_dir . '/' . $file);
                }
            }
        }

        closedir($dir);
    }


    //Update(Rename) File/Folder on Local Directory By File/Folder Name
    //and File/Folder Path(where file is located on local directory)
    function updateItemLocally($client, $driveId, $itemId, $itemNewName, $localPath)
    {
        try {

            // Update the local directory
            if (file_exists($localPath)) {
                $newLocalPath = dirname($localPath) . '/' . $itemNewName;
                echo $newLocalPath;
                if (rename($localPath, $newLocalPath)) {
                    echo "Local file/directory updated successfully.";
                    $messagelog =  "Local file/directory updated successfully: $newLocalPath\n";
                    store_log($messagelog);
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


    // Update(Rename) File/Folder on both SharePoint and Local Directory
    //Rename File/Folder on SharePoint By File/Folder Id and New Name
    //Rename File/Folder on Local Directory By Old Name and New Name
    function updateItemBoth($client, $driveId, $itemId, $itemname, $localPath)
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
            $messagelog =  "Item Updated successfully on SharePoint: $response\n";
            store_log($messagelog);

            // Update the local directory
            if (file_exists($localPath)) {
                $newLocalPath = dirname($localPath) . '/' . $itemname;
                //echo $newLocalPath;
                if (rename($localPath, $newLocalPath)) {
                    echo "Local file/directory updated successfully.";

                    $messagelog =  "Local file/directory updated successfully: $newLocalPath\n";
                    store_log($messagelog);
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


    //Update(Rename) File/Folder on SharePoint By File/Folder Id and its New Name
    function updateItemSharePoint($client, $driveId, $itemId, $itemname)
    {
        try {
            $response = $client->drive($driveId)->updateItem(
                $itemId,
                [
                    'name' => $itemname
                ]
            );
            // If the operation was successful, display a success message
            echo "Item Updated successfully on SharePoint: " . $response;
            $messagelog =  "Item Updated successfully on SharePoint: $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //List Single File/Folder By File/Folder Id from SharePoint 
    function listItemByIdSharePoint($client, $driveId, $itemId)
    {
        try {
            $response = $client->drive($driveId)->listById($itemId);
            // // If the operation was successful, display a success message
            echo "Item Listed successfully: " . $response;
            $messagelog =  "Item Listed successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            //// If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //List Single File/Folder By File/Folder Path(Name) from SharePoint
    function listItemByPathSharePoint($client, $driveId, $itemPath)
    {
        try {
            $response = $client->drive($driveId)->listByPath($itemPath);
            // If the operation was successful, display a success message
            echo "Item Listed successfully: " . $response;
            $messagelog =  "Item Listed successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //List All Files/Folders from SharePoint
    function listItemsSharePoint($client, $driveId)
    {
        try {
            $response = $client->drive($driveId)->listItems();
            // If the operation was successful, display a success message
            echo "Item Listed successfully: " . $response;
            $messagelog =  "Item Listed successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
    }


    //Create Folder on both SharePoint directory and Local directory 
    //Create Folder on SharePoint by Folder Name 
    //Create Folder on Local Directory by Folder Name and Path of Local Directory
    function createFolderBoth($client, $driveId, $itemPath, $localDirectory)
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

    //Create Folder on Local Directory by Folder Name and Path of Local Directory
    function createFolderLocally($client, $driveId, $itemPath, $localDirectory)
    {
        $localFolder = $localDirectory . '/' . $itemPath;

        if (mkdir($localFolder)) {
            echo "Local Folder Created Successfully at $localFolder\n";
            $messagelog =  "Local Folder Created Successfully at: $localFolder\n";
            store_log($messagelog);
        } else {
            echo "Failed to create Local Folder\n";
        }
    }

    //Create Folder on SharePoint directory By Folder Name
    function createFolderSharePoint($client, $driveId, $itemPath, $localDirectory)
    {
        // Create the folder on SharePoint
        $response = $client->drive($driveId)->createFolder($itemPath);

        // Check if the SharePoint folder was created successfully
        if ($response) {
            echo "SharePoint Folder Created Successfully!\n";
            $messagelog =  "SharePoint Folder Created Successfully: \n";
            store_log($messagelog);
        } else {
            echo "Failed to create SharePoint Folder\n";
        }
    }


    //Upload File/Folder on SharePoint to Root by File/Folder Name and
    // its Content(if it is a file) and Root Id (ParentId)
    //and Download in Local Directory By Id
    function uploadItemSharePoint($client, $driveId, $itemName, $parentId, $localDirectory)
    {
        try {
            $response = $client->drive($driveId)->uploadItem($itemName, $itemName, $parentId);
            $data = json_decode($response, true);
            $itemid = $data['id'];
            $itemname = $data['name'];
            // If the operation was successful, display a success message
            echo "Item Upload successfully: " . $response;
            $messagelog =  "Item Upload successfully: $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
        //getItemById($client, $driveId, $itemid);
        //getItemByPath($client, $driveId, $itemname);
        //downloadItemById($client, $driveId, $itemname, $itemid);
        //downloadItemByPath($client, $driveId, $itemname);
        //createFolder($client, $driveId, $itemname);
        //createFolder($client, $driveId, $itemName,$localDirectory);
        //deleteItem($client, $driveId, $itemid);
    }


    //Upload File/Folder on SharePoint in Specific Folder by File/Folder Name and
    // its Content(if it is a file) and Specific Folder Name 
    //and Download in Local Directory By Id
    function uploadItemtoPathSharePoint($client, $driveId, $itemName, $parentName, $localDirectory)
    {
        try {
            $response = $client->drive($driveId)->uploadItemToPath($itemName, $itemName, $parentName);
            $data = json_decode($response, true);

            $itemid =  $data['id'];
            $itemname = $data['name'];

            // If the operation was successful, display a success message
            echo "Item Upload successfully: " . $response;
            $messagelog =  "Item Upload successfully: $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            echo "Error: " . $e->getMessage();
        }
        // getItemById($client, $driveId, $itemid);
        //getItemByPath($client, $driveId, $itemname);
        //downloadItemById($client, $driveId, $itemname, $itemid, $localDirectory);
        //downloadItemByPath($client, $driveId, $itemname);
        //createFolder($client, $driveId, $itemname);
        //createFolder($client, $driveId, $itemName,$localDirectory);
        //moveItem($client, $driveId, $itemid,$parentId);
        //copyItem($client, $driveId, $itemid,$parentId);
    }




    // $source_dir = __DIR__ . '/../src/LocalDrive/f234';    
    // $destination_dir = __DIR__ . '/../src/LocalDrive/4000';
    // recursive_files_copy($source_dir, $destination_dir);


    //Move File/Folder from one directory to another on Local Directory
    //  $file = __DIR__ . '/../src/LocalDrive/f1/f23444';
    //  $to =  __DIR__ . '/../src/LocalDrive/';
    //  move_file_Locally($file, $to);



    //    $logFilePath = 'logs/my_log_file.log';
    //    $logFile = fopen($logFilePath, 'a'); // 'a' mode for appending to the file
    //    $message = "Something happened at " . date('Y-m-d H:i:s') . ": This is a log message.\n";
    //    fwrite($logFile, $message);
    //    fclose($logFile);
    // $localDirectory = __DIR__ . '/../src/LocalDrive';


    //  $itemname='f789New';
    //  $itemoldname='f789';
    //  $itemId = '01FJOJ76HKTALENHONCZGYD6HGT457JEFT';

    //$localPath = __DIR__ . '\LocalDrive/4000/'.$itemoldname;
    // echo $localPath;

    //updateItem($client, $driveId, $itemId,$itemname, $localPath);



    //$itemid = 'Newfolder(3)';
    //$parentId = '01FJOJ76F6Y2GOVW7725BZO354PWSELRRZ';
    //$parentName = 'folder789';
    //$itemName = 'Conflict.txt';
    //$itemname = '4000';
    //$itemPath = '400000000';
    //01FJOJ76EIEB2ZRHWCNBCIFAZHAOSPHPTK
    //$itemname='DemoFileRenameAgain.txt';
    // $itemId = '01FJOJ76EHVARO536YANFIGY3L3CIP47GA';

    //delta($client, $driveId);
    //deltaByToken($client, $driveId, $tokendelta);
    //copyItem($client, $driveId, $itemid, $parentId);
    //moveItem($client, $driveId, $itemid,$parentId);
    //downloadFolderByPath($client, $driveId, $itemname);
    //downloadFolder($client, $driveId, $itemname,$localDirectory);
    //createFolder($client, $driveId, $itemname,$localDirectory);
    //createFolderBoth($client, $driveId, $itemPath, $localDirectory)
    //createFolderLocally($client, $driveId, $itemname,$localDirectory);
    //createFolderSharePoint($client, $driveId, $itemPath, $localDirectory);
    //downloadItemByPath($client, $driveId, $itemName);
    //uploadItem($client, $driveId, $itemName,$parentId);
    //uploadItemSharePoint($client, $driveId, $itemName, $parentId, $localDirectory);
    //uploadItemtoPath($client, $driveId, $itemName,$parentName,$localDirectory);
    //deleteItem($client, $driveId, $itemid,$localDirectory);
    //deleteItemBothByName($client, $driveId,$itemId,$itemName, $localDirectory)
    //deleteItemSharePoint($client, $driveId, $itemId);
    ///listItemById($client, $driveId, $itemId);
    //listItemByPath($client, $driveId, $itemPath);
    //listItems($client, $driveId);
    //updateItem($client, $driveId, $itemId, $itemname,$localDirectory);
    //updateItemSharePoint($client, $driveId, $itemId, $itemname)
    //getItemById($client, $driveId, $itemId);
    //getItems($client, $driveId);
    //updateItem($client, $driveId, $itemId, $itemname, $localPath);
    //updateItemLocally($client, $driveId, $itemId, $itemname, $localPath);
    //updateItemBoth($client, $driveId, $itemId, $itemname, $localPath)
    //copyItem($client, $driveId, $itemId, $parentId, $localDirectory);
    //deleteItemlocally($client, $driveId, $itemId, $itemName, $localDirectory);
    //deleteItemlocally($client, $driveId, $itemId, $itemName, $localDirectory);




    //Download Item on Local Directory By Name
    //  function downloadItemByPath($client, $driveId, $itemname, $itemId)
    //  {

    //      // Define the local directory where you want to save the item
    //      $localDirectory = __DIR__ . '/../src/LocalDrive';

    //      // Define the local file/folder path
    //      $localFilePath = $localDirectory . DIRECTORY_SEPARATOR . $itemname;

    //      // Check if the item (file or folder) already exists locally
    //      if (file_exists($localFilePath)) {
    //          echo "Item already exists at: $localFilePath\n";
    //      } else {
    //          // Get information about the item
    //          $itemInfo = $client->drive($driveId)->getItemByPath($itemname);

    //          //if ($itemInfo !== false) {
    //          $data = json_decode($itemInfo, true);
    //          if ($data['folder']) {
    //              // If the item is a folder, create the local folder
    //              if (mkdir($localFilePath, 0777, true)) {
    //                  echo "Folder created successfully at: $localFilePath\n";
    //              } else {
    //                  echo "Failed to create folder at: $localFilePath\n";
    //              }

    //              // Recursively download the contents of the folder
    //              $children = $client->drive($driveId)->listById($itemId);

    //              foreach ($children as $child) {
    //                  downloadItemByPath($client, $driveId, $child['name'], $child['id']);
    //              }
    //          } else {
    //              // If the item is a file, download and save it
    //              $response = $client->drive($driveId)->downloadItemByPath($itemname);
    //              if ($response !== false) {
    //                  if (file_put_contents($localFilePath, $response) !== false) {
    //                      echo "File saved successfully to $localFilePath\n";
    //                  } else {
    //                      echo "Failed to save the file to $localFilePath\n";
    //                  }
    //              } else {
    //                  echo "Failed to download the file.\n";
    //              }
    //          }

    //          //}
    //          //}
    //          // else {
    //          //     echo "Failed to get item information.\n";
    //          // }
    //      }
    //  }









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
    // ->deleteItem('01FJOJ76FWPKNLI3WZVFDLZCYQNA4YUCCQ'); 
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




    // $url = "https://5jd7y6.sharepoint.com/sites/SPFX3/Library1/4000/777777.txt";

    // // Define a regular expression pattern to match the value between the last two slashes
    // $pattern = "/\/([^\/]+)\/([^\/]+)\.txt$/";

    // // Use preg_match to find the value
    // if (preg_match($pattern, $url, $matches)) {
    //     $value = $matches[1]; // The value you want (4000) will be in $matches[1]
    //     echo "Extracted value: " . $value;
    // } else {
    //     echo "Value not found in the URL.";
    // }




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
