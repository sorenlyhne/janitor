<?php
/**
* @package wires
*/
/**
*
*/

/**
* includes
*/

//include_once("class/basics/itemtype.core.class.php");

/**
* This class holds Item functionallity.
*
*/
class Item {

	/**
	* Init, set varnames, validation rules
	*/
	function __construct() {
		// $query = new Query();
		// $this->itemtypes = $query->get(UT_BAS_ITT);
		// 
		// $this->itemtype = false;
		// $this->itemtype_id = false;
		
	}

	/**
	* Set itemtype
	* Sets both this->itemtype and this->itemtype_id
	*
	* @param $index Itemtype or itemtype_id (both will work)
	* @param $update Optional parameter to indicate resetting of itemtype (not part of an iteration, thus it cannot result in mixed type)
	*/
/*	function setItemtype($index, $update = false) {

		// no index
		if(!$index) {
			$this->itemtype = false;
			$this->itemtype_id = false;
		}
		// multiple itemtypes
		else if(!$update && $this->itemtype && $this->itemtype_id != $index && $this->itemtype != $index) {
			$this->itemtype = "mixed";
			$this->itemtype_id = false;
		}
		// numeric index
		else if(is_numeric($index)) {
			$this->itemtype = $this->itemtypes["values"][array_search($index, $this->itemtypes["id"])];
			$this->itemtype_id = $index;
		}
		// itemname index
		else {
			$this->itemtype = $index;
			$this->itemtype_id = $this->itemtypes["id"][array_search($index, $this->itemtypes["values"])];
		}

	}
*/
	/**
	* Get matching type object instance
	*
	* @return return instance of type object
	*/
	function TypeObject($itemtype) {

		// include generic type (for mixed itemtypes)
		if($itemtype == "mixed" || !$itemtype) {
			$itemtype = "mixed";
			$class = "TypeMixed";
		}
		else {
			$class = "Type".ucfirst($itemtype);
		}

		if(!isset($this->itemtypes["class"][$itemtype])) {
			include_once("class/items/type.$itemtype.class.php");
			$this->itemtypes["class"][$itemtype] = new $class();

		}
		return $this->itemtypes["class"][$itemtype];
	}


	/**
	* Global getItem
	*
	* @param $id Item id or sindex to get
	*/
	function getItem($id) {
//		print "get item:" . "SELECT * FROM ".UT_ITE." WHERE sindex = '$id' OR id = '$id'";

		$item = array();

		$query = new Query();
		if($query->sql("SELECT * FROM ".UT_ITE." WHERE sindex = '$id' OR id = '$id'")) {


			$item["id"] = $query->result(0, "id");

			// create sindex value if it doesn't exist (backwards compatibility)
			$item["itemtype"] = $query->result(0, "itemtype");
//			$sindex = $query->result(0, "sindex");
			$item["sindex"] = $query->result(0, "sindex");

			$item["status"] = $query->result(0, "status");


			$item["user_id"] = $query->result(0, "user_id");

			$item["created_at"] = $query->result(0, "created_at");
			$item["modified_at"] = $query->result(0, "modified_at");
			$item["published_at"] = $query->result(0, "published_at");
                                                                                                                                                                                                              
//			$this->setItemtype($this->item["itemtype_id"], true);
			return $item;
		}
		return false;
	}

	/**
	* Global getCompleteItem (both getItem and get on itemtype)
	*
	* @param $id Item id or sindex to get
	*/
	function getCompleteItem($id) {
		$item = $this->getItem($id);
		if($item) {
			$item = array_merge($item, $this->TypeObject($item["itemtype"])->get($item["id"]));

			// get item tags
			// $item["tags"] = $this->getTags($id);

			return $item;
		}
		return false;
	}

