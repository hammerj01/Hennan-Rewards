<?php


if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
	require_once(dirname(__FILE__) . '/../class/logs.class.php');
	$logs = NEW logs();
	$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
	$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
	header('Location: ../../../api.php');
}

class reactivate_deactivate
{
	public static $file_name = 'reactivate_deactivate.class.php';	
	
	function get_reactivate_deactivate($param)	
	{
		global $logs;
		global $protocol;
		global $error000;
		$file_name = self::$file_name;
		$output = array();
		$data = (object) $param;

		$dateNow = DATE('Y-m-d H:i:s');
		if ($data->actionOfVoucher == 'reactivate'){
			$qry = process_qry("select * from redeemvouchertable where memberID = ? and voucherID = ? and redeemVID= ? AND DATE_FORMAT(?, '%Y-%m-%d') >= DATE_FORMAT(`startDate`, '%Y-%m-%d') AND DATE_FORMAT(`endDate`, '%Y-%m-%d') >= DATE_FORMAT(?, '%Y-%m-%d')  AND status ='active'",array($data->memberID,$data->voucherID,$data->redeemVID,$dateNow,$dateNow),$logs); 
			if (count($qry) > 0 ){
				$insert_reactivation = process_qry("INSERT INTO earnvouchertable(`voucherID`,`memberID`,`email`,`name`,`image`,`month`,`startDate`,`endDate`,`type`,`action`,`status`,`dateAdded`,`dateModified`) SELECT `voucherID`,`memberID`,`email`,`name`,`image`,`month`,`startDate`,`endDate`,`type`,`action`,`status`, ? AS `dateAdded`, ? AS `dateModified` from redeemvouchertable where memberID = ? and voucherID = ? and redeemVID = ? AND DATE_FORMAT(?, '%Y-%m-%d') >= DATE_FORMAT(`startDate`, '%Y-%m-%d') AND DATE_FORMAT(`endDate`, '%Y-%m-%d') >= DATE_FORMAT(?, '%Y-%m-%d')  AND status ='active'", array($dateNow,$dateNow,$data->memberID,$data->voucherID,$data->redeemVID,$dateNow,$dateNow),$logs);
				
				if(count($insert_reactivation) > 0){
					$logs->write_logs("Success - reactivate and delete redeemvouchertable", $file_name, array(array("response"=>"Success", "data"=>array("voucherID"=>$qry[0]['voucherID'],"memberID"=>$qry[0]['memberID'],"email"=>$qry[0]['email'],"name"=>$qry[0]['name'],"image"=>$qry[0]['image'],"month"=>$qry[0]['month'],"startDate"=>$qry[0]['startDate'],"endDate"=>$qry[0]['endDate'],"type"=>$qry[0]['type'],"action"=>$qry[0]['action']))));
					$qry = process_qry("DELETE from redeemvouchertable where memberID = ? and voucherID = ? and redeemVID= ? AND DATE_FORMAT(?, '%Y-%m-%d') >= DATE_FORMAT(`startDate`, '%Y-%m-%d') AND DATE_FORMAT(`endDate`, '%Y-%m-%d') >= DATE_FORMAT(?, '%Y-%m-%d')  AND status ='active'",array($data->memberID,$data->voucherID,$data->redeemVID,$dateNow,$dateNow),$logs);
					return json_encode(array(array("response"=>"Success")));
				}else{
					return json_encode(array(array("response"=>"Error", "description"=>"Cannot reactivate.")));
				}

			}else{
				return json_encode(array(array("response"=>"Error", "description"=>"Invalid voucher.")));
			}

		}elseif ($data->actionOfVoucher == 'deactivate') {
			$qry = process_qry("select * from earnvouchertable where memberID = ? and voucherID = ? and id= ? AND email= ? AND status ='active'",array($data->memberID,$data->voucherID,$data->id,$data->email),$logs); 
			
			if (count($qry) > 0 ){
				$insert_deactivate = process_qry("INSERT INTO redeemvouchertable(`redeemVID`,voucherID,memberID,memberCode,email,name,image,month,startDate,endDate,type,action,status,deviceID,locID,locName,branchCode,terminalNum,transactionID,transactionType,version,earnDate,dateAdded,dateModified) SELECT ? AS `redeemVID`,voucherID,memberID,'web' as memberCode,email,name,image,month,startDate,endDate,type,action,status,'web' as deviceID,'web' as locID,'web' as locName,'web' as branchCode,'web' as terminalNum,? as transactionID,NULL as transactionType,'web' as version,? as earnDate,? as dateAdded, ? as dateModified from earnvouchertable where memberID = ? and voucherID = ? and id= ? AND email= ? AND status ='active'",array($data->redeem_ID, $data->trans_ID,$dateNow,$dateNow,$dateNow,$data->memberID,$data->voucherID,$data->id,$data->email),$logs);
				$logs->write_logs("Success - deactivate and delete earnvoucher record", $file_name, array(array("response"=>"Success", "data"=>array("voucherID"=>$qry[0]['voucherID'],"memberID"=>$qry[0]['memberID'],"email"=>$qry[0]['email'],"name"=>$qry[0]['name'],"image"=>$qry[0]['image'],"month"=>$qry[0]['month'],"startDate"=>$qry[0]['startDate'],"endDate"=>$qry[0]['endDate'],"type"=>$qry[0]['type'],"action"=>$qry[0]['action']))));
				if (count($insert_deactivate) > 0){
					$delete_vouchers_available = process_qry("DELETE from earnvouchertable where memberID = ? and voucherID = ? and id = ? AND email= ? AND status ='active'",array($data->memberID,$data->voucherID,$data->id,$data->email),$logs); 
					if(count($delete_vouchers_available) > 0){
						return json_encode(array(array("response"=>"Success")));	
					}else{
						return json_encode(array(array("response"=>"Error", "description"=>"Voucher cannot be deleted.")));
					}
					
				
					
				}else{
					return json_encode(array(array("response"=>"Error", "description"=>"Invalid voucher.")));
				}
			}

		}

	}




}
?>