# Steam Group Widget
This PHP allows you to display information about a steam group and its members. 
In order to retrive data from the users you will need a steam web api key from https://steamcommunity.com/dev/apikey .

### Example
The following PHP will load the group details, render it using your own templates and output the HTML:
```
<?php
	include("steamgroup.php");
	$group=loadGroup('<yourSteamID>','<yourGroupIdentifier>');
	if($groupInfo != null){
		$html=renderGroup($group,'steamGroupTemplate.html','steamMemberTemplate.html');
		echo($html);
	}
?>
```
The templates themselves are very easy to use, double curly brackets are used to insert api data:
```
<span>
	<a target="_blank" rel="noreferrer" href="{{memberURL}}"><img src="{{memberAvatarMedium}}" class="{{memberState}}"></a>
</span>
```


## Result of the example provided
![screenshot](https://i.imgur.com/KUz3gLG.png)
