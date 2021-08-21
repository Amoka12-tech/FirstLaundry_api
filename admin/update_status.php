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
        !isset($data->userId)
    || !isset($data->orderId)
    || !isset($data->value)
    || empty($data->orderId)
    || empty($data->userId)
    ){
        $returnData = messg(0, 422, 'Invalid Order Request');
    }else{
        $userId = mysqli_real_escape_string($dbConnect, trim($data->userId));
        $orderId = mysqli_real_escape_string($dbConnect, trim($data->orderId));
        $value = mysqli_real_escape_string($dbConnect, trim($data->value));
        date_default_timezone_set("Africa/Lagos");
        $updateDate = date(DATE_ATOM);

        $qry = "";
        $message = "Your order status is updated to ".$value;
        if($value === 'pending'){
            $qry = "UPDATE `orderdb` SET `status`='$value' WHERE `userId`='$userId' AND `orderId`='$orderId'";
        }elseif($value === 'confirmed'){
            $qry = "UPDATE `orderdb` SET `status`='$value', `confirmationDate`='$updateDate' WHERE `userId`='$userId' AND `orderId`='$orderId'";
        }elseif($value === 'dispatched'){
            $qry = "UPDATE `orderdb` SET `status`='$value', `dispatcherDate`='$updateDate' WHERE `userId`='$userId' AND `orderId`='$orderId'";
        }elseif($value === 'inProgress'){
            $qry = "UPDATE `orderdb` SET `status`='$value', `inProgressDate`='$updateDate' WHERE `userId`='$userId' AND `orderId`='$orderId'";
        }elseif($value === 'delivered'){
            $qry = "UPDATE `orderdb` SET `status`='$value', `completedDate`='$updateDate' WHERE `userId`='$userId' AND `orderId`='$orderId'";
        }elseif($value === 'canceled'){
            $qry = "UPDATE `orderdb` SET `status`='$value', `completedDate`='$updateDate' WHERE `userId`='$userId' AND `orderId`='$orderId'";
        };

        try {
            //code...
            $updateStatus = $dbConnect->query($qry);
            if($updateStatus === TRUE){
                $insetHistoryQry = "INSERT INTO `notificationsdb` (`userId`, `orderId`, `type`, `message`, `date`) VALUES ('$userId', '$orderId', 'order', '$message', '$updateDate');";

                $retriveOrder = "SELECT * FROM `orderdb` WHERE `userId`='$userId' AND `orderId`='$orderId'";
                $retriveItems = "SELECT * FROM `items` WHERE `orderId`='$orderId'";
                $retriveUser = "SELECT * FROM `users` WHERE id='$userId'";


                $retriveOrderData = $dbConnect->query($retriveOrder);
                $retriveItemsData = $dbConnect->query($retriveItems);
                $userQry = $dbConnect->query($retriveUser);
                $insetHistory = $dbConnect->query($insetHistoryQry);

                if($retriveOrderData->num_rows > 0 && $insetHistory === TRUE){
                    $orderFetch = $retriveOrderData->fetch_assoc();

                    $userFetch = $userQry->fetch_assoc();
                    $orderFetch['userData'] = $userFetch;

                    $itemFetch = $retriveItemsData->fetch_all(MYSQLI_ASSOC);
                    $orderFetch['items'] = $itemFetch;
                    

                    $returnData = messg(1, 200, $orderFetch); //Return order data
                }else{
                    throw new Exception("Order Details not found try again");
                }
            }else{
                throw new Exception("Failed to update order status");
            }
        } catch (Exception $e) {
            //throw $e;
            $returnData = messg(0, 400, $e->getMessage());
        }
    };

    echo json_encode($returnData);
?>