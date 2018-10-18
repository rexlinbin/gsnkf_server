<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnGuild.class.php 230745 2016-03-03 07:55:36Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/EnGuild.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-03-03 07:55:36 +0000 (Thu, 03 Mar 2016) $
 * @version $Revision: 230745 $
 * @brief 
 *  
 **/
class EnGuild
{
	public static function loginNotify()
	{
		$uid = RPCContext::getInstance()->getUid();
		if (empty($uid))
		{
			Logger::debug("user is not login, ignore init");
			return;
		}
	
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		
		if (!empty($guildId))
		{
			Logger::debug( "user is a guild member, set session now" );
			RPCContext::getInstance()->setSession(GuildDef::SESSION_GUILD_ID, $guildId);
			$guildName = GuildObj::getInstance($guildId)->getGuildName();
			RPCContext::getInstance()->setSession(GuildDef::SESSION_GUILD_NAME, $guildName);
		}
		
		//修复用户数据
		$user = EnUser::getUserObj($uid);
		if ($user->getGuildId() != $guildId)
		{
			Logger::warning("user guild id is wrong, fix it. uid:%d, old:%d, new:%d", $uid, $user->getGuildId(), $guildId);
			$user->setGuildId($guildId);
		}
	}
	
	public static function getMultiGuild($arrGuildId, $arrField = array())
	{
		if (empty($arrGuildId))
		{
			return array();
		}
		if (empty($arrField))
		{
			$arrField = GuildDef::$GUILD_FIELDS;
		}
		else if (!in_array(GuildDef::GUILD_ID, $arrField))
		{
			$arrField[] = GuildDef::GUILD_ID;
		}

		$arrCond = array(array(GuildDef::GUILD_ID, 'IN', $arrGuildId));
		$arrRet = GuildDao::getGuildList($arrCond, $arrField, 0, CData::MAX_FETCH_SIZE);
		return Util::arrayIndex($arrRet, GuildDef::GUILD_ID);
	}
	
	public static function getMultiMember($arrUid, $arrField = array())
	{
		if (empty($arrUid))
		{
			return array();
		}
		if (count($arrUid) > CData::MAX_FETCH_SIZE)
		{
			throw new InterException('too much guild member');
		}
		if (empty($arrField))
		{
			$arrField = GuildDef::$GUILD_MEMBER_FIELDS;
		}
		else if (!in_array(GuildDef::USER_ID, $arrField))
		{
			$arrField[] = GuildDef::USER_ID;
		}

		$arrRet = GuildDao::getArrMember($arrUid, $arrField, 0, CData::MAX_FETCH_SIZE);
		return Util::arrayIndex($arrRet, GuildDef::USER_ID);
	}
	
	public static function getGuildId($uid)
	{
		return GuildLogic::getGuildId($uid);
	}
	
	public static function getGuildInfo($uid)
	{
		$ret = array();
		
		$guildId = GuildLogic::getGuildId($uid);
		
		//如果用户在军团里面，就获取军团信息
		if (!empty($guildId))
		{
			$arrGuildInfo = self::getArrGuildInfo(array($guildId));
			$ret = $arrGuildInfo[$guildId];
		}
		
		return $ret;
	}
	
	public static function getArrGuildInfo($arrGuildId, $arrField = array())
	{
		$arrGuildId = array_unique($arrGuildId);
		foreach ($arrGuildId as $key => $guildId)
		{
			if (empty($guildId))
			{
				unset($arrGuildId[$key]);
			}
		}
		if (empty($arrGuildId))
		{
			return array();
		}
		if (empty($arrField))
		{
			$arrField = GuildDef::$GUILD_FIELDS;
		}
		else if (!in_array(GuildDef::GUILD_ID, $arrField))
		{
			$arrField[] = GuildDef::GUILD_ID;
		}
	
		return GuildDao::getArrGuild($arrGuildId, $arrField);
	}
	
