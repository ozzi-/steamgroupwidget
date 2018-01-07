<?php
	/**
	 * Fetches the group data from Steam
	 *
	 * @param string $steamAPIKey See: https://steamcommunity.com/dev/apikey
	 * @param string $groupIdentifier The Steam Group Identifier (SteamID or CustomURL)
	 * @param string $noMembers optional, if true members won't be loaded
	 * @return array or null if an error occured
	 */
	function loadGroup($steamAPIKey,$groupIdentifier,$noMembers=false){
		$steamGroupArray=getSteamGroupArray($groupIdentifier);
		if($steamGroupArray==null){
			return null;
		}
		
		$group=[];
		$group['groupID']= $steamGroupArray['groupID64'];
		$group['groupName']= $steamGroupArray['groupDetails']['groupName'];
		$group['groupURL']= "https://steamcommunity.com/groups/".$steamGroupArray['groupDetails']['groupURL'];
		$group['groupAvatarFull']= $steamGroupArray['groupDetails']['avatarFull'];
		$group['groupAvatarMedium']= $steamGroupArray['groupDetails']['avatarMedium'];
		$group['groupAvatarSmall']= $steamGroupArray['groupDetails']['avatarIcon'];
		$group['groupMemberCount']= $steamGroupArray['groupDetails']['memberCount'];
		$group['groupMembersInChat']= $steamGroupArray['groupDetails']['membersInChat'];
		$group['groupMembersInGame']= $steamGroupArray['groupDetails']['membersInGame'];
		$group['groupMembersOnline']= $steamGroupArray['groupDetails']['membersOnline'];
	
		if(!$noMembers){
			$memberIDs="";
			foreach($steamGroupArray['members']['steamID64'] as $index=>$memberID){
				$memberIDs.=$memberID.",";
			}
			$membersJSON=@file_get_contents("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$steamAPIKey&steamids=$memberIDs");
			if($http_response_header[0]!=="HTTP/1.0 200 OK"){
				echo("steamgroupwidget: is your steam api key invalid?<br>server returned: ".$http_response_header[0]);
				return null;
			}
			$members=json_decode($membersJSON,true);
			foreach($members['response']['players'] as $index=>$member){
				$group['members'][$index]['memberID']=$member['steamid'];
				$group['members'][$index]['memberURL']="https://steamcommunity.com/profiles/".$member['steamid'];
				$group['members'][$index]['memberName']=$member['personaname'];
				$group['members'][$index]['memberState']=$member['personastate']===0?'offline':'online';
				$group['members'][$index]['memberAvatarFull']=$member['avatarfull'];
				$group['members'][$index]['memberAvatarMedium']=$member['avatarmedium'];
				$group['members'][$index]['memberAvatarIcon']=$member['avatar'];
			}
		}
		return $group;
	}
	/**
	 * Returns HTML as defined in the templates
	 *
	 * @param string $groupArray array from loadGroup 
	 * @param string $templateGroupPath path to the group template
	 * @param string $templateMemberPath optional, path to the member template
	 * @return string
	 */
	function renderGroup($groupArray,$templateGroupPath,$templateMemberPath=false){
		if(!file_exists($templateGroupPath)){
			die("steamgroupwidget: group template '$templateGroupPath' does not exist");
		}
		$template=file_get_contents($templateGroupPath);
		$template=injectTemplate($groupArray,$template);
		if($templateMemberPath!==false && ($lastPos = strpos($template, "[[members]]"))!== false) {
			if(!file_exists($templateMemberPath)){
				echo("steamgroupwidget: member template '$templateMemberPath' does not exist");
				return null;
			}
			$templateMember=file_get_contents($templateMemberPath);
			$templateMembers="";
			for ($i = 0; $i < $groupArray['groupMemberCount']; $i++) {
				$templateMembers.=injectTemplate($groupArray['members'][$i],$templateMember);
			}
			$template = substr_replace($template, $templateMembers, $lastPos,0);
		}
		return str_replace("[[members]]","",$template);
	}
	
	function getSteamGroupArray($groupIdentifier){
		$steamgroupXMLURL= "https://steamcommunity.com/groups/$groupIdentifier/memberslistxml/?xml=1";
		$use_errors= libxml_use_internal_errors(true);
		$steamgroupXML= @simplexml_load_file($steamgroupXMLURL,'SimpleXMLElement', LIBXML_NOCDATA);
		if(!$steamgroupXML){
			echo("steamgroupwidget: group not found or other (connectivity?) problem");
			return null;
		}
		$steamgroupJSON = json_encode($steamgroupXML);
		return json_decode($steamgroupJSON,TRUE);
	}
	
	function injectTemplate($valueArray,$template){
		$needle = "{{";
		$needleLength=strlen($needle);
		$needleEnd = "}}";
		$lastPos = 0;
		$variables = array();
		$index=0;
		while (($lastPos = strpos($template, $needle, $lastPos))!== false) {
			if(($lastPosEnd = strpos($template, $needleEnd, $lastPos))!== false){
				$varLength=$lastPosEnd-$lastPos-$needleLength;
				$var=substr($template,$lastPos+$needleLength,$varLength);
				if(!isset($valueArray[$var])){
					die("steamgroupwidget: invalid variable '$var' in template");
				}
				$variables[$index]=$var;
				$index++;
			}else{
				die("steamgroupwidget: error in template, missing ".$needleEnd);
			}
			$lastPos=$lastPos + $varLength;
		}
		foreach ($variables as $variable) {
			$template=str_replace($needle.$variable.$needleEnd,$valueArray[$variable],$template);
		}
		return $template;
	}
?>
