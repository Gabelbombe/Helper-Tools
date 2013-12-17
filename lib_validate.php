<?php
/*==========================================================
 Script =   lib_validate.php
 URL =      None
 Author =   Laird Smith
 Version =  1.0
 Last mod =     11/21/2012 2:15PM
 Database =     MySql
 Security =     No
 Description =  Validation functions
 Status =   Done
==========================================================*/

/*==========================================================
 Lv_Validate_Email($addr);
 * Validate email addresses
 - Done
==========================================================*/

function Lv_Validate_Email($addr) {

    if (is_array($addr)) {
        foreach ($addr as $entry) {
            if(!filter_var($entry, FILTER_VALIDATE_EMAIL)){
                return false;
            }
        }
    } else {
        if(!filter_var($addr, FILTER_VALIDATE_EMAIL)){
            return false;
        }
    }

    return true;
}

/*==========================================================
 Lv_Validate_Password($pass,$user);
 * Validate passwords for complexity requirements
 - Done
==========================================================*/

function Lv_Validate_Password($pass,$user) {
    if(strlen($pass)<8){
        return false;   
    }
    if( !preg_match("#[0-9]+#", $pass) ) {
        return false;
    }
    if( !preg_match("#[A-Z]+#", $pass) ) {
        return false;
    }
    if( !preg_match("#[a-z]+#", $pass) ) {
        return false;
    }
    if(strtolower(trim($pass))==strtolower(trim($user))){
        return false;   
    }
    if(strtolower($pass)=='password' || strtolower($pass)=='12345678'){
        return false;   
    }
    if(stristr($pass,'password')){
        return false;   
    }   
    return true;    
}


/*==========================================================
 Lv_Validate_Smime_Key($key, &$key_info);
 * Validate S/MIME key
 * Bug: openssl_x509_checkpurpose is returning "false" on
   valid S/MIME keys because of a key chain issue? See:
   https://bugs.php.net/bug.php?id=42886 for more info. May
   not be a problem as nobody EVER has uploaded a S/MIME key!
 - Done
==========================================================*/

function Lv_Validate_Smime_Key($key, &$key_info) {

    if (openssl_x509_checkpurpose($key, X509_PURPOSE_SMIME_ENCRYPT) != 1) {
        return false;
    }

    function openssl_to_timestamp ($in) {
            $year  = substr($in, 0, 2); /* NOTE: Yes, this returns a two digit year */
            $month = substr($in, 2, 2);
            $day   = substr($in, 4, 2);
            $hour  = substr($in, 6, 2);
            $min   = substr($in, 8, 2);
            $sec   = substr($in, 10, 2);
            return gmmktime($hour, $min, $sec, $month, $day, $year);
    }

    $data = openssl_x509_parse($key);

    $key_info["key_address"] = $data["subject"]["emailAddress"];
    $key_info["key_expires"] = date("Y-m-d H:i:s",openssl_to_timestamp($data["validTo"]));
    $key_info["key_fingerprint"] = $data["hash"];

    return true;
}


/*==========================================================
 Lv_Validate_Pgp_Key($key, &$key_info);
 * Validate PGP key
 - Done
==========================================================*/

function Lv_Validate_Pgp_Key($key, &$key_info) {
    // set path to keyring directory
    $dir = '/var/www/manage.erado.com/includes/pgp/' . session_id();
    mkdir($dir);
    putenv("GNUPGHOME=$dir");
    $can_encrypt='N';
    $addresses="";
    $gpg= new gnupg();
    // throw exception if error occurs
    $gpg->seterrormode(gnupg::ERROR_EXCEPTION);
    try {
        $info = $gpg -> import($key);
        if($info["imported"]!=1){Lib_Deltree($dir);return false; }
        $key_info["key_fingerprint"]=$info["fingerprint"];
        $res = gnupg_init();
        $data=gnupg_keyinfo($res,".");
        $imported_key=$data[0];
        $current_timestamp=0;
        foreach($imported_key["subkeys"] as $subkey){
            if($subkey["can_encrypt"]== 1) {
                $can_encrypt='Y';
                if($subkey["timestamp"]>$current_timestamp){
                    $current_timestamp=$subkey["timestamp"];
                }
            }
        }
        if($can_encrypt=='N'){Lib_Deltree($dir); return false; }
        $key_info["key_expires"]=date("Y-m-d H:i:s",$current_timestamp);
        foreach($imported_key["uids"] as $uid){
            if($uid["revoked"]!=1 && $uid["invalid"]!=1){
                if(strlen($addresses)>0){$addresses.=";";}
                $addresses.=$uid["email"];
            }
        }
        $key_info["key_address"]=$addresses;
        Lib_Deltree($dir);
        return true;
    } catch(Exception $e) {
        @Le_Error_Die($e->getMessage());
    }
}
?>