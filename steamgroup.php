<?php
	/**
	 * Fetches the group data from Steam
	 *
	 * @param string $steamAPIKey See: https://steamcommunity.com/dev/apikey
	 * @param string $groupIdentifier The Steam Group Identifier (SteamID or CustomURL)
	 * @param string $noMembers optional, if true members won't be loaded
	 * @return array
	 */
	function loadGroup($steamAPIKey,$groupIdentifier,$noMembers=false){
		$result=[];
		$steamgroupXMLURL = 			"http://steamcommunity.com/groups/$groupIdentifier/memberslistxml/?xml=1";
		$use_errors = 					libxml_use_internal_errors(true);
		$steamgroupXML = 				@simplexml_load_file($steamgroupXMLURL,'SimpleXMLElement', LIBXML_NOCDATA);
		if(false === $steamgroupXML){
			die("group not found or other (connectivity?) problem");
		}
		$steamgroupJSON = 				json_encode($steamgroupXML);
		$steamgroupArray = 				json_decode($steamgroupJSON,TRUE);
		
		$result['groupID']=				$steamgroupArray['groupID64'];
		$result['groupName']=			$steamgroupArray['groupDetails']['groupName'];
		$result['groupURL']=			"https://steamcommunity.com/groups/".$steamgroupArray['groupDetails']['groupURL'];
		$result['groupAvatar']=			$groupURL=$steamgroupArray['groupDetails']['avatarFull'];
		$result['groupMemberCount']=	$groupURL=$steamgroupArray['groupDetails']['memberCount'];
		$result['groupMembersInChat']=	$groupURL=$steamgroupArray['groupDetails']['membersInChat'];
		$result['groupMembersInGame']=	$groupURL=$steamgroupArray['groupDetails']['membersInGame'];
		$result['groupMembersOnline']=	$groupURL=$steamgroupArray['groupDetails']['membersOnline'];
		if(!$noMembers){
			$memberIDs="";
			foreach($steamgroupArray['members']['steamID64'] as $index=>$memberID){
				$memberIDs.=$memberID.",";
			}
			$membersJSON=@file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$steamAPIKey&steamids=$memberIDs");
			if($http_response_header[0]!=="HTTP/1.0 200 OK"){
				die("steam api key invalid?<br>server returned: ".$http_response_header[0]);
			}
			$members=json_decode($membersJSON,true);
			foreach($members['response']['players'] as $index=>$member){
				$result['members'][$index]['memberID']=$member['steamid'];
				$result['members'][$index]['memberURL']="https://steamcommunity.com/profiles/".$member['steamid'];
				$result['members'][$index]['memberName']=$member['personaname'];
				$result['members'][$index]['memberState']=$member['personastate']===0?'offline':'online';
				$result['members'][$index]['memberAvatar']=$member['avatarfull'];
			}
			
		}
		return $result;
	}
	/**
	 * Outputs HTML as defined in the templates
	 *
	 * @param string $groupArray array from loadGroup 
	 * @param string $templateGroupPath path to the group template
	 * @param string $templateMemberPath optional, path to the member template
	 * @return array
	 */
	function renderGroup($groupArray,$templateGroupPath,$templateMemberPath=false){
		if(!file_exists($templateGroupPath)){
			die("group template '$templateGroupPath' does not exist");
		}
		$template=file_get_contents($templateGroupPath);
		$template=injectTemplate($groupArray,$template);
		if($templateMemberPath!==false && ($lastPos = strpos($template, "[[members]]"))!== false) {
			if(!file_exists($templateMemberPath)){
				die("member template '$templateMemberPath' does not exist");
			}
			$templateMember=file_get_contents($templateMemberPath);
			$templateMembers="";
			for ($i = 0; $i < $groupArray['groupMemberCount']; $i++) {
				$templateMembers.=injectTemplate($groupArray['members'][$i],$templateMember);
			}
			$template = substr_replace($template, $templateMembers, $lastPos,0);
		}
		$template = str_replace("[[members]]","",$template);
		echo($template);
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
					die("invalid variable '$var' in template");
				}
				$variables[$index]=$var;
				$index++;
			}else{
				die("error in template, missing ".$needleEnd);
			}
			$lastPos = $lastPos + $varLength;
		}
		foreach ($variables as $variable) {
			$template=str_replace($needle.$variable.$needleEnd,$valueArray[$variable],$template);
		}
		return $template;
	}
?>