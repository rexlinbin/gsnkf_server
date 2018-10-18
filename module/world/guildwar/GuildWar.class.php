<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildWar.class.php 155332 2015-01-26 10:50:24Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/guildwar/GuildWar.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-01-26 10:50:24 +0000 (Mon, 26 Jan 2015) $
 * @version $Revision: 155332 $
 * @brief 
 *  
 **/
 
class GuildWar implements IGuildWar
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::enter()
	*/
	public function enter()
	{
		return GuildWarLogic::enter($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::leave()
	*/
	public function leave()
	{
		return GuildWarLogic::leave($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::signUp()
	*/
	public function signUp()
	{
		return GuildWarLogic::signUp($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::updateFormation()
	*/
	public function updateFormation()
	{
		return GuildWarLogic::updateFormation($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::clearUpdFmtCdByGold()
	*/
	public function clearUpdFmtCdByGold()
	{
		return GuildWarLogic::clearUpdFmtCdByGold($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::buyMaxWinTimes()
	*/
	public function buyMaxWinTimes()
	{
		return GuildWarLogic::buyMaxWinTimes($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::getUserGuildWarInfo()
	*/
	public function getUserGuildWarInfo()
	{
		return GuildWarLogic::getUserGuildWarInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::getGuildExpendableList()
	*/
	public function getGuildWarMemberList()
	{
		return GuildWarLogic::getGuildWarMemberList($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::changeExpendablies()
	*/
	public function changeCandidate($type, $uid)
	{
		return GuildWarLogic::changeCandidate($this->uid, $type, $uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::getMyTeamInfo()
	*/
	public function getMyTeamInfo()
	{
		return GuildWarLogic::getMyTeamInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::getGuildWarInfo()
	*/
	public function getGuildWarInfo()
	{
		return GuildWarLogic::getGuildWarInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::getHistoryCheerInfo()
	*/
	public function getHistoryCheerInfo()
	{
		return GuildWarLogic::getHistoryCheerInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::cheer()
	*/
	public function cheer($cheerGuildId, $cheerServerId)
	{
		if(empty($cheerGuildId) || empty($cheerServerId))
		{
			throw new FakeException("GuildWar.cheer failed, Para err.");
		}
		return GuildWarLogic::cheer($this->uid, $cheerGuildId, $cheerServerId);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::getTempleInfo()
	*/
	public function getTempleInfo()
	{
		return GuildWarLogic::getTempleInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::worship()
	*/
	public function worship($type)
	{
		if (!in_array($type, GuildWarWorshipType::$ALL_TYPE)) 
		{
			throw new FakeException('GuildWar.worship failed, invalid worship type[%d], valid type[%s]', $type, GuildWarWorshipType::$ALL_TYPE);
		}
		return GuildWarLogic::worship($this->uid, $type);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::getHistoryFightInfo()
	*/
	public function getHistoryFightInfo()
	{
		return GuildWarLogic::getHistoryFightInfo($this->uid);
	}
	
	/* (non-PHPdoc)
	 * @see IGuildwar::getReplay()
	*/
	public function getReplay($guildId01, $serverId01, $guildId02, $serverId02)
	{
		if(empty($guildId01) || empty($serverId01) || empty($guildId02) || empty($serverId02)
			|| ($serverId01 == $serverId02 && $guildId01 == $guildId02))
		{
			throw new FakeException("GuildWar.getReplay failed, invalid param[guildId01:%d,serverId01:%d,guildId01:%d,serverId01:%d].", $guildId01, $serverId01, $guildId02, $serverId02);
		}
		
		return GuildWarLogic::getReplay($this->uid, $guildId01, $serverId01, $guildId02, $serverId02);
	}

	/* (non-PHPdoc)
	 * @see IGuildwar::getPrize()
	*/
	public function getReplayDetail($arrReplayId)
	{
		return GuildWarLogic::getReplayDetail($this->uid, $arrReplayId);
	}
	
	
	/////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////
	//		自己调用的函数
	/////////////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////////////////////////////////////////////////////////////////////////
	public function initUserGuildWarByUid($serverId, $uid)
	{
		return GuildWarLogic::initUserGuildWarByUid($serverId, $uid);
	}
	
	public function reInitUserGuildWarInfo($serverId, $uid)
	{
		return GuildWarLogic::reInitUserGuildWarInfo($serverId, $uid);
	}
	
	public function getChampionPresidentInfo($serverId, $guildId)
	{
		return GuildWarLogic::doGetChampionPresidentInfo($serverId, $guildId);
	}
	
	public function sendMail($arrUid, $round, $finalRank, $isWin, $objGuildName, $objServerName)
	{
		Util::asyncExecute('guildwar.sendMailByMain', array($arrUid, $round, $finalRank, $isWin, $objGuildName, $objServerName));
	}
	
	public function sendMailByMain($arrUid, $round, $finalRank, $isWin, $objGuildName, $objServerName)
	{
		return GuildWarLogic::sendMailByMain($arrUid, $round, $finalRank, $isWin, $objGuildName, $objServerName);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */