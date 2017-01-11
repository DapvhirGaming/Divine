<?php
try {
	$host = "localhost";
	$user = "root";
	$pass = "61fb00e03d0f8645";
	$dbname = "ragnarok_main";
	$dsn = 'mysql:host=' . $host . ';dbname=' . $dbname.';';
	// Set options
	$options = array(
		PDO::ATTR_PERSISTENT    => true,
		PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_EMULATE_PREPARES	=> false,
		PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC
	);
	$pdo = new PDO($dsn, $user, $pass, $options);
	$target_dir = "uploads/";
	if($_POST['import']) {
		$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
		$file_info = pathinfo($target_file);
		if($file_info['basename'] == "npc.zip") {
			if($file_info['extension'] == "zip") {
				if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
					echo '<p>Zip file detected...<a href="import-npcs.php?action=unzip">Unzip NPCs</a></p>';
				} else {
					echo "<p>Sorry, there was an error uploading your file.</p>".$target_file;
				}
			} else {
				echo '<p>Please make sure you are uploading a .zip file.</p>';
			}
		} else {
			echo '<p>Invalid file name.</p>';
		}
	}
	if($_GET['action'] == 'unzip') {
		$file = 'uploads/npc.zip';
		// get the absolute path to $file
		$path = pathinfo(realpath($file), PATHINFO_DIRNAME);
		$zip = new ZipArchive;
		$res = $zip->open($file);
		if ($res === TRUE) {
			// extract it to the path we determined above
			$zip->extractTo('npc/');
			$zip->close();
			echo "<p>WOOT! $file extracted to $path...<a href=\"import-npcs.php?action=read\">Read Files</a></p>";
		} else {
  			echo "<p>Doh! I couldn't open $file</p>";
		}
	} elseif($_GET['action'] == 'read') {
		$npc_files_array = array();
		$npc_confs = array("npc/scripts_athena.conf", 'npc/scripts_custom.conf', 'npc/scripts_guild.conf', 'npc/scripts_jobs.conf', 'npc/scripts_mapflags.conf', 'npc/scripts_monsters.conf', 'npc/scripts_test.conf', 'npc/scripts_warps.conf', 'npc/re/scripts_athena.conf', 'npc/re/scripts_guild.conf', 'npc/re/scripts_jobs.conf', 'npc/re/scripts_mapflags.conf', 'npc/re/scripts_monsters.conf', 'npc/re/scripts_warps.conf');
		$file = "npc/scripts_athena.conf";
		foreach($npc_confs as $c) {
			$handle = fopen($c, "r");
			$file_info = fread($handle, filesize($c));
			fclose($handle);
			$array = preg_split("/\r\n|\n|\r/", $file_info);
			foreach($array as $key=>$n) {
				if($n != "") {
					if(substr($n,0,2) != "//") {
						$npc_files_array[] = $n;
					}
				}
			}
		}
		/**  NPC File List is compiled.  Now to load each file and find npcs and monsters for spawn points */
		$npcs_array = array();
		$shops_array = array();
		$monsters_array = array();
		$warps_array = array();
		$dups_array = array();
		foreach($npc_files_array as $f) {
			//echo '<h1>'.$f.'</h1>';
			if(substr($f,0,3) == "npc") {
				$handle = fopen(substr($f, 5), "r");
				$file_info = fread($handle, filesize(substr($f, 5)));
				fclose($handle);
				$array = preg_split("/\r\n|\n|\r/", $file_info);
				foreach($array as $a) {
					$comp = preg_split("/[\t]/", $a);
					if(substr($comp[2],0,2) != "//") {
						if(count($comp) > 1) {
							if(trim($comp[1]) == "script") {
								if(trim(strlen($comp[0])) > 4) {
									//echo 'NPC FOUND '.$comp[2].' Location: '.$comp[0].' Details: ';
									$location = explode(',', $comp[0]);
									if(preg_match('/,/', $comp[3])) {
										$sprite = explode(',', $comp[3]);
										if(is_numeric($sprite[0]) && $sprite[0] >= 0) {
											$sprite = $sprite[0];
										} else {
											$sprite = 0;
										}
									} elseif(is_numeric($comp[3])) {
										if($comp[3] >= 0) {
											$sprite = $comp[3];
										} else {
											$sprite = 0;
										}
									} else {
										$sprite = 0;
									}
									$npcs_array[] = array('name' => $comp[2], 'map' => $location[0], 'x' => $location[1], 'y' => $location[2], 'sprite' => $sprite);
									//print_r($comp);
									//echo '<br />';
								} else {
									//echo '<b>SKIPPED:</b> ';
									//print_r($comp);
									//echo '<br />';
								}
							}
							if(trim($comp[1]) == "shop") {
								if(trim(strlen($comp[0])) > 4) {
									//echo 'Shop Found '.$comp[2].' Location: '.$comp[0].' Details: ';
									//print_r($comp);
									$location = explode(',', $comp[0]);
									$items = explode(',', $comp[3]);
									$sprite = $items[0];
									unset($items[0]);
									$shop_items = array();
									foreach($items as $i) {
										$seperate = explode(":", $i);
										$shop_items[] = array('item_id' => $seperate[0], 'price' => $seperate[1]);
									}
									$shops_array[] = array('name' => $comp[2], 'map' => $location[0], 'x' => $location[1], 'y' => $location[2], 'sprite' => $sprite, 'items' => $shop_items);
									//echo '<br />';
								} else {
									//echo '<b>SKIPPED:</b> ';
									//print_r($comp);
									//echo '<br />';
								}
							}
							if(preg_match("/duplicate/", $comp[1])) {
								/**
								 * @todo Need to sort out the dups between npc and warps
								 */
								$found = false;
								if(trim(strlen($comp[0])) > 4) {
									if(substr($comp[0],0,2) != '//') {
										//echo 'NPC Duplicate Found '.$comp[2].' Location: '.$comp[0].' Details: ';
										$location = explode(',', $comp[0]);
										if(preg_match('/,/', $comp[3])) {
											$sprite = explode(',', $comp[3]);
											if(is_numeric($sprite[0]) && $sprite[0] >= 0) {
												$sprite = $sprite[0];
											} else {
												$sprite = 0;
											}
										} elseif(is_numeric($comp[3])) {
											if($comp[3] >= 0) {
												$sprite = $comp[3];
											} else {
												$sprite = 0;
											}
										} else {
											$sprite = 0;
										}
										$npcs_array[] = array('name' => $comp[2], 'map' => $location[0], 'x' => $location[1], 'y' => $location[2], 'sprite' => $sprite);
									}
									//print_r($comp);
									//echo '<br />';
								} else {
									//echo '<b>SKIPPED:</b> ';
									//print_r($comp);
									//echo '<br />';
								}
							}
							if(trim($comp[1]) == "monster") {
								if(trim(strlen($comp[0])) > 4) {
									if(substr($comp[0],0,2) != '//') {
										//echo 'Monster Found '.$comp[2].' Location: '.$comp[0].' Details: '.$comp[3].'<br />';
										$location = explode(',', $comp[0]);
										$details = explode(',', $comp[3]);
										$monsters_array[] = array('mob_id' => $details[0], 'name' => $comp[2], 'map' => $location[0], 'x' => $location[1], 'y' => $location[2], 'range_x' => $location[3], 'range_y' => $location[4], 'count' => $details[1], 'time_to' => $details[2], 'time_from' => $details[3]);
									}
								} else {
									//echo '<b>SKIPPED:</b> ';
									//print_r($comp);
									//echo '<br />';
								}
							}
							if(trim($comp[1] == "warp")) {
								if(trim(strlen($comp[0])) > 4) {
									if(substr($comp[0],0,2) != '//') {
										//echo 'Warp Found '.$comp[2].' Location: '.$comp[0].' Details: ';
										//print_r($comp);
										$location = explode(',', $comp[0]);
										$destination = explode(',', $comp[3]);
										$warps_array[] = array('map' => $location[0], 'x' => $location[1], 'y' => $location[2], 'to' => $destination[2], 'tx' => $destination[3], 'ty' => $destination[4]);
										//echo '<br />';
									}
								} else {
									//echo '<b>SKIPPED:</b> ';
									//print_r($comp);
									//echo '<br />';
								}
							}
						}
					}
					//echo '<br />'.$a;
				}
				//echo $file_info;
				//echo '<br /><br /><br />';
			}
		}
		/** Removed all npcs from the table to re-insert */
		$pdo->query("TRUNCATE npcs")->execute();
		/** Prepare the sql statement */
		$stmt = $pdo->prepare("INSERT INTO npcs(map, x, y, name, sprite)VALUES(?, ?, ?, ?, ?)");
		/** Let the inserting begin! */
		foreach($npcs_array as $n) {
			print_r($n);
			if($n['map'] != '' && $n['x'] != '' && $n['y'] != 'y') {
				$stmt->execute(array($n['map'], $n['x'], $n['y'], $n['name'], $n['sprite']));
			}
			echo '<br /><br />';
		}
		echo '<hr />';
		echo '<h1>Shops</h1>';
		/** Only need to remove what shop sells since all npcs were removed already */
		$pdo->query("TRUNCATE shops_sells")->execute();
		/** Let the inserting begin! */
		foreach($shops_array as $s) {
			print_r($s);
			$pdo->prepare("INSERT INTO npcs(map, x, y, name, sprite, is_shop)VALUES(?, ?, ?, ?, ?, ?)")->execute(array($s['map'], $s['x'], $s['y'], $s['name'], $s['sprite'], true));
			$shopID = $pdo->lastInsertId();
			foreach($s['items'] as $i) {
				$stmt = $pdo->prepare("SELECT * FROM item_db WHERE id=:id");
				$stmt->bindParam(':id', $i['item_id'], PDO::PARAM_INT);
				$stmt->execute();
				$item_info = $stmt->fetch();
				//echo '<br /><br />';
				//print_r($item_info);
				if(count($item_info) > 0) {
					if($item_info['name_japanese'] == '') {
						$item_info['name_japanese'] = 'Unknown';
						$i['price'] = 0;
					}
					if($i['price'] == -1 && $item_info['price_buy'] > 0) {
						$i['price'] = $item_info['price_buy'];
					}
				} else {
					$item_info['name_japanese'] = 'Unknown';
					$i['price'] = 0;
				}
				//print_r($db->queries);
				$pdo->prepare("INSERT INTO shops_sells(id_shop, item, price, name)VALUES(?, ?, ?, ?)")->execute(array($shopID, $i['item_id'], $i['price'], $item_info['name_japanese']));
			}
			echo '<br /><br />';
		}
		echo '<hr />';
		echo '<h1>Mob Spawn Points</h1>';
		$pdo->query("TRUNCATE mob_spawns")->execute();
		$stmt = $pdo->prepare("INSERT INTO mob_spawns(map, x, y, range_x, range_y, mob_id, count, name, time_to, time_from)VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		foreach($monsters_array as $m) {
			print_r($m);
			$stmt->execute(array($m['map'], $m['x'], $m['y'], $m['range_x'], $m['range_y'], $m['mob_id'], $m['count'], $m['name'], $m['time_to'], $m['time_from']));
			echo '<br /><br />';
		}
		echo '<hr />';
		echo '<h1>Warps</h1>';
		$pdo->query("TRUNCATE warps")->execute();
		$stmt = $pdo->prepare("INSERT INTO warps(map, x, y, to_map, tx, ty)VALUES(?, ?, ?, ?, ?, ?)");
		foreach($warps_array as $w) {
			print_r($w);
			if($w['to'] != '') {
				$stmt->execute(array($w['map'], $w['x'], $w['y'], $w['to'], $w['tx'], $w['ty']));
				echo '<br /><br />';
			}
		}
	} else {
		?><!DOCTYPE html>
<html>
<body>
<form method="post" enctype="multipart/form-data">
    Select image to upload:
    <input type="file" name="fileToUpload" id="fileToUpload" />
    <input type="submit" value="Upload zip NPC" name="import" />
</form>

</body>
</html><?php
	}
} catch (PDOException $e) {
	print_r($e);
}