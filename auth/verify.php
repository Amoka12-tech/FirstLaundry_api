<?php 
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: access");
    header("Access-Control-Allow-Methods: POST");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

    require '../utils/uuidGenerator.php';
    require '../utils/dbConfig.php';
    require '../utils/calculateDate.php';

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
        !isset($data->id)
        || !isset($data->code)
        || empty(trim($data->id))
        || empty(trim($data->code))
    ){
        $fields = ['fields' => ['id','code']];
        $returnData = messg(0, 422, 'Please Fill in all Required field');
    }else{
        $id = mysqli_real_escape_string($dbConnect, trim($data->id));
        $code = mysqli_real_escape_string($dbConnect, trim($data->code));
        try {
            //check if otp with id exist
            $checkOtp = "SELECT * FROM `otpdb` WHERE `userId`='$id'";
            $otpResult = $dbConnect->query($checkOtp);
            if($otpResult->num_rows > 0){
                $otpData = $otpResult->fetch_assoc();
                $otpCreatedAt = $otpData['date'];
                $convertDate = new ConvertDateToSec($otpCreatedAt);
                $secDate = $convertDate->convert_date_to_seconds();
                if($secDate > 180){
                    throw new Exception('This token has expired please try resend the otp');
                }else{
                    if($otpData['code'] == $code){
                        $updateUser = "UPDATE `users` SET `status`='activated' WHERE `id`='$id'";
                        $updateUserQry = $dbConnect->query($updateUser);
                        if($updateUserQry){
                            $deleteOldOtp = "DELETE FROM `otpdb` WHERE `userId`='$id'"; //Delete Old otp
                            $deleteOldOtpQry = $dbConnect->query($deleteOldOtp);
                            if($deleteOldOtpQry){
                                $selectUser = "SELECT * FROM `users` WHERE `id`='$id'";
                                $selectUserQry = $dbConnect->query($selectUser);
                                if($selectUserQry->num_rows > 0){
                                    $userData = $selectUserQry->fetch_assoc();
                                    $returnData = messg(1, 200, $userData);
                                }else{
                                    throw new Exception('User data not found contact admin +2348034329120');
                                }
                            }else{
                                throw new Exception('System can\'t remove old OTP contact admin');
                            }
                        }else{
                            throw new Exception('Contact admin user not activated');
                        }
                    }else{
                        throw new Exception('Incorrect OTP check and try again or resend after 60 seconds');
                    }
                }
            }else{
                throw new Exception('OTP not found for this User'); //id not in otpdb
            }
        } catch (Exception $e) {
            //throw $e
            $returnData = messg(0, 500, $e->getMessage());
        }
    }

    echo json_encode($returnData);
?>