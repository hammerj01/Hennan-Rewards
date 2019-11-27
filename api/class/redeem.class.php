<?php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	require_once(dirname(__FILE__) . '/../class/logs.class.php');
	$logs = NEW logs();
	$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
	header('Location: ../../../api.php');
}


class redeem{
	
	public static $file_name = 'vouchers.class.php';

	public function redeem_campaign($param){
		global $logs;
		global $protocol;
		global $error000;
		$file_name = self::$file_name;
		$output = array();
		$data = (object) $param;
// var_dump($data);
		$chck = $this->check_request_rewards(array("memberID"=>$data->memberID,"requestID"=>$data->requestID,"rewardsID"=>$data->rewardsID,"transactionID"=>$data->transactionID));
		$dateNow = DATE('Y-m-d H:i:s');
		if($chck == 'true'){
			// echo $data->memberID.','. $data->requestID.','. $data->rewardsID.','. $data->transactionID;
			$update = process_qry("UPDATE `redeem_request_rewards` SET `description` = 'redeem on premium' where `memberID` = ? and `requestID` = ? and `rewardsID` = ? and `transactionID` = ?", array($data->memberID, $data->requestID, $data->rewardsID, $data->transactionID), $logs );

		}else{
			// echo $data->memberID.','.$data->requestID.','.$data->rewardsID.','.$data->email.','.$data->fname.','.$data->lname.','.$data->rewards_name.','.$data->promoType.','.$data->points.','.$data->description.','.$data->status.','.$data->platform.','.$data->barCode.','. $data->locID.','.$data->locName;
			$insert = process_qry("INSERT INTO `redeem_request_rewards` (`memberID`,`requestID`, `rewardsID`, `transactionID`, `email`, `fname`, `lname`, `rewards_name`, `promoType`,`points`, `description`, `status`,`platform`,`barCode`,`locID`,`locName` ,`dateAdded`, `dateModified`,`emailSent`)VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)",array($data->memberID,$data->requestID,$data->rewardsID,$data->transactionID,$data->email,$data->fname,$data->lname,$data->rewards_name,$data->promoType,$data->points,$data->description,$data->status,$data->platform,$data->barCode,$data->locID,$data->locName, $dateNow, $dateNow,$data->emailSent),$logs);
			// echo 'false';
		}
		$totalPoints = $data->sub_total - $data->points;
		$update_member = process_qry("UPDATE `memberstable` SET `totalPoints` = ?, `lastTransaction` = NOW() where `memberID` = ? and status = 'active' LIMIT 1", array($totalPoints, $data->memberID), $logs);
		if (count($update_member) > 0){
			return "Success";
		}else{
			return "Failed";
		}
	}


	public function check_request_rewards($param){
		global $logs;
		global $protocol;
		global $error000;
		$file_name = self::$file_name;
		$output = array();
		$data = (object) $param;
		$ret = 'false';
		$cnt = process_qry("SELECT * FROM redeem_request_rewards where `memberID` = ? and `requestID` = ? and `rewardsID` = ? and `transactionID` = ?", array($data->memberID, $data->requestID, $data->rewardsID, $data->transactionID), $logs );
		
		if (count($cnt) > 0 ){
			$ret = "true";
		}

		return $ret;
	}

}

?>