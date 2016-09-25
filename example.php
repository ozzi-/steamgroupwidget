<?php
	include("steamgroup.php");
	$group=loadGroup('<yourSteamID>','<yourGroupIdentifier>');
	$html=renderGroup($group,'steamGroupTemplate.html','steamMemberTemplate.html');
	echo($html);
?>