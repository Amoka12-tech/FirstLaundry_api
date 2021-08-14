<?php 
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    require '../utils/uuidGenerator.php';
    require '../utils/dbConfig.php';
    require '../utils/generateOTP.php';
    require '../utils/sendSMS.php';

    //Database connection
    $dbClass = new Database();
    $dbConnect = $dbClass->dbConnection();

    //Return Message
    function messg($success, $status, $message, $extra=[]){
        return array_merge([
            'success' => $success,
            'status' => $status,
            'message' => $message
        ],$extra);
    };

    //Get form data
    $data = json_decode(file_get_contents("php://input"));
    $returnData = [];

    if($_SERVER["REQUEST_METHOD"] != "POST"){
        $returnData = messg(0, 404, "Page Not Found");
    }elseif(
        !isset($data->userData)
    || !isset($data->selectedItems)
    || !isset($data->totalCount)
    || !isset($data->totalPrice)
    || !isset($data->payment)
    || empty($data->selectedItems)
    || empty($data->userData)
    ){
        $returnData = messg(0, 422, 'Oops! data not provided for this operation');
    }else{
        $userData = $data->userData;

        $uuid = new UUID_V4();
        $id = $uuid->guidv4();// uniq id for database
        $code = new OTP();
        $orderId = $code->get_otp();// uniqe order id six digit
        $userId = mysqli_real_escape_string($dbConnect, trim($data->userData->id)); //User Id from front end
        $amount = $data->totalPrice;
        $totalCount = $data->totalCount;
        $paymentRef = mysqli_real_escape_string($dbConnect, trim($data->payment->paymentData->reference));
        $paymentStatus = mysqli_real_escape_string($dbConnect, trim($data->payment->paymentStatus));
        $deliveryDateTime = mysqli_real_escape_string($dbConnect, trim($data->deliveryDateTime->Date))." ".mysqli_real_escape_string($dbConnect, trim($data->deliveryDateTime->Time));
        $pickupDateTime = mysqli_real_escape_string($dbConnect, trim($data->pickupDateTime->Date))." ".mysqli_real_escape_string($dbConnect, trim($data->pickupDateTime->Time));

        $deliveryAddress = mysqli_real_escape_string($dbConnect, trim($data->locationData->deliveryAddressName));
        $pickupAddress = mysqli_real_escape_string($dbConnect, trim($data->locationData->pickupAddressName));
        $deliveryLatLng = mysqli_real_escape_string($dbConnect, trim($data->locationData->deliveryLat)).",".mysqli_real_escape_string($dbConnect, trim($data->locationData->deliveryLng));
        $pickupLatLng = mysqli_real_escape_string($dbConnect, trim($data->locationData->pickupLat)).",".mysqli_real_escape_string($dbConnect, trim($data->locationData->pickupLng));

        // Return date/time info of a timestamp; then format the output
        $mydate=getdate(date("U"));
        $orderDate = $mydate['weekday'].", ".$mydate['month']." ".$mydate['mday'].", ".$mydate['year']." ".$mydate['hours'].":".$mydate['minutes'];

        $selectedItems = $data->selectedItems;
        $orderCount = count($selectedItems);

        $itemAddCount = 0;

        //Now insert data of line
        $insertOrder = "INSERT INTO 
        `orderdb`(`id`, `orderId`, `userId`, `amount`, `totalCount`, `paymentRef`, `paymentStatus`, `deliveryDateTime`, `pickupDateTime`, `deliveryAddress`, `pickupAddress`, `deliveryLatLng`, `pickupLatLng`, `orderDate`) 
        VALUES('$id','$orderId','$userId','$amount','$totalCount','$paymentRef','$paymentStatus','$deliveryDateTime','$pickupDateTime','$deliveryAddress','$pickupAddress','$deliveryLatLng','$pickupLatLng','$orderDate')";
        $orderResult = $dbConnect->query($insertOrder);
        if($orderResult === TRUE){
            
            //Start looping insert for every item
            for ($item=0; $item < $orderCount; $item++) { 
                # code...
                $thisItem = $selectedItems[$item];
                $itemName = $thisItem->name;
                $itemOrder = $thisItem->order;
                $itemOrderCount = count($itemOrder);
                for ($order=0; $order < $itemOrderCount; $order++) { 
                    # code...
                    $orderData = $itemOrder[$order];
                    $itemId = $thisItem->id;
                    $orderService = $orderData->type;
                    $orderPrice = $orderData->price;

                    $insertItem = "INSERT INTO 
                    `items`(`orderId`,`itemId`,`itemName`,`itemService`,`itemPrice`)
                    VALUES('$orderId','$itemId','$itemName','$orderService','$orderPrice')";

                    $insertResult = $dbConnect->query($insertItem);
                    if($insertResult === TRUE){
                        $itemAddCount++;
                    }else{
                        $returnData = messg(0, 400, 'Critical Error on adding items contact');
                    }

                    //Insert each into database
                }
            }

            $retriveOrder = "SELECT * FROM `orderdb` WHERE `userId`='$userId' AND `orderId`='$orderId'";
            $retriveItems = "SELECT * FROM `items` WHERE `orderId`='$orderId'";

            $retriveOrderData = $dbConnect->query($retriveOrder);
            $retriveItemsData = $dbConnect->query($retriveItems);

            $orderFetch = $retriveOrderData->fetch_assoc();
            $itemFetch = $retriveItemsData->fetch_all(MYSQLI_ASSOC);
            $orderFetch['items'] = $itemFetch;
            

            $returnData = messg(1, 200, $orderFetch); //Return order data

        }else{
            $returnData = messg(0, 400, 'Oops! Failed to update order, contact admin or try again');
        }

    }
    echo json_encode($returnData);
?>