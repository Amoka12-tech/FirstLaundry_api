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
    || empty($data->orderId)
    || empty($data->userId)
    ){
        $returnData = messg(0, 422, 'Invalid Order Request');
    }else{
        $userId = mysqli_real_escape_string($dbConnect, trim($data->userId));
        $orderId = mysqli_real_escape_string($dbConnect, trim($data->orderId));
        date_default_timezone_set("Africa/Lagos");
        $cancelDate = date(DATE_ATOM);

        $retriveOrder = "SELECT * FROM `orderdb` WHERE `userId`='$userId' AND `orderId`='$orderId'";
        $retriveItems = "SELECT * FROM `items` WHERE `orderId`='$orderId'";

            $retriveOrderData = $dbConnect->query($retriveOrder);
            if($retriveOrderData->num_rows > 0){
                $updateOrder = "UPDATE `orderdb` SET `status`='canceled', `completedDate`='$cancelDate' WHERE `userId`='$userId' AND `orderId`='$orderId'";
                $updateQry = $dbConnect->query($updateOrder);
                if($updateQry){
                    $newOrderData = $dbConnect->query($retriveOrder);
                    $retriveItemsData = $dbConnect->query($retriveItems);
                    $newOrderFetch = $newOrderData->fetch_assoc();
                    $itemFetch = $retriveItemsData->fetch_all(MYSQLI_ASSOC);
                    $newOrderFetch['items'] = $itemFetch;

                    $returnData = messg(1, 200, $newOrderFetch); //Return update
                }else{
                    $returnData = messg(0, 400, 'Order not cancel try again'); //Error
                }
            }else{
                $returnData = messg(0, 400, 'Order not found');
            }
    };

    echo json_encode($returnData);
?>