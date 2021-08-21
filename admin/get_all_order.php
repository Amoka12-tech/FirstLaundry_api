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
    }else{
        $orderArray = [];

        $retriveOrder = "SELECT * FROM `orderdb` ORDER BY `orderDate` DESC";

        $retriveOrderData = $dbConnect->query($retriveOrder); 

        while($orderFetch = $retriveOrderData->fetch_assoc()){
            $userId = $orderFetch['userId'];
            $retriveUser = "SELECT * FROM `users` WHERE id='$userId'";

            $userQry = $dbConnect->query($retriveUser);
            $userFetch = $userQry->fetch_assoc();
            $orderFetch['userData'] = $userFetch;

            $orderId = $orderFetch['orderId'];
            $retriveItems = "SELECT * FROM `items` WHERE `orderId`='$orderId'";

            $itemData = $dbConnect->query($retriveItems);
            $itemFetch = $itemData->fetch_all(MYSQLI_ASSOC);

            $orderFetch['items'] = $itemFetch;
            array_push($orderArray, $orderFetch);
        };

        $returnData = messg(1, 200, $orderArray);
    };

    echo json_encode($returnData);
?>