	/**
	* Get all matching items
	*
	* @param String $itemtype Item type name
	* @param String $order 
	* @param String $sindex Optional navigation index - s(earch)index
	*
	* @return Array [id][] + [itemtype][]
	*/
	function getItems($options = false) {

		if($options !== false) {
			foreach($options as $option => $value) {
				switch($option) {
					case "itemtype" : $itemtype = $value; break;
					case "status" : $status = $value; break;
					case "tags" : $tags = $value; break;
					case "sindex" : $sindex = $value; break;
					case "order" : $order = $value; break;
					case "limit" : $limit = $value; break;
					
					// TODO: implement date ranges

				}

			}
		}


		$query = new Query();

		$SELECT = array();
		$FROM = array();
		$WHERE = array();
		$GROUP_BY = "";
		$ORDER = array();


		$SELECT[] = "items.id";
		$SELECT[] = "items.sindex";
		$SELECT[] = "items.status";
		$SELECT[] = "items.itemtype";
		$SELECT[] = "items.user_id";

		$SELECT[] = "items.created_at";
		$SELECT[] = "items.modified_at";
		$SELECT[] = "items.published_at";

		// sindex mapped to nav items
		// if(isset($sindex)) {
		// 	$SELECT[] = UT_NAV_ITE.".sequence";
		// 	$FROM[] = UT_ITE." as items LEFT JOIN ".UT_NAV_ITE." ON items.id = ".UT_NAV_ITE.".item_id  AND ".UT_NAV_ITE.".sindex = '$sindex'";
		// 	$ORDER[] = UT_NAV_ITE.".sequence";
		// 
		// 	$tags .= ",".$this->getNavTags($sindex);
		// }
		// else {
	 	$FROM[] = UT_ITE." as items";
		// }

		if(isset($status)) {
			$WHERE[] = "items.status = $status";
		}

		// TODO: implement dateranges
		// if(isset($published_at)) {
		// 	$WHERE[] = "items.published_at = $published_at";
		// }

		if(isset($itemtype)) {
			$WHERE[] = "items.itemtype = '$itemtype'";
		}

		if(isset($tags)) {
			$FROM[] = UT_TAGGINGS . " as item_tags";
			$FROM[] = UT_TAG . " as tags";
			$tag_array = explode(",", $tags);
			foreach($tag_array as $tag) {
//				$exclude = false;
				// tag id
				if($tag) {

					// dechipher tag
					$exclude = false;

					// negative tag, exclude
					if(substr($tag, 0, 1) == "!") {
						$tag = substr($tag, 1);
						$exclude = true;
					}

					// if tag has both context and value
					if(strpos($tag, ":")) {
						list($context, $value) = explode(":", $tag);
					}
					// only context present, value false
					else {
						$context = $tag;
						$value = false;
					}

					if($context || $value) {
						// Negative !tag
						if($exclude) {
							$WHERE[] = "items.id NOT IN (SELECT item_id FROM ".UT_TAGGINGS." as item_tags, ".UT_TAG." as tags WHERE item_tags.tag_id = tags.id" . ($context ? " AND tags.context = '$context'" : "") . ($value ? " AND tags.value = '$value'" : "") . ")";
//							$WHERE[] = "items.id NOT IN (SELECT item_id FROM ".UT_TAGGINGS." as item_tags, ".UT_TAG." as tags WHERE item_tags.tag_id = tags.id" . ($context ? " AND tags.context = '$context'" : "") . ($value ? " AND tags.value = '$value'" : "") . ")";
						}
						// positive tag
						else {
							$WHERE[] = "items.id IN (SELECT item_id FROM ".UT_TAGGINGS." as item_tags, ".UT_TAG." as tags WHERE item_tags.tag_id = tags.id" . ($context ? " AND tags.context = '$context'" : "") . ($value ? " AND tags.value = '$value'" : "") . ")";
	//						$WHERE[] = "items.id IN (SELECT item_id FROM ".UT_TAGGINGS." as item_tags, ".UT_TAG." as tags WHERE item_tags.tag_id = '$tag' OR (item_tags.tag_id = tags.id AND tags.name = '$tag'))";
						}
					}
				}
			}
		}

		$GROUP_BY = "items.id";


		// add item-order specific SQL
		if(isset($order)) {
			$ORDER[] = $order;
		}

		$ORDER[] = "items.published_at DESC";

		if(isset($limit)) {
			$limit = " LIMIT $limit";
		}
		else {
			$limit = "";
		}

		$items = array();

//		print $query->compileQuery($SELECT, $FROM, array("WHERE" => $WHERE, "GROUP_BY" => $GROUP_BY, "ORDER" => $ORDER)) . $limit;
		$query->sql($query->compileQuery($SELECT, $FROM, array("WHERE" => $WHERE, "GROUP_BY" => $GROUP_BY, "ORDER" => $ORDER)) . $limit);
		for($i = 0; $i < $query->count(); $i++){

			$item = array();

			$item["id"] = $query->result($i, "items.id");
			$item["itemtype"] = $query->result($i, "items.itemtype");

//			$item_sindex = $query->result($i, "items.sindex");
			$item["sindex"] = $query->result($i, "items.sindex"); //$item_sindex ? $item_sindex : $this->sindex($item["id"]);

			$item["status"] = $query->result($i, "items.status");

			$item["user_id"] = $query->result($i, "items.user_id");

			$item["created_at"] = $query->result($i, "items.created_at");
			$item["modified_at"] = $query->result($i, "items.modified_at");
			$item["published_at"] = $query->result($i, "items.published_at");

			$items[] = $item;
		}

		return $items;
	}


