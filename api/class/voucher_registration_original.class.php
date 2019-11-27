<?php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	require_once(dirname(__FILE__) . '/../class/logs.class.php');
	$logs = NEW logs();
	$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
	header('Location: ../../../api.php');
}

class registration
{
	public static $file_name = 'vouchers_registration.class.php';

	public function get_registration($param) {
		global $logs;
				global $protocol;
				global $error000;
				$file_name = self::$file_name;
				$output = array();
				$data = (object) $param;
				
				$output = process_qry(" SELECT a.*, b.`description`, b.`terms`, COUNT(*) AS `count` FROM `earnvouchertable` a, `vouchertable` b, `memberstable` m WHERE a.`memberID` = ? AND DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(a.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(a.`endDate` , '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND a.`voucherID` = b.`voucherID` AND a.`status` = 'active' AND b.`status` = 'active' AND a.`type` IN ('registration','membership') and a.`memberiD` = m.`memberID`  AND DATE_FORMAT(m.`dateReg`, '%Y-%m-%d') >= DATE_FORMAT(b.`startDate`, '%Y-%m-%d')  and DATE_FORMAT(b.`endDate`, '%Y-%m-%d') >= DATE_FORMAT(m.`dateReg`, '%Y-%m-%d') GROUP BY b.`voucherID` ",array($data->memberID),$logs);

				if (count($output) > 0) {
					for ($iii=0; $iii<count($output); $iii++) {
						foreach ($output[$iii] as $array_key => $array_value) {
							if ($array_key == 'image') {
								$output[$iii][$array_key] =  DOMAIN . '/assets/images/vouchers/full/' . $array_value;
								$output[$iii]['thumb'] = DOMAIN . '/assets/images/vouchers/thumb/' . $array_value;
								
							}
						}
					}
					$logs->write_logs("Success - Voucher List Freebies", $file_name, array(array("response"=>"Success", "data"=>array($output))));
						return $output;
				}else{
					return $output;
				}
				
				
	}		
}
?>