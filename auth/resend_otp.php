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
    || !isset($data->phone)
    || empty($data->id)
    || empty($data->phone)
    ){
        $fields = ['fields' => ['id', 'phone']];
        $returnData = messg(0, 422, 'Please Fill in all Required field');
    }else{
        $id = mysqli_real_escape_string($dbConnect, trim($data->id));
        $phone = mysqli_real_escape_string($dbConnect, trim($data->phone));
        try {
            //code...
            $checkOtp = "SELECT * FROM `otpdb` WHERE `userId`='$id'";
            $otpResult = $dbConnect->query($checkOtp);
            if($otpResult->num_rows > 0){
                $otpData = $otpResult->fetch_assoc();
                $otpCreatedAt = $otpData['date'];
                $convertDate = new ConvertDateToSec($otpCreatedAt);
                $secDate = $convertDate->convert_date_to_seconds();
                if($secDate > 180){
                    $newOtp = new OTP();
                    $otp = $newOtp->get_otp(); //Generate new otp
                    $smsMessage = "Welcome to First Laundry verify your account with this OTP: ".$otp." 
                    this code is valid for 3 minutes";
                    $newSMS = new SendSMS($phone, $smsMessage);
                    $sendSMS = $newSMS->send_sms(); //Send SMS with otp
                    if($sendSMS){
                        $updateOtp = "DELETE FROM `otpdb` WHERE `userId`='$id'";
                        $otpResult = $dbConnect->query($updateOtp);
                        if($otpResult){
                            $insertOtp = "INSERT INTO `otpdb`(`userId`, `code`) VALUES('$id','$otp')";
                            $insertResult = $dbConnect->query($insertOtp);
                            if($insertResult){
                                $returnData = messg(1, 200, 'New OTP is send to this number '.$phone.' check and verify');
                            }else{
                                throw new Exception('New OTP not registered contact admin');
                            }
                        }else{
                            throw new Exception('Old OTP not removed contact admin +2348034329120');
                        }
                    }else{
                        throw new Exception('Can not reach your phone, contact admin +2348034329120 to activate your account');
                    }
                }else{
                    throw new Exception('Please wait for the next '.$secDate.' seconds and try again');
                }
            }else{
                throw new Exception('User with this id not found');
            }
        } catch (Exception $e) {
            //throw $e;
            $returnData = $e->getMessage();
        }
    }

    echo json_encode($returnData);
?>