	function getSetItems($set) {

		$items = array();

		$query = new Query();
//		print "SELECT * FROM ".UT_ITE." as items, " . UT_ITE_SET . " as item_set, " . UT_ITE_SET_ITE . " as set_items WHERE item_set.name = '$set' AND item_set.item_id = set_items.set_id AND items.id = set_items.item_id ORDER BY set_items.position, items.published_at<br>";
		$query->sql("SELECT * FROM ".UT_ITE." as items, " . UT_ITE_SET . " as item_set, " . UT_ITE_SET_ITE . " as set_items WHERE item_set.name = '$set' AND item_set.item_id = set_items.set_id AND items.id = set_items.item_id ORDER BY set_items.position, items.published_at");
		$results = $query->results();

//		foreach($results as $item);
		for($i = 0; $i < $query->count(); $i++){

			$item = array();

			$item["id"] = $query->result($i, "items.id");
			$item["itemtype"] = $query->result($i, "items.itemtype");

//			$item_sindex = $query->result($i, "items.sindex");
			$item["sindex"] = $query->result($i, "items.sindex"); //$item_sindex ? $item_sindex : $this->sindex($item["id"]);

			$item["status"] = $query->result($i, "items.status");

			$item["user_id"] = $query->result($i, "items.user_id");

			$item["created_at"] = $query->result($i, "items.created_at");
			$item["modified_at"] = $query->result($i, "items.modified_at");
			$item["published_at"] = $query->result($i, "items.published_at");

			$items[] = $item;
		}

		return $items;
	}





	/**
	* set sIndex value for item
	*
	* @param string $item_id Item id
	* @param string $sindex
	* @return String final/valid sindex
	*/
	function sindex($item_id, $sindex = false) {
		$query = new Query();

		if($sindex) {
			$sindex = superNormalize(substr($sindex, 0, 40));

			// check for existance
			if(!$query->sql("SELECT sindex FROM ".UT_ITE." WHERE sindex = '$sindex' AND id != $item_id")) {
				$query->sql("UPDATE ".UT_ITE." SET sindex = '$sindex' WHERE id = $item_id");
			}
			// try with timestamped variation
			else {
				$query->sql("SELECT published_at FROM ".UT_ITE." WHERE id = $item_id");
				$sindex = $this->sindex($item_id, $query->result(0, "published_at")."_".$sindex);
			}
		}
		else {
			$query->sql("SELECT itemtype FROM ".UT_ITE." WHERE id = $item_id");
			$itemtype = $query->result(0, "itemtype");

			$typeObject = $this->TypeObject($itemtype);

			if(method_exists($typeObject, "sindexBase")) {
				$sindex = $typeObject->sindexBase($item_id);
			}
			else if($query->sql("SELECT name FROM ".$typeObject->db." WHERE item_id = " . $item_id)) {
				$sindex = $query->result(0, "name");
			}
			
			$sindex = $this->sindex($item_id, $sindex);
		}
		return $sindex;
	}


	// checks posted values and saves item if all informations is available
	function saveItem() {

		// TODO: user_id
		// TODO: access validation
		// TODO: format of published_at

		$itemtype = RESTParams(1);
		$typeObject = $this->TypeObject($itemtype);

		if($typeObject) {
			$query = new Query();

			$published_at = getPost("published_at") ? toTimestamp(getPost("published_at")) : false;

			// create item
			$query->sql("INSERT INTO ".UT_ITE." VALUES(DEFAULT, DEFAULT, 0, '$itemtype', DEFAULT, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, ".($published_at ? "'$published_at'" : "CURRENT_TIMESTAMP").")");
			$new_id = $query->lastInsertId();

			if($new_id && $typeObject->save($new_id)) {

				// add tags
				$tags = getPost("tags");
				foreach($tags as $tag) {
					if($tag) {
						$this->addTag($new_id, $tag);
					}
				}

				// create sindex
				$this->sindex($new_id);

				return true;
			}
			else if($new_id) {

				// save failed, remove item again
				$query->sql("DELETE FROM ".UT_ITE." WHERE id = $new_id");

			}

		}
		return false;
	}


