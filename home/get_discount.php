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
    || empty($data->userId)){
        $returnData = messg(0, 400, 'Invalid parameter provided');
    }else{
        $userId = mysqli_real_escape_string($dbConnect, trim($data->userId));
        $qry = "SELECT * FROM `discountdb` WHERE `userId`='$userId' AND status='active'";
            $searchQry = $dbConnect->query($qry);
            if($searchQry->num_rows > 0){
                $discount = $searchQry->fetch_assoc();
                $returnData = messg(1, 200, $discount['discount']);
            }else{
                $returnData = messg(0, 400, 0);
            }
    }

    echo json_encode($returnData);
?>