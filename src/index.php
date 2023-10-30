
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

    // $token = get_token();


    // if ($token === null) {
    //     $token = $client->getAccessToken()->serialize();
    //     store_token($token);
    //     echo $token;
    // }

    $token = $client->getAccessToken()->serialize();
    $auth = AccessTokenAuthenticator::unserialize($token);
    $client->authenticate($auth);


    //Local Directory Path
    $localDirectory = __DIR__ . '/../src/LocalDrive';

    //Get Token of Delta Response
    $tokendelta =  @file_get_contents(__DIR__ . '/../storage/deltaToken') ?: null;

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


    function disable_Warnings()
    {
        // Save the current error reporting level
        $previousErrorReporting = error_reporting();

        // Disable warnings
        error_reporting($previousErrorReporting & ~E_WARNING);
        // Restore the previous error reporting level
        error_reporting($previousErrorReporting);
    }

    if ($tokendelta === null) {
        delta();
    } else {
        deltaByToken($tokendelta);
    }



    //$itemId='01FJOJ76G5X5GSX2TE4ZB2FZYVPYPOOM5E';
    //$fileInfo = $client->drive($driveId)->getItemById($itemId);
    //echo $fileInfo;



    //First Time Delta Call
    //Give Information of All Files/Folders in JSON
    function delta()
    {
        global $client;
        global $driveId;

        // Set the timezone to Pakistani Standard Time (PKT)
        //date_default_timezone_set('Asia/Karachi');

        // Display a message when the job starts
        // $startTime = date('d-m-Y h:i:s A'); // Use 'h:i A' format for time with AM/PM
        //echo "Job started at $startTime (PKT)<br>";

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

        // Display a message when the job is completed
        //$endTime = date('d-m-Y h:i:s A'); // Use 'h:i A' format for time with AM/PM
        //echo "Job completed at $endTime (PKT)<br>";

    }


    //This functions track changes of files/folders created/uploaded on SharePoint
    function function_for_Create_Item($data)
    {
        global $client;
        global $driveId;

        disable_Warnings();
        $value = '';
        // Check if the 'value' array exists in the JSON data
        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                if (isset($data['value'][$i])) {
                    $item = $data['value'][$i];
                    disable_Warnings();
                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($item['id']) && isset($item['name'])) {
                        $itemid = $item['id'];
                        $createdDateTime = $item['createdDateTime'];
                        $lastModifiedDateTime = $item['lastModifiedDateTime'];
                        $itemPath = $item['webUrl'];
                        $parentReferencecId = $item['parentReference']['id'];
                        $itemname = $item['name'];
                        echo $itemname;

                        $fileExtension = pathinfo($itemname, PATHINFO_EXTENSION);

                        if ($fileExtension === 'txt') {
                             // Convert the date and time to a string
                        $createdDateTimeString = date('Y-m-d H:i:s', strtotime($createdDateTime));
                        $lastModifiedDateTimeString = date('Y-m-d H:i:s', strtotime($lastModifiedDateTime));

                        // // Find the position of "Library1" in the URL
                        $libraryPosition = strpos($itemPath, "Library1");

                        if ($libraryPosition !== false) {
                            // Extract the value after "Library1" and everything after it
                            $value = substr($itemPath, $libraryPosition + strlen("Library1"));
                        } else {
                            //echo "Value not found in the URL.";
                        }

                        disable_Warnings();
                        if ($createdDateTimeString === $lastModifiedDateTimeString) {


                            $localDirectory = __DIR__ . '/../src/LocalDrive';
                            downloadItemByIdLocally($itemname, $itemid, $localDirectory, $value);
                            //delta();
                        }
                        } elseif ($fileExtension === 'docx' || $fileExtension === 'pdf' || $fileExtension === 'xlsx' || $fileExtension === 'pptx') {

                             // Convert the date and time to a string
                        $createdDateTimeString = date('Y-m-d H:i:s', strtotime($createdDateTime));
                        $lastModifiedDateTimeString = date('Y-m-d H:i:s', strtotime($lastModifiedDateTime));

                        

                        disable_Warnings();
                        if ($createdDateTimeString === $lastModifiedDateTimeString) {

                            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                            $mappingDatabase = json_decode($mappingFile, true);

                            $remoteparentReferencecId = $parentReferencecId;
                            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                                // Start iterating from the second element (index 1)
                                for ($j = 0; $j <= count($mappingDatabase['value']); $j++) {
                                    if (isset($mappingDatabase['value'][$j])) {
                                        $itemDatabase = $mappingDatabase['value'][$j];

                                        $remoteremoteparentReferencecIdNew = $remoteparentReferencecId;


                                        // Check if 'id' and 'name' keys exist in the current item
                                        if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteremoteparentReferencecIdNew) {

                                            $itemUrlDatabaseParent = $itemDatabase['webUrl'];

                                            // // Find the position of "Library1" in the URL
                                            $libraryPosition = strpos($itemUrlDatabaseParent, "Library1");

                                            if ($libraryPosition !== false) {
                                                // Extract the value after "Library1" and everything after it
                                                $valueParent = substr($itemUrlDatabaseParent, $libraryPosition + strlen("Library1"));
                                            } else {
                                                // echo "Value not found in the URL.";
                                            }

                                            if ($valueParent === ' ') {
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                downloadItemByIdLocally($itemname, $itemid, $localDirectory, $itemname);

                                                // delta();
                                            } else {

                                                $valueParent =  $valueParent . "/" . $itemname;
                           
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                downloadItemByIdLocally($itemname, $itemid, $localDirectory, $valueParent);

                                                //delta();
                                            }

                                           
                                            
                                            
                                            //delta();
                                        } else {
                                            //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                        }
                                    }
                                }
                            } else {
                                // echo "Error: 'value' array not found in the JSON response.\n";
                            }


                                                        //delta();
                        }
                        else if($createdDateTimeString !== $lastModifiedDateTimeString){
                            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                            $mappingDatabase = json_decode($mappingFile, true);

                            $remoteparentReferencecId = $parentReferencecId;
                            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                                // Start iterating from the second element (index 1)
                                for ($j = 0; $j <= count($mappingDatabase['value']); $j++) {
                                    if (isset($mappingDatabase['value'][$j])) {
                                        $itemDatabase = $mappingDatabase['value'][$j];

                                        $remoteremoteparentReferencecIdNew = $remoteparentReferencecId;


                                        // Check if 'id' and 'name' keys exist in the current item
                                        if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteremoteparentReferencecIdNew) {

                                            $itemUrlDatabaseParent = $itemDatabase['webUrl'];

                                            // // Find the position of "Library1" in the URL
                                            $libraryPosition = strpos($itemUrlDatabaseParent, "Library1");

                                            if ($libraryPosition !== false) {
                                                // Extract the value after "Library1" and everything after it
                                                $valueParent = substr($itemUrlDatabaseParent, $libraryPosition + strlen("Library1"));
                                            } else {
                                                // echo "Value not found in the URL.";
                                            }

                                            if ($valueParent === ' ') {
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                downloadItemByIdLocally($itemname, $itemid, $localDirectory, $itemname);

                                                // delta();
                                            } else {

                                                $valueParent =  $valueParent . "/" . $itemname;
                           
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                downloadItemByIdLocally($itemname, $itemid, $localDirectory, $valueParent);

                                                //delta();
                                            }

                                           
                                            
                                            
                                            //delta();
                                        } else {
                                            //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                        }
                                    }
                                }
                            } else {
                                // echo "Error: 'value' array not found in the JSON response.\n";
                            }
                        }
                        } else {
                            echo "Not a .txt or .docx file";
                        }

                    } else {
                        // echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
            }
        } else {
            //echo "Error: 'value' array not found in the JSON response.\n";
        }
        disable_Warnings();
    }

    //This functions track changes of files/folders Renamed on SharePoint
    function function_for_Rename_Item($data)
    {

        global $client;
        global $driveId;
        disable_Warnings();

        $itemOldName = '';
        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                if (isset($data['value'][$i])) {
                    $item = $data['value'][$i];

                    // Check if 'id' and 'name' keys exist in the current item and it is folder
                    if (isset($item['createdDateTime']) && isset($item['lastModifiedDateTime']) && isset($item['folder']) && isset($item['folder']['childCount'])) {
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
                                    if (isset($mappingDatabase['value'][$j])) {
                                        $itemDatabase = $mappingDatabase['value'][$j];
                                        $remoteItemIdNew = $remoteItemId;
                                        // Check if 'id' and 'name' keys exist in the current item
                                        if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

                                            $itemOldName = $itemDatabase['name'];
                                        } else {
                                            //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                        }
                                    }
                                }
                            } else {
                                //echo "Error: 'value' array not found in the JSON response.\n";
                            }

                            $itemOldNameOld = $itemOldName;
                            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                            $mappingDatabase = json_decode($mappingFile, true);
                            $remoteItemParentId = $itemParentId;
                            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                                // Start iterating from the second element (index 1)
                                for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                                    if (isset($mappingDatabase['value'][$k])) {
                                        $itemDatabase = $mappingDatabase['value'][$k];
                                        $remoteItemParentIdNew = $remoteItemParentId;

                                        // Check if 'id' and 'name' keys exist in the current item
                                        if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemParentIdNew) {

                                            $itemParentnameDatabase = $itemDatabase['name'];
                                            $itemParentWebUrl = $itemDatabase['webUrl'];
                                            // // Find the position of "Library1" in the URL
                                            $libraryPosition = strpos($itemParentWebUrl, "Library1");
                                            if ($libraryPosition !== false) {
                                                // Extract the value after "Library1" and everything after it
                                                $value = substr($itemParentWebUrl, $libraryPosition + strlen("Library1"));
                                            } else {
                                                //echo "Value not found in the URL.";
                                            }



                                            if ($value === ' ') {
                                                $itemOldNameOldOld = $itemOldNameOld;

                                                $localPath = __DIR__ . '\LocalDrive/' . $itemOldNameOldOld;

                                                updateItemLocally($itemNewName, $localPath);
                                                // delta();
                                            } else {


                                                $itemOldNameOldOld = $itemOldNameOld;

                                                $localPath = __DIR__ . '\LocalDrive' . $value . "/" . $itemOldNameOldOld;

                                                updateItemLocally($itemNewName, $localPath);
                                                //delta();
                                            }
                                        } else {
                                            //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                        }
                                    }
                                }
                            } else {
                                // echo "Error: 'value' array not found in the JSON response.\n";
                            }
                        }
                    } else {
                        //  echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }

                    $itemOldName = '';
                    $valueitemPath = '';
                    disable_Warnings();
                    // Check if 'id' and 'name' keys exist in the current item and it is file
                    if (isset($item['createdDateTime']) && isset($item['lastModifiedDateTime']) && isset($item['file'])) {
                        $createdDateTime = $item['createdDateTime'];
                        $lastModifiedDateTime = $item['lastModifiedDateTime'];
                        $itemid = $item['id'];
                        $itemNewName = $item['name'];
                        $itemParentId = $item['parentReference']['id'];
                        $itemPath = $item['webUrl'];



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
                                    if (isset($mappingDatabase['value'][$j])) {
                                        $itemDatabase = $mappingDatabase['value'][$j];
                                        $remoteItemIdNew = $remoteItemId;



                                        // Check if 'id' and 'name' keys exist in the current item
                                        if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

                                            $itemOldName = $itemDatabase['name'];
                                        } else {
                                            //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                        }
                                    }
                                }
                            } else {
                                //echo "Error: 'value' array not found in the JSON response.\n";
                            }

                            $itemOldNameOld = $itemOldName;
                            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                            $mappingDatabase = json_decode($mappingFile, true);
                            $remoteItemParentId = $itemParentId;
                            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                                // Start iterating from the second element (index 1)
                                for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                                    if (isset($mappingDatabase['value'][$k])) {
                                        $itemDatabase = $mappingDatabase['value'][$k];
                                        $remoteItemParentIdNew = $remoteItemParentId;



                                        // Check if 'id' and 'name' keys exist in the current item
                                        if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemParentIdNew) {

                                            $itemParentnameDatabase = $itemDatabase['name'];
                                            $itemParentWebUrl = $itemDatabase['webUrl'];


                                            // // Find the position of "Library1" in the URL
                                            $libraryPosition = strpos($itemParentWebUrl, "Library1");

                                            if ($libraryPosition !== false) {
                                                // Extract the value after "Library1" and everything after it
                                                $value = substr($itemParentWebUrl, $libraryPosition + strlen("Library1"));
                                            } else {
                                                //echo "Value not found in the URL.";
                                            }

                                            // // Find the position of "Library1" in the URL
                                            $libraryPosition = strpos($itemPath, "Library1");

                                            if ($libraryPosition !== false) {
                                                // Extract the value after "Library1" and everything after it
                                                $valueitemPath = substr($itemPath, $libraryPosition + strlen("Library1"));
                                            } else {
                                                //echo "Value not found in the URL.";
                                            }


                                            if ($value === ' ') {
                                                $itemOldNameOldOld = $itemOldNameOld;
                                                $localPath = __DIR__ . '\LocalDrive/' . $itemOldNameOldOld;
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                deleteItemlocally($localPath);
                                                downloadItemByIdLocally($itemNewName, $itemid, $localDirectory, $valueitemPath);
                                                //delta();
                                            } else {
                                                $itemOldNameOldOld = $itemOldNameOld;
                                                $localPath = __DIR__ . '\LocalDrive' . $value . "/" . $itemOldNameOldOld;
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                deleteItemlocally($localPath);
                                                downloadItemByIdLocally($itemNewName, $itemid, $localDirectory, $valueitemPath);
                                                //delta();
                                            }
                                        } else {
                                            //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                        }
                                    }
                                }
                            } else {
                                // echo "Error: 'value' array not found in the JSON response.\n";
                            }
                        }
                    } else {
                        //  echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
            }
        } else {
            // echo "Error: 'value' array not found in the JSON response.\n";
        }
    }

    //This functions track changes of files/folders deleted on SharePoint
    function function_for_delete_Item($data)
    {
        global $client;
        global $driveId;

        disable_Warnings();

        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                if (isset($data['value'][$i])) {
                    $item = $data['value'][$i];

                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($item['deleted']) && $item['deleted']['state'] === 'deleted') {

                        $itemid = $item['id'];


                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        $remoteItemId = $itemid;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
                                if (isset($mappingDatabase['value'][$j])) {
                                    $itemDatabase = $mappingDatabase['value'][$j];
                                    $remoteItemIdNew = $remoteItemId;

                                    // Check if 'id' and 'name' keys exist in the current item
                                    if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

                                        $itemOldNameOld = $itemDatabase['name'];
                                        $itemWebUrl = $itemDatabase['webUrl'];

                                        // // Find the position of "Library1" in the URL
                                        $libraryPosition = strpos($itemWebUrl, "Library1");

                                        if ($libraryPosition !== false) {
                                            // Extract the value after "Library1" and everything after it
                                            $value = substr($itemWebUrl, $libraryPosition + strlen("Library1"));
                                        } else {
                                            // echo "Value not found in the URL.";
                                        }



                                        $localDirectory = __DIR__ . '\LocalDrive' . $value;
                                        deleteItemlocally($localDirectory);
                                        // delta();
                                    } else {
                                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                    }
                                }
                            }
                        } else {
                            // echo "Error: 'value' array not found in the JSON response.\n";
                        }
                    } else {
                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
            }
        } else {
            //echo "Error: 'value' array not found in the JSON response.\n";
        }
    }

    //This functions track changes of files/folders moved on SharePoint
    function function_for_moving_Item($data)
    {
        global $client;
        global $driveId;
        $valueItem = '';
        $valueItemWebUrl = '';
        $valueItemWebUrlNew = '';
        disable_Warnings();
        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                if (isset($data['value'][$i])) {
                    $item = $data['value'][$i];

                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($item['createdDateTime']) && isset($item['lastModifiedDateTime'])) {
                        $createdDateTime = $item['createdDateTime'];
                        $lastModifiedDateTime = $item['lastModifiedDateTime'];
                        $itemid = $item['id'];
                        $itemname = $item['name'];
                        $itemWebUrl = $item['webUrl'];
                        $parentReferencecId = $item['parentReference']['id'];


                        // // Find the position of "Library1" in the URL
                        $libraryPosition = strpos($itemWebUrl, "Library1");

                        if ($libraryPosition !== false) {
                            // Extract the value after "Library1" and everything after it
                            $valueItemWebUrl = substr($itemWebUrl, $libraryPosition + strlen("Library1"));
                        } else {
                            //echo "Value not found in the URL.";
                        }



                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);

                        $remoteItemId = $itemid;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
                                if (isset($mappingDatabase['value'][$j])) {
                                    $itemDatabase = $mappingDatabase['value'][$j];

                                    $remoteItemIdNew = $remoteItemId;


                                    // Check if 'id' and 'name' keys exist in the current item
                                    if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

                                        $itemUrlOld = $itemDatabase['webUrl'];

                                        // // Find the position of "Library1" in the URL
                                        $libraryPosition = strpos($itemUrlOld, "Library1");

                                        if ($libraryPosition !== false) {
                                            // Extract the value after "Library1" and everything after it
                                            $valueItemWebUrlNew = substr($itemUrlOld, $libraryPosition + strlen("Library1"));
                                            //echo "Extracted value: " . $valueItem;
                                        } else {
                                            //echo "Value not found in the URL.";
                                        }
                                        //$valueItemNew=$valueItem;
                                    } else {
                                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                    }
                                }
                            }
                        } else {
                            //echo "Error: 'value' array not found in the JSON response.\n";
                        }

                        $valueItemWebUrlOld = $valueItemWebUrl;
                        $valueItemWebUrlNewNew = $valueItemWebUrlNew;

                        // Convert the date and time to a string
                        $createdDateTimeString = date('Y-m-d H:i:s', strtotime($createdDateTime));
                        $lastModifiedDateTimeString = date('Y-m-d H:i:s', strtotime($lastModifiedDateTime));
                        if ($createdDateTimeString !== $lastModifiedDateTimeString && $valueItemWebUrlOld !== $valueItemWebUrlNewNew) {

                            disable_Warnings();
                            $valueItemnew = $valueItemWebUrlNew;
                            $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                            $mappingDatabase = json_decode($mappingFile, true);

                            $remoteparentReferencecId = $parentReferencecId;
                            if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                                // Start iterating from the second element (index 1)
                                for ($j = 0; $j <= count($mappingDatabase['value']); $j++) {
                                    if (isset($mappingDatabase['value'][$j])) {
                                        $itemDatabase = $mappingDatabase['value'][$j];

                                        $remoteremoteparentReferencecIdNew = $remoteparentReferencecId;


                                        // Check if 'id' and 'name' keys exist in the current item
                                        if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteremoteparentReferencecIdNew) {

                                            $itemUrlDatabaseParent = $itemDatabase['webUrl'];

                                            // // Find the position of "Library1" in the URL
                                            $libraryPosition = strpos($itemUrlDatabaseParent, "Library1");

                                            if ($libraryPosition !== false) {
                                                // Extract the value after "Library1" and everything after it
                                                $valueParent = substr($itemUrlDatabaseParent, $libraryPosition + strlen("Library1"));
                                            } else {
                                                // echo "Value not found in the URL.";
                                            }


                                            $file = __DIR__ . '\LocalDrive/' . $valueItemnew;
                                            $to = __DIR__ . '\LocalDrive/' . $valueParent;

                                            move_file_Locally($file, $to);
                                            //delta();
                                        } else {
                                            //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                        }
                                    }
                                }
                            } else {
                                // echo "Error: 'value' array not found in the JSON response.\n";
                            }
                        }
                    } else {
                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
            }
        } else {
            //echo "Error: 'value' array not found in the JSON response.\n";
        }
    }


    //This functions track changes of files/folders copy on SharePoint
    function function_for_copy_Item($data)
    {
        global $client;
        global $driveId;

        disable_Warnings();
        $valueItem = '';
        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                if (isset($data['value'][$i])) {
                    $item = $data['value'][$i];

                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($item['id']) && isset($item['name'])) {

                        $itemid = $item['id'];
                        $itemname = $item['name'];
                        $parentReferencecId = $item['parentReference']['id'];




                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);

                        $remoteItemId = $itemid;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($j = 1; $j <= count($mappingDatabase['value']); $j++) {
                                if (isset($mappingDatabase['value'][$j])) {
                                    $itemDatabase = $mappingDatabase['value'][$j];

                                    $remoteItemIdNew = $remoteItemId;


                                    // Check if 'id' and 'name' keys exist in the current item
                                    if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteItemIdNew) {

                                        $itemUrlDatabase = $itemDatabase['webUrl'];

                                        // // Find the position of "Library1" in the URL
                                        $libraryPosition = strpos($itemUrlDatabase, "Library1");

                                        if ($libraryPosition !== false) {
                                            // Extract the value after "Library1" and everything after it
                                            $valueItem = substr($itemUrlDatabase, $libraryPosition + strlen("Library1"));
                                        } else {
                                            //echo "Value not found in the URL.";
                                        }
                                    } else {
                                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                    }
                                }
                            }
                        } else {
                            // echo "Error: 'value' array not found in the JSON response.\n";
                        }

                        $valueItemnew = $valueItem;
                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);

                        $remoteparentReferencecId = $parentReferencecId;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($j = 0; $j <= count($mappingDatabase['value']); $j++) {
                                if (isset($mappingDatabase['value'][$j])) {
                                    $itemDatabase = $mappingDatabase['value'][$j];

                                    $remoteremoteparentReferencecIdNew = $remoteparentReferencecId;


                                    // Check if 'id' and 'name' keys exist in the current item
                                    if (isset($itemDatabase['id']) && $itemDatabase['id'] === $remoteremoteparentReferencecIdNew) {

                                        $itemUrlDatabaseParent = $itemDatabase['webUrl'];

                                        // // Find the position of "Library1" in the URL
                                        $libraryPosition = strpos($itemUrlDatabaseParent, "Library1");

                                        if ($libraryPosition !== false) {
                                            // Extract the value after "Library1" and everything after it
                                            $valueParent = substr($itemUrlDatabaseParent, $libraryPosition + strlen("Library1"));
                                        } else {
                                            //echo "Value not found in the URL.";
                                        }


                                        $source_dir = __DIR__ . '\LocalDrive/' . $valueItemnew;
                                        $destination_dir = __DIR__ . '\LocalDrive/' . $valueParent;

                                        copyFilesLocally($source_dir, $destination_dir);
                                        //delta();
                                    } else {
                                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                    }
                                }
                            }
                        } else {
                            // echo "Error: 'value' array not found in the JSON response.\n";
                        }
                    } else {
                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
            }
        } else {
            // echo "Error: 'value' array not found in the JSON response.\n";
        }
    }


    //Delta By Token
    //Track Changes of Files/Folders on SharePoint
    function deltaByToken($tokendelta)
    {

        global $client;
        global $driveId;

        disable_Warnings();
        try {
            $response = $client->drive($driveId)->delta($tokendelta);
            echo $response;
            $data = json_decode($response, true);
            $deltaLink = $data['@odata.deltaLink'];
            $startIndex = strpos($deltaLink, "token='") + 7; // starting position of the token
            $endIndex = strpos($deltaLink, "')", $startIndex); // ending position of the token
            $tokendelta = substr($deltaLink, $startIndex, $endIndex - $startIndex);
            // Save the token to another file
            $tokenFilePath = __DIR__ . '/../storage/deltaToken';
            file_put_contents($tokenFilePath, $tokendelta);

            // Set the timezone to Pakistani Standard Time (PKT)
            date_default_timezone_set('Asia/Karachi');

            // Display a message when the job starts
            $startTime = date('d-m-Y h:i:s A'); // Use 'h:i A' format for time with AM/PM
            echo "Job started at $startTime <br>";

            //if item has created/uploaded
            function_for_Create_Item($data);

            //if item has renamed
            //function_for_Rename_Item($data);


            //if item has deleted
            //function_for_delete_Item($data);

            //if item has moved
            //function_for_moving_Item($data);

            //if item has copy
            //function_for_copy_Item($data);

            // Display a message when the job is completed
            $endTime = date('d-m-Y h:i:s A'); // Use 'h:i A' format for time with AM/PM
            echo "Job completed at $endTime <br>";

            delta();
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //Download File/Folder on Local Directory By File/Folder Id and Name and Path (where to download)
    function downloadItemByIdLocally($itemname, $itemId, $localDirectory, $dynamicPath)
    {
        try {
            global $client;
            global $driveId;

            disable_Warnings();

            // Define the local file/folder path
            $localFilePath = $localDirectory . '/' . $dynamicPath;

            // Check if the item (file or folder) already exists locally
            if (file_exists($localFilePath)) {
                //echo "Item already exists at: $localFilePath\n";
                $messagelog = "Item already exists at: $localFilePath\n";
                store_error_log($messagelog);
                return;
            } else {
                // Get information about the item
                $itemInfo = $client->drive($driveId)->getItemById($itemId);
                if ($itemInfo !== false) {
                    $data = json_decode($itemInfo, true);
                    $Name = $data['name'];
                    $ID = $data['id'];

                    if (isset($data['folder']) && $data['folder']) {
                        // If the item is a folder, create the local folder
                        if (mkdir($localFilePath, 0777, true)) {
                            //echo "Folder created successfully at: $localFilePath\n";
                            $messagelog =  "Folder created successfully at: $localFilePath\n";
                            store_log($messagelog);

                            // // Recursively download the contents of the folder
                            // $children = $client->drive($driveId)->listById($itemId);
                            // $data = json_decode($children, true);

                            // if (isset($data['value']) && is_array($data['value'])) {
                            //     // Iterate through the children items
                            //     foreach ($data['value'] as $child) {

                            //         downloadItemByIdLocally($child['name'], $child['id'], $localFilePath);
                            //     }
                            // } else {
                            //     //echo "Error: 'value' array not found in the JSON response.\n";
                            // }


                        } else {
                            //echo "Failed to create folder at: $localFilePath\n";
                            $messagelog = "Failed to create folder at: $localFilePath\n";
                            store_error_log($messagelog);
                        }
                    } else {

                        // If the item is a file, download and save it
                        // $response = $client->drive($driveId)->downloadItemById($itemId);
                        // if ($response !== false) {
                        //     // Check the MIME type of the file
                        //     $fileInfo = $client->drive($driveId)->getItemById($itemId);
                        //     $mimeType = $fileInfo['file']['mimeType'];

                        //     // Specify the file extension based on the MIME type
                        //     $fileExtension = '';
                        //     switch ($mimeType) {
                        //         case 'application/pdf':
                        //             $fileExtension = 'pdf';
                        //             break;
                        //         case 'application/msword':
                        //             $fileExtension = 'doc';
                        //             break;
                        //         case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
                        //             $fileExtension = 'docx';
                        //             break;
                        //             // Add more cases for other document types as needed
                        //         default:
                        //             $fileExtension = 'txt'; // Default to .txt for unknown types
                        //     }
                        //     //$localFilePath = $localDirectory ;
                        //     // Generate the local file path with the appropriate extension
                        //     $localFilePath = $localDirectory . '/' . $fileExtension;

                        //     if (file_put_contents($localFilePath, $response) !== false) {
                        //         $messagelog = "File created successfully at: $localFilePath\n";
                        //         store_log($messagelog);
                        //     } else {
                        //         $messagelog = "Failed to create the file at: $localFilePath\n";
                        //         store_error_log($messagelog);
                        //     }
                        // } else {
                        //     $messagelog = "Failed to download the file.\n";
                        //     store_error_log($messagelog);
                        // }

                        //If the item is a file, download and save it

                        $response = $client->drive($driveId)->downloadItemById($itemId);
                        if ($response !== false) {
                            if (file_put_contents($localFilePath, $response) !== false) {
                                // echo "File saved successfully to $localFilePath\n";

                                $messagelog =  "File created successfully at: $localFilePath\n";
                                store_log($messagelog);
                            } else {
                                //echo "Failed to save the file to $localFilePath\n";
                                $messagelog = "Failed to Create the file at: $localFilePath\n";
                                store_error_log($messagelog);
                            }
                        } else {
                            //echo "Failed to download the file.\n";
                            $messagelog = "Failed to download the file.\n";
                            store_error_log($messagelog);
                        }
                    }
                } else {
                    //echo "Failed to get item information.\n";
                    $messagelog = "Failed to get item information.\n";
                    store_error_log($messagelog);
                }
            }
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //Delete File/Folder from Local Directory By File/Folder Path(Name)
    function deleteItemlocally($localDirectory)
    {
        try {
            global $client;
            global $driveId;

            disable_Warnings();

            $localItemPath = $localDirectory;

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
                //echo "Local Item Deleted Successfully at $localItemPath\n";
                $messagelog =  "Local Item Deleted Successfully at: $localItemPath\n";
                store_log($messagelog);

                return rmdir($localItemPath);
            } else if ((is_file($localItemPath) === true) || (is_link($localItemPath) === true)) {
                //echo "Local Item Deleted Successfully at $localItemPath\n";
                $messagelog =  "Local Item Deleted Successfully at: $localItemPath\n";
                store_log($messagelog);

                return unlink($localItemPath);
            }

            return false;
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //Move File/Folder on Local Directory By Source File Name and Destination File Name
    function move_file_Locally($file, $to)
    {
        try {

            disable_Warnings();

            $path_parts = pathinfo($file);
            $newplace = "$to/{$path_parts['basename']}";

            if (@rename($file, $newplace)) {
                $messagelog = "File/Folder $file Moved successfully on Local Directory at: $newplace\n";
                store_log($messagelog);
                return $newplace;
            } else {
                $messagelog = "Failed to Move File/Folder $file on Local Directory at: $newplace\n";
                store_error_log($messagelog);
            }
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $error_message = "Error moving file: " . $e->getMessage();
            store_error_log($error_message);
            return null;
        }
    }


    //Copy File/Folder on Local Directory By Source File Name and Destination File Name
    //Empty Folder not Copy
    function copyFilesLocally($source_dir, $destination_dir)
    {
        try {

            disable_Warnings();

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
                        $messagelog = "File/Folder $file Copied successfully on Local Directory at: $destination_dir\n";
                        store_log($messagelog);
                    }
                }
            }

            closedir($dir);
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //Update(Rename) File/Folder on Local Directory By File/Folder Name
    //and File/Folder Path(where file is located on local directory)
    function updateItemLocally($itemNewName, $localPath)
    {
        try {

            disable_Warnings();
            // Update the local directory
            if (file_exists($localPath)) {
                $newLocalPath = dirname($localPath) . '/' . $itemNewName;

                if (rename($localPath, $newLocalPath)) {
                    //echo "Local file/directory updated successfully.";
                    $messagelog =  "Local file/directory updated successfully: $newLocalPath\n";
                    store_log($messagelog);
                } else {
                    //echo "Failed to update local file/directory.";
                    $errorlog = "Failed to Update local file/directory at: $newLocalPath\n";
                    store_error_log($errorlog);
                }
            } else {
                //echo "Local file/directory not found.";
                $errorlog = "Local file/directory not found at: $localPath\n";
                store_error_log($errorlog);
            }
        } catch (Exception $e) {

            // If there was an error, display an error message
            // echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }

    //Create Folder on Local Directory by Folder Name and Path of Local Directory
    function createFolderLocally($itemPath, $localDirectory)
    {
        try {
            disable_Warnings();

            $localFolder = $localDirectory . '/' . $itemPath;

            if (mkdir($localFolder)) {
                //echo "Local Folder Created Successfully at $localFolder\n";
                $messagelog =  "Local Folder Created Successfully at: $localFolder\n";
                store_log($messagelog);
            } else {
                //echo "Failed to create Local Folder\n";
                $errorlog = "Failed to Create Local Folder at: $localFolder\n";
                store_error_log($errorlog);
            }
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //Get Single File/Folder By File/Folder Id from SharePoint Directory
    function getItemByIdSharePoint($itemId)
    {
        global $client;
        global $driveId;
        try {
            disable_Warnings();
            $response = $client->drive($driveId)->getItemById($itemId);
            // If the operation was successful, display a success message
            // echo "Item retrieved successfully: " . $response;
            $messagelog =  "Item retrieved successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            // echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //Get Single File/Folder By File/Folder Path(Name) from SharePoint Directory
    function getItemByPathSharePoint($itemPath)
    {
        global $client;
        global $driveId;

        try {
            disable_Warnings();
            $response = $client->drive($driveId)->getItemByPath($itemPath);
            // If the operation was successful, display a success message
            //echo "Item retrieved successfully: " . $response;
            $messagelog =  "Item retrieved successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }

    //Get All Files/Folders from SharePoint Directory
    function getItemsSharePoint()
    {
        global $client;
        global $driveId;
        try {
            disable_Warnings();
            $response = $client->drive($driveId)->getItems();
            // If the operation was successful, display a success message
            //echo "Items Retrieved successfully: " . $response;
            $messagelog =  "Items retrieved successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }

    //List Single File/Folder By File/Folder Id from SharePoint 
    function listItemByIdSharePoint($itemId)
    {
        global $client;
        global $driveId;
        try {
            disable_Warnings();
            $response = $client->drive($driveId)->listById($itemId);
            // // If the operation was successful, display a success message
            //echo "Item Listed successfully: " . $response;
            $messagelog =  "Item Listed successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            //// If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //List Single File/Folder By File/Folder Path(Name) from SharePoint
    function listItemByPathSharePoint($itemPath)
    {
        global $client;
        global $driveId;
        try {
            disable_Warnings();
            $response = $client->drive($driveId)->listByPath($itemPath);
            // If the operation was successful, display a success message
            //echo "Item Listed successfully: " . $response;
            $messagelog =  "Item Listed successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //List All Files/Folders from SharePoint
    function listItemsSharePoint()
    {
        global $client;
        global $driveId;
        try {
            disable_Warnings();
            $response = $client->drive($driveId)->listItems();
            // If the operation was successful, display a success message
            //echo "Item Listed successfully: " . $response;
            $messagelog =  "Item Listed successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            //echo "Error: " . $e->getMessage();
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    ?>