	// update item - does not update tags which is a separate process entirely
	function updateItem($id) {
//		print "update item<br>";

		// TODO: user_id
		// TODO: access validation
		// TODO: format of published_at

		$item = $this->getItem($id);
		$typeObject = $this->TypeObject($item["itemtype"]);

		if($typeObject) {
//			print "typeobject:" . $item["itemtype"] . "<br>";
			$query = new Query();

			// is published_at posted?
			$published_at = getPost("published_at") ? toTimestamp(getPost("published_at")) : false;

//			print "published_at:" . $published_at ."<br>";

//			print "UPDATE ".UT_ITE." SET modified_at=CURRENT_TIMESTAMP ".($published_at ? "published_at=$published_at" : "")." WHERE id = $id<br>";
			// create item
			$query->sql("UPDATE ".UT_ITE." SET modified_at=CURRENT_TIMESTAMP ".($published_at ? ",published_at='$published_at'" : "")." WHERE id = $id");

			if($typeObject->update($id)) {

				// update sindex
				$this->sindex($id);

				return true;
			}

		}
		return false;
	}

	// upload to item_id/variant
	// checks content of $_FILES, looks for uploaded file where type matches $type and uploads
	// supports video, audio, image
	function upload($item_id, $type, $variant=false) {

		if(isset($_FILES["files"])) {
//			print_r($_FILES["files"]);

			foreach($_FILES["files"]["name"] as $index => $value) {
				if(!$_FILES["files"]["error"][$index]) {

					$extension = false;
					$temp_file = $_FILES["files"]["tmp_name"][$index];
					$temp_type = $_FILES["files"]["type"][$index];

					if(preg_match("/".$type."/", $temp_type)) {

						$variant = $variant ? "/".$variant : "";


						// video upload
						if(preg_match("/video/", $temp_type)) {

							include_once("class/system/video.class.php");
							$Video = new Video();

							$info = $Video->info($temp_file);
							// check if we can get relevant info about movie
							if($info) {
								// TODO: add extension to Video Class
								// TODO: add better bitrate detection to Video Class
//								$extension = $info["extension"];
//								$bitrate = $info["bitrate"];

								$width = $info["width"];
								$height = $info["height"];

								$output_file = PRIVATE_FILE_PATH."/".$item_id.$variant."/mov";

//								print $output_file . "<br>";
								FileSystem::removeDirRecursively(dirname($output_file));
								FileSystem::removeDirRecursively(PUBLIC_FILE_PATH."/".$item_id.$variant);
								FileSystem::makeDirRecursively(dirname($output_file));

								copy($temp_file, $output_file);
								unlink($temp_file);
							}

						}
						// audio upload
						else if(preg_match("/audio/", $temp_type)) {

							include_once("class/system/audio.class.php");
							$Audio = new Audio();

 							$info = $Audio->info($temp_file);
//							print_r($info);
// 							// check if we can get relevant info about movie
 							if($info) {
 								$output_file = PRIVATE_FILE_PATH."/".$item_id.$variant."/mp3";
// 
// 								print $output_file . "<br>";
 								FileSystem::removeDirRecursively(dirname($output_file));
 								FileSystem::removeDirRecursively(PUBLIC_FILE_PATH."/".$item_id.$variant);
 								FileSystem::makeDirRecursively(dirname($output_file));

								copy($temp_file, $output_file);
								unlink($temp_file);
							}

						}
						// image upload
						else if(preg_match("/image/", $temp_type)) {

							$gd = getimagesize($temp_file);
							// is image valid format
							if(isset($gd["mime"])) {
								$extension = mimetypeToExtension($gd["mime"]);

								if(isset($extension)) {
									$output_file = PRIVATE_FILE_PATH."/".$item_id.$variant."/".$extension;

//									print $output_file . "<br>";
									FileSystem::removeDirRecursively(dirname($output_file));
									FileSystem::removeDirRecursively(PUBLIC_FILE_PATH."/".$item_id.$variant);
									FileSystem::makeDirRecursively(dirname($output_file));

									copy($temp_file, $output_file);
									unlink($temp_file);
								}
							}
						}
					}

				}
				else {
					// error
				}
			}

		}


	}


	function disableItem($item_id) {

	}
	function enableItem($item_id) {

	}

	function deleteItem($item_id) {
		$query = new Query();

		// delete item + itemtype + files
		if($query->sql("SELECT id FROM ".UT_ITE." WHERE id = $item_id")) {
			
			$query->sql("DELETE FROM ".UT_ITE." WHERE id = $item_id");
			FileSystem::removeDirRecursively(PUBLIC_FILE_PATH."/$item_id");
			FileSystem::removeDirRecursively(PRIVATE_FILE_PATH."/$item_id");
		}
	}



