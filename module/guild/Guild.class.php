<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Guild.class.php 230589 2016-03-02 10:18:19Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/Guild.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-03-02 10:18:19 +0000 (Wed, 02 Mar 2016) $
 * @version $Revision: 230589 $
 * @brief 
 *  
 **/

class Guild implements IGuild
{	
	/**
	 * 用户id
	 * @var $uid
	 */
	private $uid;
	
	/**
	 * 构造函数
	 */
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
		
		if (!empty($this->uid)) 
		{
			if (EnSwitch::isSwitchOpen(SwitchDef::GUILD) == false)
	 		{
	 			throw new FakeException('user:%d does not open the guild', $this->uid);
	 		}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::createGuild()
	 */
	public function createGuild($name, $isGold = 0, $slogan = "", $post = "", $passwd = "")
	{
		Logger::trace('Guild::createGuild Start.');
		
		if (strval($name) == '' || !in_array($isGold, GuildDef::$VALID_CREATE_TYPE)) 
		{
			throw new FakeException('Err para, invalid name:%s isGold:%d!', $name, $isGold);
		}
		$ret = GuildLogic::createGuild($this->uid, $name, $isGold, $slogan, $post, $passwd);
		
		Logger::trace('Guild::createGuild End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::applyGuild()
	 */
	public function applyGuild($guildId)
	{
		Logger::trace('Guild::applyGuild Start.');
		
		if (empty($guildId))
		{
			throw new FakeException('Err para, guildId:%d!', $guildId);
		}
		$ret = GuildLogic::applyGuild($this->uid, $guildId);
		
		Logger::trace('Guild::applyGuild End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::cancelApply()
	 */
	public function cancelApply($guildId)
	{
		Logger::trace('Guild::cancelApply Start.');
		
		if (empty($guildId))
		{
			throw new FakeException('Err para, guildId:%d!', $guildId);
		}
		$ret = GuildLogic::cancelApply($this->uid, $guildId);
		
		Logger::trace('Guild::cancelApply End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::agreeApply()
	 */
	public function agreeApply($uid)
	{
		Logger::trace('Guild::agreeApply Start.');
		
		if (empty($uid) || $uid == $this->uid)
		{
			throw new FakeException('Err para, uid:%d!', $uid);
		}
		$ret = GuildLogic::agreeApply($this->uid, $uid);
		
		Logger::trace('Guild::agreeApply End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::refuseApply()
	 */
	public function refuseApply($uid)
	{
		Logger::trace('Guild::refuseApply Start.');
		
		if (empty($uid) || $uid == $this->uid)
		{
			throw new FakeException('Err para, uid:%d!', $uid);
		}
		$ret = GuildLogic::refuseApply($this->uid, $uid);
		
		Logger::trace('Guild::refuseApply End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::refuseAllApply()
	 */
	public function refuseAllApply()
	{
		Logger::trace('Guild::refuseAllApply Start.');
	
		$ret = GuildLogic::refuseAllApply($this->uid);
		
		Logger::trace('Guild::refuseAllApply End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::quitGuild()
	 */
	public function quitGuild()
	{
		Logger::trace('Guild::quitGuild Start.');
	
		$ret = GuildLogic::quitGuild($this->uid);
	
		Logger::trace('Guild::quitGuild End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::kickMember()
	 */
	public function kickMember($uid)
	{
		Logger::trace('Guild::kickMember Start.');
		
		if (empty($uid) || $uid == $this->uid)
		{
			throw new FakeException('Err para, uid:%d!', $uid);
		}
		$ret = GuildLogic::kickMember($this->uid, $uid);
		
		Logger::trace('Guild::kickMember End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::modifyIcon()
	 */
	public function modifyIcon($icon)
	{
		Logger::trace('Guild::modifyIcon Start.');
		
		$ret = GuildLogic::modifyIcon($this->uid, $icon);
		
		Logger::trace('Guild::modifyIcon End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::modifySlogan()
	 */
	public function modifySlogan($slogan)
	{
		Logger::trace('Guild::modifySlogan Start.');

		$ret = GuildLogic::modifySlogan($this->uid, $slogan);
		
		Logger::trace('Guild::modifySlogan End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::modifyPost()
	 */
	public function modifyPost($post)
	{
		Logger::trace('Guild::modifyPost Start.');
	
		$ret = GuildLogic::modifyPost($this->uid, $post);
	
		Logger::trace('Guild::modifyPost End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::modifyPasswd()
	 */
	public function modifyPasswd($oldPasswd, $newPasswd)
	{
		Logger::trace('Guild::modifyPasswd Start.');
	
		if (empty($oldPasswd) || empty($newPasswd))
		{
			throw new FakeException('Err para, oldPasswd:%s newPasswd:%s!', $oldPasswd, $newPasswd);
		}
		$ret = GuildLogic::modifyPasswd($this->uid, $oldPasswd, $newPasswd);
	
		Logger::trace('Guild::modifyPasswd End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::modifyName()
	 */
	public function modifyName($name)
	{
		Logger::trace('Guild::modifyName Start.');
		
		if (strval($name) == '') 
		{
			throw new FakeException('Err para, invalid name:%s!', $name);
		}
		$ret = GuildLogic::modifyName($this->uid, $name);
		
		Logger::trace('Guild::modifyName End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::setVicePresident()
	 */
	public function setVicePresident($uid)
	{
		Logger::trace('Guild::setVicePresident Start.');
		
		if (empty($uid) || $uid == $this->uid)
		{
			throw new FakeException('Err para, uid:%d!', $uid);
		}
		$ret = GuildLogic::setVicePresident($this->uid, $uid);
		
		Logger::trace('Guild::setVicePresident End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::setVicePresident()
	 */
	public function unsetVicePresident($uid)
	{
		Logger::trace('Guild::unsetVicePresident Start.');
	
		if (empty($uid) || $uid == $this->uid)
		{
			throw new FakeException('Err para, uid:%d!', $uid);
		}
		$ret = GuildLogic::unsetVicePresident($this->uid, $uid);
	
		Logger::trace('Guild::unsetVicePresident End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::transPresident()
	 */
	public function transPresident($uid, $passwd)
	{
		Logger::trace('Guild::transPresident Start.');
		
		if (empty($uid) || empty($passwd) || $uid == $this->uid)
		{
			throw new FakeException('Err para, uid:%d passwd:%s!', $uid, $passwd);
		}
		$ret = GuildLogic::transPresident($this->uid, $uid, $passwd);
		
		Logger::trace('Guild::transPresident End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::dismiss()
	 */
	public function dismiss($passwd)
	{
		Logger::trace('Guild::dismiss Start.');
		
		if (empty($passwd))
		{
			throw new FakeException('Err para, passwd:%s!', $passwd);
		}
		$ret = GuildLogic::dismiss($this->uid, $passwd);
		
		Logger::trace('Guild::dismiss End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::impeach()
	 */
	public function impeach()
	{
		Logger::trace('Guild::impeach Start.');
		
		$ret = GuildLogic::impeach($this->uid);
		
		Logger::trace('Guild::impeach End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::contribute()
	 */
	public function contribute($type)
	{
		Logger::trace('Guild::contribute Start.');
		
		if ($type <= 0)
		{
			throw new FakeException('Err para, type:%d!', $type);
		}
		$ret = GuildLogic::contribute($this->uid, $type);
		
		Logger::trace('Guild::contribute End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::upgradeGuild()
	 */
	public function upgradeGuild($type)
	{
		Logger::trace('Guild::upgradeGuild Start.');
	
		if (!key_exists($type, GuildConf::$GUILD_BUILD_DEFAULT) || $type == GuildDef::TECH)
		{
			throw new FakeException('Err para, type:%d!', $type);
		}
		$ret = GuildLogic::upgradeGuild($this->uid, $type);
	
		Logger::trace('Guild::upgradeGuild End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::reward()
	 */
	public function reward($type = 0)
	{
		Logger::trace('Guild::reward Start.');
		
		if ($type != 0 && $type != 1)
		{
			throw new FakeException('Err para, type:%d!', $type);
		}
		
		$ret = GuildLogic::reward($this->uid, $type);
		
		Logger::trace('Guild::reward End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::leaveMessage()
	 */
	public function leaveMessage($msg)
	{
		Logger::trace('Guild::leaveMessage Start.');
		
		$ret = GuildLogic::leaveMessage($this->uid, $msg);
		
		Logger::trace('Guild::leaveMessage End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::lottery()
	 */
	public function lottery()
	{
		Logger::trace('Guild::lottery Start.');
	
		$ret = GuildLogic::lottery($this->uid);
	
		Logger::trace('Guild::lottery End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::harvest()
	 */
	public function harvest($fieldId, $num = 1)
	{
		Logger::trace('Guild::harvest Start.');
	
		if ($fieldId <= 0 || $num <= 0)
		{
			throw new FakeException('Err para, field:%d, num:%d!', $fieldId, $num);
		}
	
		$ret = GuildLogic::harvest($this->uid, $fieldId, $num);
	
		Logger::trace('Guild::harvest End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::quickHarvest()
	 */
	public function quickHarvest()
	{
		Logger::trace('Guild::quickHarvest Start.');
		
		$ret = GuildLogic::quickHarvest($this->uid);
		
		Logger::trace('Guild::quickHarvest End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::refreshOwn()
	 */
	public function refreshOwn()
	{
		Logger::trace('Guild::refreshOwn Start.');
	
		$ret = GuildLogic::refreshOwn($this->uid);
	
		Logger::trace('Guild::refreshOwn End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::refreshAll()
	 */
	public function refreshAll($type)
	{
		Logger::trace('Guild::refreshAll Start.');
		
		if (!in_array($type, array(RefreshAllType::GOLD, RefreshAllType::GUILDEXP)))
		{
			throw new FakeException('Err para, type:%d!', $type);
		}
		$ret = GuildLogic::refreshAll($this->uid,$type);
		
		Logger::trace('Guild::refreshAll End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::share()
	 */
	public function share()
	{
		Logger::trace('Guild::share Start.');
	
		$ret = GuildLogic::share($this->uid);
		
		Logger::trace('Guild::share End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::buyFightBook()
	 */
	public function buyFightBook()
	{
		Logger::trace('Guild::buyFightBook Start.');
		
		$ret = GuildLogic::buyFightBook($this->uid);
		
		Logger::trace('Guild::buyFightBook End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::fightEachOther()
	 */
	public function fightEachOther($atkedUid)
	{
		Logger::trace('Guild::fightEachOther Start. atkedUid = %d', $atkedUid);
	
		list($atkedUid) = Util::checkParam(__METHOD__, func_get_args());
		if (empty($atkedUid) || $atkedUid == $this->uid)
		{
			throw new FakeException('Err para, atkedUid:%d!', $atkedUid);
		}
		$ret = GuildLogic::fightEachOther($this->uid, $atkedUid);
	
		Logger::trace('Guild::fightEachOther Start. atkedUid = %d', $atkedUid);
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::promote()
	 */
	public function promote($id, $type)
	{
		Logger::trace('Guild::promote Start.');

		if (empty(btstore_get()->GUILD_SKILL[$id]) || empty($type))
		{
			throw new FakeException('Err para, id:%d, type:%d!', $id, $type);
		}
		$ret = GuildLogic::promote($this->uid, $id, $type);
		
		Logger::trace('Guild::promote End');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getGuildApplyList()
	 */
	public function getGuildApplyList($offset, $limit)
	{
		Logger::trace('Guild::getGuildApplyList Start.');
		
		if ($offset < 0 || $limit <= 0 || $limit > CData::MAX_FETCH_SIZE)
		{
			throw new FakeException('Err para, offset:%d limit:%d!', $offset, $limit);
		}
		$ret = GuildLogic::getGuildApplyList($this->uid, $offset, $limit);
		
		Logger::trace('Guild::getGuildApplyList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getUserApplyList()
	 */
	public function getUserApplyList()
	{
		Logger::trace('Guild::getUserApplyList Start.');
		
		$ret = GuildLogic::getUserApplyList($this->uid);
		
		Logger::trace('Guild::getUserApplyList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getGuildList()
	 */
	public function getGuildList($offset, $limit)
	{
		Logger::trace('Guild::getGuildList Start.');
		
		if ($offset < 0 || $limit < GuildConf::MAX_APPLY_NUM || $limit > CData::MAX_FETCH_SIZE)
		{
			throw new FakeException('Err para, offset:%d limit:%d!', $offset, $limit);
		}
		$ret = GuildLogic::getGuildList($this->uid, $offset, $limit);
		
		Logger::trace('Guild::getGuildList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getGuildListByName()
	 */
	public function getGuildListByName($offset, $limit, $name)
	{
		Logger::trace('Guild::getGuildListByName Start.');
	
		if ($offset < 0 || $limit <= 0 || $limit > CData::MAX_FETCH_SIZE || $name == null)
		{
			throw new FakeException('Err para, offset:%d limit:%d name:%s!', $offset, $limit, $name);
		}
		$ret = GuildLogic::getGuildList($this->uid, $offset, $limit, $name);
		unset($ret['appnum']);
	
		Logger::trace('Guild::getGuildListByName End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getGuildRankList()
	 */
	public function getGuildRankList()
	{
		Logger::trace('Guild::getGuildRankList Start.');
		
		$ret = GuildLogic::getGuildRankList($this->uid);
		
		Logger::trace('Guild::getGuildRankList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getMemberList()
	 */
	public function getMemberList($offset, $limit)
	{
		Logger::trace('Guild::getMemberList Start.');
		
		if ($offset < 0 || $limit <= 0 || $limit > CData::MAX_FETCH_SIZE)
		{
			throw new FakeException('Err para, offset:%d limit:%d!', $offset, $limit);
		}
		$ret = GuildLogic::getMemberList($this->uid, $offset, $limit);
		
		Logger::trace('Guild::getMemberList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getRecordList()
	 */
	public function getRecordList()
	{
		Logger::trace('Guild::getRecordList Start.');
		
		$arrType = range(1, GuildRecordType::CONTRI_EXP);
		$ret = GuildLogic::getRecordList($this->uid, $arrType, GuildConf::MAX_RECORD_NUM);
		
		Logger::trace('Guild::getRecordList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getMessageList()
	 */
	public function getMessageList($offset, $limit)
	{
		Logger::trace('Guild::getMessageList Start.');
		
		if ($offset < 0 || $limit <= 0 || $limit > CData::MAX_FETCH_SIZE)
		{
			throw new FakeException('Err para, offset:%d limit:%d!', $offset, $limit);
		}
		$ret = GuildLogic::getMessageList($this->uid, $offset, $limit);
		
		Logger::trace('Guild::getMessageList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getDynamicList()
	 */
	public function getDynamicList()
	{
		Logger::trace('Guild::getDynamicList Start.');
		
		$ret = GuildLogic::getDynamicList($this->uid, GuildConf::MAX_DYNAMIC_NUM);
		
		Logger::trace('Guild::getDynamicList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getEnemyList()
	 */
	public function getEnemyList($offset, $limit)
	{
		Logger::trace('Guild::getEnemyList Start.');
		
		if ($offset < 0 || $limit <= 0 || $limit > CData::MAX_FETCH_SIZE)
		{
			throw new FakeException('Err para, offset:%d limit:%d!', $offset, $limit);
		}
		$ret = GuildLogic::getEnemyList($this->uid, $offset, $limit);
		
		Logger::trace('Guild::getEnemyList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getHarvestList()
	 */
	public function getHarvestList($fieldId)
	{
		Logger::trace('Guild::getHarvestList Start.');
		
		if (!key_exists($fieldId, GuildConf::$GUILD_FIELD_DEFAULT))
		{
			throw new FakeException('Err para, fieldId:%d!', $fieldId);
		}
		$ret = GuildLogic::getHarvestList($this->uid, $fieldId, GuildConf::MAX_DYNAMIC_NUM);
		
		Logger::trace('Guild::getHarvestList End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getGuildInfo()
	 */
	public function getGuildInfo()
	{
		Logger::trace('Guild::getGuildInfo Start.');
		
		$ret = GuildLogic::getGuildInfo($this->uid);
		
		Logger::trace('Guild::getGuildInfo End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getMemberInfo()
	 */
	public function getMemberInfo()
	{
		Logger::trace('Guild::getMemberInfo Start.');
		
		$ret = GuildLogic::getMemberInfo($this->uid);
		
		Logger::trace('Guild::getMemberInfo End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getShareInfo()
	 */
	public function getShareInfo()
	{
		Logger::trace('Guild::getShareInfo Start.');
		
		$ret = GuildLogic::getShareInfo($this->uid);
		
		Logger::trace('Guild::getShareInfo End.');
		return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IGuild::getRefreshInfo()
	 */
	public function getRefreshInfo()
	{
		Logger::trace('Guild::getRefreshInfo Start.');
		
		$ret = GuildLogic::getRefreshInfo($this->uid);
		
		Logger::trace('Guild::getRefreshInfo End.');
		return $ret;
	}
	
	public static function getTopGuild($limit)
	{
		Logger::trace('Guild::getTopGuild Start.');
	
		$arrCond = array(array(GuildDef::GUILD_ID, '>', 0));
		$arrField = array(GuildDef::GUILD_ID, GuildDef::GUILD_NAME);
		$guildList = GuildDao::getGuildList($arrCond, $arrField, 0, $limit);
		$guildList = Util::arrayIndex($guildList, GuildDef::GUILD_ID);
		$rank = 1;
		foreach ($guildList as $guildId => $guildInfo)
		{
			//获得团长信息
			$arrCond = array(
					array(GuildDef::GUILD_ID, '=', $guildId),
					array(GuildDef::MEMBER_TYPE, '=', GuildMemberType::PRESIDENT)
			);
			$presidentInfo = GuildDao::getMember($arrCond);
			$uid = $presidentInfo[0][GuildDef::USER_ID];
			$user = EnUser::getUserObj($uid);
			$guildInfo[GuildDef::LEADER_UID] = $uid;
			$guildInfo[GuildDef::LEADER_NAME] = $user->getUname();
			$guildInfo['rank'] = $rank++;
		}
	
		Logger::trace('Guild::getTopGuild End.');
		return $guildList;
	}
	
	public static function refreshUser($uid, $guildId, $join)
	{
		Logger::trace('Guild::refreshUser Start.');
		
		if($uid <= 0)
		{
			throw new FakeException('Invalid uid:%d', $uid);
		}
		
		//如果用户不在线，就设置一下session,伪装自己在当前的用户连接中
		$guid = RPCContext::getInstance()->getSession('global.uid');
		if($guid == null)
		{
			RPCContext::getInstance()->setSession('global.uid', $uid);
		}
		else if($uid != $guid)
		{
			Logger::fatal('Guild refreshUser, uid:%d, guid:%d', $uid, $guid);
			return;
		}

		GuildLogic::refreshUser($uid, $guildId, $join);
		
		Logger::trace('Guild::refreshUser End.');
	}
	
    public function guildDataRefresh($uid, $atkUid, $atkTimes, $beAtkTimes, $isSuc, $replayId)
    {
        Logger::trace('Guild::guildDataRefresh Start.');

        if($uid <= 0)
        {
            throw new FakeException('Invalid uid:%d', $uid);
        }

        //如果用户不在线，就设置一下session,伪装自己在当前的用户连接中
        $guid = RPCContext::getInstance()->getSession('global.uid');
        if($guid == null)
        {
            RPCContext::getInstance()->setSession('global.uid', $uid);
        }
        else if($uid != $guid)
        {
            Logger::fatal('Guild atkUserByOther, uid:%d, guid:%d', $uid, $guid);
            return;
        }

        GuildLogic::atkUserByOther($uid, $atkUid, $atkTimes, $beAtkTimes, $isSuc, $replayId);

        Logger::trace('Guild::guildDataRefresh End.');
    }
    
    public function addUserPoint($uid, $point)
    {
    	Logger::trace('Guild::addUserPoint Start.');
    
    	if($uid <= 0)
    	{
    		throw new FakeException('Invalid uid:%d', $uid);
    	}
    
    	//如果用户不在线，就设置一下session,伪装自己在当前的用户连接中
    	$guid = RPCContext::getInstance()->getSession('global.uid');
    	if($guid == null)
    	{
    		RPCContext::getInstance()->setSession('global.uid', $uid);
    	}
    	else if($uid != $guid)
    	{
    		Logger::fatal('Guild addUserPoint, uid:%d, guid:%d', $uid, $guid);
    		return;
    	}
    
    	GuildLogic::addUserPoint($uid, $point);
    
    	Logger::trace('Guild::addUserPoint End.');
    }
    
    public function refreshFields($uid, $uname, $addRefreshNum, $type)
    {
    	Logger::trace('Guild::refreshFields Start.');
    	
    	if($uid <= 0)
    	{
    		throw new FakeException('Invalid uid:%d', $uid);
    	}
    	
    	//如果用户不在线，就设置一下session,伪装自己在当前的用户连接中
    	$guid = RPCContext::getInstance()->getSession('global.uid');
    	if($guid == null)
    	{
    		RPCContext::getInstance()->setSession('global.uid', $uid);
    	}
    	else if($uid != $guid)
    	{
    		Logger::fatal('Guild addUserPoint, uid:%d, guid:%d', $uid, $guid);
    		return;
    	}
    	
    	GuildLogic::refreshFields($uid, $uname, $addRefreshNum, $type);
    	
    	Logger::trace('Guild::refreshFields End.');
    }
    
    public function distributeGrain($uid, $memberType, $share)
    {
    	Logger::trace('Guild::distributeGrain Start.');
    	 
    	if($uid <= 0)
    	{
    		throw new FakeException('Invalid uid:%d', $uid);
    	}
    	 
    	//如果用户不在线，就设置一下session,伪装自己在当前的用户连接中
    	$guid = RPCContext::getInstance()->getSession('global.uid');
    	if($guid == null)
    	{
    		RPCContext::getInstance()->setSession('global.uid', $uid);
    	}
    	else if($uid != $guid)
    	{
    		Logger::fatal('Guild addUserPoint, uid:%d, guid:%d', $uid, $guid);
    		return;
    	}
    	 
    	MailTemplate::distributeGrain($uid, $memberType, $share);
    	 
    	Logger::trace('Guild::distributeGrain End.');
    }
    
    public function initLevelUpInfo($guildId, $type)
    {
        GuildLogic::initLevelUpInfo($guildId, $type);
    }
    
    public static function refreshGuildName($uid, $guildName)
    {
    	Logger::trace('Guild::refreshGuildName Start.');
    
    	if($uid <= 0)
    	{
    		throw new FakeException('Invalid uid:%d', $uid);
    	}
    
    	//如果用户不在线就直接返回
    	$guid = RPCContext::getInstance()->getSession('global.uid');
    	if($guid == null)
    	{
    		return;
    	}
    	else if($uid != $guid)
    	{
    		Logger::fatal('Guild refreshGuildName, uid:%d, guid:%d', $uid, $guid);
    		return;
    	}
    	
    	GuildLogic::refreshGuildName($guildName);
    
    	Logger::trace('Guild::refreshGuildName End.');
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */