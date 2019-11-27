<?php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	require_once(dirname(__FILE__) . '/../class/logs.class.php');
	$logs = NEW logs();
	$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
	header('Location: ../../../api.php');
}

class birthday
{
	public static $file_name = 'vouchers_bday.class.php';

	public function get_birthday($param) {
		global $logs;
		global $protocol;
		global $error000;
		$file_name = self::$file_name;
		$output = array();
		$data = (object) $param;

		$check_bdayvoucher = process_qry("SELECT e.`month` as bday_month, a.`month` as bday_base from earnvouchertable e inner join vouchertable a on e.`voucherID` = a.`voucherID` where e.`memberID` =  ? and  a.`type` = 'birthday' and a.`status` = 'active'",array($data->memberID),$logs);

		// $x= process_qry(" update memberstable set fname = 'niño pio Emmanuel' where id = 8",array(),$logs);

		$update_bday = process_qry("UPDATE `earnvouchertable` SET `status` = 'expired' WHERE `status` = 'active' AND DATE_FORMAT(NOW(), '%Y-%m-%d') > DATE_FORMAT(`endDate`, '%Y-%m-%d') and memberID = ? ", array($data->memberID));
		if(count($check_bdayvoucher) > 0 ){
			// echo $check_bdayvoucher[0]['bday_month'].'=='. $check_bdayvoucher[0]['bday_base'];
			if($check_bdayvoucher[0]['bday_month'] == 13){
				// echo $check_bdayvoucher[0]['bday_month'] . ' ' . (int) $data->month . ' ' . DATE('m');
				if ((int) DATE('m') == (int) $data->month){
					$output = process_qry(" SELECT a.*, b.`description`, b.`terms`, COUNT(*) AS `count` FROM `earnvouchertable` a, `vouchertable` b WHERE a.`memberID` = ? AND DATE_FORMAT(a.`startDate`, '%Y-%m-%d') >= DATE_FORMAT(b.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(b.`endDate` , '%Y-%m-%d') >= DATE_FORMAT(a.`endDate`, '%Y-%m-%d') AND a.`voucherID` = b.`voucherID` AND a.`status` = 'active' AND b.`status` = 'active' AND a.`type` = 'birthday' GROUP BY b.`voucherID`", array($data->memberID),$logs);

					$logs->write_logs("Success - My Voucher Birthday all month", $file_name, array(array("response"=>"Success", "data"=>array($output))));
				}
			}elseif ($check_bdayvoucher[0]['bday_month'] == $check_bdayvoucher[0]['bday_base'] ) {
				# code...
				if ($check_bdayvoucher[0]['bday_month'] == (int) $data->month){
					$output = process_qry(" SELECT a.*, b.`description`, b.`terms`, COUNT(*) AS `count` FROM `earnvouchertable` a, `vouchertable` b WHERE a.`memberID` = ? AND DATE_FORMAT(NOW(), '%Y-%m-%d') >= DATE_FORMAT(b.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(b.`endDate` , '%Y-%m-%d') >= DATE_FORMAT(NOW(), '%Y-%m-%d') AND a.`voucherID` = b.`voucherID` AND a.`status` = 'active' AND b.`status` = 'active' AND b.`type` = 'birthday' GROUP BY b.`voucherID`", array($data->memberID),$logs);
					// $output = process_qry(" SELECT a.*, b.`description`, b.`terms`, COUNT(*) AS `count` FROM `earnvouchertable` a, `vouchertable` b WHERE a.`memberID` = ? AND DATE_FORMAT(a.`startDate`, '%Y-%m-%d') >= DATE_FORMAT(b.`startDate`, '%Y-%m-%d') AND DATE_FORMAT(b.`endDate` , '%Y-%m-%d') >= DATE_FORMAT(a.`endDate`, '%Y-%m-%d') AND a.`voucherID` = b.`voucherID` AND a.`status` = 'active' AND b.`status` = 'active' AND a.`type` = 'birthday' GROUP BY b.`voucherID`", array($data->memberID),$logs);

					// var_dump($output);
					$logs->write_logs("Success - My Voucher Birthday month", $file_name, array(array("response"=>"Success", "data"=>array($output))));
				}
				
			}

				if (count($output) > 0) {
					for ($iii=0; $iii<count($output); $iii++) {
						foreach ($output[$iii] as $array_key => $array_value) {
							if ($array_key == 'image') {
								$output[$iii][$array_key] =  DOMAIN . '/assets/images/vouchers/full/' . $array_value;						
								$output[$iii]['thumb'] = DOMAIN . '/assets/images/vouchers/thumb/' . $array_value;
								// $output[$x][$array_key] = $protocol. DOMAIN . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'vouchers' . DIRECTORY_SEPARATOR . 'full' . DIRECTORY_SEPARATOR . $array_value;
							}
						}
					}
				}
			return $output;			
		
		}else{

			return $output;
		}

	}
}

?>