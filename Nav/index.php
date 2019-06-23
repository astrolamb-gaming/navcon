<?php
	function isEmpty($var) {
		return (!isset($var) || empty($var) || trim($var)==='');
	}
	
	function toRoman($integer) {
		// Convert the integer into an integer (just to make sure)
		$integer = intval($integer);
		$result = '';

		// Create a lookup array that contains all of the Roman numerals.
		$lookup = array('M' => 1000,
		'CM' => 900,
		'D' => 500,
		'CD' => 400,
		'C' => 100,
		'XC' => 90,
		'L' => 50,
		'XL' => 40,
		'X' => 10,
		'IX' => 9,
		'V' => 5,
		'IV' => 4,
		'I' => 1);

		foreach ($lookup as $roman => $value) {
			// Determine the number of matches
			$matches = intval($integer/$value);

			// Add the same number of characters to the string
			$result .= str_repeat($roman,$matches);

			// Set the integer to be the remainder of the integer and the value
			$integer = $integer % $value;
		}

		// The Roman numeral should be built, return it
		return $result;
	}

	/** check if a string starts with another string */
	function startsWith($haystack, $needle) {
		return (substr($haystack, 0, strlen($needle)) == $needle);
	}
	
	/** check if a string ends with another string */
	function endsWith($haystack, $needle) {
		$length = strlen($needle);
		if ($length == 0) {
			return true;
		}
		return (substr($haystack, -$length) == $needle);
	}

	function getGateNetworkFromSector ($sector) {
		$ret="";
		$handle=fopen("sectors/".$sector."/gateNetwork.txt","r");
		if ($handle) {
			$ret = trim(fgets($handle));
			fclose($handle);
		}
		return $ret;
	}

	//returns an array of arrays where each array is a menu to be shown
	function getSystemsMenus() {
		$files = scandir("sectors", 0);
		$sectorList=array();
		foreach ($files as $name) {
			if ($name != "." && $name != ".." && $name != "desktop.ini") {
				array_push($sectorList,$name);
			}
		}
		asort($sectorList);
		//note array_chunk may turn out to be the wrong call
		//for instance if we decide 7 sectors need to be devided over 3 it will be
		//3,3,1 rather than the more logical 3,2,2
		//lets fix that when it becomes an issue
		$amountOfSystemMenus=3;
		return array_chunk($sectorList,ceil(count($sectorList)/$amountOfSystemMenus));
	}

	function lookupClassifedFile($classifed,$file) {
		if ($classifed) {
			$classifedFile="classifed/".$file;
			if (file_exists($classifedFile)) {
				return $classifedFile;
			} else {
				return $file;
			}
		} else {
			return $file;
		}
	}

	$sub = isset($_GET['sub']) ? trim($_GET['sub']) : "";
	$entType = isset($_GET['entType']) ? trim($_GET['entType']) : "";

	$gateNetwork= isset($_GET['gateNetwork']) ? trim($_GET['gateNetwork']) : "Upper";

	$classifed = isset($_GET['Classifed']);
	$classifedHref = isset($_GET['Classifed'])? "Classifed&" : "" ;

	$sector ="";
	if (isset($_GET['sector'])) {
		$sector=$_GET['sector'];
		$gateNetwork=getGateNetworkFromSector($sector);
		$sectorDir = "sectors/".$sector;
		$gateButtonDest=$gateNetwork;
		$gateNetText = ($gateNetwork=='Upper') ? "VIEW UPPER ARC" : "VIEW LOWER ARC";
	} else {
		$gateButtonDest = $gateNetwork=="Upper" ? "Lower" : "Upper";
		$gateNetText = $gateNetwork=="Upper" ? "VIEW LOWER ARC" : "VIEW UPPER ARC";
	}

	$menus=getSystemsMenus();
?>
<html>
<head>
	<link rel="stylesheet" type="text/css" href="style.css">
	<link rel="stylesheet" type="text/css" href="sectors.css">
	<link rel="stylesheet" type="text/css" href="sectorEntities.css">
	<link rel="stylesheet" type="text/css" href="sectorSubCross.css">
	<link rel="stylesheet" type="text/css" href="menu.css">
	<script>
function toggleSystemView() {
	var toggled=false;
		<?php	// the code here is ugly, and it generates ugly code
			// if I knew more javascript there probably is a nice soultion
			// however I dont and so you get ugly code
		for ($i=count($menus);$i!=0;$i--) {?>
			if (document.getElementById("menuSectorsPart<?php printf($i);?>").classList.contains("show")) {
				document.getElementById("menuSectorsPart<?php printf($i);?>").classList.toggle("show");
				<?php if ($i!=count($menus)) { ?>
				document.getElementById("menuSectorsPart<?php printf($i+1);?>").classList.toggle("show");
				<?php } ?>
				<?php if (($i+1)==count($menus)) {?>
					document.getElementById("systemButton").innerHTML = "CANCEL SYSTEMS";
				<?php } else if ($i==count($menus)) {?>
					document.getElementById("systemButton").innerHTML = "SYSTEMS";
				<?php }?>
				toggled=true;
			}
		<?php }?>
	if (toggled==false) {
		document.getElementById("menuSectorsPart1").classList.toggle("show");
		document.getElementById("systemButton").innerHTML = "MORE SYSTEMS";
	}
}

