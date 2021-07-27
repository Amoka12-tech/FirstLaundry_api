<?php 
    class OTP {
        public $otp;

        //Generate six digit random number;
        function __construct()
        {
            $this->otp = mt_rand(100000, 999999);
        }
        function get_otp() {
            return $this->otp;
        }
    }
?>