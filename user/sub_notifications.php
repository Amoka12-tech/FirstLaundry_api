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
    || !isset($data->token)
    || empty(trim($data->userId))
    || empty(trim($data->token))
    ){
        $returnData = messg(0, 400, 'Wrong parameter supplied');
    }else{
        try {
            //code...
            $userId = mysqli_real_escape_string($dbConnect, trim($data->userId));
            $token = mysqli_real_escape_string($dbConnect, trim($data->token));

            $updateTokenQry = "UPDATE `users` SET `notificationKey`='$token' WHERE `id`='$userId'";
            $updateToken = $dbConnect->query($updateTokenQry);
            if($updateToken === TRUE){
                $returnData = messg(1, 200, 'User notification updated');
            }else{
                throw('Fail to register notification');
            }
        } catch (Exception $e) {
            //throw $th;
            $returnData = messg(0, 400, $e->getMessage());
        }
    }

    echo json_encode($returnData);
?>