	public static function getArrContriWeek($arrGuildId, $refreshTime = 0)
	{
		$arrGuildId = array_unique($arrGuildId);
		foreach ($arrGuildId as $key => $guildId)
		{
			if (empty($guildId))
			{
				unset($arrGuildId[$key]);
			}
		}
		if (empty($arrGuildId))
		{
			return array();
		}
		
		$arrField = array(
				GuildDef::GUILD_ID, 
				GuildDef::CONTRI_WEEK, 
				GuildDef::LAST_CONTRI_WEEK, 
				GuildDef::CONTRI_TIME
		);
		$arrRet = GuildLogic::getArrMemberList($arrGuildId, $arrField);
	
		//按军团计算周贡献
		$arrContriWeek = array();
		foreach($arrGuildId as $guildId)
		{
			$arrContriWeek[$guildId] = 0;
		}
		
		$lastSignupEndTime = EnCityWar::getLastSignupEndTime();
		$refreshTime = empty($refreshTime) ? $lastSignupEndTime : $refreshTime;
		
		$contriField = GuildDef::LAST_CONTRI_WEEK;
		if($refreshTime == $lastSignupEndTime)
		{
			$contriField = GuildDef::CONTRI_WEEK;
		}
		else if($refreshTime == $lastSignupEndTime - CityWarConf::ROUND_DURATION)
		{
			Logger::debug('get last contri week');
		}
		else 
		{
			Logger::fatal('invalid refresh time:%d', $refreshTime);
		}

		foreach ($arrRet as $ret)
		{
			$arrContriWeek[$ret[GuildDef::GUILD_ID]] += $ret[$contriField];
		}
	
		return $arrContriWeek;
	}
	
	public static function getMemberList($guildId, $arrField = array(), $dbName = '')
	{
		if (empty($guildId))
		{
			return array();
		}
		if (empty($arrField))
		{
			$arrField = GuildDef::$GUILD_MEMBER_FIELDS;
		}
		else if (!in_array(GuildDef::USER_ID, $arrField))
		{
			$arrField[] = GuildDef::USER_ID;
		}
		$arrRet = GuildDao::getMemberList($guildId, $arrField, 0, CData::MAX_FETCH_SIZE, $dbName);
		return Util::arrayIndex($arrRet, GuildDef::USER_ID);
	}
	
	public static function getBuildLevel($uid, $type = GuildDef::COPY)
	{
		if (!key_exists($type, GuildConf::$GUILD_BUILD_DEFAULT)) 
		{
			throw new FakeException('invalid guild build type:%d', $type);
		}
		
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId)) 
		{
			return -1;
		}
		$level = GuildObj::getInstance($guildId)->getBuildLevel($type);
		
		return $level;
	}
	
	public static function getMemberType($uid)
	{
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			return 3;
		}
		return $member->getMemberType();
	}
	
	public static function getMemberInfo($uid)
	{
		$memberInfo = GuildDao::selectMember($uid);
		if (!empty($memberInfo))
		{
			$memberInfo['city_id'] = EnCityWar::getGuildCityId($uid);
		}
		return $memberInfo;
	}
	
	/**
	 * 给军团加建设度
	 * 注：会给用户加周贡献
	 * 
	 * @param int $exp 军团建设值
	 * @return string 'ok'/'failed'
	 */
	public static function addGuildExp($uid, $exp)
	{
		if($uid != RPCContext::getInstance()->getUid())
		{
			throw new InterException('Not in the uid:%d session', $uid);
		}
		if ($exp <= 0) 
		{
			return 'ok';
		}
		return GuildLogic::addGuildExp($uid, $exp);
	}
	
	/**
	 * 给用户加贡献值
	 *
	 * @param int $point 用户贡献值
	 * @return string 'ok'/'failed'
	 */
	public static function addMemberPoint($uid, $point)
	{
		if($uid != RPCContext::getInstance()->getUid())
		{
			throw new InterException('Not in the uid:%d session', $uid);
		}
		if ($point <= 0)
		{
			return 'ok';
		}
		return GuildLogic::addUserPoint($uid, $point);
	}
	
	public static function robGuildByOther($attackGuildId, $defendGuildId, $robGrain)
	{
		return GuildLogic::robGuildByOther($attackGuildId, $defendGuildId, $robGrain);
	}
	
	public static function recordGuildCopyAllAttack($uid, $guildId)
	{
		return GuildLogic::recordGuildCopyAllAttack($uid, $guildId);
	}
	
	public static function getAddAttr($uid)
	{
		$member = GuildMemberObj::getInstance($uid);
		return HeroUtil::adaptAttr($member->getAddAttr());
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
