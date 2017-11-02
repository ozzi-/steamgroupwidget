<?php
	include("steamgroup.php");
	$group=loadGroup('<yourSteamID>','<yourGroupIdentifier>');
	if($group!=null){
		$html=renderGroup($group,'steamGroupTemplate.html','steamMemberTemplate.html');
		echo($html);
	}
?>
