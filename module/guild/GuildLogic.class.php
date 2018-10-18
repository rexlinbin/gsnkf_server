<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildLogic.class.php 242385 2016-05-12 09:29:27Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/GuildLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-12 09:29:27 +0000 (Thu, 12 May 2016) $
 * @version $Revision: 242385 $
 * @brief 
 *  
 **/
class GuildLogic
{
	public static function createGuild($uid, $name, $isGold, $slogan, $post, $passwd)
	{
		Logger::trace('GuildLogic::createGuild Start.');
		
		$ret = array('ret' => 'ok', 'info' => array());
		
		//检查用户是否已经加入军团
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (!empty($guildId))
		{
			throw new FakeException('user is in guild:%d already!', $guildId);
		}
		
		//检查用户是否在CD时间内
		$now = Util::getTime();
		if ($member->getRejoinCd() > $now) 
		{
			throw new FakeException('user is in rejoin cd, cant create guild!');
		}
		
		//检查用户等级是否达到条件
		$conf = btstore_get()->GUILD;
		$user = EnUser::getUserObj($uid);
		if ($user->getLevel() < $conf[GuildDef::GUILD_USER_LEVEL])
		{
			throw new FakeException('user level is not enough to create guild!');
		}
		
		//检查军团名称,宣言,公告长度
		self::checkLength($name, GuildConf::MAX_NAME_LENGTH, 'name');
		self::checkLength($slogan, GuildConf::MAX_SLOGAN_LENGTH, GuildDef::SLOGAN);
		self::checkLength($post, GuildConf::MAX_POST_LENGTH, GuildDef::POST);
		
		//检查名称中的英文和中文空格
		if (false !== mb_strpos($name, ' ', 0, FrameworkConfig::ENCODING)
		|| false !== mb_strpos($name, '　', 0, FrameworkConfig::ENCODING))
		{
			Logger::trace('guild name has blank space!');
			$ret['ret'] = 'blank';
			return $ret;
		}
		
		//检查名称中的敏感词汇
		$harmony = Util::checkName($name);
		if ('ok' != $harmony)
		{
			Logger::trace('guild name has filter content!');
			$ret['ret'] = 'harmony';
			return $ret;
		}
		
		//检查名称是否存在
		$arrCond = array(array(GuildDef::GUILD_NAME, '==', $name));
		$count = GuildDao::getGuildCount($arrCond);
		if ($count > 0)
		{
			Logger::trace('guild name is used!');
			$ret['ret'] = 'used';
			return $ret;
		}
		
		//检查是否超过创建次数
		$arrCond = array (
				array(GuildDef::CREATE_UID, '=', $uid),
				array(GuildDef::STATUS, '=', GuildStatus::OK) 
		);
		$count = GuildDao::getGuildCount($arrCond);
		if ($count >= GuildConf::MAX_CREATE_NUM)
		{
			Logger::warning('user created guild reach limit!');
			$ret['ret'] = 'exceed';
			return $ret;
		}	
		
		//处理宣言，公告
		if (empty($slogan))
		{
			$slogan = GuildConf::DEFAULT_SLOGAN;
		}
		if (empty($post))
		{
			$post = GuildConf::DEFAULT_POST;
		}
		//密码md5加密
		if (!empty($passwd))
		{
			$passwd = md5($passwd);
		}
		
		//过滤宣言中的敏感词汇
		$slogan = TrieFilter::mb_replace($slogan);
		$post = TrieFilter::mb_replace($post);	
		
		//判断创建方式
		if ($isGold) 
		{
			$cost = $conf[GuildDef::GUILD_GOLD_CREATE];
			if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_GUILD_CREATE_COST) == false)
			{
				throw new FakeException('no enough gold:%d', $cost);
			}
		}
		else 
		{
			$cost = $conf[GuildDef::GUILD_SILVER_CREATE];
			if ($user->subSilver($cost) == false)
			{
				throw new FakeException('no enough silver:%d', $cost);
			}
		}
		
		//军团数据初始化,插入数据失败表示有同名军团刚创建完，冲突了
		$guildId = GuildObj::createGuild($uid, $name, $slogan, $post, $passwd);
		if (empty($guildId)) 
		{
			Logger::trace('guild name is used!');
			$ret['ret'] = 'used';
			return $ret;
		}
		
		
		//成员信息初始化
		$member->setGuildId($guildId);
		$member->setMemberType(GuildMemberType::PRESIDENT);
		$member->update();
		
		//取消用户的其他申请记录
		self::cancelAllApply($uid);
		
		$user->update();
		
		self::refreshUser($uid, $guildId, true);
		
		//获取额外信息
		$guild = GuildObj::getInstance($guildId);
		$info = $guild->getInfo();
		$guildLevel = $guild->getGuildLevel();
		$fightForce = $guild->getFightForce();
		$upgradeTime = $guild->getUpgradeTime();
		unset($info[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::PASSWD]);
		$info[GuildDef::LEADER_UID] = $uid;
		$info[GuildDef::LEADER_UTID] = $user->getUtid();
		$info[GuildDef::LEADER_NAME] = $user->getUname();
		$info[GuildDef::LEADER_LEVEL] = $user->getLevel();
		$info[GuildDef::LEADER_FORCE] = $user->getFightForce();
		$info[GuildDef::MEMBER_NUM] = 1;
		$info[GuildDef::MEMBER_LIMIT] = self::getMemberLimit($info);
		$info[GuildDef::VP_NUM] = 0;
		$info[GuildDef::RANK] = self::getRank($guildId, $guildLevel, $fightForce, $upgradeTime);
		$ret['info'] = $info;
		
		EnNewServerActivity::updateGuild($uid);
		
		Logger::trace('GuildLogic::createGuild End.');
		
		return $ret;
	}
	
	public static function applyGuild($uid, $guildId)
	{
		Logger::trace('GuildLogic::applyGuild Start.');
		
		//检查用户是否已经加入军团
		$member = GuildMemberObj::getInstance($uid);
		$curGuildId = $member->getGuildId();
		if (!empty($curGuildId))
		{
			throw new FakeException('user is in guild:%d already!', $curGuildId);
		}
		
		//检查用户是否在CD时间内
		$now = Util::getTime();
		if ($member->getRejoinCd() > $now)
		{
			throw new FakeException('user is in rejoin cd, cant apply guild!');
		}
		
		//检查用户是否已申请军团
		$applyInfo = GuildDao::selectApply($uid, $guildId);
		if (!empty($applyInfo))
		{
			throw new FakeException('user:%d applyed guild:%d already!', $uid, $guildId);
		}
		
		//检查该军团是否存在
		$arrCond = array(
				array(GuildDef::GUILD_ID, '=', $guildId),
				array(GuildDef::STATUS, '=', GuildStatus::OK)
		);
		$count = GuildDao::getGuildCount($arrCond);
		if ($count == 0)
		{
			throw new FakeException('guild:%d is not exist!', $guildId);
		}
		
		//检查用户的申请数量
		$arrCond = array(
				array(GuildDef::USER_ID, '=', $uid), 
				array(GuildDef::STATUS, '=', GuildApplyStatus::OK)
		);
		$count = GuildDao::getApplyCount($arrCond);
		if ($count >= GuildConf::MAX_APPLY_NUM)
		{
			throw new FakeException('user:%d has too much unresolved apply, exceed apply limit:%d', $uid, GuildConf::MAX_APPLY_NUM);
		}
		
		//申请信息初始化
		self::initApply($uid, $guildId);
		
		Logger::trace('GuildLogic::applyGuild End.');
		
		return 'ok';	
	}
	
	public static function cancelApply($uid, $guildId)
	{
		Logger::trace('GuildLogic::cancelApply Start.');
		
		//检查用户是否已经加入军团
		$member = GuildMemberObj::getInstance($uid);
		$curGuildId = $member->getGuildId();
		if (!empty($curGuildId))
		{
			throw new FakeException('user is in guild:%d already!', $curGuildId);
		}
		
		//检查用户是否在CD时间内
		$now = Util::getTime();
		if ($member->getRejoinCd() > $now)
		{
			throw new FakeException('user is in rejoin cd, cant cancel apply!');
		}
		
		//更新申请信息
		$arrCond = array(
				array(GuildDef::USER_ID, '=', $uid),
				array(GuildDef::GUILD_ID, '=', $guildId),
				array(GuildDef::STATUS, '=', GuildApplyStatus::OK)
		);
		$arrField = array(GuildDef::STATUS => GuildApplyStatus::CANCEL);
		$isSuc = GuildDao::updateApply($arrCond, $arrField);
		if (empty($isSuc))
		{
			//发生条件：用户没有申请或者用户已经取消了申请或者被拒绝了申请
			Logger::warning('user:%d does not apply guild:%d!', $uid, $guildId);
		}
		
		Logger::trace('GuildLogic::cancelApply End.');
		
		return 'ok';
	}
	
	public static function agreeApply($uid, $applyUid)
	{
		Logger::trace('GuildLogic::agreeApply Start.');
		
		//检查用户是否已经加入军团
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::MEMBER_MANAGE] ))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::MEMBER_MANAGE);
		}
		
		//城池战报名结束前1小时不得加入或者退出军团
		if (EnCityWar::isSignup($guildId) && EnCityWar::inForbiddenTime())
		{
			Logger::warning('guild:%d signed up the city war, can not agree apply.', $guildId);
			return 'forbidden_citywar';
		}
		
		//抢粮战期间不能加入军团
		if (EnGuildRob::isInRobBattle($guildId))
		{
			Logger::warning('guild:%d is in the guild rob, can not agree apply.', $guildId);
			return 'forbidden_guildrob';
		}
		
		try
		{
			//检查军团当前的总人数是否达到上限
			$guild = GuildObj::getInstance($guildId, array(GuildDef::JOIN_NUM));
		    $guildLevel = $guild->getGuildLevel();
		    $memberNum = self::getMemberNum($guildId);
		    $memberLimit = self::getMemberLimit($guild->getInfo());
		    if ($memberNum >= $memberLimit)
		    {
		        $guild->unlockArrField();
		        Logger::warning('guild:%d member num:%d reach member limit:%d', $guildId, $memberNum, $memberLimit);
		        return 'exceed';
		    }
		    
		    //检查军团当天加入人数是否达到上限
		    $conf = btstore_get()->GUILD;
		    $joinExtra = $conf[GuildDef::GUILD_JOIN_EXTRA];
		    $joinLimit = $memberLimit + $joinExtra;
		    $joinNum = $guild->getJoinNum();
		    if ($joinNum >= $joinLimit)
		    {
		        $guild->unlockArrField();
		        Logger::warning('guild:%d join num:%d reach join limit:%d', $guildId, $joinNum, $joinLimit);
		        return 'limited';
		    }
		    
		    //更新申请记录
		    $arrCond = array(
		            array(GuildDef::USER_ID, '=', $applyUid),
		            array(GuildDef::GUILD_ID, '=', $guildId),
		            array(GuildDef::STATUS, '=', GuildApplyStatus::OK)
		    );
		    $arrField = array(GuildDef::STATUS => GuildApplyStatus::AGREED);
		    $isSuc = GuildDao::updateApply($arrCond, $arrField);
		    if (empty($isSuc))
		    {
		        //此时可能加锁前申请已经被别人处理过了（用户取消了申请或者是管理者已经同意了申请）
		        $guild->unlockArrField();
		        Logger::warning("user:%d has no apply for guild:%d", $applyUid, $guildId);
		        return 'failed';
		    }
		    
		    //更新军团加入人数
		    $now = Util::getTime();
		    $guild->setJoinNum($joinNum + 1);
		    $guild->setJoinTime($now);
		    $guild->update();
		}
		catch(Exception $e)
		{
			$guild->unlockArrField();
		    throw $e;
		}
		    
	    //将该用户添加到军团中去
	    $applyMember = GuildMemberObj::getInstance($applyUid);
	    $applyMember->setGuildId($guildId);
	    $applyMember->setMemberType(GuildMemberType::NONE);
	    $applyMember->update();
	    
	    //取消所有其他申请记录
	    self::cancelAllApply($applyUid);
		
		//记录加入信息
		self::initRecord($applyUid, $guildId, GuildRecordType::JOIN_GUILD, 0);
		
		MailTemplate::sendGuildResponse($applyUid, $guild->getTemplateInfo(), true);
		RPCContext::getInstance()->executeTask($applyUid, 'guild.refreshUser', array($applyUid, $guildId, true), false);
		RPCContext::getInstance()->sendMsg(array($applyUid), PushInterfaceDef::REFRESH_MEMBER, self::getMemberInfo($applyUid));
		
		//更新申请者军团成就
		EnAchieve::updateGuildLevel($applyUid, $guildLevel);

		//通知资源矿
		Mineral::changeGuild($guildId, $applyUid);
		
		EnNewServerActivity::updateGuild($applyUid);
		
		Logger::trace('GuildLogic::agreeApply End.');
		
		return 'ok';
	}
	
	public static function refuseApply($uid, $applyUid)
	{
		Logger::trace('GuildLogic::refuseApply Start.');
		
		//检查用户是否已经加入军团
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::MEMBER_MANAGE] ))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::MEMBER_MANAGE);		
		}
		
		//更新申请记录
		$arrCond = array(
				array(GuildDef::USER_ID, '=', $applyUid),
				array(GuildDef::GUILD_ID, '=', $guildId),
				array(GuildDef::STATUS, '=', GuildApplyStatus::OK)
		);
		$arrField = array(GuildDef::STATUS => GuildApplyStatus::REFUSED);
		$isSuc = GuildDao::updateApply($arrCond, $arrField);
		if (empty($isSuc))
		{
			//可能申请已经被人处理过了（用户取消了申请或者是被其他管理者拒绝了）
			Logger::warning("user:%d has no apply for guild:%d", $applyUid, $guildId);
			return 'failed';
		}
		
		$guildInfo = GuildObj::getInstance($guildId)->getTemplateInfo();
		MailTemplate::sendGuildResponse($applyUid, $guildInfo, false);
		
		Logger::trace('GuildLogic::refuseApply End.');
		
		return 'ok';
	}
	
	public static function refuseAllApply($uid)
	{
		Logger::trace('GuildLogic::refuseAllApply Start.');
	
		//检查用户是否已经加入军团
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::MEMBER_MANAGE]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::MEMBER_MANAGE);
		}
		
		//更新申请记录
		$offset = 0;
		$arrRet = array();
		$limit = CData::MAX_FETCH_SIZE;
		while ($limit >= CData::MAX_FETCH_SIZE)
		{
			$cond = array(GuildDef::GUILD_ID, '=', $guildId);
			$arrField = array(GuildDef::USER_ID);
			$ret = GuildDao::getApplyList($cond, $arrField, $offset, $limit);
			$arrRet = array_merge($arrRet, $ret);
			$offset += $limit;
			$limit = count($ret);
		}

		$arrCond = array(
				array(GuildDef::GUILD_ID, '=', $guildId),
				array(GuildDef::STATUS, '=', GuildApplyStatus::OK)
		);
		$arrField = array(GuildDef::STATUS => GuildApplyStatus::REFUSED);
		$isSuc = GuildDao::updateApply($arrCond, $arrField);
		if (empty($isSuc))
		{
			//可能申请已经被人处理过了（用户取消了申请或者是被其他管理者拒绝了）
			Logger::warning("Thers is no apply for guild:%d", $guildId);
			return 'failed';
		}
	
		//发邮件拒绝所有申请人
		$guildInfo = GuildObj::getInstance($guildId)->getTemplateInfo();
		foreach ($arrRet as $ret)
		{
			$applyUid = $ret[GuildDef::USER_ID];
			MailTemplate::sendGuildResponse($applyUid, $guildInfo, false);
		}
	
		Logger::trace('GuildLogic::refuseAllApply End.');
	
		return 'ok';
	}
	
	public static function quitGuild($uid)
	{
		Logger::trace('GuildLogic::quitGuild Start.');

		//检查用户是否已经加入军团
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//军团长不能退出军团
		$memberType = $member->getMemberType();
		if (GuildMemberType::PRESIDENT == $memberType) 
		{
			throw new FakeException('user:%d can not quit guild!', $uid);
		}
		
		//城池战报名结束前1小时不得加入或者退出军团
		if (EnCityWar::isSignup($guildId) && EnCityWar::inForbiddenTime(1))
		{
			Logger::warning('guild:%d signup the city war, can not quit guild.', $guildId);
			return 'forbidden_citywar';
		}
		
		//抢粮战期间不能退出军团
		if (EnGuildRob::isInRobBattle($guildId))
		{
			Logger::warning('guild:%d is in the guild rob, can not quit guild.', $guildId);
			return 'forbidden_guildrob';
		}
		
		//跨服军团赛不能退出军团
		if (EnGuildWar::duringGuildWar($guildId))
		{
			Logger::warning('guild:%d is in the guild war, can not quit guild.', $guildId);
			return 'forbidden_guildwar';
		}

		//更新成员信息
		$rejoinCd = Util::getTime() + btstore_get()->GUILD[GuildDef::GUILD_REJOIN_CD];
		$member->setGuildId(0);
		$member->setRejoinCd($rejoinCd);
		$member->update();
		
		//记录退出信息
		self::initRecord($uid, $guildId, GuildRecordType::QUIT_GUILD, 0);
		
		self::refreshUser($uid, 0, false);
		
		//通知资源矿
		Mineral::changeGuild(0);
		
		Logger::trace('GuildLogic::quitGuild End.');	
		
		return 'ok';
	}
	
	public static function kickMember($uid, $memberUid)
	{
		Logger::trace('GuildLogic::kickMember Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::MEMBER_MANAGE]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::MEMBER_MANAGE);
		}
		
		//被踢用户所在的军团id
		$kickMember = GuildMemberObj::getInstance($memberUid);
		$kickGuildId = $kickMember->getGuildId();
		if ($kickGuildId != $guildId) 
		{
			throw new FakeException('user:%d is not in guild:%d!', $memberUid, $guildId);
		}
		
		//检查成员类型，只能团长踢副团长和成员，副团长踢成员
		$kickMemberType = $kickMember->getMemberType();
		if (GuildMemberType::PRESIDENT == $memberType && !in_array($kickMemberType, array(GuildMemberType::VICE_PRESIDENT, GuildMemberType::NONE))
		|| GuildMemberType::VICE_PRESIDENT == $memberType && !in_array($kickMemberType, array(GuildMemberType::NONE))) 
		{
			throw new FakeException('user:%d has no privilege to kick user:%d', $uid, $memberUid);
		}
		
		//城池战报名结束前1小时不得加入或者退出军团
		if (EnCityWar::isSignup($guildId) && EnCityWar::inForbiddenTime(1))
		{
			Logger::warning('guild:%d signup the city war, can not kick member.', $guildId);
			return 'forbidden_citywar';
		}
		
		//抢粮战期间不能退出军团
		if (EnGuildRob::isInRobBattle($guildId))
		{
			Logger::warning('guild:%d is in the guild rob, can not kick member.', $guildId);
			return 'forbidden_guildrob';
		}
		
		//跨服军团赛不能退出军团
		if (EnGuildWar::duringGuildWar($guildId))
		{
			Logger::warning('guild:%d is in the guild war, can not kick member.', $guildId);
			return 'forbidden_guildwar';
		}
		
		//更新成员信息
		$rejoinCd = Util::getTime() + btstore_get()->GUILD[GuildDef::GUILD_REJOIN_CD];
		$kickMember->setGuildId(0);
		$kickMember->setRejoinCd($rejoinCd);
		$kickMember->update();
		
		//记录踢出信息
		self::initRecord($uid, $guildId, GuildRecordType::KICK_MEMBER, $memberUid);
		
		$userInfo = EnUser::getUserObj($uid)->getTemplateUserInfo();
		$guildInfo = GuildObj::getInstance($guildId)->getTemplateInfo();
		MailTemplate::sendGuildKick($memberUid, $guildInfo, $userInfo);
		RPCContext::getInstance()->executeTask($memberUid, 'guild.refreshUser', array($memberUid, 0, false), false);
		RPCContext::getInstance()->sendMsg(array($memberUid), PushInterfaceDef::REFRESH_MEMBER, self::getMemberInfo($memberUid));
		
		//通知资源矿
		Mineral::changeGuild(0, $memberUid);
		
		Logger::trace('GuildLogic::kickMember End.');
		
		return 'ok';
	}
	
	public static function modifyIcon($uid, $icon)
	{
		Logger::trace('GuildLogic::modifyIcon Start.');
		
		//检查徽章
		if (empty(btstore_get()->GUILD_ICON[$icon])) 
		{
			throw new FakeException('icon:%d is not exist!', $icon);
		}
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::ICON_MODIFY]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::ICON_MODIFY);
		}
		
		//更新军团信息
		try
		{
			$guild = GuildObj::getInstance($guildId, array(GuildDef::GUILD_ICON));
			$guild->setGuildIcon($icon);
			$guild->update();
		}
		catch(Exception $e)
		{
			$guild->unlockArrField();
			throw $e;
		}
		
		Logger::trace('GuildLogic::modifyIcon End.');
		
		return 'ok';
	}
	
	public static function modifySlogan($uid, $slogan)
	{
		Logger::trace('GuildLogic::modifySlogan Start.');
		
		//检查宣言长度
		self::checkLength($slogan, GuildConf::MAX_SLOGAN_LENGTH, GuildDef::SLOGAN);
		//过滤敏感词汇
		$slogan = TrieFilter::mb_replace($slogan);
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::SLOGAN_MODIFY]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::SLOGAN_MODIFY);
		}
		
		//更新军团信息
		try 
		{
			$guild = GuildObj::getInstance($guildId, array(GuildDef::VA_INFO));
			$guild->setSlogan($slogan);
			$guild->update();
		}
		catch(Exception $e)
		{
			$guild->unlockArrField();
			throw $e;
		}
		
		Logger::trace('GuildLogic::modifySlogan End.');
		
		return array('ret' => 'ok', 'slogan' => $slogan);
	}
	
	public static function modifyPost($uid, $post)
	{
		Logger::trace('GuildLogic::modifyPost Start.');
	
		//检查宣言长度
		self::checkLength($post, GuildConf::MAX_POST_LENGTH, GuildDef::POST);
		//过滤敏感词汇
		$post = TrieFilter::mb_replace($post);
	
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::POST_MODIFY]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::POST_MODIFY);
		}
	
		try 
		{
			//更新军团信息
			$guild = GuildObj::getInstance($guildId, array(GuildDef::VA_INFO));
			$guild->setPost($post);
			$guild->update();
		}
		catch(Exception $e)
		{
			$guild->unlockArrField();
			throw $e;
		}
	
		Logger::trace('GuildLogic::modifyPost End.');
	
		return array('ret' => 'ok', 'post' => $post);
	}
	
	public static function modifyPasswd($uid, $oldPasswd, $newPasswd)
	{
		Logger::trace('GuildLogic::modifyPasswd Start.');
		
		//参数检查
		if (!is_string($oldPasswd))
		{
			throw new FakeException('guild old passwd is not string!');
		}
		if (!is_string($newPasswd))
		{
			throw new FakeException('guild new passwd is not string!');
		}
	
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::PASSWD_MODIFY]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::PASSWD_MODIFY);
		}
	
		//检查oldpasswd是否正确，目前只有军团长一个人能改军团密码，所以就不加trycatch
		$guild = GuildObj::getInstance($guildId, array(GuildDef::VA_INFO));
		if ($guild->verifyPasswd($oldPasswd) == false)
		{
			return 'err_passwd';
		}
	
		//更新密码
		$guild->setPasswd($newPasswd);
		$guild->update();
	
		Logger::trace('GuildLogic::modifyPasswd End.');
	
		return 'ok';
	}
	
	public static function modifyName($uid, $name)
	{
		Logger::trace('GuildLogic::modifyName Start.');
		
		//检查用户是否已经加入军团
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::NAME_MODIFY]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::NAME_MODIFY);
		}
		
		//检查军团名称长度
		self::checkLength($name, GuildConf::MAX_NAME_LENGTH, 'name');
		
		//检查名称中的英文和中文空格
		if (false !== mb_strpos($name, ' ', 0, FrameworkConfig::ENCODING)
		|| false !== mb_strpos($name, '　', 0, FrameworkConfig::ENCODING))
		{
			Logger::trace('guild name has blank space!');
			return 'blank';
		}
		
		//检查名称中的敏感词汇
		$harmony = Util::checkName($name);
		if ('ok' != $harmony)
		{
			Logger::trace('guild name has filter content!');
			return 'harmony';
		}
		
		//检查名称是否存在
		$arrCond = array(array(GuildDef::GUILD_NAME, '==', $name));
		$count = GuildDao::getGuildCount($arrCond);
		if ($count > 0)
		{
			Logger::trace('guild name is used!');
			return 'used';
		}
		
		$user = EnUser::getUserObj($uid);
		$cost = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GUILD_CHANGE_NAME];
		if (!$user->subGold($cost, StatisticsDef::ST_FUNCKEY_GUILD_CHANGE_NAME))
		{
			throw new FakeException('no enough gold:%d', $cost);
		}
		
		//更新军团信息
		try 
		{
			$guild = GuildObj::getInstance($guildId);
			$guild->setGuildName($name);
			$guild->update();
			$user->update();
		}
	 	catch(Exception $e)
        {
            Logger::trace('modify name failed, error msg is %s.', $e->getMessage());
            return 'used';
        }
		
		RPCContext::getInstance()->setSession(GuildDef::SESSION_GUILD_NAME, $name);
		
		$memberList = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID));
		$arrMember = Util::arrayExtract($memberList, GuildDef::USER_ID);
		foreach ($arrMember as $memberUid)
		{
			if ($memberUid == $uid) 
			{
				continue;
			}
			RPCContext::getInstance()->executeTask($memberUid, 'guild.refreshGuildName', array($memberUid, $name), false);
		}
	
		Logger::trace('GuildLogic::modifyName End.');
		
		return 'ok';
	}
	
	public static function setVicePresident($uid, $memberUid)
	{
		Logger::trace('GuildLogic::setVicePresident Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::SET_VP]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::SET_VP);
		}
		
		//检查被设置用户是否在军团中
		$vpMember = GuildMemberObj::getInstance($memberUid);
		$vpGuildId = $vpMember->getGuildId();
		if ($vpGuildId != $guildId) 
		{
			throw new FakeException('user:%d is not in guild:%d!', $memberUid, $guildId);
		}
		
		//检查被设置用户职位
		$vpMemberType = $vpMember->getMemberType();
		if (GuildMemberType::VICE_PRESIDENT == $vpMemberType) 
		{
			throw new FakeException('user:%d is vice president!', $memberUid);
		}
		
		//检查当前军团等级下副团员个数
		$conf = btstore_get()->GUILD;
		$vpNumArr = $conf[GuildDef::GUILD_VP_NUM];
		$guild = GuildObj::getInstance($guildId);
		$guildLevel = $guild->getGuildLevel();
		$maxVpNum = 0;
		foreach ($vpNumArr as $level => $num)
		{
			if ($guildLevel <= $level)
			{
				$maxVpNum = $num;
				break;
			}
		}
		$vpNum = self::getVpNum($guildId);
		if ($vpNum >= $maxVpNum)
		{
			throw new FakeException('guild:%d has max vp:%d already, no position!', $guildId, $vpNum);
		}
		
		//更新成员信息
		$vpMember->setMemberType(GuildMemberType::VICE_PRESIDENT);
		$vpMember->update();
		
		//记录设置副团信息
		self::initRecord($uid, $guildId, GuildRecordType::SET_VP, $memberUid);
		RPCContext::getInstance()->sendMsg(array($memberUid), PushInterfaceDef::REFRESH_MEMBER, self::getMemberInfo($memberUid));
		
		Logger::trace('GuildLogic::setVicePresident End.');
		
		return 'ok';
	}
	
	public static function unsetVicePresident($uid, $memberUid)
	{
		Logger::trace('GuildLogic::unsetVicePresident Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::SET_VP]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::SET_VP);
		}
		
		//检查被设置用户是否在军团中
		$vpMember = GuildMemberObj::getInstance($memberUid);
		$vpGuildId = $vpMember->getGuildId();
		if ($vpGuildId != $guildId)
		{
			throw new FakeException('user:%d is not in guild:%d!', $memberUid, $guildId);
		}
		
		//检查被设置用户职位
		$vpMemberType = $vpMember->getMemberType();
		if (GuildMemberType::VICE_PRESIDENT != $vpMemberType)
		{
			throw new FakeException('user:%d is not vice president!', $memberUid);
		}
		
		//跨服军团赛不能取消副军团长
		if (EnGuildWar::duringGuildWar($guildId))
		{
			Logger::warning('guild:%d is in the guild war, can not unset president.', $guildId);
			return 'forbidden_guildwar';
		}
		
		//更新成员信息
		$vpMember->setMemberType(GuildMemberType::NONE);
		$vpMember->update();
		
		//刷新军团用户信息
		RPCContext::getInstance()->sendMsg(array($memberUid), PushInterfaceDef::REFRESH_MEMBER, self::getMemberInfo($memberUid));
		
		Logger::trace('GuildLogic::unsetVicePresident End.');
		
		return 'ok';
	}
	
	public static function transPresident($uid, $memberUid, $passwd)
	{
		Logger::trace('GuildLogic::transPresident Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::ROLE_TRANS]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::ROLE_TRANS);
		}
		
		//检查被设置用户是否在军团中
		$pMember = GuildMemberObj::getInstance($memberUid);
		$pGuildId = $pMember->getGuildId();
		if ($pGuildId != $guildId)
		{
			throw new FakeException('user:%d is not in guild:%d!', $memberUid, $guildId);
		}
		
		//检查被设置用户职位
		$pMemberType = $pMember->getMemberType();
		if (GuildMemberType::PRESIDENT == $pMemberType)
		{
			throw new FakeException('user:%d is president!', $memberUid);
		}
		
		//跨服军团赛不能转让军团长
		if (EnGuildWar::duringGuildWar($guildId))
		{
			Logger::warning('guild:%d is in the guild war, can not trans president.', $guildId);
			return 'forbidden_guildwar';
		}
		
		//检查passwd是否正确，只有军团长能转让职位，所以也不加trycatch了
		$guild = GuildObj::getInstance($guildId, array(GuildDef::VA_INFO));
		if ($guild->verifyPasswd($passwd) == false)
		{
			return 'err_passwd';
		}
		
		//将当前会长权限解除
		$member->setMemberType(GuildMemberType::NONE);
		$member->update();
		
		//将目标用户权限提升
		$pMember->setMemberType(GuildMemberType::PRESIDENT);
		$pMember->update();
		
		//重置密码
		$guild->setPasswd('');
		$guild->update();
		
		//记录转让信息
		self::initRecord($uid, $guildId, GuildRecordType::TRANS_P, $memberUid);
		RPCContext::getInstance()->sendMsg(array($memberUid), PushInterfaceDef::REFRESH_MEMBER, self::getMemberInfo($memberUid));
		
		Logger::trace('GuildLogic::transPresident End.');
		
		return 'ok';
	}
	
	public static function dismiss($uid, $passwd)
	{
		Logger::trace('GuildLogic::dismiss Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::DISMISS]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::DISMISS);
		}
		
		//城池战报名结束前1小时至战斗结束前不得解散军团
		if (EnCityWar::isSignup($guildId) && EnCityWar::inForbiddenTime(1))
		{
			Logger::warning('guild:%d signup the city war, can not dismiss guild.', $guildId);
			return 'forbidden_citywar';
		}
		
		//抢粮战期间不能解散军团
		if (EnGuildRob::isInRobBattle($guildId))
		{
			Logger::warning('guild:%d is in the guild rob, can not dismiss guild.', $guildId);
			return 'forbidden_guildrob';
		}
		
		//跨服军团赛不能解散军团
		if (EnGuildWar::duringGuildWar($guildId))
		{
			Logger::warning('guild:%d is in the guild war, can not dismiss guild.', $guildId);
			return 'forbidden_guildwar';
		}
		
		//检查oldpasswd是否正确
		$guild = GuildObj::getInstance($guildId);
		if ($guild->verifyPasswd($passwd) == false)
		{
			return 'err_passwd';
		}
		
		//检查军团是否超过解散的等级上限
		if ($guild->getGuildLevel() >= GuildConf::GUILD_DISMISS_LEVEL) 
		{
			throw new FakeException('guild level is too large to dimiss');
		}
		
		//检查军团当前的总人数
		if (self::getMemberNum($guildId) != 1)
		{
			throw new FakeException('guild:%d has more than one member, can not be dismissed', $guildId);
		}
		
		//将用户从军团删除
		$rejoinCd = Util::getTime() + btstore_get()->GUILD[GuildDef::GUILD_REJOIN_CD];
		$member->setGuildId(0);
		$member->setRejoinCd($rejoinCd);
		$member->update();
		
		//删除军团
		$guild->setStatus(GuildStatus::DEL);
		$guild->update();
		
		//删除申请记录
		$arrCond = array(
				array(GuildDef::GUILD_ID, '=', $guildId),
				array (GuildDef::STATUS, '=', GuildApplyStatus::OK) 
		);
		$arrField = array(GuildDef::STATUS => GuildApplyStatus::CANCEL);
		GuildDao::updateApply($arrCond, $arrField);
		
		self::refreshUser($uid, 0, false);
		
		//通知资源矿
		Mineral::changeGuild(0);
		
		Logger::trace('GuildLogic::dismiss End.');
		
		return 'ok';
	}
	
	public static function impeach($uid)
	{
		Logger::trace('GuildLogic::impeach Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::IMPEACH]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::IMPEACH);
		}
		
		try
		{
			$guild = GuildObj::getInstance($guildId, array(GuildDef::VA_INFO));
			//获得团长信息
			$presidentUid = self::getPresidentUid($guildId);
			if ($presidentUid == $uid) 
			{
				$guild->unlockArrField();
				Logger::warning('can not impeach self');
				return 'failed';	
			}
		
			//判断团长离线时间是否达到弹劾时间
			$puser = EnUser::getUserObj($presidentUid);
			$lastLoginTime = $puser->getLastLoginTime();
			if (Util::getTime() - $lastLoginTime < GuildConf::MAX_IMPEACHMENT_TIME)
			{
				$guild->unlockArrField();
				Logger::warning('time is not arrive');
				return 'failed';			
			}
		
			//用户减金币
			$user = EnUser::getUserObj($uid);
			$impeachGold = btstore_get()->GUILD[GuildDef::GUILD_IMPEACH_GOLD];
			if ($user->subGold($impeachGold, StatisticsDef::ST_FUNCKEY_GUILD_IMPEACH_COST) == false)
			{
				$guild->unlockArrField();
				throw new FakeException('user:%d do not have enough gold for impeach!', $uid);		
			}
		
			//重置密码
		    $guild->setPasswd('');
		    $guild->update();
		}
		catch(Exception $e)
		{
		    $guild->unlockArrField();
		    throw $e;
		}
		$user->update();
		
		//将团长权限撤销
		$pMember = GuildMemberObj::getInstance($presidentUid);
		$pMember->setMemberType(GuildMemberType::NONE);
		$pMember->update();
		
		//转移团长职位给弹劾用户
		$member->setMemberType(GuildMemberType::PRESIDENT);
		$member->update();
		
		//记录弹劾信息
		self::initRecord($uid, $guildId, GuildRecordType::IMPEACH_P, $presidentUid);
		
		Logger::trace('GuildLogic::impeach End.');
		
		return 'ok';
	}
	
	public static function contribute($uid, $type)
	{
		Logger::trace('GuildLogic::contribute Start.');
		
		//检查类型参数是否合法
		$contriArr = btstore_get()->GUILD[GuildDef::GUILD_CONTRI_ARR];
		if (!isset($contriArr[$type]))
		{
			throw new FakeException('type:%d is invalid!', $type);
		}
		
		$now = Util::getTime();
		$citywarSignupEndTime = EnCityWar::getSignupEndTime();
		if ($now >= $citywarSignupEndTime && $now < $citywarSignupEndTime + 60)
		{
			Logger::trace('in citywar signup end time');
			return 'insigntime';
		}
		
		//检查用户是否在军团
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//目前每人每天只能贡献1次
		$contriNum = $member->getContriNum();
		if ($contriNum >= 1) 
		{
			throw new FakeException('user:%d has contributed once today!', $uid);
		}
		
		//减去相应花费
		$user = EnUser::getUserObj($uid);
		$vip = $contriArr[$type]['vip'];
		if ($user->getVip() < $vip) 
		{
			throw new FakeException('user:%d vip is not enough to contribute type:%d!', $uid, $type);
		}
		//减银币
		$silver = $contriArr[$type]['silver'];
		if ($user->subSilver($silver) == false)
		{
			throw new FakeException('user:%d has not enough silver:%d to contribute!', $uid, $silver);
		}
		//减金币
		$gold = $contriArr[$type]['gold'];
		if ($user->subGold($gold, StatisticsDef::ST_FUNCKEY_GUILD_CONTRI_COST) == false)
		{
			throw new FakeException('user:%d has not enough gold:%d to contribute!', $uid, $gold);
		}
		
		$exp = $contriArr[$type]['exp'];
		$point = $contriArr[$type]['point'];
		//是否开启福利活动
		$contriWeal = EnWeal::getWeal(WealDef::GUILD_CONTRI);
		if (!empty($contriWeal)) 
		{
			$expRate = $contriWeal[$type][0] / UNIT_BASE;
			$pointRate = $contriWeal[$type][1] / UNIT_BASE; 
			if ($expRate < 0 || $expRate > 10) 
			{
				throw new ConfigException('invalid contri weal rate for guild:%d!', $expRate);
			}
			if ($pointRate < 0 || $pointRate > 10)
			{
				throw new ConfigException('invalid contri weal rate for user:%d!', $pointRate);
			}
			$exp = intval($expRate * $exp);
			$point = intval($pointRate * $point);
		}
		
		try
		{
			//给军团加上贡献值并更新贡献次数和时间
			$guild = GuildObj::getInstance($guildId, array(GuildDef::CURR_EXP, GuildDef::CONTRI_NUM));
			$guild->addCurrExp($exp);
			$guild->addContriNum(1);
			$guild->setContriTime($now);
			$guild->update();
		}
		catch(Exception $e)
		{
			$guild->unlockArrField();
			throw $e;
		}
		$user->update();
		
		//更新成员的信息，贡献值和贡献时间
		$member->addContriPoint($point);
		$member->addContriWeek($exp);
		$member->setContriNum($contriNum + 1);
		$member->setContriTime($now);
		$member->update();

		//插入贡献记录
		self::initRecord($uid, $guildId, $type, $exp);
		
		//每日任务和成就
		EnAchieve::updateGuildContribution($uid, $member->getContriTotal());
		EnActive::addTask(ActiveDef::GUILDCONTRI);
		if ($type == 4) 
		{
			EnNewServerActivity::updateSpecialDonation($uid, 1);
		}
		if ($type == 5) 
		{
			EnNewServerActivity::updateUltimateDonation($uid, 1);
		}
		
		Logger::trace('GuildLogic::contribute End.');
		
		return 'ok';
	}
	
	public static function upgradeGuild($uid, $type)
	{
		Logger::trace('GuildLogic::upgradeGuild Start.');
	
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::LEVEL_UP]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::LEVEL_UP);
		}
	
		try
		{
			//获得军团信息
			$guild = GuildObj::getInstance($guildId, array(GuildDef::CURR_EXP, GuildDef::SHARE_CD, GuildDef::VA_INFO));
		    if ($guild->upgradeBuild($type) == false)
		    {
		    	$guild->unlockArrField();
		        Logger::warning('guild:%d build type:%d cant upgrade', $guildId, $type);
		        return 'noexp';
		    }
		    $guild->update();
		}
		catch(Exception $e)
		{
		    $guild->unlockArrField();
		    throw $e;
		}
		
		//记录升级信息
		$conf = btstore_get()->GUILD_BARN;
		$level = $guild->getBuildLevel($type);
		self::initRecord($uid, $guildId, GuildRecordType::UPGRADE_GUILD, $type, array($level-1, $level));
		//更新成就
		$memberList = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID));
		$arrMember = Util::arrayExtract($memberList, GuildDef::USER_ID);
		if (GuildDef::GUILD == $type)
		{
		    foreach ($arrMember as $uid)
		    {
		        EnAchieve::updateGuildLevel($uid, $level);
		    }
		}
		//开启粮仓，通知所有军团成员
		if (isset($conf[GuildDef::GUILD_BARN_OPEN][$type])
		        && $conf[GuildDef::GUILD_BARN_OPEN][$type] == $level
		        && $guild->isGuildBarnOpen())
		{
			$type = GuildDef::BARN;
			$level = GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::LEVEL];
		}
		//粮仓升级开启新的粮田，通知所有军团成员
		$fieldNum = $conf[GuildDef::GUILD_FIELD_NUM]->toArray();
		if (GuildDef::BARN == $type && in_array($level, $fieldNum))
		{
			RPCContext::getInstance()->sendMsg($arrMember, PushInterfaceDef::GUILD_BARN_LEVEL, array($level));
		}
	
		Logger::trace('GuildLogic::upgradeGuild End.');
	
		return 'ok';
	}
	
	public static function reward($uid, $buyType)
	{
		Logger::trace('GuildLogic::reward Start.');
		
		//关公殿类型
		$type = GuildDef::TEMPLE;
		$ret = array('ret' => 'ok', 'level' => GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::LEVEL]);
		
		//检查领奖时间到达没有
		$confname = GuildDef::$TYPE_TO_CONFNAME[$type];
		$conf = btstore_get()->$confname;
		$now = strftime("%H%M%S", Util::getTime());
		if ($now < $conf[GuildDef::GUILD_REWARD_START] 
		|| $now > $conf[GuildDef::GUILD_REWARD_END])
		{
			throw new FakeException('now:%s is not in reward time!', $now);
		}
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户的领奖时间
		$user = EnUser::getUserObj($uid);
		if ($buyType == 0) 
		{
			//免费奖励是否领取了
			$rewardTime = $member->getRewardTime();
			if (Util::isSameDay($rewardTime))
			{
				throw new FakeException('user:%d has no free reward today!', $uid);
			}
			
			//检查用户贡献度是否足够
			if ($member->subContriPoint($conf[GuildDef::GUILD_REWARD_COST]) == false)
			{
				throw new FakeException('user:%d contribute point is not enough to reward!', $uid);
			}
			
			try 
			{
				$guild = GuildObj::getInstance($guildId, array(GuildDef::REWARD_NUM));
			    $level = $guild->getBuildLevel($type);
			    $ret['level'] = $level;
			    
			    //检查军团的每日领奖次数是否达到上限
			    $rewardNum = $guild->getRewardNum();
			    $guildLevel = $guild->getGuildLevel();
			    $memberLimit = self::getMemberLimit($guild->getInfo());
			    if ($rewardNum >= $memberLimit)
			    {
			        $guild->unlockArrField();
			        Logger::warning('guild:%d has reached reward limit num today!', $guildId);
			        $ret['ret'] = 'exceed';
			        return $ret;
			    }
			    
			    //更新军团的领奖次数和时间
			    $guild->setRewardNum($rewardNum + 1);
			    $guild->setRewardTime(Util::getTime());
			    $guild->update();
			}
			catch(Exception $e)
			{
			    $guild->unlockArrField();
			    throw $e;
			}
			//更新成员的信息，领奖时间
			$member->setRewardTime(Util::getTime());
		}
		else 
		{
			//是否有VIP购买次数
			list($limit, $base, $incre) = btstore_get()->VIP[$user->getVip()]['buyGuildReward'];
			$rewardBuyNum = $member->getRewardBuyNum();
			if ($rewardBuyNum >= $limit) 
			{
				throw new FakeException('user:%d has no gold reward today!', $uid);
			}
			$cost = $base + $incre * $rewardBuyNum;
			if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_GUILD_BUY_REWARD_GUAN) == false) 
			{
				throw new FakeException('no enough gold');
			}
			
			$guild = GuildObj::getInstance($guildId);
			$level = $guild->getBuildLevel($type);
			$ret['level'] = $level;
			
			//更新成员的信息，领奖时间
			$member->setRewardBuyNum($rewardBuyNum + 1);
			$member->setRewardBuyTime(Util::getTime());
		}
		$member->update();
		
		//给用户发奖
		$reward = array();
		//发体力
		$execution = floor($conf[GuildDef::GUILD_EXECUTION_BASE] + $conf[GuildDef::GUILD_EXECUTION_INCRE] * $level / 100);
		$user->addExecution($execution);
		$reward['execution'] = $execution;
		//发耐力
		$stamina = floor($conf[GuildDef::GUILD_STAMINA_BASE] + $conf[GuildDef::GUILD_STAMINA_INCRE] * $level / 100);
		$user->addStamina($stamina);
		$reward['stamina'] = $stamina;
		//发声望
		$prestige = floor($conf[GuildDef::GUILD_PRESTIGE_BASE] + $conf[GuildDef::GUILD_PRESTIGE_INCRE] * $level / 100);
		$user->addPrestige($prestige);
		$reward['prestige'] = $prestige;
		//发将魂
		$soul = floor($conf[GuildDef::GUILD_SOUL_BASE] + $conf[GuildDef::GUILD_SOUL_INCRE] * $level / 100);
		$user->addSoul($soul);
		$reward['soul'] = $soul;
		//发银币
		$silver = floor($conf[GuildDef::GUILD_SILVER_BASE] + $conf[GuildDef::GUILD_SILVER_INCRE] * $level / 100);
		$user->addSilver($silver);
		$reward['silver'] = $silver;
		//发金币
		$gold = floor($conf[GuildDef::GUILD_GOLD_BASE] + $conf[GuildDef::GUILD_GOLD_INCRE] * $level / 100);
		$user->addGold($gold, StatisticsDef::ST_FUNCKEY_GUILD_REWARD_GUAN);
		$reward['gold'] = $gold;
		$user->update();
		
		//记录参拜信息
		if ($buyType == 0) 
		{
			self::initRecord($uid, $guildId, GuildRecordType::GUAN_REWARD, 0, $reward);
			RPCContext::getInstance()->sendFilterMessage('guild', $guildId, PushInterfaceDef::REFRESH_GUILD, self::getGuildInfo($uid));
		}
		
		//加入每日任务
		EnActive::addTask(ActiveDef::GUILDREWARD);
		
		Logger::trace('GuildLogic::reward End.');
		
		return $ret;
	}
	
	public static function leaveMessage($uid, $msg)
	{
		Logger::trace('GuildLogic::leaveMessage Start.');
		
		//检查用户是否已经加入军团
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查留言的长度
		self::checkLength($msg, GuildConf::MAX_MSG_LENGTH, 'msg');
		
		//检查用户当天留言次数
		$date = intval(strftime("%Y%m%d", Util::getTime()));
		$today = strtotime($date . "000000");
		$arrCond = array(
				array(GuildDef::USER_ID, '=', $uid),
				array(GuildDef::RECORD_TYPE, '=', GuildRecordType::LEAVE_MSG),
				array(GuildDef::RECORD_TIME, '>=', $today),
		);
		$arrRet = GuildDao::getRecord($arrCond, GuildDef::$GUILD_RECORD_FIELDS);
		if (count($arrRet) >= GuildConf::MAX_MSG_NUM) 
		{
			throw new FakeException('user:%d leave msgs reach max!', $uid);
		}
		
		//过滤宣言中的敏感词汇
		$msg = TrieFilter::mb_replace($msg);
		
		//插入留言
		self::initRecord($uid, $guildId, GuildRecordType::LEAVE_MSG, 0, array($msg));
		
		Logger::trace('GuildLogic::leaveMessage End.');
		
		return $msg;
	}
	
	public static function lottery($uid)
	{
		Logger::trace('GuildLogic::lottery Start.');
	
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in guild!', $uid);
		}
	
		//检查用户的摇奖次数
		$conf = btstore_get()->GUILD_LOTTERY;
		$lotteryNum = $member->getLotteryNum();
		if ($conf[GuildDef::GUILD_LOTTERY_NUM] <= $lotteryNum)
		{
			throw new FakeException('user:%d has no lottery num:%d!', $uid, $lotteryNum);
		}
	
		//检查用户的功勋值
		if ($member->subMeritNum($conf[GuildDef::GUILD_LOTTERY_COST]) == false)
		{
			throw new FakeException('user:%d has not enough merit num!', $uid);
		}
	
		//更新成员的信息，摇奖次数和时间
		$member->setLotteryNum($lotteryNum + 1);
		$member->setLotteryTime(Util::getTime());
		$member->update();
	
		//掉落物品
		$dropId = $conf[GuildDef::GUILD_LOTTERY_DROP];
		$dropInfo = EnUser::drop($uid, array($dropId), false, false, true);
		EnUser::getUserObj($uid)->update();
		BagManager::getInstance()->getBag($uid)->update();
	
		Logger::trace('GuildLogic::lottery End.');
	
		return $dropInfo;
	}
	
	public static function harvest($uid, $fieldId, $num)
	{
		Logger::trace('GuildLogic::harvest Start.');
	
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}

		try 
		{
			//检查粮仓是否开启
			$guild = GuildObj::getInstance($guildId, array(GuildDef::GRAIN_NUM, GuildDef::VA_INFO));
		    $conf = btstore_get()->GUILD_BARN;
		    if ($guild->isGuildBarnOpen() == FALSE)
		    {
		        throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
		                $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		    }
		    
		    //检查粮田是否可采集
		    $barnLevel = $guild->getBuildLevel(GuildDef::BARN);
		    if (!isset($conf[GuildDef::GUILD_FIELD_NUM][$fieldId])
		            || $conf[GuildDef::GUILD_FIELD_NUM][$fieldId] > $barnLevel)
		    {
		        throw new FakeException('user:%d can not harvest field:%d!', $uid, $fieldId);
		    }
		    
		    //检查粮田采集次数是否足够
		    if ($member->subFieldNum($fieldId, $num) == false)
		    {
		        throw new FakeException('user:%d have no num to harvest field:%d!', $uid, $fieldId);
		    }
		    
		    //检查用户花费是否足够
		    $user = EnUser::getUserObj($uid);
		    $subSilver = $conf[GuildDef::GUILD_HARVEST_SILVER] * $num;
		    if ($user->subSilver($subSilver) == false)
		    {
		        throw new FakeException('user:%d has no enough silver:%d!', $uid, $subSilver);
		    }
		    $user->update();
		    
		    //给军团加粮草, 给粮田加经验并升级；如果粮田等级超过上限后，不加经验。
		    $addGrain = 0;
		    $addExtra = array();
		    $addExp = $conf[GuildDef::GUILD_HARVEST_EXP];
		    $preFieldLevel = $guild->getFieldLevel($fieldId);
		    for ($i = 0; $i < $num; $i++)
		    {
		    	$fieldLevel = $guild->getFieldLevel($fieldId);
		    	list($memberMerit, $guildGrain) = $conf[GuildDef::GUILD_HARVEST_GRAIN][$fieldId][$fieldLevel];
		    	$guild->addFieldExp($fieldId, $addExp);
		    	$guild->addGrainNum($guildGrain);
		    	$member->addMeritNum($memberMerit);
		    	$addGrain += $guildGrain;
		    	$addExtra[] = $conf[GuildDef::GUILD_HARVEST_EXTRA];
		    }
		    //掉落表
		    $addExtra = EnUser::drop($uid, $addExtra, false, false);
		    $guild->update();
		}
		catch(Exception $e)
		{
		    $guild->unlockArrField();
		    throw $e;
		}
		
		//更新背包
		BagManager::getInstance()->getBag($uid)->update();
		
		//给用户加功勋 
		$member->update();
		
		//记录采集信息：粮田，经验，等级，物品
		$curFieldLevel = $guild->getFieldLevel($fieldId);
		self::initRecord($uid, $guildId, GuildRecordType::HARVEST_FIELD, $fieldId, array($addExp * $num, $preFieldLevel, $curFieldLevel, $num, $addGrain, $addExtra));
		
		//给所有军团成员推送粮田信息
		$fieldInfo = array($fieldId => $guild->getFieldInfo($fieldId));
		RPCContext::getInstance()->sendFilterMessage('guild', $guildId, PushInterfaceDef::GUILD_FIELD_HARVEST, $fieldInfo);
		
		//完成每日任务
		EnActive::addTask(ActiveDef::HARVEST, $num);
		
		Logger::trace('GuildLogic::harvest End.');
	
		return array($guild->getGrainNum(), $member->getMeritNum(), $addGrain, $addExtra);
	}
	
	public static function quickHarvest($uid)
	{
		Logger::trace('GuildLogic::quickHarvest Start.');
	
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
	
		try
		{
			//检查粮仓是否开启
			$guild = GuildObj::getInstance($guildId, array(GuildDef::GRAIN_NUM, GuildDef::VA_INFO));
			$conf = btstore_get()->GUILD_BARN;
			if ($guild->isGuildBarnOpen() == FALSE)
			{
				throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
						$guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
			}
	
			//检查粮田是否可采集,用户花费是否足够
			$user = EnUser::getUserObj($uid);
			$sumSilver = $user->getSilver();
			$subSilver = $conf[GuildDef::GUILD_HARVEST_SILVER];
			$addExp = $conf[GuildDef::GUILD_HARVEST_EXP];
			$canNum = empty($subSilver) ? -1 : intval($sumSilver / $subSilver);
			$barnLevel = $guild->getBuildLevel(GuildDef::BARN);
			
			$first = true;
			$sum = 0;
			$sumGrain = 0;
			$arrRecord = array();
			$sumExtra = array();
			foreach (GuildConf::$MEMBER_FIELD_DEFAULT as $fieldId => $fieldInfo)
			{
				if (!isset($conf[GuildDef::GUILD_FIELD_NUM][$fieldId])
				|| $conf[GuildDef::GUILD_FIELD_NUM][$fieldId] > $barnLevel)
				{
					continue;
				}
				$fieldNum = $member->getFieldNum($fieldId);
				if (empty($canNum) || empty($fieldNum)) 
				{
					continue;
				}
				$num = $canNum < 0 ? $fieldNum : min($canNum, $fieldNum);
				$sum += $num;
				$canNum -= $num;
				$user->subSilver($subSilver * $num);
				$member->subFieldNum($fieldId, $num);
				//给军团加粮草, 给粮田加经验并升级；如果粮田等级超过上限后，不加经验。
				$preFieldLevel = $guild->getFieldLevel($fieldId);
				$addGrain = 0;
				$addExtra = array();
				for ($i = 0; $i < $num; $i++)
				{
					$fieldLevel = $guild->getFieldLevel($fieldId);
					list($memberMerit, $guildGrain) = $conf[GuildDef::GUILD_HARVEST_GRAIN][$fieldId][$fieldLevel];
					$guild->addFieldExp($fieldId, $addExp);
					$guild->addGrainNum($guildGrain);
					$member->addMeritNum($memberMerit);
					$addGrain += $guildGrain;
					$addExtra[] = $conf[GuildDef::GUILD_HARVEST_EXTRA];
				}
				$sumGrain += $addGrain;
				$curFieldLevel = $guild->getFieldLevel($fieldId);
				$addExtra = EnUser::drop($uid, $addExtra, false, !$first);
				$arrRecord[$fieldId] = array($addExp * $num, $preFieldLevel, $curFieldLevel, $num, $addGrain, $addExtra);
				$sumExtra = Util::arrayAdd3V(array($sumExtra, $addExtra));
				$first = false;
			}
			$user->update();
			$guild->update();
		}
		catch(Exception $e)
		{
			$guild->unlockArrField();
			throw $e;
		}
		
		//更新背包
		BagManager::getInstance()->getBag($uid)->update();
	
		//给用户加功勋
		$member->update();
		//记录采集信息：粮田，经验，等级，物品
		$arrInfo = array();
		$arrFieldInfo = array();
		foreach ($arrRecord as $fieldId => $record)
		{
			self::initRecord($uid, $guildId, GuildRecordType::HARVEST_FIELD, $fieldId, $record);
			$arrInfo[$fieldId] = array($record[0], $record[2]);
			$arrFieldInfo[$fieldId] = $guild->getFieldInfo($fieldId);
		}
		
		//给所有军团成员推送新的粮田信息
		RPCContext::getInstance()->sendFilterMessage('guild', $guildId, PushInterfaceDef::GUILD_FIELD_HARVEST, $arrFieldInfo);
		
		//完成每日任务
		EnActive::addTask(ActiveDef::HARVEST, $sum);
		
		Logger::trace('GuildLogic::quickHarvest End.');
		
		return array(
				$guild->getGrainNum(), 
				$member->getMeritNum(), 
				$sumGrain,
				$sum,
				$arrInfo,
				$sumExtra
		);
	}
	
	public static function refreshOwn($uid)
	{
		Logger::trace('GuildLogic::refreshOwn Start.');
	
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
	
		//检查粮仓是否开启
		$conf = btstore_get()->GUILD_BARN;
		$guild = GuildObj::getInstance($guildId);
		if ($guild->isGuildBarnOpen() == FALSE)
		{
		    throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
		            $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		}
		
		//检查用户是否能够刷新，只要有一个粮田的采集次数到达上限就不能刷新
		$barnLevel = $guild->getBuildLevel(GuildDef::BARN);
		if($member->canRfrOwn($barnLevel) == FALSE)
		{
		    throw new FakeException('one field num reach max, can not refresh.');
		}
		
		//检查用户刷新次数是否达到上限
		$refreshNum = $member->getRefreshNum();
		if ($conf[GuildDef::GUILD_REFRESH_OWN] <= $refreshNum)
		{
			throw new FakeException('user:%d can not refresh field!', $uid);
		}
		
		//检查用户金币是否足够
		$user = EnUser::getUserObj($uid);
		$fieldCount = $member->getFieldCount($barnLevel);
		$cost = $conf[GuildDef::GUILD_REFRESH_BASE][$fieldCount] + $conf[GuildDef::GUILD_REFRESH_ADD][$fieldCount] * $refreshNum;
		if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_GUILD_REFRESH_OWN) == false)
		{
			throw new FakeException('user:%d has no enough gold:%d!', $uid, $cost);
		}
		$user->update();
	
		//更新用户的粮田采集次数，刷新次数
		$member->refreshOwn($barnLevel, 1);
		$member->setRefreshNum($refreshNum + 1);
		$member->update();
	
		Logger::trace('GuildLogic::refreshOwn End.');
	
		return 'ok';
	}
	
	public static function refreshAll($uid, $type)
	{
		Logger::trace('GuildLogic::refreshAll Start.');
	
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
	
		$conf = btstore_get()->GUILD_BARN;
		try 
		{
			//只锁rfrnum_rfrtime,保证同时更新refresh_num和refresh_num_byexp
			$guild = GuildObj::getInstance($guildId, array(GuildDef::CURR_EXP, GuildDef::RFRNUM_RFRTIME));
			//检查粮仓是否开启
		    if ($guild->isGuildBarnOpen() == FALSE)
		    {
		        throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
		                $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		    }
		    //分两种类型刷新,金币和军团建设度
		    $user = EnUser::getUserObj($uid);
		    if(RefreshAllType::GOLD == $type)
		    {
		        list($can, $cost) = btstore_get()->VIP[$user->getVip()]['refreshFieldCost'];
		        //检查用户是否能全体刷新
		        if (empty($can))
		        {
		            throw new FakeException('user:%d can not refresh all field!', $uid);
		        }
		        //检查用户金币是否足够
		        if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_GUILD_REFRESH_ALL) == false)
		        {
		        	throw new FakeException('user:%d has no enough gold:%d!', $uid, $cost);
		        }
		        //检查军团刷新次数是否达到上限
		        $refreshNum = $guild->getRefreshNum();
		        list($refreshNumLimit, $addFieldNum) = $conf[GuildDef::GUILD_REFRESH_ALL_BYGOLD];
		        if($refreshNum >= $refreshNumLimit)
		        {
		        	throw new FakeException('refresh num:%d limit:%d', $refreshNum, $refreshNumLimit);
		        }
		        $guild->setRefreshNum($refreshNum + 1);
		    }
		    if(RefreshAllType::GUILDEXP == $type)
		    {
		    	//检查用户权限
		    	$memberType = $member->getMemberType();
		    	if(!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::REFRESH_BYEXP]))
		    	{
		    		
		    		throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::REFRESH_BYEXP);
		    	}
		        //检查军团刷新次数是否达到上限
		    	$refreshNum = $guild->getRefreshNumByGuildExp();
		    	list($refreshNumLimit, $addFieldNum) = $conf[GuildDef::GUILD_REFRESH_ALL_BYGUILDEXP];
		        if($refreshNum >= $refreshNumLimit)
		        {
		        	throw new FakeException('refresh num:%d limit:%d', $refreshNum, $refreshNumLimit);
		        }
		        //检查军团建设度是否足够
		        $fieldCount = $guild->getFieldCount();
		        $needGuildExp = $conf[GuildDef::GUILD_RFRALL_BYEXP_COST][$refreshNum] * $fieldCount;
		        if($guild->subCurrExp($needGuildExp) == false)
		        {
		        	$guild->unlockArrField();
		        	Logger::warning('refresh all need guild exp %d', $needGuildExp);
		        	return 'noexp';
		        }
		        $guild->setRefreshNumByGuildExp($refreshNum + 1);
		    }
		    //改下bak减1
		    $guild->setRefreshNumRfrTime(Util::getTime() + 1);
		    //军团刷新次数更新
		    $guild->update();
		}
		catch(Exception $e)
		{
		    $guild->unlockArrField();
		    throw $e;
		}
		$user->update();
		//刷新用户的粮田采集次数，全体刷新次数
		$barnLevel = $guild->getBuildLevel(GuildDef::BARN);
		$member->refreshOwn($barnLevel, $addFieldNum);
		$member->update();
		
		//增加记录
		if(RefreshAllType::GOLD == $type)
		{
		    self::initRecord($uid, $guildId, GuildRecordType::REFRESH_ALL, 0);
		}
		
		//刷新其他用户的粮田采集次数
		$uname = $user->getUname();
		$memberList = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID));
		$arrUid = Util::arrayExtract($memberList, GuildDef::USER_ID);
		foreach ($arrUid as $value)
		{
			if ($value == $uid) 
			{
				continue;
			}
			RPCContext::getInstance()->executeTask($value, 'guild.refreshFields', array($value, $uname, $addFieldNum, $type), false);
		}
		RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_REFRESH_ALL, array($uname, $member->getFields(), $type));
		Logger::trace('user:%d use refresh all type:%d time:%s', $uid, $type, Util::getTime());
		
		Logger::trace('GuildLogic::refreshAll End.');
	
		return 'ok';
	}
	
	public static function share($uid)
	{
		Logger::trace('GuildLogic::share Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::SHARE]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::SHARE);
		}
		
		if (EnGuildRob::isInRobBattle($guildId))
		{
		    Logger::warning('guild:%d is in the guild rob, can not share.', $guildId);
		    return 'forbidden_guildrob';
		}
				
		$conf = btstore_get()->GUILD_BARN;
		try 
		{
			$guild = GuildObj::getInstance($guildId, array(GuildDef::GRAIN_NUM, GuildDef::SHARE_CD));
		    //检查粮仓是否开启
		    if ($guild->isGuildBarnOpen() == FALSE)
		    {
		        throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
		                $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		    }
		    
		    //检查军团的分粮冷却时间
		    $now = Util::getTime();
		    if ($guild->getShareCd() > $now)
		    {
		    	$guild->unlockArrField();
		    	Logger::warning('guild:%d is in share cd!', $guildId);
		    	return 'sharecd';
		    }
		    
		    //检查军团的粮草是否还有剩余
		    $grainNum = $guild->getGrainNum();
		    if (empty($grainNum))
		    {
		    	throw new FakeException('guild:%d has no grain!', $guildId);
		    }
		    
		    //获得各职位的分粮数
		    $sumCoef = 0;
		    $sumShare = 0;
		    $arrUid = array();
		    $arrShare = array();
		    $arrShareMember = $arrMember = self::getMembersByType($guildId);
		    $shareCoef = $conf[GuildDef::GUILD_SHARE_COEF];
		    $noShare = $now - btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GUILD_JOIN_SHARE];
		    foreach ($arrMember as $key => $value)
		    {
		    	$arrUid = array_merge($arrUid, $value);
		    }
		    $arrCond = array(
		    		array(GuildDef::USER_ID, 'in', $arrUid),
		    		array(GuildDef::GUILD_ID, '=', $guildId),
		    		array(GuildDef::RECORD_TYPE, '=', GuildRecordType::JOIN_GUILD),
		    		array(GuildDef::RECORD_TIME, '>', $noShare),
		    );
		    $arrField = array(GuildDef::USER_ID);
		    $arrRet = GuildDao::getRecord($arrCond, $arrField);
		    $arrNoShareUid = Util::arrayExtract($arrRet, GuildDef::USER_ID);
		    foreach ($arrShareMember as $type => $arrUid)
		    {
		    	foreach ($arrUid as $key => $memberUid)
		    	{
		    		if (in_array($memberUid, $arrNoShareUid)) 
		    		{
		    			unset($arrShareMember[$type][$key]);
		    		}
		    	}
		    }
		    foreach ($shareCoef as $type => $coef)
		    {
		        $sumCoef += $coef * count($arrMember[$type]);
		    }
		    foreach ($shareCoef as $type => $coef)
		    {
		        $arrShare[$type] = min(intval($coef / $sumCoef * $grainNum), GuildConf::MAX_SHARE_NUM);
		        $sumShare += $arrShare[$type] * count($arrShareMember[$type]);
		    }
		    
		    //检查粮草是否够分
		    if ($guild->subGrainNum($sumShare) == false)
		    {
		        throw new FakeException('guild:%d has no enough grain!', $guildId);
		    }
		    
		    //军团先更新
		    $guild->setShareCd($now + $conf[GuildDef::GUILD_SHARE_CD]);
		    $guild->update();
		}
		catch(Exception $e)
		{
		    $guild->unlockArrField();
		    throw $e;
		}
		
		try 
		{
			//分粮哈，给成员发
			foreach ($arrShareMember as $type => $arrUid)
			{
				if (empty($arrShare[$type])) 
				{
					continue;
				}
				$reward = array(
						RewardType::GRAIN => $arrShare[$type],
						RewardDef::EXT_DATA => array('rank' => $type),
				);
				foreach ($arrUid as $memberUid)
				{
					Logger::trace('generate reward for user:%d, reward:%s', $memberUid, $reward);
					EnReward::sendReward($memberUid, RewardSource::GUILD_SHARE_GRAIN, $reward);
					RPCContext::getInstance()->executeTask($memberUid, 'guild.distributeGrain', array($memberUid, $type, $arrShare[$type]), false);
				}
			}
		}
		catch(Exception $e)
		{
			throw $e;
		}
		
		Logger::trace('GuildLogic::share End.');
		
		$memberShare = in_array($uid, $arrNoShareUid) ? 0 : intval($arrShare[$memberType]);
		
		return array($memberShare, $guild->getGrainNum());
	}
	
	public static function buyFightBook($uid)
	{
		Logger::trace('GuildLogic::buyFightBook Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::BUY_FIGHTBOOK])) 
		{
			throw new FakeException('user:%d is not president or viceprisident.can not buyfb', $uid);
		}
		
		//检查粮仓是否开启
		$conf = btstore_get()->GUILD_BARN;
		try
		{
			$guild = GuildObj::getInstance($guildId, array(GuildDef::FIGHT_BOOK, GuildDef::CURR_EXP));
		    if ($guild->isGuildBarnOpen() == FALSE)
		    {
		        throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
		                $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		    }
		    
		    //检查军团战书是否为0
		    $fightBook = $guild->getFightBook();
		    $fightBookLimit = $conf[GuildDef::GUILD_FIGHTBOOK_LIMIT];
		    if ($fightBook >= $fightBookLimit)
		    {
		        throw new FakeException('fight book num:%d is reach limit:%d', $fightBook, $fightBookLimit);
		    }
		    
		    //检查军团贡献度是否足够
		    $guildExp = $guild->getCurrExp();
		    $needExp = $conf[GuildDef::GUILD_CHALLENGE_COST];
		    if($guild->subCurrExp($needExp) == false)
		    {
		        throw new FakeException('need exp:%d guild exp:%d can not buy fightbook', $needExp, $guildExp); 
		    }
		    
		    //更新军团的战书数量
		    $guild->addFightBook(1);
		    $guild->update();
		}
		catch(Exception $e)
		{
		    $guild->unlockArrField();
		    throw $e;
		}
		
		Logger::trace('GuildLogic::buyFightBook End.');
		
		return 'ok';
	}
	
	public static function fightEachOther($uid, $atkedUid)
	{
		Logger::trace('GuildLogic::fightEachOther Start $uid:%d, $atkedUid:%d.', $uid, $atkedUid);
	
		//加锁
		$key = "guild.fightEachOther.$atkedUid";
		$locker = new Locker();
		$locker->lock($key);
		$errcode = 0;
		try
		{
			$member = GuildMemberObj::getInstance($uid);
			$atkedMember = GuildMemberObj::getInstance($atkedUid);
			if ($member->getGuildId() != $atkedMember->getGuildId())
			{
				throw new FakeException('user:%d and atked user:%d is not in the same guild!', $uid, $atkedUid);
			}
			$playWithNumOfU = $member->getPlayWithNum();
			$bePlayWithNumOfU = $member->getBePlayWithNum();
			$playWithNumOfAtkedU = $atkedMember->getPlayWithNum();
			$beplayWithNumOfAtkedU = $atkedMember->getBePlayWithNum();
			$conf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GUILD_FIGHTEACHOTHER_LIMITS]->toArray();
			//检查进攻方的切磋次数限制
			if($playWithNumOfU >= $conf[0])
			{
				$errcode = 1;
				$locker->unlock($key);
				return array('errcode' => $errcode);
			}
			//检查防守方的被切磋次数限制
			if($beplayWithNumOfAtkedU >= $conf[1])
			{
				$errcode = 2;
				$locker->unlock($key);
				return array('errcode' => $errcode);
			}
	
			$user = EnUser::getUserObj($uid);
			$atkedUser = EnUser::getUserObj($atkedUid);
			// 准备用户和被攻击方的战斗信息,战斗
			$battleUser = $user->getBattleFormation();
			$atkedBattleUser = $atkedUser->getBattleFormation();
			$userFF = $user->getFightForce();
			$atkedUserFF = $atkedUser->getFightForce();
			$atkType = EnBattle::setFirstAtk(0, $userFF >= $atkedUserFF);
	
			$atkRet = EnBattle::doHero($battleUser, $atkedBattleUser, $atkType);
			$isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
	
			//切磋完毕，进攻方切磋次数+1， 防守方被切磋次数+1
			$playWithNumOfU++;
			$beplayWithNumOfAtkedU++;
	
			//更新进攻方成员信息
			self::atkUserByOther($uid, $atkedUid, $playWithNumOfU, $bePlayWithNumOfU, $isSuc, $atkRet['server']['brid'], false);
			//更新防守方成员信息
			$arrFieldbeAtked = array($atkedUid, $uid, $playWithNumOfAtkedU, $beplayWithNumOfAtkedU, !$isSuc, $atkRet['server']['brid']);
			RPCContext::getInstance()->executeTask($atkedUid, 'guild.guildDataRefresh', $arrFieldbeAtked, false);
	
		}catch(Exception $e)
		{
			$locker->unlock($key);
			throw $e;
		}
	
		Logger::trace('GuildLogic::fightEachOther End $uid:%d, $atkedUid:%d.', $uid, $atkedUid);
		$ret = array(
				'errcode' =>$errcode,
				'battleRes' => $atkRet,
				'uPlayWithNum' => $playWithNumOfU,
				'atkedUBePlayWithNum' => $beplayWithNumOfAtkedU
		);
		return $ret;
	}
	
	public static function promote($uid, $id, $type)
	{
		Logger::trace('GuildLogic::promote Start. id:%d, $type:%d', $id, $type);
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//只有军团长和副军团长可以提升技能等级上限
		$memberType = $member->getMemberType();
		if ($type == 2 && !in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::PROMOTE_SKILL]))
		{
			throw new FakeException('user:%d is not president or viceprisident.can not promote skill', $uid);
		}
		
		//所有人都不能学习技能类型2
		$conf = btstore_get()->GUILD_SKILL[$id];
		if ($type == 1 && $conf[GuildDef::GUILD_SKILL_TYPE] == 2)
		{
			throw new FakeException('can not learn skill type 2');
		}
		
		//检查军团科技是否开启
		$guild = GuildObj::getInstance($guildId);
		if (!$guild->isGuildTechOpen())
		{
			throw new FakeException('guild tech is not open');
		}
		
		//学习技能
		if ($type == 1) 
		{
			//检查用户的技能等级是否达到军团的技能等级上限
			$memberSkillLevel = $member->getSkillLevel($id);
			$guildSkillLevel = $guild->getSkillLevel($id);
			if ($memberSkillLevel >= $guildSkillLevel) 
			{
				throw new FakeException('learn skill reach limit, memberSkillLevel:%d, guildSkillLevel:%d', $memberSkillLevel, $guildSkillLevel);
			}
			
			//消耗赤卷天书
			$cost = $conf[GuildDef::GUILD_MEMBER_COST][$memberSkillLevel];
			if ($cost[0] != RewardConfType::BOOK) 
			{
				throw new FakeException('learn skill cost reward type:%d is not support', $cost[0]);
			}
			$user = EnUser::getUserObj($uid);
			if (!$user->subBookNum($cost[2])) 
			{
				throw new FakeException('user sub book num:%d failed', $cost[2]);
			}
			
			//提升用户的技能等级
			$member->setSkillLevel($id, $memberSkillLevel + 1);
			
			//更新数据
			$user->update();
			$member->update();
			
			//清空战斗缓存
			$user->modifyBattleData();
		}
		else 
		{
			try
			{
				$guild = GuildObj::getInstance($guildId, array(GuildDef::CURR_EXP, GuildDef::VA_INFO));
			
				//检查军团的技能等级是否到达配置的上限
				$guildSkillLevel = $guild->getSkillLevel($id);
				$skillLevelLimit = count($conf[GuildDef::GUILD_MANAGER_COST]->toArray());
				if ($guildSkillLevel >= $skillLevelLimit) 
				{
					throw new FakeException('promote skill reach limit, guildSkillLevel:%d, skillLevelLimit:%d', $guildSkillLevel, $skillLevelLimit);
				}
			
				//消耗军团建设度
				$cost = $conf[GuildDef::GUILD_MANAGER_COST][$guildSkillLevel];
				if ($cost[0] != RewardConfType::GUILD_EXP)
				{
					throw new FakeException('promote skill cost reward type:%d is not support', $cost[0]);
				}
				if(!$guild->subCurrExp($cost[2]))
				{
					throw new FakeException('guild sub curr exp:%d failed', $cost[2]);
				}
			
				//提升军团的技能等级
				$guild->setSkillLevel($id, $guildSkillLevel + 1);
			
				//更新数据
				$guild->update();
			}
			catch(Exception $e)
			{
				$guild->unlockArrField();
				throw $e;
			}
		}
		
		Logger::trace('GuildLogic::promote End.');
		
		return 'ok';
	}
	
	public static function getGuildApplyList($uid, $offset, $limit)
	{
		Logger::trace('GuildLogic::getGuildApplyList Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查用户权限
		$memberType = $member->getMemberType();
		if (!in_array($memberType, GuildConf::$ARR_PRIV[GuildPrivType::MEMBER_MANAGE]))
		{
			throw new FakeException('user:%d has no privilege to:%d', $uid, GuildPrivType::MEMBER_MANAGE);
		}
		
		$cond = array(GuildDef::GUILD_ID, '=', $guildId);
		$arrField = array(GuildDef::USER_ID, GuildDef::APPLY_TIME);
		$applyList = GuildDao::getApplyList($cond, $arrField, $offset, $limit);
		
		$arrRet = array();
		//判断是否有申请
		if (!empty($applyList))
		{
			$arrUidTime = Util::arrayIndexCol($applyList, GuildDef::USER_ID, GuildDef::APPLY_TIME);
			$arrUid = array_keys($arrUidTime);
			$arrUser = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'utid', 'uname', 'htid', 'dress', 'level', 'vip', 'fight_force'));
			$arrPosition = EnArena::getArrArena($arrUid, array('uid', 'position'));
			foreach ($arrUser as $uid => $user)
			{
				if (!empty($arrPosition[$uid]['position'])) 
				{
					$user['position'] = $arrPosition[$uid]['position'];
				}
				$user[GuildDef::APPLY_TIME] = $arrUidTime[$uid];
				$arrRet[$uid] = $user;
			}		
		}
		
		//申请数量
		$arrCond = array(
				array(GuildDef::GUILD_ID, '=', $guildId), 
				array(GuildDef::STATUS, '=', GuildApplyStatus::OK)
		);
		$count = GuildDao::getApplyCount($arrCond);
			
		Logger::trace('GuildLogic::getGuildApplyList End.');

		return array('count' => $count, 'offset' => $offset, 'data' => $arrRet);
	}
	
	public static function getUserApplyList($uid)
	{
		Logger::trace('GuildLogic::getUserApplyList Start.');
		
		$cond = array(GuildDef::USER_ID, '=', $uid);
		$arrField = array(GuildDef::GUILD_ID);
		$applyList = GuildDao::getApplyList($cond, $arrField, 0, GuildConf::MAX_APPLY_NUM);
		$arrGuildId = Util::arrayExtract($applyList, GuildDef::GUILD_ID);
		
		Logger::trace('GuildLogic::getUserApplyList End.');
		
		return $arrGuildId;
	}
	
	public static function getGuildList($uid, $offset, $limit, $name = "")
	{
		Logger::trace('GuildLogic::getGuildList Start.');
		
		//默认的申请列表
		$appnum = 0;
		$offsetback = $offset;
		$applyGuildList = array();
		
		$arrField = array(
				GuildDef::GUILD_ID,
				GuildDef::GUILD_NAME,
				GuildDef::GUILD_LEVEL,
				GuildDef::FIGHT_FORCE,
				GuildDef::UPGRADE_TIME,
				GuildDef::VA_INFO
		);
		
		//一种是按军团名字获取军团列表，一种是拉取所有的军团列表
		if (!empty($name)) 
		{
			$name = trim($name);
			$arrCond = array(
					array(GuildDef::GUILD_NAME, 'LIKE', "%$name%"),
					array(GuildDef::STATUS, '=', GuildStatus::OK)
			);
			$count = GuildDao::getGuildCount($arrCond);
		}
		else 
		{
			//先拉取用户申请的军团列表
			$arrGuildId = self::getUserApplyList($uid);
			$appnum = count($arrGuildId);
			$shift = 0;
			if (!empty($arrGuildId)) 
			{
				$arrCond = array(array(GuildDef::GUILD_ID, 'IN', $arrGuildId));
				$applyGuildList = GuildDao::getGuildList($arrCond, $arrField, 0, $appnum);
				foreach ($applyGuildList as $key => $guildInfo)
				{
					$guildId = $guildInfo[GuildDef::GUILD_ID];
					$guildLevel = $guildInfo[GuildDef::GUILD_LEVEL];
					$fightForce = $guildInfo[GuildDef::FIGHT_FORCE];
					$upgradeTime = $guildInfo[GuildDef::UPGRADE_TIME];
					$guildInfo['rank'] = self::getRank($guildId, $guildLevel, $fightForce, $upgradeTime);
					$applyGuildList[$key] = $guildInfo;
					if ($guildInfo['rank'] > $limit) 
					{
						$shift++;
					}
				}
			}
			if ($offset >= $limit)
			{
				$appnum = 0;
				$offset -= $shift;
				$applyGuildList = array();
			}
			$arrCond = array(
					array(GuildDef::GUILD_ID, '>', 0),
					array(GuildDef::STATUS, '=', GuildStatus::OK)
			);
			$count = GuildDao::getGuildCount($arrCond);
		}
		unset($arrCond[1]);
		$guildList = GuildDao::getGuildList($arrCond, $arrField, $offset, $limit);
		foreach ($guildList as $key => $guildInfo)
		{
			$guildInfo['rank'] = ++$offset;
			$guildList[$key] = $guildInfo;
		}
		$guildList = array_merge($applyGuildList, $guildList);
		$arrGuildId = Util::arrayExtract($guildList, GuildDef::GUILD_ID);
		Logger::trace('guildList:%s', $guildList);
		
		if (!empty($guildList)) 
		{
			//获得团长信息
			$arrCond = array(
					array(GuildDef::GUILD_ID, 'IN', $arrGuildId),
					array(GuildDef::MEMBER_TYPE, '=', GuildMemberType::PRESIDENT)
			);
			$arrPresidentInfo = GuildDao::getMember($arrCond, array(GuildDef::GUILD_ID, GuildDef::USER_ID));
			$arrGuildUid = Util::arrayIndexCol($arrPresidentInfo, GuildDef::GUILD_ID, GuildDef::USER_ID);
			Logger::trace('arrGuildUid:%s', $arrGuildUid);
			$arrUser = EnUser::getArrUserBasicInfo($arrGuildUid, array('uid', 'utid', 'uname', 'htid', 'dress', 'level', 'fight_force'));
		}
		
		$arrRet = array();
		$arrGuildId = array();
		foreach ($guildList as $key => $guildInfo)
		{
			$guildId = $guildInfo[GuildDef::GUILD_ID];
			$guildLevel = $guildInfo[GuildDef::GUILD_LEVEL];
			if (in_array($guildId, $arrGuildId))
			{
				continue;
			}
			$puid = $arrGuildUid[$guildId];
			$guildInfo[GuildDef::LEADER_UID] = $puid;
			$guildInfo[GuildDef::LEADER_UTID] = $arrUser[$puid]['utid'];
			$guildInfo[GuildDef::LEADER_NAME] = $arrUser[$puid]['uname'];
			$guildInfo[GuildDef::LEADER_HTID] = $arrUser[$puid]['htid'];
			$guildInfo[GuildDef::LEADER_DRESS] = $arrUser[$puid]['dress'];
			$guildInfo[GuildDef::LEADER_LEVEL] = $arrUser[$puid]['level'];
			$guildInfo[GuildDef::LEADER_FORCE] = $arrUser[$puid]['fight_force'];
			$guildInfo[GuildDef::MEMBER_NUM] = self::getMemberNum($guildId);
			$guildInfo[GuildDef::MEMBER_LIMIT] = self::getMemberLimit($guildInfo);
			$guildInfo[GuildDef::SLOGAN] = $guildInfo[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::SLOGAN];
			unset($guildInfo[GuildDef::VA_INFO]);
			$arrRet[] = $guildInfo;
			$arrGuildId[] = $guildId;
			if (count($arrRet) >= $limit)
			{
				break;
			}
		}
		
		Logger::trace('GuildLogic::getGuildList End.');
		
		return array('count' => $count, 'offset' => $offsetback, 'appnum' => $appnum, 'data' => $arrRet);
	}
	
	public static function getGuildRankList($uid)
	{
		Logger::trace('GuildLogic::getGuildRankList Start.');
		
		$arrField = array(
				GuildDef::GUILD_ID,
				GuildDef::GUILD_NAME,
				GuildDef::GUILD_LEVEL,
				GuildDef::FIGHT_FORCE,
		);
		$guildList = GuildDao::getGuildRankList($arrField, 0, GuildConf::GUILD_RANK_LIST);
		if (!empty($guildList))
		{
			$arrGuildId = Util::arrayExtract($guildList, GuildDef::GUILD_ID);
			$arrCond = array(
					array(GuildDef::GUILD_ID, 'IN', $arrGuildId),
					array(GuildDef::MEMBER_TYPE, '=', GuildMemberType::PRESIDENT)
			);
			$arrPresidentInfo = GuildDao::getMember($arrCond, array(GuildDef::GUILD_ID, GuildDef::USER_ID));
			$arrGuildUid = Util::arrayIndexCol($arrPresidentInfo, GuildDef::GUILD_ID, GuildDef::USER_ID);
			Logger::trace('arrGuildUid:%s', $arrGuildUid);
			$arrUser = EnUser::getArrUser($arrGuildUid, array('uid', 'utid', 'uname'));
		}
		foreach ($guildList as $key => $guildInfo)
		{
			$guildId = $guildInfo[GuildDef::GUILD_ID];
			$puid = $arrGuildUid[$guildId];
			$guildInfo[GuildDef::LEADER_UTID] = $arrUser[$puid]['utid'];
			$guildInfo[GuildDef::LEADER_NAME] = $arrUser[$puid]['uname'];
			$guildInfo['rank'] = $key + 1;
			$guildList[$key] = $guildInfo;
		}
		Logger::trace('guild rank list:%s', $guildList);
		
		Logger::trace('GuildLogic::getGuildRankList End.');
		
		return $guildList;
	}

	public static function getMemberList($uid, $offset, $limit)
	{
		Logger::trace('GuildLogic::getMemberList Start.');
		
		//检查用户是否已经加入军团
		$guildId = self::getGuildId($uid);
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//军团成员
		$arrField = array(GuildDef::USER_ID, GuildDef::CONTRI_TIME, GuildDef::CONTRI_POINT, GuildDef::CONTRI_TOTAL, GuildDef::MEMBER_TYPE, GuildDef::PLAYWITH_NUM,
                        GuildDef::BE_PLAYWITH_NUM, GuildDef::PLAYWITH_TIME);
		$memberList = GuildDao::getMemberList($guildId, $arrField, $offset, $limit);
		$memberList = Util::arrayIndex($memberList, GuildDef::USER_ID);
		//修数据的请求理论上最多调用一次
		$memberList = self::fixTotalContri($memberList);
		$arrUid = array_keys($memberList);
		$arrUser = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'utid', 'uname', 'htid', 'dress', 'level', 'vip', 'status', 'fight_force', 'last_logoff_time'));
		$arrPosition = EnArena::getArrArena($arrUid, array('uid', 'position'));
		$arrTypeTime = self::getArrTypeTime($arrUid);
		$arrRet = array();
		foreach ( $arrUser as $uid => $user )
		{
			if (!empty($arrPosition[$uid]['position'])) 
			{
				$user['position'] = $arrPosition[$uid]['position'];
			}
			//默认贡献时间是一周前
			$user[GuildDef::CONTRI_TIME] = Util::getTime() - 604800 - 3600;
			if (!empty($arrTypeTime[$uid])) 
			{
				$user['contri_type'] = $arrTypeTime[$uid][GuildDef::RECORD_TYPE];
				$user[GuildDef::CONTRI_TIME] = $arrTypeTime[$uid][GuildDef::RECORD_TIME];
			}
			$user[GuildDef::CONTRI_POINT] = $memberList[$uid][GuildDef::CONTRI_POINT];
			$user[GuildDef::CONTRI_TOTAL] = $memberList[$uid][GuildDef::CONTRI_TOTAL];
			$user[GuildDef::MEMBER_TYPE] = $memberList[$uid][GuildDef::MEMBER_TYPE];
            $user[GuildDef::PLAYWITH_NUM] = 0; 
            $user[GuildDef::BE_PLAYWITH_NUM] = 0; 
            if(!empty($memberList[$uid][GuildDef::PLAYWITH_TIME]) && Util::isSameDay($memberList[$uid][GuildDef::PLAYWITH_TIME]))
            {
                $user[GuildDef::PLAYWITH_NUM] = $memberList[$uid][GuildDef::PLAYWITH_NUM];
                $user[GuildDef::BE_PLAYWITH_NUM] = $memberList[$uid][GuildDef::BE_PLAYWITH_NUM];
            }

			$arrRet[$uid] = $user;
		}	
		
		$arrCond = array(array(GuildDef::GUILD_ID, '=', $guildId));
		$count = GuildDao::getMemberCount($arrCond);
		
		Logger::trace('GuildLogic::getMemberList End.');
		
		return array('count' => $count, 'offset' => $offset, 'data' => $arrRet);
	}
	
	public static function getRecordList($uid, $arrType, $num)
	{
		Logger::trace('GuildLogic::getRecordList Start.');
		
		//检查用户是否已经加入军团
		$guildId = self::getGuildId($uid);
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		$time = Util::getTime() - SECONDS_OF_DAY * 30;
		$recordList = GuildDao::getRecordList($guildId, $arrType, 0, $num, $time);
		$arrUid = Util::arrayExtract($recordList, GuildDef::USER_ID);
		$arrUid = array_unique($arrUid);
		$arrUser = EnUser::getArrUser($arrUid, array('uid', 'utid', 'uname'));
		
		foreach ($recordList as $key => $recordInfo)
		{
			$uid = $recordInfo[GuildDef::USER_ID];
			$recordInfo = array_merge($arrUser[$uid], $recordInfo);
			$recordList[$key] = $recordInfo;
		}
		
		Logger::trace('GuildLogic::getRecordList End.');
		
		return $recordList;
	}
	
	public static function getMessageList($uid, $offset, $limit)
	{
		Logger::trace('GuildLogic::getMessageList Start.');
		
		//检查用户是否已经加入军团
		$guildId = self::getGuildId($uid);
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//获取用户当天留言次数
		$date = intval(strftime("%Y%m%d", Util::getTime()));
		$today = strtotime($date . "000000");
		$arrCond = array(
				array(GuildDef::USER_ID, '=', $uid),
				array(GuildDef::RECORD_TYPE, '=', GuildRecordType::LEAVE_MSG),
				array(GuildDef::RECORD_TIME, '>=', $today),
		);
		$arrRet = GuildDao::getRecord($arrCond, GuildDef::$GUILD_RECORD_FIELDS);
		
		$time = Util::getTime() - GuildConf::MAX_KEEPMSG_TIME;
		$messageList = GuildDao::getRecordList($guildId, array(GuildRecordType::LEAVE_MSG), $offset, $limit, $time);
		$arrUid = Util::arrayExtract($messageList, GuildDef::USER_ID);
		$arrUid = array_unique($arrUid);
		$arrUser = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'utid', 'uname', 'htid', 'dress', 'level'));
		$arrMember = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID, GuildDef::MEMBER_TYPE), 0, CData::MAX_FETCH_SIZE);
		$arrMember = Util::arrayIndex($arrMember, GuildDef::USER_ID);
		
		foreach ($messageList as $key => $recordInfo)
		{
			$uid = $recordInfo[GuildDef::USER_ID];
			$user = $arrUser[$uid];
			$user['type'] = isset($arrMember[$uid]) ? $arrMember[$uid][GuildDef::MEMBER_TYPE] : 0;
			$user['time'] = $recordInfo[GuildDef::RECORD_TIME];
			$user['message'] = $recordInfo[GuildDef::VA_INFO][0];
			$messageList[$key] = $user;
		}
		
		Logger::trace('GuildLogic::getMessageList End.');
		
		return array(
				'list' => $messageList,
				'num' => GuildConf::MAX_MSG_NUM - count($arrRet),
		);
	}
	
	public static function getDynamicList($uid, $num)
	{
		Logger::trace('GuildLogic::getDynamicList Start.');
		
		$arrType = array(
				GuildRecordType::JOIN_GUILD,
				GuildRecordType::QUIT_GUILD,
				GuildRecordType::KICK_MEMBER,
				GuildRecordType::IMPEACH_P,
				GuildRecordType::SET_VP,
				GuildRecordType::UPGRADE_GUILD,
				GuildRecordType::TRANS_P,
				GuildRecordType::GUAN_REWARD,
		);
		$contriType = range(1, GuildRecordType::CONTRI_EXP);
		foreach ($contriType as $type)
		{
			$arrType[] = $type;
		}
		
		//检查用户是否已经加入军团
		$guildId = self::getGuildId($uid);
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		$time = Util::getTime() - SECONDS_OF_DAY * 30;
		$recordList = GuildDao::getRecordList($guildId, $arrType, 0, $num, $time);
		$arrUid = Util::arrayExtract($recordList, GuildDef::USER_ID);
		$arrUid = array_unique($arrUid);
		$arrUser = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'utid', 'uname', 'htid', 'dress', 'level'));
		
		$arrUid = array();
		$dynamicList = array();
		$conf = btstore_get()->GUILD;
		$contriArr = $conf[GuildDef::GUILD_CONTRI_ARR];
		foreach ($recordList as $key => $recordInfo)
		{
			$recordUid = $recordInfo[GuildDef::USER_ID];
			$recordType = $recordInfo[GuildDef::RECORD_TYPE];
			$recordTime = $recordInfo[GuildDef::RECORD_TIME];
			$recordData = $recordInfo[GuildDef::RECORD_DATA];
			$recordVaInfo = $recordInfo[GuildDef::VA_INFO];
			$dynamicList[$key]['user'] = $arrUser[$recordUid];
			//这个地方是强制让php产生一块新内存，否则前端amf解码会出问题
			$dynamicList[$key]['user']['dress']['useless'] = 0;
			unset($dynamicList[$key]['user']['dress']['useless']);
			$dynamicList[$key]['info'] = array(
					'type' => $recordType,
					'time' => $recordTime,
			);
			switch ($recordType)
			{
				case GuildRecordType::JOIN_GUILD:
				case GuildRecordType::QUIT_GUILD:	
					break;
				case GuildRecordType::KICK_MEMBER:
				case GuildRecordType::IMPEACH_P:
				case GuildRecordType::SET_VP:
				case GuildRecordType::TRANS_P:
					$arrUid[] = $recordData;
					$dynamicList[$key]['info']['uname'] = $recordData;
					break;
				case GuildRecordType::UPGRADE_GUILD:
					$dynamicList[$key]['info']['upgrade'] = array(
							'type' => $recordData,
							'oldLevel' => $recordVaInfo[0],
							'newLevel' => $recordVaInfo[1],
					);
					break;
				case GuildRecordType::GUAN_REWARD:
					$dynamicList[$key]['info']['reward'] = $recordVaInfo;
					break;
				default://默认是贡献类型
					$dynamicList[$key]['info']['type'] = GuildRecordType::ALL_CONTRI;
					$dynamicList[$key]['info']['contribute'] = array(
							'silver' => $contriArr[$recordType]['silver'],
							'gold' => $contriArr[$recordType]['gold'],
							'exp' => $contriArr[$recordType]['exp'],
							'point' => $contriArr[$recordType]['point']
					);
					break;
			}
		}
		
		$arrUid = array_unique($arrUid);
		$arrUser = EnUser::getArrUser($arrUid, array('uid', 'uname'));
		$arrUser = Util::arrayIndexCol($arrUser, 'uid', 'uname');
		foreach ($dynamicList as $key => $dynamicInfo)
		{
			if (isset($dynamicInfo['info']['uname'])) 
			{
				$recordData = $dynamicInfo['info']['uname'];
				$dynamicList[$key]['info']['uname'] = $arrUser[$recordData];
			}
		}
		
		Logger::trace('GuildLogic::getDynamicList End.');
		
		return $dynamicList;
	}
	
	public static function getEnemyList($uid, $offset, $limit)
	{
		Logger::trace('GuildLogic::getEnemyList Start.');
		
		//检查用户是否已经加入军团
		$guildId = self::getGuildId($uid);
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查粮仓是否开启
		$conf = btstore_get()->GUILD_BARN;
		$guild = GuildObj::getInstance($guildId);
		if ($guild->isGuildBarnOpen() == FALSE)
		{
		    throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
		            $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		}
		
		//获取军团被抢粮的记录
		$time = Util::getTime() - SECONDS_OF_DAY * 30;
		$recordList = GuildDao::getRecordList($guildId, array(GuildRecordType::ROB_GRAIN), $offset, $limit, $time);
		$arrGuildId = Util::arrayExtract($recordList, GuildDef::RECORD_DATA);
		$arrField = array(GuildDef::GUILD_ID, GuildDef::GUILD_NAME, GuildDef::GRAIN_NUM, GuildDef::VA_INFO);
		$arrGuildInfo = GuildDao::getArrGuild($arrGuildId, $arrField);
		
		//遍历补全
		foreach ($recordList as $key => $recordInfo)
		{
			if (!isset($arrGuildInfo[$recordInfo[GuildDef::RECORD_DATA]])) 
			{
				Logger::warning('guild:%d is not exist', $recordInfo[GuildDef::RECORD_DATA]);
				continue;
			}
			$guildInfo = $arrGuildInfo[$recordInfo[GuildDef::RECORD_DATA]];
			$guildInfo['rob_grain'] = $recordInfo[GuildDef::VA_INFO][0];
			$guildInfo['rob_time'] = $recordInfo[GuildDef::RECORD_TIME];
			$level = $guildInfo[GuildDef::VA_INFO][GuildDef::BARN][GuildDef::LEVEL];
			$grainLimit = $conf[GuildDef::GUILD_GRAIN_CAPACITY][$level];
			$guildInfo['rob_free'] = EnGuildRob::canRobGrain($guildInfo[GuildDef::GRAIN_NUM], $grainLimit);
			$guildInfo['shelter_time'] = EnGuildRob::guildShelterTime($guildInfo[GuildDef::GUILD_ID]);
			unset($guildInfo[GuildDef::VA_INFO]);
			unset($guildInfo[GuildDef::GRAIN_NUM]);
			$recordList[$key] = $guildInfo;
		}
		
		Logger::trace('GuildLogic::getEnemyList End.');
		
		return $recordList;
	}
	
	public static function getHarvestList($uid, $fieldId, $num)
	{
		Logger::trace('GuildLogic::getHarvestList Start.');
	
		//检查用户是否已经加入军团
		$guildId = self::getGuildId($uid);
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查粮仓是否开启
		$conf = btstore_get()->GUILD_BARN;
		$guild = GuildObj::getInstance($guildId);
		$guildLevel = $guild->getGuildLevel();
		if ($guild->isGuildBarnOpen() == FALSE)
		{
		    throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
		            $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		}
		
		//获取用户采集列表
		$harvestList = GuildDao::getHarvestList($guildId, $fieldId);
		$arrUid = array_unique(Util::arrayExtract($harvestList, GuildDef::USER_ID));
		$arrChunkUid = array_chunk($arrUid, CData::MAX_FETCH_SIZE);
		$arrUid = array();
		foreach ($arrChunkUid as $chunkUid)
		{
			$arrCond = array(
					array(GuildDef::GUILD_ID, '=', $guildId),
					array(GuildDef::USER_ID, 'in', $chunkUid),	
			);
			$arrMember = GuildDao::getMember($arrCond, array(GuildDef::USER_ID));
			$chunkUid = Util::arrayExtract($arrMember, GuildDef::USER_ID);
			$arrUid = array_merge($arrUid, $chunkUid);
		}
		$arrUser = EnUser::getArrUser($arrUid, array('uname'));
		
		//遍历补全
		$list = array();
		$harvestConf = $conf[GuildDef::GUILD_HARVEST_GRAIN][$fieldId];
		foreach ($harvestList as $key => $harvestInfo)
		{
			$muid = $harvestInfo[GuildDef::USER_ID];
			if (!in_array($muid, $arrUid)) 
			{
				continue;
			}
			$time = strftime("%Y%m%d%H", $harvestInfo[GuildDef::RECORD_TIME]);
			list($addExp, $preLevel, $afterLevel) = $harvestInfo[GuildDef::VA_INFO];
			$n = isset($harvestInfo[GuildDef::VA_INFO][3]) ? $harvestInfo[GuildDef::VA_INFO][3] : 1;
			$addGrain = isset($harvestInfo[GuildDef::VA_INFO][4]) ? $harvestInfo[GuildDef::VA_INFO][4] : $harvestConf[$preLevel][1];
			$addExtra = isset($harvestInfo[GuildDef::VA_INFO][5]) ? $harvestInfo[GuildDef::VA_INFO][5] : array();
			if (!isset($list[$muid][$time])) 
			{
				//用户名字，采集时间，采集次数，增加粮草，增加经验，增加等级，粮草产量，功勋产量
				$list[$muid][$time] = array(
						'uname' => $arrUser[$muid]['uname'],
						'time' => $time, 
						'num' => 0,
						'add_exp' => 0,
						'add_grain' => 0,
						'add_level' => 0,
						'merit_output' => 0,
						'grain_output' => 0,
						'add_extra' => array(),
				);
			}
			$list[$muid][$time]['num'] += $n;
			$list[$muid][$time]['add_exp'] += $addExp;
			$list[$muid][$time]['add_grain'] += $addGrain;
			$list[$muid][$time]['add_level'] += $afterLevel - $preLevel;
			$list[$muid][$time]['merit_output'] = max($list[$muid][$time]['merit_output'], $harvestConf[$afterLevel][0]);
			$list[$muid][$time]['grain_output'] = max($list[$muid][$time]['grain_output'], $harvestConf[$afterLevel][1]);
			$list[$muid][$time]['add_extra'] = Util::arrayAdd3V(array($list[$muid][$time]['add_extra'], $addExtra));
		}
		
		//排序，按time降序
		$sortArray = array();
		foreach ($list as $key => $value)
		{
			$sortArray = array_merge($sortArray, $value);
		}
		$sortCmp = new SortByFieldFunc(array('time' => SortByFieldFunc::DESC));
		usort($sortArray, array($sortCmp, 'cmp'));
	
		Logger::trace('GuildLogic::getHarvestList End.');
	
		return array_slice($sortArray, 0, $num);
	}
	
	public static function getGuildId($uid)
	{
		Logger::trace('GuildLogic::getGuildId Start.');
	
		$guildId = 0;
		if ($uid == RPCContext::getInstance()->getUid())
		{
			//session中的值可能是NULL
			$guildId = RPCContext::getInstance()->getSession(GuildDef::SESSION_GUILD_ID);
		}
		if (empty($guildId))
		{
			$member = GuildMemberObj::getInstance($uid);
			$guildId = $member->getGuildId();
		}
	
		Logger::trace('GuildLogic::getGuildId End.');
	
		return $guildId;
	}
	
	public static function getGuildInfo($uid)
	{
		Logger::trace('GuildLogic::getGuildInfo Start.');
		
		//获得用户所在的军团id
		$guildId = self::getGuildId($uid);
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//针对老军团粮仓已开启的情况，初始化粮仓默认等级0的升级时间。
		$type = GuildDef::BARN;
		$level = GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::LEVEL];
		$guild = GuildObj::getInstance($guildId);
		if (0 == $guild->getBuildTime($type, $level)) 
		{
		    RPCContext::getInstance()->executeTask(SPECIAL_UID::INIT_GUILD_LEVELUP_TIME_UID, 'guild.initLevelUpInfo', array($guildId, $type));
		}
		$memberList = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID));
		if (!empty($memberList))
		{
			$arrUid = Util::arrayExtract($memberList, GuildDef::USER_ID);
			$arrUser = EnUser::getArrUser($arrUid, array('fight_force'));
			$fightForce = array_sum(Util::arrayExtract($arrUser, 'fight_force'));
			$guild->setFightForce($fightForce);
		}
		$guild->update();
		$guildLevel = $guild->getGuildLevel();
		$fightForce = $guild->getFightForce();
		$upgradeTime = $guild->getUpgradeTime();
		$info = $guild->getInfo();
		unset($info[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::PASSWD]);
		unset($info[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::GOODS]);
		unset($info[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_LIST]);
		unset($info[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::REFRESH_CD]);
		$user = EnUser::getUserObj($uid);
		$info[GuildDef::LEADER_UID] = $uid;
		$info[GuildDef::LEADER_UTID] = $user->getUtid();
		$info[GuildDef::LEADER_NAME] = $user->getUname();
		$info[GuildDef::LEADER_LEVEL] = $user->getLevel();
		$info[GuildDef::LEADER_FORCE] = $user->getFightForce();
		$info[GuildDef::MEMBER_NUM] = self::getMemberNum($guildId);
		$info[GuildDef::MEMBER_LIMIT] = self::getMemberLimit($info);
		$info[GuildDef::VP_NUM] = self::getVpNum($guildId);
		$info[GuildDef::RANK] = self::getRank($guildId, $guildLevel, $fightForce, $upgradeTime);
		
		Logger::trace('GuildLogic::getGuildInfo End.');
		
		return $info;
	}
	
	public static function getMemberInfo($uid)
	{
		Logger::trace('GuildLogic::getMemberInfo Start.');
		
		$memberInfo = GuildDao::selectMember($uid);
		if (empty($memberInfo)) 
		{
			return array();
		}
		$member = GuildMemberObj::getInstance($uid);
		$member->refreshFields();
		$info = $member->getInfo();
		$info[GuildDef::CONTRI_NUM] = 1 - $member->getContriNum(); 
		$info[GuildDef::REWARD_NUM] = !Util::isSameDay($member->getRewardTime()) ? 1 : 0;
        $info['city_id'] = EnCityWar::getGuildCityId($uid);
        $guildId = $member->getGuildId();
        if(empty($guildId))
        {
            return $info;
        }
        $guild = GuildObj::getInstance($guildId);
        $guildLevel = $guild->getGuildLevel();
        $fightForce = $guild->getFightForce();
        $upgradeTime = $guild->getUpgradeTime();
        $info[GuildDef::GUILD_LEVEL] = $guildLevel;
        $info[GuildDef::FIGHT_FORCE] = $fightForce;
        $info[GuildDef::RANK] = self::getRank($guildId, $guildLevel, $fightForce, $upgradeTime);
        $info[GuildDef::MEMBER_NUM] = self::getMemberNum($guildId);
        $noShare = Util::getTime() - btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GUILD_JOIN_SHARE];
        $arrCond = array(
        		array(GuildDef::USER_ID, '=', $uid),
        		array(GuildDef::GUILD_ID, '=', $guildId),
        		array(GuildDef::RECORD_TYPE, '=', GuildRecordType::JOIN_GUILD),
        		array(GuildDef::RECORD_TIME, '>', $noShare),
        );
        $arrField = array(GuildDef::USER_ID, GuildDef::RECORD_TIME);
        $arrRet = GuildDao::getRecord($arrCond, $arrField);
        $info['join_time'] = empty($arrRet) ? $noShare : $arrRet[0][GuildDef::RECORD_TIME];
        
		Logger::trace('GuildLogic::getMemberInfo End.');
		
		return $info;
	}
	
	public static function getShareInfo($uid)
	{
		Logger::trace('GuildLogic::getShareInfo Start.');
	
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查粮仓是否开启
		$conf = btstore_get()->GUILD_BARN;
		$guild = GuildObj::getInstance($guildId);
		if ($guild->isGuildBarnOpen() == FALSE)
		{
		    throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
		            $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		}
		
		//检查军团的粮草是否还有剩余
		$grainNum = $guild->getGrainNum();
		if (empty($grainNum)) 
		{
			Logger::warning('guild:%d has no grain!', $guildId);
			return 'nograin';
		}
		
		//获得各职位的分粮数
		$sumCoef = 0;
		$sumShare = 0;
		$arrShare = array();
		$arrMember = self::getMembersByType($guildId);
		$shareCoef = $conf[GuildDef::GUILD_SHARE_COEF];
		foreach ($shareCoef as $type => $coef)
		{
			$sumCoef += $coef * count($arrMember[$type]);
		}
		foreach ($shareCoef as $type => $coef)
		{
			$num = count($arrMember[$type]);
			$share = empty($num) ? 0 : min(intval($coef / $sumCoef * $grainNum), GuildConf::MAX_SHARE_NUM);
			$arrShare[$type] = array('share' => $share, 'num' => $num);
			$sumShare += $share * $num;
		}
		
		//返回军团粮草总数
		$arrShare[0] = array('total' => $grainNum);
		
		Logger::trace('GuildLogic::getShareInfo End.');
		
		return $arrShare;
	}
	
	public static function getRefreshInfo($uid)
	{
		Logger::trace('GuildLogic::getRefreshInfo Start.');
		
		//获得用户所在的军团id
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('user:%d is not in any guild!', $uid);
		}
		
		//检查粮仓是否开启
		$conf = btstore_get()->GUILD_BARN;
		$guild = GuildObj::getInstance($guildId);
		if ($guild->isGuildBarnOpen() == FALSE)
		{
		    throw new FakeException('guild:%d info:%s is not reach barn open level:%s.barn not open.',
		            $guildId, $guild->getInfo(), $conf[GuildDef::GUILD_BARN_OPEN]);
		}
		
		//获取军团被抢粮的记录
		$time = strtotime(intval(strftime("%Y%m%d", Util::getTime())) . " 00:00:00");
		$recordList = GuildDao::getRecordList($guildId, array(GuildRecordType::REFRESH_ALL), 0, CData::MAX_FETCH_SIZE, $time);
		$arrUid = Util::arrayExtract($recordList, GuildDef::USER_ID);
		$arrUser = EnUser::getArrUser($arrUid, array('uname'));
		$info = array();
		foreach ($arrUid as $value)
		{
			$info[] = $arrUser[$value]['uname'];
		}
		
		Logger::trace('GuildLogic::getRefreshInfo End.');
		return $info;
	}
	
	/**
	 * 根据当前时间调整memeber的contri信息
	 * 如果后面需要update，一定要同时update被调整的字段
	 * 	GuildDef::CONTRI_TIME
	 *  GuildDef::CONTRI_NUM
	 *  GuildDef::CONTRI_WEEK
	 *  GuildDef::LAST_CONTRI_WEEK
	 * @param array $info
	 * @return number
	 */
	public static function adpMemberContriInfo($info)
	{
		//重置用户的当天贡献次数
		if (isset($info[GuildDef::CONTRI_NUM]) && !Util::isSameDay($info[GuildDef::CONTRI_TIME]))
		{
			$info[GuildDef::CONTRI_NUM] = 0;
		}
	
		//重置成员的本周贡献值和更新成员的上周贡献值
		$lastSignupEndTime = EnCityWar::getLastSignupEndTime();
		if ($info[GuildDef::CONTRI_TIME] < $lastSignupEndTime)
		{
			if($info[GuildDef::CONTRI_TIME] < $lastSignupEndTime - CityWarConf::ROUND_DURATION)
			{
				$info[GuildDef::LAST_CONTRI_WEEK] = 0;
			}
			else
			{
				$info[GuildDef::LAST_CONTRI_WEEK] = $info[GuildDef::CONTRI_WEEK];
			}
			$info[GuildDef::CONTRI_WEEK] = 0;
		}
		$info[GuildDef::CONTRI_TIME] = Util::getTime();
	
		return $info;
	}
	
	public static function getArrMemberList($arrGuildId, $arrField)
	{
		if(!in_array(GuildDef::GUILD_ID, $arrField))
		{
			$arrField[] = GuildDef::GUILD_ID;
		}
		if(in_array(GuildDef::CONTRI_WEEK, $arrField) || in_array(GuildDef::LAST_CONTRI_WEEK, $arrField))
		{
			$arrField = array_merge($arrField, array(GuildDef::CONTRI_WEEK, GuildDef::LAST_CONTRI_WEEK, GuildDef::CONTRI_TIME));
		}
		$arrField = array_values(array_unique($arrField));
	
		$arrRet = GuildDao::getArrMemberList($arrGuildId, $arrField);
		foreach($arrRet as $key => $value)
		{
			$arrRet[$key] = self::adpMemberContriInfo($value);
		}
		return $arrRet;
	}
	
	public static function addGuildExp($uid, $exp)
	{
		Logger::trace('GuildLogic::addGuildExp Start.');
		
		//更新成员的信息，贡献值和贡献时间
		$member = GuildMemberObj::getInstance($uid);
		$member->addContriWeek($exp);
		$member->update();

		//修改军团经验，加锁
		$guildId = $member->getGuildId();
		if (!empty($guildId)) 
		{
		    try 
		    {
		    	//给军团加上贡献值
		    	$guild = GuildObj::getInstance($guildId, array(GuildDef::CURR_EXP));
		        $guild->addCurrExp($exp);
		        $guild->update();
		    }
		    catch(Exception $e)
		    {
		        $guild->unlockArrField();
		        throw $e;
		    }
		}
		
		Logger::trace('GuildLogic::addGuildExp End.');
		
		return 'ok';
	}
	
	public static function addUserPoint($uid, $point)
	{
		Logger::trace('GuildLogic::addUserPoint Start.');
	
		//更新成员的贡献值
		$member = GuildMemberObj::getInstance($uid);
		$member->addContriPoint($point);
		$member->update();

		EnAchieve::updateGuildContribution($uid, $member->getContriTotal());
	
		Logger::trace('GuildLogic::addUserPoint End.');
	
		return 'ok';
	}
	
	public static function atkUserByOther($uid, $atkUid, $atkTimes, $beAtkTimes, $isSuc, $replayId, $ifmail=true)
	{
		Logger::trace('GuildLogic::atkUserByOther Start. uid:%d, atkUid:%d', $uid, $atkUid);
	
		$member = GuildMemberObj::getInstance($uid);
		$member->setPlayWithTime(Util::getTime());
		$member->setPlayWithNum($atkTimes);
		$member->setBePlayWithNum($beAtkTimes);
		$member->update();
	
		//给玩家发邮件
		$atkUser = EnUser::getUserObj($atkUid);
		if($ifmail)
		{
			MailTemplate::sendGuildVersus($uid, $isSuc, $atkUser->getTemplateUserInfo(), $replayId);
		}
	
		Logger::trace('GuildLogic::atkUserByOther Start. uid:%d, atkUid:%d', $uid, $atkUid);
	}
	
	public static function robGuildByOther($attackGuildId, $defendGuildId, $robGrain)
	{
		self::initRecord(0, $defendGuildId, GuildRecordType::ROB_GRAIN, $attackGuildId, array($robGrain));
		return 'ok';
	}
	
	public static function recordGuildCopyAllAttack($uid, $guildId)
	{
		self::initRecord($uid, $guildId, GuildRecordType::ALL_ATTACK, 0);
		return 'ok';
	}
	
	public static function refreshGuildName($guildName)
	{
		RPCContext::getInstance()->setSession(GuildDef::SESSION_GUILD_NAME, $guildName);
	}
	
	/**
	 * 更新用户的军团信息，设置session等
	 * 
	 * @param int $uid
	 * @param int $guildId
	 * @param int $join
	 * @throws InterException
	 */
	public static function refreshUser($uid, $guildId, $join)
	{
		Logger::trace('GuildLogic::refreshUser Start.');
		
		if ($join)
		{
			RPCContext::getInstance()->setSession(GuildDef::SESSION_GUILD_ID, $guildId);
			$guildName = GuildObj::getInstance($guildId)->getGuildName();
			RPCContext::getInstance()->setSession(GuildDef::SESSION_GUILD_NAME, $guildName);
		}
		else
		{
			RPCContext::getInstance()->unsetSession(GuildDef::SESSION_GUILD_ID);
			RPCContext::getInstance()->unsetSession(GuildDef::SESSION_GUILD_NAME);
		}
		
		$user = EnUser::getUserObj($uid);
		$user->setGuildId($guildId);
		$user->update();
		
		Logger::trace('GuildLogic::refreshUser End.');
	}
	
	public static function refreshFields($uid, $uname, $addRefreshNum, $type)
	{
		Logger::trace('GuildLogic::refreshFields Start.');
		
		$member = GuildMemberObj::getInstance($uid);
		$guildId = $member->getGuildId();
		$barnLevel = GuildObj::getInstance($guildId)->getBuildLevel(GuildDef::BARN);
		$member->refreshOwn($barnLevel, $addRefreshNum);
		$member->update();
		
		RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_REFRESH_ALL, array($uname, $member->getFields(), $type));
		
		Logger::trace('GuildLogic::refreshFields End.');
	}
	
	public static function initLevelUpInfo($guildId, $type)
	{
		Logger::trace('GuildLogic::initLevelUpInfo Start.');
		
		try
		{
			$guild = GuildObj::getInstance($guildId, array(GuildDef::SHARE_CD, GuildDef::VA_INFO));
			$now = Util::getTime();
			$type = GuildDef::BARN;
			$level = GuildConf::$GUILD_BUILD_DEFAULT[$type][GuildDef::LEVEL];
			$guild->addBuildTime($type, $level, $now);
			$guild->update();
		}
		catch (Exception $e)
		{
			$guild->unlockArrField();
			throw $e;
		}
		
		$shareCd = btstore_get()->GUILD_BARN[GuildDef::GUILD_SHARE_CD];
		RPCContext::getInstance()->sendFilterMessage('guild', $guildId, PushInterfaceDef::GUILD_SHARE_CD, array($now + $shareCd));
		
		Logger::trace('GuildLogic::initLevelUpInfo End.');
	}
	
	private static function initApply($uid, $guildId)
	{
		Logger::trace('GuildLogic::initApply Start.');
		
		$arrField = array(
				GuildDef::USER_ID => $uid, 
				GuildDef::GUILD_ID => $guildId, 
				GuildDef::APPLY_TIME => Util::getTime(),
				GuildDef::STATUS => GuildApplyStatus::OK 
		);
		//插入数据库
		GuildDao::insertApply($arrField);
		
		Logger::trace('GuildLogic::initApply End.');
	}
	
	private static function initRecord($uid, $guildId, $type, $data, $info = array())
	{
		Logger::trace('GuildLogic::initRecord Start.');
		
		$arrField = array(
				GuildDef::USER_ID => $uid,
				GuildDef::GUILD_ID => $guildId,
				GuildDef::RECORD_TYPE => $type,
				GuildDef::RECORD_DATA => $data,
				GuildDef::RECORD_TIME => Util::getTime(),
				GuildDef::VA_INFO => $info
		);
		//插入数据库
		GuildDao::insertRecord($arrField);
		
		Logger::trace('GuildLogic::initRecord End.');
	}
	
	private static function checkLength($data, $length, $name)
	{
		Logger::trace('GuildLogic::checkLength Start.');
		
		if (mb_strlen($data, FrameworkConfig::ENCODING) > $length)
		{
			throw new FakeException('%s must be shorter than %d', $name, $length);
		}
		
		Logger::trace('GuildLogic::checkLength End.');
	}
	
	private static function cancelAllApply($uid)
	{
		Logger::trace('GuildLogic::cancelAllApply Start.');
		
		//取消该用户所有其他申请记录
		$arrCond = array(
				array(GuildDef::USER_ID, '=', $uid),
				array (GuildDef::STATUS, '=', GuildApplyStatus::OK) 
		);
		$arrField = array(GuildDef::STATUS => GuildApplyStatus::CANCEL);
		GuildDao::updateApply($arrCond, $arrField);
		
		Logger::trace('GuildLogic::cancelAllApply End.');
	}
	
	private static  function getPresidentUid($guildId)
	{
		Logger::trace('GuildLogic::getPresidentUid Start.');
		
		$arrCond = array(
				array(GuildDef::GUILD_ID, '=', $guildId),
				array(GuildDef::MEMBER_TYPE, '=', GuildMemberType::PRESIDENT)
		);
		$arrRet = GuildDao::getMember($arrCond, array(GuildDef::USER_ID));
		$uid = $arrRet[0][GuildDef::USER_ID];
		
		Logger::trace('GuildLogic::getPresidentUid End.');
		return $uid;
	}
	
	private static function getVpNum($guildId)
	{
		Logger::trace('GuildLogic::getVpNum Start.');
		
		$arrCond = array(
				array(GuildDef::GUILD_ID, '=', $guildId),
				array(GuildDef::MEMBER_TYPE, '=', GuildMemberType::VICE_PRESIDENT),
		);
		$num =  GuildDao::getMemberCount($arrCond);
		
		Logger::trace('GuildLogic::getVpNum End.');
		return $num;
	}
	
	private static function getMemberNum($guildId)
	{
		Logger::trace('GuildLogic::getMemberNum Start.');
		
		$arrCond = array(array(GuildDef::GUILD_ID, '=', $guildId));
		$num = GuildDao::getMemberCount($arrCond);
		
		Logger::trace('GuildLogic::getMemberNum End.');
		return $num;
	}

	private static function getMemberLimit($guildInfo)
	{
		Logger::trace('GuildLogic::getMemberLimit Start.');
		
		$conf = btstore_get()->GUILD;
		$limit =  min($conf[GuildDef::GUILD_CAPACITY_BASE][$guildInfo[GuildDef::GUILD_LEVEL]], $conf[GuildDef::GUILD_CAPACITY_LIMIT]);
		
		$type = GuildDef::TECH;
		if (!isset($guildInfo[GuildDef::VA_INFO][$type][GuildDef::SKILLS]))
		{
			return $limit;
		}
		
		$conf = btstore_get()->GUILD_SKILL;
		foreach ($guildInfo[GuildDef::VA_INFO][$type][GuildDef::SKILLS] as $id => $level)
		{
			if ($conf[$id][GuildDef::GUILD_SKILL_TYPE] == 2)
			{
				$limit += $level * $conf[$id][GuildDef::GUILD_SKILL_ATTR];
			}
		}
		
		Logger::trace('GuildLogic::getMemberLimit End.');
		
		return $limit;
	}
	
	private static function getMembersByType($guildId)
	{
		Logger::trace('GuildLogic::getMembersByType Start.');
	
		$divide = array();
		for ($i = 1; $i <= 6; $i++)
		{
			$divide[$i] = array();
		}
		$memberList = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID, GuildDef::MEMBER_TYPE));
		foreach ($memberList as $memberInfo)
		{
			$divide[$memberInfo[GuildDef::MEMBER_TYPE]][] = $memberInfo[GuildDef::USER_ID];
		}
		$i = GuildMemberType::VICE_PRESIDENT + 1;
		if (!empty($divide[GuildMemberType::NONE]))
		{
			$guildInfo = GuildObj::getInstance($guildId)->getInfo();
			$divide[$i++] = array_slice($divide[GuildMemberType::NONE], 0, 5);
			$divide[$i++] = array_slice($divide[GuildMemberType::NONE], 5, 5);
			$divide[$i++] = array_slice($divide[GuildMemberType::NONE], 10, 10);
			$divide[$i++] = array_slice($divide[GuildMemberType::NONE], 20, 10 + self::getMemberLimit($guildInfo) - 30);
			unset($divide[GuildMemberType::NONE]);
		}
	
		Logger::trace('GuildLogic::getMembersByType End.');
		return $divide;
	}
	
	private static function getRank($guildId, $guildLevel, $fightForce, $upgradeTime)
	{
		Logger::trace('GuildLogic::getRank Start.');
		
		$arrCond = array(
				array(GuildDef::FIGHT_FORCE, '>', $fightForce),
				array(GuildDef::STATUS, '=', GuildStatus::OK)
		);
		$rank = GuildDao::getGuildCount($arrCond);

		$arrCond = array(
				array(GuildDef::FIGHT_FORCE, '=', $fightForce),
				array(GuildDef::GUILD_LEVEL, '>', $guildLevel),
				array(GuildDef::STATUS, '=', GuildStatus::OK)
		);
		$rank += GuildDao::getGuildCount($arrCond);
		
		$arrCond = array(
				array(GuildDef::FIGHT_FORCE, '=', $fightForce),
				array(GuildDef::GUILD_LEVEL, '=', $guildLevel),
				array(GuildDef::UPGRADE_TIME, '<', $upgradeTime),
				array(GuildDef::STATUS, '=', GuildStatus::OK) 
		);
		$rank += GuildDao::getGuildCount($arrCond);
		
		$arrCond = array(
				array(GuildDef::FIGHT_FORCE, '=', $fightForce),
				array(GuildDef::GUILD_LEVEL, '=', $guildLevel),
				array(GuildDef::UPGRADE_TIME, '=', $upgradeTime),
				array(GuildDef::GUILD_ID, '<', $guildId),
				array(GuildDef::STATUS, '=', GuildStatus::OK) 
		);
		$rank += GuildDao::getGuildCount($arrCond);
		
		Logger::trace('GuildLogic::getRank End.');
		return $rank + 1;
	}
	
	private static function fixTotalContri($memberList)
	{
		Logger::trace('GuildLogic::fixTotalContri Start.');
		
		$arrUid = array();
		foreach ($memberList as $uid => $memberInfo)
		{
			//如果总贡献值小于贡献值，则记录用户，等会修下数据
			if ($memberInfo[GuildDef::CONTRI_TOTAL] == 0 && $memberInfo[GuildDef::CONTRI_TIME] > 0)
			{
				$arrUid[] = $uid;
				Logger::info('user:%d, contri point:%d, total:%d', $uid, $memberInfo[GuildDef::CONTRI_POINT], $memberInfo[GuildDef::CONTRI_TOTAL]);
			}
		}
		if (!empty($arrUid)) 
		{
			//一次拉取所需数据
			$arrType = range(1, GuildRecordType::CONTRI_EXP);
			$arrCond = array(
					array(GuildDef::USER_ID, 'in', $arrUid),
					array(GuildDef::RECORD_TYPE, 'in', $arrType)
			);
			$arrField = array(GuildDef::USER_ID, GuildDef::RECORD_DATA);
			$arrRet = GuildDao::getRecord($arrCond, $arrField);
			$arrUidData = array();
			foreach ($arrRet as $ret)
			{
				$uid = $ret[GuildDef::USER_ID];
				if (!isset($arrUidData[$uid]))
				{
					$arrUidData[$uid] = 0;
				}
				$arrUidData[$uid] += $ret[GuildDef::RECORD_DATA];
			}
			foreach ($arrUidData as $uid => $data)
			{
				$memberList[$uid][GuildDef::CONTRI_TOTAL] += $data;
				//如果总贡献值还是比当前贡献值小，就将总贡献值替换为当前贡献值
				if ($memberList[$uid][GuildDef::CONTRI_TOTAL] < $memberList[$uid][GuildDef::CONTRI_POINT])
				{
					$memberList[$uid][GuildDef::CONTRI_TOTAL] = $memberList[$uid][GuildDef::CONTRI_POINT];
				}
				$arrCond = array(array(GuildDef::USER_ID, '=', $uid));
				$arrField = array(GuildDef::CONTRI_TOTAL => $memberList[$uid][GuildDef::CONTRI_TOTAL]);
				GuildDao::updateMember($arrCond, $arrField);
				Logger::info('user:%d, contri point:%d, total:%d', $uid, $memberList[$uid][GuildDef::CONTRI_POINT], $memberList[$uid][GuildDef::CONTRI_TOTAL]);
			}
		}
		
		Logger::trace('GuildLogic::fixTotalContri End.');
		return $memberList;
	}
	
	private static function getArrTypeTime($arrUid)
	{
		Logger::trace('GuildLogic::getArrType Start.');
		
		if (empty($arrUid)) 
		{
			return array();
		}
		
		//一周前
		$start = Util::getTime() - 604800;
		$arrType = range(1, GuildRecordType::CONTRI_EXP);
		
		$arrCond = array(
				array(GuildDef::USER_ID, 'in', $arrUid),
				array(GuildDef::RECORD_TYPE, 'in', $arrType),
				array(GuildDef::RECORD_TIME, '>=', $start),
		);
		$arrField = array(GuildDef::USER_ID, GuildDef::RECORD_TYPE, GuildDef::RECORD_TIME);
		$arrRet = GuildDao::getRecord($arrCond, $arrField);
		//从每个用户的贡献记录中取出贡献时间最近的
		$arrTypeTime = array();
		foreach ($arrRet as $ret)
		{
			$uid = $ret[GuildDef::USER_ID];
			if (!isset($arrTypeTime[$uid])) 
			{
				$arrTypeTime[$uid] = $ret;
			}
			else 
			{
				if ($ret[GuildDef::RECORD_TIME] > $arrTypeTime[$uid][GuildDef::RECORD_TIME]) 
				{
					$arrTypeTime[$uid] = $ret;
				}
			}
		}
		
		Logger::trace('GuildLogic::getArrType End.');
		
		return $arrTypeTime;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */