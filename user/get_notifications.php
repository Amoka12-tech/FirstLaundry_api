<?php 
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    require '../utils/dbConfig.php';

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
    || empty(trim($data->userId))
    ){
        $returnData = messg(0, 400, 'Wrong parameter supplied');
    }else{
        $userId = mysqli_real_escape_string($dbConnect, trim($data->userId));

            $getNotificationQry = "SELECT * FROM `notificationsdb` WHERE `userId`='$userId'";
            $result = $dbConnect->query($getNotificationQry);
            if($result->num_rows > 0){
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $returnData = messg(1, 200, $data);
            }else{
                $returnData = messg(0, 400, []);
            }
    }

    echo json_encode($returnData);
?>