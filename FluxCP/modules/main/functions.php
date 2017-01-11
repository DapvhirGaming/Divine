<?php

function	getServerStatus($rootName, $serverName, $serverStatus) {
		$val = (bool)$serverStatus[$rootName][$serverName]['loginServerUp'] +
		(bool)$serverStatus[$rootName][$serverName]['charServerUp'] +
		(bool)$serverStatus[$rootName][$serverName]['mapServerUp'];	
		
		if($val == 3){		
			return 'On';	
		}	
		else
		{ 		
			return 'Off';	
		}
}


function	getOnlineCount($rootName, $serverName, $serverStatus) {
	return $serverStatus[$rootName][$serverName]['playersOnline'];

}

function    getWoeStatus($server){
    return $server->isWoe() ? 'On' : 'Off';
}


?>