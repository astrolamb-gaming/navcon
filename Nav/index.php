<?php
	session_start();	
	//---- RELEASE VERSION ----//

	
        $redirect = false;
	// Actually starts things
	if (sessionUpdate()) {
	    if (checkForUpdate()) {
	    //    echo "True";
		//echo "update needed";
		redirectWithQuery();
		exit();
	    } //else Continue with your book report

	}


	function checkForUpdate() {
	    //$dir1 = dirname(__DIR__);
	    $dir1 = dirname(__DIR__, 2);
	    //echo $dir1;
	    //exit();
	    if (!file_exists($dir1."./saved.txt")) {
		return true;
	    }
	    // else, continue
	    $saved = fopen($dir1."./saved.txt", "r") or die("Unable to open file");
	    fgets($saved);// Passoword Unused here, function called so update date can be read
	    $last_update = fgets($saved);
	    fclose($saved);

	    $commitDate = getLatestCommit();
	    return $commitDate != $last_update;
	}

	


	// returns true if an update check is needed.
	function sessionUpdate() {
	    $d = date("U");
	    $luc = $_SESSION["lastUpdateCheck"];
            
//	    echo "<br>luc = ";
//	    echo $luc;
//            echo "<br>";
	    if ($luc === false || $luc === null) {
		$_SESSION["lastUpdateCheck"] = $d;
		return true; // Cause updateCheck to happen
	    } else {
		$hour = 60*60;//seconds*minutes
		$luc += $hour;
		if ($luc < $d) {
		    $_SESSION["lastUpdateCheck"] = $d;
		    return true;
		} else {
		    return false;
		}
	    }
	}

	// Gets time of last commit to whichever branch
	// Default is master of course
	function getLatestCommit() {
	    $context = stream_context_create(
		array(
		    "http" => array(
			"header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
		    )
		)
	    );
	    $url = "https://api.github.com/repos/tsnrp/navcon/commits/NavTest";
	    $json = file_get_contents($url, false, $context);
	    $arr = json_decode($json, true);
	    $date = $arr["commit"]["committer"]["date"];
	    return $date;
	}
        
        // Redirects with the fancy extra stuff on the end of the url
	function redirectWithQuery() {
	    //echo "trying";
	    $uri = filter_input(INPUT_SERVER, "REQUEST_URI", FILTER_SANITIZE_URL);
	    $t = parse_url($uri, PHP_URL_QUERY);
	    //$dir1 = dirname(__DIR__,2);
	    if (strlen($t)>0) {
		$r = "./../../NavUpdate.php?".$t; // This may need changed someday
	    } else {
		$r = "./../../NavUpdate.php";
	    }
	//    echo "Redirecting to...".$r;
	//    exit();
	    header("Location: ".$r, TRUE, 303);
	    exit();
	}



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

	function getAllSystems($classified) {
		$files = scandir("sectors", 0);
		if ($classified) {
			$files = array_unique(array_merge($files,scandir("classified/sectors",0)));
		}
		$sectorList=array();
		foreach ($files as $name) {
			if ($name != "." && $name != ".." && $name != "desktop.ini") {
				array_push($sectorList,$name);
			}
		}
		asort($sectorList);
		return $sectorList;
	}

	//returns an array of arrays where each array is a menu to be shown
	function getSystemsMenus($classified) {
		$sectorList=getAllSystems($classified);
		// 12 is roughly right for a 768 height screen
		// however this was tested on a machine that had a signifcantly different display
		// (27 inch) 2560x1440, resized
		// its possible that if it was really on a machine with such a small display menus would of been resized
		// and/or the display scaling would be on
		// as such it may be worth trying to find someone with the worst display we want to support and check we cant raise it
		$maxMenuSize=12;
		$amountOfSystemMenus=ceil(count($sectorList)/$maxMenuSize);

		//note array_chunk may turn out to be the wrong call
		//for instance if we decide 7 sectors need to be devided over 3 it will be
		//3,3,1 rather than the more logical 3,2,2
		//lets fix that when it becomes an issue
		return array_chunk($sectorList,ceil(count($sectorList)/$amountOfSystemMenus));
	}

	function lookupClassifiedFile($classified,$file) {
		if ($classified) {
			$classifiedFile="classified/".$file;
			if (file_exists($classifiedFile)) {
				return $classifiedFile;
			} else {
				return $file;
			}
		} else {
			return $file;
		}
	}

	function readEntitesFile($classified,$sector) {
		$file=lookupClassifiedFile($classified,"sectors/".$sector."/entities.txt");
		if (file_exists($file)) {
			$entities=explode("\n",file_get_contents($file));
			$ret=array();
			foreach ($entities as $line) {
				$line=str_replace("\r","",$line);
				$line=explode(",",$line);
				$entity=array();
				$entity['name']=$line[0];
				$entity['type']=$line[1];
				$entity['loc']=$line[2];
				$isClassified=(isset($line[3]) && $line[3]=="Classified");
				if (!$isClassified || $classified) {
					array_push($ret,$entity);
				}
			}
			return $ret;
		}
		return array();
	}

	function getSectorInfo($classified,$sector) {
		$file=lookupClassifiedFile($classified,"sectors/".$sector."/sector.txt");
		if (file_exists($file)) {
			$ret=array();
			$file_contents=explode(',',file_get_contents($file));
			$ret['network']=$file_contents[0];
			$ret['x']=(int)$file_contents[1];
			$ret['y']=(int)$file_contents[2];
			return $ret;
		}
		return array();
	}

	$sub = isset($_GET['sub']) ? trim($_GET['sub']) : "";
	$entType = isset($_GET['entType']) ? trim($_GET['entType']) : "";

	$gateNetwork= isset($_GET['gateNetwork']) ? trim($_GET['gateNetwork']) : "Upper";

	$classified = isset($_GET['Classified']);
	$classifiedHref = isset($_GET['Classified'])? "Classified&" : "" ;

	//if passwords are stored on disc it can be tricky (as an understatement) to do them securely
	//even if the password is unimportant (as it is in this case)
	//the ever present reuse of passwords means it probably needs to be done properly
	//to prevent people putting a important password in here then complaining when it gets leaked
	//we are only going to have a fixed password
	$requestPassword=false;
	if ($classified) {
		$requestPassword=true;
		if (isset($_COOKIE['passwordOK'])) {
			$requestPassword=false;
		} else if (isset($_POST['pass']) && $_POST['pass']=="ONI-2F4L") {
			setcookie('passwordOK',"true",time()+60*60*24*365*10);//expires 10 years into the future
			$requestPassword=false;
		} else {
			$classified=false;
			$classifiedHref="";
		}
	}

	$sector ="";
	if (isset($_GET['sector'])) {
		$sector=$_GET['sector'];
		$gateNetwork=getSectorInfo($classified,$sector)['network'];
		$gateButtonDest=$gateNetwork;
		$gateNetText = ($gateNetwork=='Upper') ? "VIEW UPPER ARC" : "VIEW LOWER ARC";
	} else {
		$gateButtonDest = $gateNetwork=="Upper" ? "Lower" : "Upper";
		$gateNetText = $gateNetwork=="Upper" ? "VIEW LOWER ARC" : "VIEW UPPER ARC";
	}

	$menus=getSystemsMenus($classified);
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
		<div class="dropdown" style="z-index:1;">
		<button onclick="toggleSystemView()" id="systemButton" class="dropbtn">SYSTEMS</button>
		<button onclick="location.href='index.php?<?=$classifiedHref?>gateNetwork=<?php printf($gateButtonDest) ?>'" class="dropbtn<?=isEmpty($sector) ? " active" : ""?>"><?php printf($gateNetText);?></button>
		<?php
		$intelButtonActiveText=$classified ? " active" : "" ;
		$getString= $classified ? "?" : "?Classified&";
		$getString.=isset($_GET['gateNetwork']) ? "gateNetwork=".$_GET['gateNetwork']."&" : "";
		$getString.=isset($_GET['sector']) ? "sector=".$_GET['sector']."&" : "";
		$getString.=isset($_GET['sub']) ? "sub=".$_GET['sub']."&" : "";
		$getString.=isset($_GET['entType']) ? "entType=".$_GET['entType']."&" : "";
		if ($getString=="?") {
			$getString="";
		} else {
			$getString=substr($getString,0,-1);
		}
		echo("<button onclick=\"location.href='index.php$getString'\" class=\"dropbtn$intelButtonActiveText\">INTEL</button>");

		for ($i=0; $i!=count($menus); $i++) {
			?><div id="menuSectorsPart<?php printf($i+1)?>" class="dropdown-content opaque">
			<?php foreach ($menus[$i] as $name) {?>
				<div class="dropdown-entry<?=(!isEmpty($sector) && $name == $sector) ? " selected" : ""?>">
					<a href="?<?=$classifiedHref?>sector=<?=$name?>"><?=strtoupper($name)?></a>
				</div>
				<?php }?>
			</div><?php
			}?>
		</div><?php

		if ($requestPassword) {?>
			<br>Please enter ONI security clearance
			<form action="index.php?Classified" method="post">
			<input type="text" name="pass"><br>
			<input type="submit" value="authenticate me">
			</form>
			<br><?php
		} else {
			if (!isEmpty($sector)) {
				$sectorSize = getSectorInfo($classified,$sector);
				$sectorWidth = $sectorSize['x'];
				$sectorHeight = $sectorSize['y'];
				if (isEmpty($sub)) {
					include 'sectorMap.php';
				} else {
					include 'sectorSubMap.php';
				}
			} else {?>
				<script>
				function systemClick(event) {
					var el=document.getElementById("gateNet");
					//we need to convert the information that we get in the event info how far into the image has been clicked
					//first up we are going to figure out how much the image has been scaled
					var imgOrigX=1654;
					var imgOrigY=1080;

					var scaleImgX=document.getElementById("gateNet").width/imgOrigX;
					var scaleImgY=document.getElementById("gateNet").height/imgOrigY;
					var imageScale=Math.min(scaleImgX,scaleImgY);

					//then we are going to calculate how far inside the window the image is
					//see https://stackoverflow.com/questions/8389156/what-substitute-should-we-use-for-layerx-layery-since-they-are-deprecated-in-web
					var x=0;
					var y=0;

					while (el && !isNaN(el.offsetLeft) && !isNaN(el.offsetTop)) {
						x += el.offsetLeft - el.scrollLeft;
						y += el.offsetTop - el.scrollTop;
						el = el.offsetParent;
					}

					x = event.clientX - x;
					y = event.clientY - y;

					//we compare the offset from the mid point of the image
					//and scale it back to original units used to make the clickables array
					var width=event.currentTarget.clientWidth;
					var clickFromMidX=x-(width/2);
					var clickX=(clickFromMidX*(1/imageScale))+(imgOrigX/2);

					var height=event.currentTarget.clientHeight;
					var clickFromMidY=y-(height/2);
					var clickY=(clickFromMidY*(1/imageScale))+(imgOrigY/2);

					var clickables=[<?php
						//build the clickables array
						//there probably is a nice way to do this with JSON
						//however I do not know it
						//logic is simliar to menu.php - if that needs duplication again
						//it probably should be moved into a function
						$files=getAllSystems($classified);
						foreach ($files as $name) {
							if ($name != "." && $name != "..") {
								$mapPos = lookupClassifiedFile($classified,"sectors/".$name."/mainMapPos.txt");
								if (file_exists($mapPos)) {
									$handle = fopen(lookupClassifiedFile($classified,"sectors/".$name."/mainMapPos.txt"), "r");
									if ($handle) {
										if (getSectorInfo($classified,$name)['network']==$gateNetwork){
											$xy=explode(",",fgets($handle));
											if (count($xy)==2) {
												printf("{x:%d, y:%d, url:\"?%ssector=%s\"},",$xy[0],$xy[1],$classifiedHref,$name);
											}
										}
										fclose($handle);
									}
								}
							}
						}
						?>];
						for (i=0; i<clickables.length; i++) {
						var deltaX=clickX-clickables[i].x;
						var deltaY=clickY-clickables[i].y;
						var delta=Math.sqrt((deltaX*deltaX)+(deltaY*deltaY));
						if (delta<50) {
							window.open(clickables[i].url,"_self");
						}
					}
				}
				</script>
				<div>
					<?php $gateImg="img/gateNetwork".$gateNetwork.".png";
					$gateImg=lookupClassifiedFile($classified,$gateImg);?>
					<img id="gateNet" onClick="systemClick(event)" style="height: 100%; width: 100%; object-fit: contain;  position: absolute; bottom: 0px; right: 0px;" src="<?=$gateImg?>"/>
				</div>
				<div style="position:absolute;top:10px;right:20px;">
					Stellar Cartography <?php if ($classified) {printf("ONI");} else {printf("TSN");}?> 11.0
				</div><?php
			}
		}
	?>
</body>
</html>