// Close the dropdown if the user clicks outside of it
window.onclick = function(event) {
  if (!event.target.matches('.dropbtn')) {
    document.getElementById("systemButton").innerHTML = "SYSTEMS";

    var dropdowns = document.getElementsByClassName("dropdown-content");
    var i;
    for (i = 0; i < dropdowns.length; i++) {
      var openDropdown = dropdowns[i];
      if (openDropdown.classList.contains('show')) {
        openDropdown.classList.remove('show');
      }
    }
  }
}
	</script>
	<title>TSN Stellar Navigation Console</title>
</head>
<body style="overflow: hidden;">
	<?php
		//menu?>
		<div class="dropdown">
		<button onclick="toggleSystemView()" id="systemButton" class="dropbtn">SYSTEMS</button>
		<button onclick="location.href='index.php?<?=$classifedHref?>gateNetwork=<?php printf($gateButtonDest) ?>'" class="dropbtn<?=isEmpty($sector) ? " active" : ""?>"><?php printf($gateNetText);?></button>
		<button onclick="location.href='http://www.1sws.com\\Intel\\NavClassified\\index.php'" class="dropbtn<?=isEmpty($sector) ? " active" : ""?>">INTEL</button><?php
		for ($i=0; $i!=count($menus); $i++) {
			?><div id="menuSectorsPart<?php printf($i+1)?>" class="dropdown-content opaque">
			<?php foreach ($menus[$i] as $name) {?>
				<div class="dropdown-entry<?=(!isEmpty($sector) && $name == $sector) ? " selected" : ""?>">
					<a href="?<?=$classifedHref?>sector=<?=$name?>"><?=strtoupper($name)?></a>
				</div>
				<?php }?>
			</div><?php
			}?>
		</div><?php
		
		if (!isEmpty($sector)) {
			// read sector size from sector directory
			$sectorSize = explode(',', file_get_contents($sectorDir."/sector.txt"));
			$sectorWidth = $sectorSize[0];
			$sectorHeight = $sectorSize[1];
			if (isEmpty($sub)) {
				include 'sectorMap.php';
			} else {
				include 'sectorSubMap.php';
			}
		} else {?>
			<script>
			function systemClick(event) {
				width=event.currentTarget.clientWidth;
				origX=1654/width*event.offsetX;
				width=event.currentTarget.clientHeight;
				origY=1080/width*event.offsetY;
				clickables=[<?php
					//build the clickables array
					//there probably is a nice way to do this with JSON
					//however I do not know it
					//logic is simliar to menu.php - if that needs duplication again
					//it probably should be moved into a function
					$files= scandir("sectors", 0);
					foreach ($files as $name) {
						if ($name != "." && $name != "..") {
							if (file_exists("sectors/".$name."/mainMapPos.txt")) {
								$handle = fopen("sectors/".$name."/mainMapPos.txt", "r");
								if ($handle) {
									if (getGateNetworkFromSector($name)==$gateNetwork){
										$xy=explode(",",fgets($handle));
										if (count($xy)==2) {
											printf("{x:%d, y:%d, url:\"?%ssector=%s\"},",$xy[0],$xy[1],$classifedHref,$name);
										}
									}
									fclose($handle);
								}
							}
						}
					}
					?>];
					for (i=0; i<clickables.length; i++) {
					deltaX=origX-clickables[i].x;
					deltaY=origY-clickables[i].y;
					delta=Math.sqrt((deltaX*deltaX)+(deltaY*deltaY));
					if (delta<50) {
						window.open(clickables[i].url,"_self");
					}
				}
			}
			</script>
			<div>
				<?php if ($gateNetwork=="Lower") {?>
					<img onClick="systemClick(event)" max-height="100%" max-width="100%" z-index="-1" position="absolute" bottom="0px" right="0px" src="img/gateNetworkLower.png"/>
				<?php } else {?>
					<img onClick="systemClick(event)" max-height="100%" max-width="100%" z-index="-1" position="absolute" bottom="0px" right="0px" src="img/gateNetworkUpper.png"/>
				<?php }?>
			</div>
			<div style="position:absolute;top:10px;right:20px;">
				Stellar Cartography <?php if ($classifed) {printf("ONI");} else {printf("TSN");}?> 11.0
			</div><?php
		}
	?>
</body>
</html>
