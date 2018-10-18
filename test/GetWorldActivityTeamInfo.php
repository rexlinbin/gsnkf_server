<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/

class GetWorldActivityTeamInfo extends BaseScript
{

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		$actName = WolrdActivityName::COUNTRYWAR;
		$referTime = CountryWarConfig::roundStartTime(Util::getTime());
		$sess = 1;
		
		if ( isset($arrOption[0]) )
		{
			$actName = $arrOption[0];
		}
		if ( isset($arrOption[1]) )
		{
			$sess = intval($arrOption[1]);
		}
		
		$teamMgr = TeamManager::getInstance( $actName, $sess);
		
		printf("activity:%s, sess:%s\n", $actName, $sess);
		var_dump( $teamMgr->getAllTeam() );
		echo "\n";
		$serverId = Util::getServerIdOfConnection();
		$teamId = $teamMgr->getTeamIdByServerId($serverId);
		printf("teamId: %s\n", $teamId);
		
		$servers = $teamMgr->getServersByServerId($serverId);
		var_dump( $servers );
		echo "\n";
		
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */