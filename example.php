<?php
	include("steamgroup.php");
	$group=loadGroup('<yourSteamID>','<yourGroupIdentifier>');
	renderGroup($group,'steamGroupTemplate.html','steamMemberTemplate.html');
?>