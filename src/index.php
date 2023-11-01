
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


    

    if ($tokendelta === null) {
        delta();
    } else {
        deltaByToken($tokendelta);
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


    //This functions track changes of files/folders created/uploaded on SharePoint
    function function_for_Create_Item($data)
    {
        global $client;
        global $driveId;

       
        $value = '';
        // Check if the 'value' array exists in the JSON data
        if (isset($data['value']) && is_array($data['value'])) {
            // Start iterating from the second element (index 1)
            for ($i = 1; $i <= count($data['value']); $i++) {
                if (isset($data['value'][$i])) {
                    $item = $data['value'][$i];
                    
                    // Check if 'id' and 'name' keys exist in the current item
                    if (isset($item['id']) && isset($item['name'])) {
                        $itemid = $item['id'];
                        $createdDateTime = $item['createdDateTime'];
                        $lastModifiedDateTime = $item['lastModifiedDateTime'];
                        $itemPath = $item['webUrl'];
                        $parentReferencecId = $item['parentReference']['id'];
                        $itemname = $item['name'];
                        
                        

                        
                         

                             // Convert the date and time to a string
                        $createdDateTimeString = date('Y-m-d H:i:s', strtotime($createdDateTime));
                        $lastModifiedDateTimeString = date('Y-m-d H:i:s', strtotime($lastModifiedDateTime));

                        

                        
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

                                                
                                            } else {

                                                $valueParent =  $valueParent . "/" . $itemname;
                           
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                downloadItemByIdLocally($itemname, $itemid, $localDirectory, $valueParent);

                                                
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

                                                
                                            } else {

                                                $valueParent =  $valueParent . "/" . $itemname;
                           
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                downloadItemByIdLocally($itemname, $itemid, $localDirectory, $valueParent);

                                                
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
                        else{
                            // echo "Not a .txt or .docx file";
                        }
                       
                        

                    } else {
                        // echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    }
                }
            }
        } else {
            //echo "Error: 'value' array not found in the JSON response.\n";
        }
        
    }

    //This functions track changes of files/folders Renamed on SharePoint
    function function_for_Rename_Item($data)
    {

        global $client;
        global $driveId;
        

        $itemOldName = '';
        $itemOldNameOld='';
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

                                                $localPath = __DIR__ . '/../src/LocalDrive' . $itemOldNameOldOld;

                                                updateItemLocally($itemNewName, $localPath);
                                                
                                            } else {


                                                $itemOldNameOldOld = $itemOldNameOld;

                                                $localPath = __DIR__ . '/../src/LocalDrive' . $value . "/" . $itemOldNameOldOld;

                                                updateItemLocally($itemNewName, $localPath);
                                                
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
                    
                    // Check if 'id' and 'name' keys exist in the current item and it is file
                    if (isset($item['createdDateTime']) && isset($item['lastModifiedDateTime']) && isset($item['file'])) {
                        $createdDateTime = $item['createdDateTime'];
                        $lastModifiedDateTime = $item['lastModifiedDateTime'];
                        $itemid = $item['id'];
                        $itemNewName = $item['name'];
                        $itemParentId = $item['parentReference']['id'];
                        $itemPath = $item['webUrl'];

                        // // Find the position of "Library1" in the URL
                        $libraryPosition = strpos($itemPath, "Library1");

                        if ($libraryPosition !== false) {
                            // Extract the value after "Library1" and everything after it
                            //$valueParent = substr($itemPath, $libraryPosition + strlen("Library1"));
                        
                        


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
                                            
                                            if (empty($itemOldName)) {
                                                
                                            }
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
                                                $localPath = __DIR__ . '/../src/LocalDrive' . $itemOldNameOldOld;
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                deleteItemlocally($localPath);
                                                downloadItemByIdLocally($itemNewName, $itemid, $localDirectory, $valueitemPath);
                                                
                                            } else {
                                                $itemOldNameOldOld = $itemOldNameOld;
                                                $localPath = __DIR__ . '/../src/LocalDrive' . $value . "/" . $itemOldNameOldOld;
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                deleteItemlocally($localPath);
                                                downloadItemByIdLocally($itemNewName, $itemid, $localDirectory, $valueitemPath);
                                                
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
                    } 
                    else {


                       

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
                                            
                                            if (empty($itemOldName)) {
                                                
                                            }
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
                                                if(empty($itemOldNameOldOld)){
                                                $localPath = __DIR__ . '/../src/LocalDrive' . $itemOldNameOldOld;
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                
                                                downloadItemByIdLocally($itemNewName, $itemid, $localDirectory, $itemNewName);
                                                
                                                }
                                                else{
                                                $localPath = __DIR__ . '/../src/LocalDrive' . $itemOldNameOldOld;
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                deleteItemlocally($localPath);
                                                downloadItemByIdLocally($itemNewName, $itemid, $localDirectory, $itemNewName);
                                                }
                                            } else {
                                                $itemOldNameOldOld = $itemOldNameOld;
                                                if(empty($itemOldNameOldOld)){
                                                $localPath = __DIR__ . '/../src/LocalDrive' . $value . "/" . $itemOldNameOldOld;
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                $valueParent =  $value . "/" . $itemNewName;
                                              
                                                downloadItemByIdLocally($itemNewName, $itemid, $localDirectory, $valueParent);
                                                }
                                                else{

                                                
                                                $localPath =__DIR__ . '/../src/LocalDrive' . $value . "/" . $itemOldNameOldOld;
                                                $localDirectory = __DIR__ . '/../src/LocalDrive';
                                                $valueParent =  $value . "/" . $itemNewName;
                                                deleteItemlocally($localPath);
                                                downloadItemByIdLocally($itemNewName, $itemid, $localDirectory, $valueParent);
                                                
                                            }
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
                        //  echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                    // }
                    // else{
                    //     // echo "Value not found in the URL.";
                    // }
                    }
                } else {
                    // echo "Value not found in the URL.";
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



                                        $localDirectory = __DIR__ . '/../src/LocalDrive' . $value;
                                        deleteItemlocally($localDirectory);
                                        
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
        $parentReferencecIdOld='';
        $valueOld='';
        $itemOldName='';
        $valueItemWebUrl = '';
        $valueItemWebUrlNew = '';
        
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

                        $fileExtension = pathinfo($itemname, PATHINFO_EXTENSION);
                       
                        

                        
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


                                            $file = __DIR__ . '/../src/LocalDrive/' . $valueItemnew;
                                            $to =  __DIR__ . '/../src/LocalDrive/' . $valueParent;
                                            
                                            move_file_Locally($file, $to);
                                            
                                        } else {
                                            //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                        }
                                    }
                                }
                            } else {
                                // echo "Error: 'value' array not found in the JSON response.\n";
                            }
                        }


                    
                 else
                 
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
                                        $parentReferencecIdOld = $itemDatabase['parentReference']['id'];

                                        if (empty($itemOldName)) {
                                            
                                        }
                                    } else {
                                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                    }
                                }
                            }
                        } else {
                            //echo "Error: 'value' array not found in the JSON response.\n";
                        }

                        $itemOldNameOld = $itemOldName;
                        $parentReferencecIdOldOld=$parentReferencecIdOld;
                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        $remoteItemParentId = $parentReferencecId;
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
                                            $valueNew = substr($itemParentWebUrl, $libraryPosition + strlen("Library1"));
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


                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        //$remoteItemId = $itemid;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                                if (isset($mappingDatabase['value'][$k])) {
                                    $itemDatabase = $mappingDatabase['value'][$k];
                                    $parentReferencecIdOldNew = $parentReferencecIdOldOld;



                                    // Check if 'id' and 'name' keys exist in the current item
                                    if (isset($itemDatabase['id']) && $itemDatabase['id'] === $parentReferencecIdOldNew) {

                                        $itemParentnameDatabase = $itemDatabase['name'];
                                        $itemParentWebUrlNew = $itemDatabase['webUrl'];
                                        


                                        // // Find the position of "Library1" in the URL
                                        $libraryPosition = strpos($itemParentWebUrlNew, "Library1");

                                        if ($libraryPosition !== false) {
                                            // Extract the value after "Library1" and everything after it
                                            $valueOld = substr($itemParentWebUrlNew, $libraryPosition + strlen("Library1"));
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


                        if ($valueOld === ' ') {
                            $itemOldNameOldOld = $itemOldNameOld;
                            if(empty($itemOldNameOldOld)){
                            
                            $file =  __DIR__ . '/../src/LocalDrive/'. $itemOldNameOldOld;
                            $to =  __DIR__ . '/../src/LocalDrive/' .$valueNew;
                           
                            move_file_Locally($file, $to);
                           
                            }
                            else{
                                
                                $file =  __DIR__ . '/../src/LocalDrive/'. $itemOldNameOldOld;
                                $to =  __DIR__ . '/../src/LocalDrive/' .$valueNew;
                              
                                move_file_Locally($file, $to);
                            }
                        } else {
                            $itemOldNameOldOld = $itemOldNameOld;
                            if(empty($itemOldNameOldOld)){
                            
                            $file =  __DIR__ . '/../src/LocalDrive/' .$valueOld."/" . $itemOldNameOldOld;
                            $to =  __DIR__ . '/../src/LocalDrive/' . $valueNew;
                            
                             move_file_Locally($file, $to);
                            }
                            else{

                                
                                $file =  __DIR__ . '/../src/LocalDrive/' .$valueOld."/" . $itemOldNameOldOld;
                            $to =  __DIR__ . '/../src/LocalDrive/' . $valueNew;
                            
                                move_file_Locally($file, $to);
                        }
                        }


                    }
                    elseif ($createdDateTimeString === $lastModifiedDateTimeString) {

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
                                        $parentReferencecIdOld = $itemDatabase['parentReference']['id'];

                                        if (empty($itemOldName)) {
                                            
                                        }
                                    } else {
                                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                    }
                                }
                            }
                        } else {
                            //echo "Error: 'value' array not found in the JSON response.\n";
                        }

                        $itemOldNameOld = $itemOldName;
                        $parentReferencecIdOldOld=$parentReferencecIdOld;
                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        $remoteItemParentId = $parentReferencecId;
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
                                            $valueNew = substr($itemParentWebUrl, $libraryPosition + strlen("Library1"));
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


                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        //$remoteItemId = $itemid;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                                if (isset($mappingDatabase['value'][$k])) {
                                    $itemDatabase = $mappingDatabase['value'][$k];
                                    $parentReferencecIdOldNew = $parentReferencecIdOldOld;



                                    // Check if 'id' and 'name' keys exist in the current item
                                    if (isset($itemDatabase['id']) && $itemDatabase['id'] === $parentReferencecIdOldNew) {

                                        $itemParentnameDatabase = $itemDatabase['name'];
                                        $itemParentWebUrlNew = $itemDatabase['webUrl'];
                                        


                                        // // Find the position of "Library1" in the URL
                                        $libraryPosition = strpos($itemParentWebUrlNew, "Library1");

                                        if ($libraryPosition !== false) {
                                            // Extract the value after "Library1" and everything after it
                                            $valueOld = substr($itemParentWebUrlNew, $libraryPosition + strlen("Library1"));
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


                        if ($valueOld === ' ') {
                            $itemOldNameOldOld = $itemOldNameOld;
                            if(empty($itemOldNameOldOld)){
                            
                            $file =  __DIR__ . '/../src/LocalDrive/' . $itemOldNameOldOld;
                            $to =  __DIR__ . '/../src/LocalDrive/'.$valueNew;
                            
                            move_file_Locally($file, $to);
                           
                            }
                            else{
                                
                                $file =  __DIR__ . '/../src/LocalDrive/' . $itemOldNameOldOld;
                                $to =  __DIR__ . '/../src/LocalDrive/' .$valueNew;
                               
                                move_file_Locally($file, $to);
                            }
                        } else {
                            $itemOldNameOldOld = $itemOldNameOld;
                            if(empty($itemOldNameOldOld)){
                            
                            $file =  __DIR__ . '/../src/LocalDrive/' .$valueOld."/" . $itemOldNameOldOld;
                            $to =  __DIR__ . '/../src/LocalDrive/' . $valueNew;
                            
                             move_file_Locally($file, $to);
                            }
                            else{

                                
                                $file =  __DIR__ . '/../src/LocalDrive/' .$valueOld."/" . $itemOldNameOldOld;
                            $to =  __DIR__ . '/../src/LocalDrive/' . $valueNew;
                            
                                move_file_Locally($file, $to);
                        }
                        }


                    }
                


                
                else{
                    //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
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



    // //This functions track changes of files/folders copy on SharePoint

    function function_for_copy_Item($data)
    {
        global $client;
        global $driveId;
        $valueItem = '';
        $valueItemWebUrl = '';
        $valueItemWebUrlNew = '';
        
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

                        $fileExtension = pathinfo($itemname, PATHINFO_EXTENSION);
                        
                        

                        
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
                                            
                                            $source_dir =  __DIR__ . '/../src/LocalDrive/' . $valueItemnew;
                                            $destination_dir =  __DIR__ . '/../src/LocalDrive/' . $valueParent;
                                            
                                            copyFilesLocally($source_dir, $destination_dir);
                                            
                                        } else {
                                            //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                        }
                                    }
                                }
                            } else {
                                // echo "Error: 'value' array not found in the JSON response.\n";
                            }
                        }


                   
                 else
                 
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
                                        $parentReferencecIdOld = $itemDatabase['parentReference']['id'];

                                        if (empty($itemOldName)) {
                                            
                                        }
                                    } else {
                                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                    }
                                }
                            }
                        } else {
                            //echo "Error: 'value' array not found in the JSON response.\n";
                        }

                        $itemOldNameOld = $itemOldName;
                        $parentReferencecIdOldOld=$parentReferencecIdOld;
                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        $remoteItemParentId = $parentReferencecId;
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
                                            $valueNew = substr($itemParentWebUrl, $libraryPosition + strlen("Library1"));
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


                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        //$remoteItemId = $itemid;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                                if (isset($mappingDatabase['value'][$k])) {
                                    $itemDatabase = $mappingDatabase['value'][$k];
                                    $parentReferencecIdOldNew = $parentReferencecIdOldOld;



                                    // Check if 'id' and 'name' keys exist in the current item
                                    if (isset($itemDatabase['id']) && $itemDatabase['id'] === $parentReferencecIdOldNew) {

                                        $itemParentnameDatabase = $itemDatabase['name'];
                                        $itemParentWebUrlNew = $itemDatabase['webUrl'];
                                        


                                        // // Find the position of "Library1" in the URL
                                        $libraryPosition = strpos($itemParentWebUrlNew, "Library1");

                                        if ($libraryPosition !== false) {
                                            // Extract the value after "Library1" and everything after it
                                            $valueOld = substr($itemParentWebUrlNew, $libraryPosition + strlen("Library1"));
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


                        if ($valueOld === ' ') {
                            $itemOldNameOldOld = $itemOldNameOld;
                            if(empty($itemOldNameOldOld)){
                            


                            $source_dir =  __DIR__ . '/../src/LocalDrive/' . $itemOldNameOldOld;
                            $destination_dir =  __DIR__ . '/../src/LocalDrive/'.$valueNew;
                           
                            copyFilesLocally($source_dir, $destination_dir);
                           
                            }
                            else{
                                
                                $source_dir =  __DIR__ . '/../src/LocalDrive/' . $itemOldNameOldOld;
                                $destination_dir =  __DIR__ . '/../src/LocalDrive/' .$valueNew;
                              
                                copyFilesLocally($source_dir, $destination_dir);
                            }
                        } else {
                            $itemOldNameOldOld = $itemOldNameOld;
                            if(empty($itemOldNameOldOld)){
                            
                            $source_dir =  __DIR__ . '/../src/LocalDrive/' .$valueOld."/" . $itemOldNameOldOld;
                            $destination_dir =  __DIR__ . '/../src/LocalDrive/' . $valueNew;
                            
                            copyFilesLocally($source_dir, $destination_dir);
                            }
                            else{

                                
                                $source_dir =  __DIR__ . '/../src/LocalDrive/' .$valueOld."/" . $itemOldNameOldOld;
                                $destination_dir =  __DIR__ . '/../src/LocalDrive/' . $valueNew;
                            
                            copyFilesLocally($source_dir, $destination_dir);
                        }
                        }


                    }
                    elseif ($createdDateTimeString === $lastModifiedDateTimeString) {

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
                                        $parentReferencecIdOld = $itemDatabase['parentReference']['id'];

                                        if (empty($itemOldName)) {
                                            
                                        }
                                    } else {
                                        //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
                                    }
                                }
                            }
                        } else {
                            //echo "Error: 'value' array not found in the JSON response.\n";
                        }

                        $itemOldNameOld = $itemOldName;
                        $parentReferencecIdOldOld=$parentReferencecIdOld;
                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        $remoteItemParentId = $parentReferencecId;
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
                                            $valueNew = substr($itemParentWebUrl, $libraryPosition + strlen("Library1"));
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


                        $mappingFile = @file_get_contents(__DIR__ . '/../storage/deltaResponse') ?: null;
                        $mappingDatabase = json_decode($mappingFile, true);
                        //$remoteItemId = $itemid;
                        if (isset($mappingDatabase['value']) && is_array($mappingDatabase['value'])) {
                            // Start iterating from the second element (index 1)
                            for ($k = 0; $k <= count($mappingDatabase['value']); $k++) {
                                if (isset($mappingDatabase['value'][$k])) {
                                    $itemDatabase = $mappingDatabase['value'][$k];
                                    $parentReferencecIdOldNew = $parentReferencecIdOldOld;



                                    // Check if 'id' and 'name' keys exist in the current item
                                    if (isset($itemDatabase['id']) && $itemDatabase['id'] === $parentReferencecIdOldNew) {

                                        $itemParentnameDatabase = $itemDatabase['name'];
                                        $itemParentWebUrlNew = $itemDatabase['webUrl'];
                                        


                                        // // Find the position of "Library1" in the URL
                                        $libraryPosition = strpos($itemParentWebUrlNew, "Library1");

                                        if ($libraryPosition !== false) {
                                            // Extract the value after "Library1" and everything after it
                                            $valueOld = substr($itemParentWebUrlNew, $libraryPosition + strlen("Library1"));
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


                        if ($valueOld === ' ') {
                            $itemOldNameOldOld = $itemOldNameOld;
                            if(empty($itemOldNameOldOld)){
                            
                            $source_dir = __DIR__ . '/../src/LocalDrive/' . $itemOldNameOldOld;
                            $destination_dir =  __DIR__ . '/../src/LocalDrive/' .$valueNew;
                            
                            copyFilesLocally($source_dir, $destination_dir);
                           
                            }
                            else{
                                
                                $source_dir =  __DIR__ . '/../src/LocalDrive/' . $itemOldNameOldOld;
                                $destination_dir =  __DIR__ . '/../src/LocalDrive/' .$valueNew;
                               
                                copyFilesLocally($source_dir, $destination_dir);
                            }
                        } else {
                            $itemOldNameOldOld = $itemOldNameOld;
                            if(empty($itemOldNameOldOld)){
                            
                            $source_dir =  __DIR__ . '/../src/LocalDrive/' .$valueOld."/" . $itemOldNameOldOld;
                            $destination_dir =  __DIR__ . '/../src/LocalDrive/' . $valueNew;
                            
                            copyFilesLocally($source_dir, $destination_dir);
                            }
                            else{

                                
                                $source_dir =  __DIR__ . '/../src/LocalDrive/'.$valueOld."/" . $itemOldNameOldOld;
                            $destination_dir =  __DIR__ . '/../src/LocalDrive/' . $valueNew;
                            
                            copyFilesLocally($source_dir, $destination_dir);
                        }
                        }


                    }
               


                
                else{
                    //echo "Error: 'id' and/or 'name' not found in the item JSON.\n";
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


    
    


    //Delta By Token
    //Track Changes of Files/Folders on SharePoint
    function deltaByToken($tokendelta)
    {

        global $client;
        global $driveId;

        
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

            // Set the timezone to Pakistani Standard Time (PKT)
            date_default_timezone_set('Asia/Karachi');

            // Display a message when the job starts
            $startTime = date('d-m-Y h:i:s A'); // Use 'h:i A' format for time with AM/PM
            echo "Job started at $startTime <br>";

            //if item has created/uploaded
            function_for_Create_Item($data);

            

            //if item has renamed
            function_for_Rename_Item($data);

            

            //if item has deleted
            function_for_delete_Item($data);

            //if item has moved
            function_for_moving_Item($data);

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

            

            // Define the local file/folder path
            $localFilePath = $localDirectory . '/' . $dynamicPath;

            // Check if the item (file or folder) already exists locally
            if (file_exists($localFilePath)) {
                
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
                            
                            $messagelog =  "Folder created successfully at: $localFilePath\n";
                            store_log($messagelog);

                            


                        } else {
                           
                            $messagelog = "Failed to create folder at: $localFilePath\n";
                            store_error_log($messagelog);
                        }
                    } else {

                        

                        //If the item is a file, download and save it

                        $response = $client->drive($driveId)->downloadItemById($itemId);
                        if ($response !== false) {
                            if (file_put_contents($localFilePath, $response) !== false) {
                                

                                $messagelog =  "File created successfully at: $localFilePath\n";
                                store_log($messagelog);
                            } else {
                                
                                $messagelog = "Failed to Create the file at: $localFilePath\n";
                                store_error_log($messagelog);
                            }
                        } else {
                            
                            $messagelog = "Failed to download the file.\n";
                            store_error_log($messagelog);
                        }
                    }
                } else {
                    
                    $messagelog = "Failed to get item information.\n";
                    store_error_log($messagelog);
                }
            }
        } catch (Exception $e) {
            // If there was an error, display an error message
           
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
                
                $messagelog =  "Local Item Deleted Successfully at: $localItemPath\n";
                store_log($messagelog);

                return rmdir($localItemPath);
            } else if ((is_file($localItemPath) === true) || (is_link($localItemPath) === true)) {
                
                $messagelog =  "Local Item Deleted Successfully at: $localItemPath\n";
                store_log($messagelog);

                return unlink($localItemPath);
            }

            return false;
        } catch (Exception $e) {
            // If there was an error, display an error message
           
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //Move File/Folder on Local Directory By Source File Name and Destination File Name
    function move_file_Locally($file, $to)
    {
        try {

           

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
            
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }


    //Update(Rename) File/Folder on Local Directory By File/Folder Name
    //and File/Folder Path(where file is located on local directory)
    function updateItemLocally($itemNewName, $localPath)
    {
        try {

            
            // Update the local directory
            if (file_exists($localPath)) {
                $newLocalPath = dirname($localPath) . '/' . $itemNewName;

                if (rename($localPath, $newLocalPath)) {
                    
                    $messagelog =  "Local file/directory updated successfully: $newLocalPath\n";
                    store_log($messagelog);
                } else {
                    
                    $errorlog = "Failed to Update local file/directory at: $newLocalPath\n";
                    store_error_log($errorlog);
                }
            } else {
                
                $errorlog = "Local file/directory not found at: $localPath\n";
                store_error_log($errorlog);
            }
        } catch (Exception $e) {

            // If there was an error, display an error message
           
            $errorlog = "Error: " . $e->getMessage();
            store_error_log($errorlog);
        }
    }

    //Create Folder on Local Directory by Folder Name and Path of Local Directory
    function createFolderLocally($itemPath, $localDirectory)
    {
        try {
           

            $localFolder = $localDirectory . '/' . $itemPath;

            if (mkdir($localFolder)) {
                
                $messagelog =  "Local Folder Created Successfully at: $localFolder\n";
                store_log($messagelog);
            } else {
                
                $errorlog = "Failed to Create Local Folder at: $localFolder\n";
                store_error_log($errorlog);
            }
        } catch (Exception $e) {
            // If there was an error, display an error message
            
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
           
            $response = $client->drive($driveId)->getItemById($itemId);
            // If the operation was successful, display a success message
            
            $messagelog =  "Item retrieved successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            
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
           
            $response = $client->drive($driveId)->getItemByPath($itemPath);
            // If the operation was successful, display a success message
           
            $messagelog =  "Item retrieved successfully:  $response\n";
            store_log($messagelog);
        } catch (Exception $e) {
            // If there was an error, display an error message
            
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
