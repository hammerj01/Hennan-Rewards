<?php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	require_once(dirname(__FILE__) . '/../class/logs.class.php');
	$logs = NEW logs();
	$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
	header('Location: ../../../api.php');
}

class flash
{
	public static $file_name = 'flash.class.php';	
	public function get_flash($param) {
		global $logs;
		global $protocol;
		global $error000;
		$file_name = self::$file_name;
		$output = array();
		$data = (object) $param;

		$check_flash = process_qry("SELECT * from vouchertable where type = 'flash' and status='active' and redemptionlimit > redemption_remaining", array(), $logs);
		if (count($check_flash) > 0 ){
			// $cnt_redeem = process_qry("SELECT * from redeemvouchertable WHERE memberID=? AND voucherID = ?",array($data->memberID,$data->voucherID),$logs);
			
			$output = process_qry(" SELECT a.*, b.`description`, b.`terms`, COUNT(*) AS `count` FROM `earnvouchertable` a, `vouchertable` b WHERE a.`memberID` = ? AND DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate` , '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND a.`voucherID` = b.`voucherID` AND a.`status` = 'active' AND b.`status` = 'active' AND a.`type` = 'flash' GROUP BY b.`voucherID`", array($data->memberID),$logs);

				if (count($output) > 0) {
					for ($iii=0; $iii<count($output); $iii++) {
						foreach ($output[$iii] as $array_key => $array_value) {
							if ($array_key == 'image') {
								$output[$iii][$array_key] =  DOMAIN . '/assets/images/vouchers/full/' . $array_value;
								$output[$iii]['thumb'] = DOMAIN . '/assets/images/vouchers/thumb/' . $array_value;
							}
						}
					}
				}
				$logs->write_logs("Success - My Voucher Flash", $file_name, array(array("response"=>"Success", "data"=>array($output))));
				// return json_encode(array(array("response"=>"Success", "data"=>$output)));
			return $output;
		}else{

			return $output;
		}
		
	}
}
?>