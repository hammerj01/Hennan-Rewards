<?php

	if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
		require_once(dirname(__FILE__) . '/logs.class.php');
		$logs = NEW logs();
		$file_name = substr(strtolower(basename($_SERVER['PHP_SELF'])),0,strlen(basename($_SERVER['PHP_SELF'])));
		$logs->write_logs('Warning - Invalid Access', $file_name, array(array("_POST" => $_POST, "_GET" => $_GET)));
		header('Location: ../../../api.php');
	}

	class json {
		public static $file_name = 'json.class.php';

		public function fetch($param) {
			global $logs;
			global $error000;
			$file_name = self::$file_name;
			$data = (object) $param;

			if ($data->table == 'loctable') {
				$qry = "SELECT *, (SELECT `name` FROM `loccategorytable` WHERE `locCategoryID` = `loctable`.`locCategoryID` AND `status` = 'active') AS `locCategoryName` FROM `loctable` WHERE `status` = 'active'";
			} 

			// elseif ($data->table == 'loyaltytable') {
			// 	// $qry = "SELECT * FROM `loyaltytable` WHERE `status` = 'active' ORDER BY `points` ASC";
			// 	$qry =	"select * from loctable l inner join  loyaltytable lyl on l.`locID` = lyl.locID where l.`locFlag` = 'active' and l.`status` = 'active' and lyl.`status` = 'active'";
			// }
			 elseif ($data->table == 'earnsettingstable') {
				$qry = "SELECT * FROM `earnsettingstable` WHERE `status` = 'active'";
				// earn base on tablet location
				// $qry = process_qry()
			} elseif ($data->table == 'producttable') {
				$qry = "SELECT a.`prodID`, b.`brandID`, b.`name` AS `brandName`, a.`name`, a.`description`, b.`featuredProduct`, a.`price`, a.`image`, a.`status`, a.`dateAdded` FROM `producttable` a, `brandtable` b WHERE a.`status` = 'active' AND  b.`status` = 'active' AND a.`brandID` = b.`brandID` ORDER BY b.`order` ASC";
			} elseif ($data->table == 'posttable') {
				$qry = "SELECT * FROM `posttable` WHERE `status` = 'active'";
			} elseif ($data->table == 'brandtable') {
				$qry = "SELECT * FROM `brandtable` WHERE `status` = 'active'";
			}elseif ($data->table == 'termtable') {
				$qry = "SELECT * FROM `termtable` WHERE `status` = 'active'";
			}elseif ($data->table == 'faqtable') {
				$qry = "SELECT * FROM `faqtable` WHERE `status` = 'active' ORDER by `id` ASC";	
			}	
			elseif ($data->table == 'abouttable') {
				$qry = "SELECT * FROM `abouttable` WHERE `status` = 'active'";	
			}elseif ($data->table == 'socialmediatable') {
				$qry = "SELECT * FROM `socialmediatable` WHERE `status` = 'active'";
			}
			elseif ($data->table == 'offerstable'){
				$catTable =  process_qry("SELECT * FROM `categorytable` WHERE `status` = 'active'", array(), $logs);
				// var_dump($catTable);
				for ($i=0; $i<count($catTable); $i++) {
					$catTable[$i]['name'] =  ucwords(strtolower(html_entity_decode($catTable[$i]['name'], ENT_QUOTES, 'UTF-8')));
					if (($catTable[$i]["image"] != NULL) && ($catTable[$i]["image"] != " ")){
						$catTable[$i]["image"] =  DOMAIN . "/assets/images/category/full/" . html_entity_decode($catTable[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');
					}
				}

				$subcatTable = process_qry("SELECT * FROM `subcategorytable` WHERE `status` = 'active'", array(), $logs);
				
				for ($i=0; $i<count($subcatTable); $i++) {
					if (($subcatTable[$i]["image"] != NULL) && ($subcatTable[$i]["image"] != " ")){
						$subcatTable[$i]["image"] =  DOMAIN . "/assets/images/subcategory/full/" . html_entity_decode($subcatTable[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');

					}
				}

				$prodTable = process_qry("SELECT * from producttable WHERE `status` = 'active' ORDER BY `name` ASC", array(), $logs);
				// var_dump($prodTable);
				for ($i=0; $i<count($prodTable); $i++) {
					if (($prodTable[$i]["image"] != NULL) && ($prodTable[$i]["image"] != " ")){
						$prodTable[$i]["image"] =  DOMAIN . "/assets/images/products/full/" . html_entity_decode($prodTable[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');
					}
				}

				$output = array();
				array_push($output, array("categorytable"=>$catTable, "subcategorytable"=>$subcatTable, "producttable" => $prodTable));
				return json_encode(array(array("response"=>"Success", "data"=>$output)), JSON_UNESCAPED_SLASHES);

			}
			elseif ($data->table == 'loyaltytable'){
				$output = array();
				$mlocs = array();
				$mlyl = array();
				$resLocation = array();
				$resloyalty = array();

				$locations = process_qry("SELECT l.* from loctable l left join  loyaltytable lyl on `l`.`locID` = `lyl`.`locID` where `l`.`locFlag` = 'active' and `l`.`status` = 'active' and `lyl`.`status` = 'active' group by `l`.`locID` order by `locname` asc ",array(), $logs);
				
				
				for ($i=0; $i<count($locations); $i++) {
					$locations[$i]['locCategoryName'] =  ucwords(strtolower(html_entity_decode($locations[$i]['locCategoryName'], ENT_QUOTES, 'UTF-8')));
					$locations[$i]['name'] =  ucwords(strtolower(html_entity_decode($locations[$i]['name'], ENT_QUOTES, 'UTF-8')));

					if (($locations[$i]["image"] != NULL) && ($locations[$i]["image"] != " ")){
						$img  = html_entity_decode($locations[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');
						$locations[$i]["image"] = DOMAIN . "/assets/images/hotels/full/" . html_entity_decode($locations[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');

						$locations[$i]["image_thumb"] = DOMAIN . "/assets/images/hotels/thumb/" . $img;
					}
				}

				$loyalty = process_qry("SELECT lyl.* from loctable l right join  loyaltytable lyl on `l`.`locID` = `lyl`.`locID` where `l`.`locFlag` = 'active' and `l`.`status` = 'active' and `lyl`.`status` = 'active' order by `lyl`.`points`  ASC ",array(), $logs);

				for ($i=0; $i<count($loyalty); $i++) {
					$loyalty[$i]['locName'] =  ucwords(strtolower(html_entity_decode($loyalty[$i]['locName'], ENT_QUOTES, 'UTF-8')));
					
					if (($loyalty[$i]["image"] != NULL) && ($loyalty[$i]["image"] != " ")){
						$img1 = html_entity_decode($loyalty[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');
						$loyalty[$i]["image"] = DOMAIN . "/assets/images/rewards/full/" . html_entity_decode($loyalty[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');
						$loyalty[$i]["image_thumb"] = DOMAIN . "/assets/images/rewards/thumb/" . $img1;
					}
				}

				array_push($output, array("locations"=>$locations, "loyalty"=>$loyalty));
				return json_encode(array(array("response"=>"Success", "data"=> $output)), JSON_UNESCAPED_SLASHES);
			}
			elseif ($data->table ==  'taxtable'){
				$qry = "SELECT * FROM `taxtable` WHERE `status` = 'true'";
			}

			else {
				$logs->write_logs('Error - JSON Fetch', $file_name, array(array("response"=>"Error", "description"=>"Table does not exist.", "data"=>array($param))));
				return json_encode(array(array("response"=>"Error", "description"=>"Table does not exist.", "data"=>array($param))));
				die($error000);
			}

			$result = process_qry($qry, array());
			$output = array();
			$temp_brand= array();
			$brand= array();
			$items = array();

			if (count($result) > 0) {
				for ($i=0; $i<count($result); $i++) {
					foreach ($result[$i] as $array_key => $array_value) {
						if (($array_key == 'image') || ($array_key == 'icon') || ($array_key == 'featuredProduct')) {
							if ($array_value != NULL && $array_value != " ") {
								$temp_url = NULL;

								if ($data->table == 'earnsettingstable') {
									$temp_url = DOMAIN . "/assets/images/earn/full/";
								} elseif ($data->table == 'posttable') {
									$temp_url = DOMAIN . "/assets/images/posts/full/";
								} elseif ($data->table == 'producttable') {
									if ($array_key == 'image') {
										$temp_url = DOMAIN . "/assets/images/products/full/";
									} elseif ($array_key == 'featuredProduct') {
										$temp_url = DOMAIN . "/assets/images/brands/full/";
									}
								} elseif ($data->table == 'brandtable') {
									$temp_url = DOMAIN . "/assets/images/brands/full/";
								}

								$result[$i][$array_key] = $temp_url . html_entity_decode($array_value, ENT_QUOTES, 'UTF-8')."?".date('is');
							}
						}

						if ($data->table == 'loctable') {
							if ($array_key == 'name') {
								$result[$i][$array_key] = ucwords(strtolower(html_entity_decode($array_value, ENT_QUOTES, 'UTF-8')));
							}
						}

					}

					if ($data->table == 'producttable') {
						if (!in_array($result[$i]['brandID'], $temp_brand)) {
							array_push($temp_brand, $result[$i]['brandID']);
							array_push($brand, array("brandID"=>$result[$i]['brandID'], "name"=>$result[$i]['brandName'], "image"=>$result[$i]['featuredProduct'], "description"=>$result[$i]['description']));
						}

						array_push($items, array("brandID"=>$result[$i]['brandID'], "prodID"=>$result[$i]['prodID'], "name"=>$result[$i]['name'], "description"=>$result[$i]['description'], "price"=>$result[$i]['price'], "image"=>$result[$i]['image'], "status"=>$result[$i]['status'], "dateAdded"=>$result[$i]['dateAdded']));
					} else {
						array_push($output, $result[$i]);
					}
				}
			}

			if ($data->table == 'producttable') {
				array_push($output, array("brand"=>$brand, "products"=>$items));
			} elseif ($data->table == "loctable") {
				// $locCategory = process_qry("SELECT `locCategoryID`, `name`,`description`, `image` FROM `loccategorytable` WHERE `status` = 'active'", array());
					$locCategory = process_qry("SELECT a.`locCategoryID`, a.`name`,a.`description`, a.`image` FROM `loccategorytable` a, `loctable` b  WHERE a.`status` = 'active' and a.`locCategoryID` = `b`.`locCategoryID` group by locCategoryID", array(), $logs);
				for ($i=0; $i<count($locCategory); $i++) {
					if (($locCategory[$i]["image"] != NULL) && ($locCategory[$i]["image"] != " ")){
						$locCategory[$i]["image"] = DOMAIN . "/assets/images/locCategorys/full/" . html_entity_decode($locCategory[$i]["image"], ENT_QUOTES, 'UTF-8')."?".date('is');
					}
				}

				$location = $output;
				$output = array();
				array_push($output, array("locCategory"=>$locCategory, "location"=>$location));
			}

			// var_dump($output);
			
			return json_encode(array(array("response"=>"Success", "data"=>$output)), JSON_UNESCAPED_SLASHES);
		}

	}

?>