	// get tag, optionally limited to context, or just check if specific tag exists
	function getTags($id, $options=false) {

		$tag_context = false;
		$tag_value = false;

		if($options !== false) {
			foreach($options as $option => $value) {
				switch($option) {
					case "context" : $tag_context = $value; break;
					case "value" : $tag_value = $value; break;
				}
			}
		}

		$query = new Query();
		if($tag_context && $tag_value) {
			return $query->sql("SELECT * FROM ".UT_TAG." as tags, ".UT_TAGGINGS." as taggings WHERE tags.context = '$tag_context' AND tags.value = '$tag_value' AND tags.id = taggings.tag_id AND taggings.item_id = $id");
		}
		else if($tag_context) {
			if($query->sql("SELECT tags.id as id, tags.context as context, tags.value as value FROM ".UT_TAG." as tags, ".UT_TAGGINGS." as taggings WHERE tags.context = '$tag_context' AND tags.id = taggings.tag_id AND taggings.item_id = $id")) {
				return $query->results();
			}
		}
		else {
			if($query->sql("SELECT tags.id as id, tags.context as context, tags.value as value FROM ".UT_TAG." as tags, ".UT_TAGGINGS." as taggings WHERE tags.id = taggings.tag_id AND taggings.item_id = $id")) {
				return $query->results();
			}
		}
		return false;
	}


	// add tag to item, create tag if it does not exist
	// tag can be tag-string or tag_id
 	function addTag($item_id, $tag) {

		$query = new Query();

		if(preg_match("/([a-zA-Z0-9_]+):([^\b]+)/", $tag, $matches)) {
			$context = $matches[1];
			$value = $matches[2];

//			print "SELECT id FROM ".UT_TAG." WHERE context = '$context' AND value = '$value'<br>";
			if($query->sql("SELECT id FROM ".UT_TAG." WHERE context = '$context' AND value = '$value'")) {
				$tag_id = $query->result(0, "id");
			}
//			print "INSERT INTO ".UT_TAG." VALUES(DEFAULT, '$context', '$value', DEFAULT)<br>";
			else if($query->sql("INSERT INTO ".UT_TAG." VALUES(DEFAULT, '$context', '$value', DEFAULT)")) {
				$tag_id = $query->lastInsertId();
			}

		}
		else if(is_numeric($tag)) {
			// is it a valid tag_id
			if($query->sql("SELECT id FROM ".UT_TAG." WHERE id = $tag)")) {
				$tag_id = $tag;
			}
		}

		if(isset($tag_id)) {
			$query->sql("INSERT INTO ".UT_TAGGINGS." VALUES(DEFAULT, $item_id, $tag_id)");
		}
	}

	// delete tag - tag can be complete context:value or tag_id (number)
	// TODO: or just context to delete all context tags for item
	// TODO: delete unused tags automatically?
 	function deleteTag($item_id, $tag) {
//		print "Delete tag:" . $item_id . ":" . $tag . ":" . is_numeric($tag) . "<br>";

		$query = new Query();

		// is tag matching context:value
		if(preg_match("/([a-zA-Z0-9_]+):([^\b]+)/", $tag, $matches)) {
			$context = $matches[1];
			$value = $matches[2];

			if($query->sql("SELECT id FROM ".UT_TAG." WHERE context = '$context' AND value = '$value')")) {
				$tag_id = $query->result(0, "id");
			}
		}
		// is tag really tag_id
		else if(is_numeric($tag)) {
			// is it a valid tag_id
			if($query->sql("SELECT id FROM ".UT_TAG." WHERE id = $tag")) {
				$tag_id = $tag;
			}
		}

		if(isset($tag_id)) {
			$query->sql("DELETE FROM ".UT_TAGGINGS." WHERE item_id = $item_id AND tag_id = $tag_id");
		}

	}




	/**
	* Clean up files - delete files that are no longer assiciated with items (someone forgot to cleanup)
	*/
	// function cleanupFiles($item_id, $file_tmp) {
	// 
	// 
	// }


	/**
	* Get status for selected item (1: enabled / 0: disabled)
	*
	* @param int $item_id Item id
	* @return int Status
	*/
	// function getStatus($item_id)	{
	// 	$query = new Query();
	// 	$query->sql("SELECT status FROM ".UT_ITE." WHERE id = $item_id");
	// 	return $query->getQueryResult(0, "status"); 
	// }

}

?>