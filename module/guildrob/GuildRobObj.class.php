<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRobObj.class.php 149431 2014-12-27 02:34:27Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/GuildRobObj.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-12-27 02:34:27 +0000 (Sat, 27 Dec 2014) $
 * @version $Revision: 149431 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : GuildRobObj
* Description : 军团抢粮战之攻击数据管理类
* Inherit     :
**********************************************************************************************************************/
class GuildRobObj
{
	private static $sArrInstance = array();
	private $mObj = array();
	private $mObjModify = array();
	private $mLocker;

	/**
	 * getInstance 获取用户实例
	 *
	 * @param int $uid 用户id
	 * @static
	 * @access public
	 * @return GuildRobObj
	 */
	public static function getInstance($guildId)
	{
		if ($guildId == 0) 
		{
			$guildId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);
			if ($guildId == null) 
			{
				throw new FakeException('guildId and global.guildBarnBattleId are 0');
			}
		}

		if (!isset(self::$sArrInstance[$guildId]))
		{
			self::$sArrInstance[$guildId] = new GuildRobObj($guildId);
		}

		return self::$sArrInstance[$guildId];
	}

	public static function releaseInstance($guildId)
	{
		if ($guildId == 0) 
		{
			$guildId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);
			if ($guildId == null) 
			{
				throw new FakeException('guildId and global.guildBarnBattleId are 0');
			}
		}

		if (isset(self::$sArrInstance[$guildId]))
		{
			unset(self::$sArrInstance[$guildId]);
		}
	}
	
	public static function addRobGrain($robId, $robGrain)
	{
		if ($robGrain == 0)
		{
			return;
		}
		
		if ($robGrain > 0)
		{
			$opGrain = new IncOperator($robGrain);
		}
		else
		{
			$opGrain = new DecOperator(-$robGrain);
		}
		
		$arrCond = array
			(
				array(GuildRobField::TBL_FIELD_GUILD_ID, '=', $robId),
			);
		if ($robGrain < 0) 
		{
			$arrCond[] = array(GuildRobField::TBL_FIELD_TATAL_ROB_NUM, '>=', -$robGrain);
		}
		
		$arrField = array
			(
				GuildRobField::TBL_FIELD_TATAL_ROB_NUM => $opGrain,
			);
		GuildRobDao::updateRob($arrCond, $arrField);
	}
	
	private function __construct($guildId)
	{
		$this->mObj = $this->getRobInfo($guildId);
		if (empty($this->mObj)) 
		{
			$this->mObj = $this->createRobInfo($guildId);
		}
		$this->mObjModify = $this->mObj;
	}

	public function getRobInfo($guildId)
	{
		$arrCond = array
		(
				array(GuildRobField::TBL_FIELD_GUILD_ID, '=', $guildId),
		);
		$arrBody = GuildRobField::$GUILD_ROB_ALL_FIELDS;

		return GuildRobDao::selectRob($arrCond, $arrBody);
	}
	
	public function createRobInfo($guildId)
	{
		$arrField = array
		(
				GuildRobField::TBL_FIELD_GUILD_ID => $guildId,
				GuildRobField::TBL_FIELD_DEFEND_GUILD_ID => 0,
				GuildRobField::TBL_FIELD_START_TIME => 0,
				GuildRobField::TBL_FIELD_END_TIME => 0,
				GuildRobField::TBL_FIELD_STAGE => GuildRobField::GUILD_ROB_STAGE_INIT,
				GuildRobField::TBL_FIELD_TATAL_ROB_NUM => 0,
				GuildRobField::TBL_FIELD_ROB_LIMIT => 0,
				GuildRobField::TBL_FIELD_VA_EXTRA => array
				(
						GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN => array(),
				),
		);
	
		GuildRobDao::insertRob($arrField);
	
		return $arrField;
	}
	
	public function start($startTime, $defendGuildId, $canRobGrain)
	{
		$this->resetAllField();
		$this->mObjModify[GuildRobField::TBL_FIELD_START_TIME] = $startTime;
		$this->mObjModify[GuildRobField::TBL_FIELD_DEFEND_GUILD_ID] = $defendGuildId;
		$this->mObjModify[GuildRobField::TBL_FIELD_ROB_LIMIT] = $canRobGrain;
		$this->setStage(GuildRobField::GUILD_ROB_STAGE_START);
		
		// 在memcache中设置抢粮军团的last_defend_time为0
		$attackGuildId = $this->getGuildId();
		GuildRobUtil::setLastDefendTime($attackGuildId, 0);
		
		// 在memcache中设置被抢军团的last_attack_time为0
		GuildRobUtil::setLastAttackTime($defendGuildId, 0);
	}
	
	public function end()
	{
		// 处理蹲点粮仓上的玩家
		//$this->endSpecBarn(); // 暂时不开放蹲点粮仓功能
		
		// 抢粮战结束，置标志
		$this->setStage(GuildRobField::GUILD_ROB_STAGE_END);
		
		// 修改当前抢粮战结束时间
		$this->mObjModify[GuildRobField::TBL_FIELD_END_TIME] = Util::getTime();
		
		// 同步粮草，从被抢军团到抢夺军团
		if ($this->syncRobGrain()) 
		{
			$this->setStage(GuildRobField::GUILD_ROB_STAGE_SYNC);
			Logger::trace('rob battle end[%d]: sync grain ok', $this->getGuildId());
		}
		
		// 调用EnGuild接口, 记录这次抢夺战
		$attackGuildId = $this->getGuildId();
		$defendGuildId = $this->getDefendGid();
		$robGrain = $this->getTotalRobGrain();
		EnGuild::robGuildByOther($attackGuildId, $defendGuildId, $robGrain);
		
		// 在memcache中设置抢夺军团的last_attack_time
		GuildRobUtil::setLastAttackTime($attackGuildId, Util::getTime());
		
		// 在memcache中设置被抢军团的last_defend_time
		GuildRobUtil::setLastDefendTime($defendGuildId, Util::getTime());
	}
	
	public function directEnd()
	{
		$this->setStage(GuildRobField::GUILD_ROB_STAGE_REWARD);
	}
	
	public function isEnd()
	{
		$defendGuildId = $this->getDefendGid();
		$startTime = $this->getStartTime();
		$endTime = $this->getEndTime();
		$stage = $this->getStage();
		
		if ($defendGuildId != 0
			&& $startTime > 0 
			&& $endTime > $startTime 
			&& $stage == GuildRobField::GUILD_ROB_STAGE_REWARD) 
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function isFighting()
	{
		$totalBattleTime = intval(btstore_get()->GUILD_ROB['battle_time']) + intval(btstore_get()->GUILD_ROB['ready_time']);
		$defendGuildId = $this->getDefendGid();
		$startTime = $this->getStartTime();
		$endTime = $this->getEndTime();
		$stage = $this->getStage();
		
		if ($defendGuildId != 0
			&& $startTime != 0
			&& $endTime == 0
			&& (Util::getTime() - $startTime) < $totalBattleTime
			&& $stage == GuildRobField::GUILD_ROB_STAGE_START) 
		{
			return TRUE;
		}
		
		return FALSE;
	}
	
	public function getRobDuration()
	{
		if ($this->getStartTime() > 0
			&& $this->getStage() >= GuildRobField::GUILD_ROB_STAGE_END
			&& $this->getEndTime() > ($this->getStartTime() + intval(btstore_get()->GUILD_ROB['ready_time'])))
		{
			return $this->getEndTime() - $this->getStartTime() - intval(btstore_get()->GUILD_ROB['ready_time']);
		}
		
		return 0;
	}
	
	public function resetAllField()
	{
		$this->mObjModify[GuildRobField::TBL_FIELD_DEFEND_GUILD_ID] = 0;
		$this->mObjModify[GuildRobField::TBL_FIELD_START_TIME] = 0;
		$this->mObjModify[GuildRobField::TBL_FIELD_END_TIME] = 0;
		$this->mObjModify[GuildRobField::TBL_FIELD_STAGE] = GuildRobField::GUILD_ROB_STAGE_INIT;
		$this->mObjModify[GuildRobField::TBL_FIELD_TATAL_ROB_NUM] = 0;
		$this->mObjModify[GuildRobField::TBL_FIELD_ROB_LIMIT] = 0;
		$this->mObjModify[GuildRobField::TBL_FIELD_VA_EXTRA] = array
				(
						GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN => array(),
				);
	}
	
	public function getGuildId()
	{
		return $this->mObjModify[GuildRobField::TBL_FIELD_GUILD_ID];
	}
	
	public function getDefendGid()
	{
		return $this->mObjModify[GuildRobField::TBL_FIELD_DEFEND_GUILD_ID];
	}
	
	public function getStartTime()
	{
		return $this->mObjModify[GuildRobField::TBL_FIELD_START_TIME];
	}
	
	public function getEndTime()
	{
		return $this->mObjModify[GuildRobField::TBL_FIELD_END_TIME];
	}
	
	public function getStage()
	{
		return $this->mObjModify[GuildRobField::TBL_FIELD_STAGE];
	}
	
	public function setStage($stage)
	{
		$this->mObjModify[GuildRobField::TBL_FIELD_STAGE] = $stage;
	}
	
	public function getTotalRobGrain()
	{
		return $this->mObjModify[GuildRobField::TBL_FIELD_TATAL_ROB_NUM];
	}
	
	public function addTotalRobGrain($num)
	{
		$this->mObjModify[GuildRobField::TBL_FIELD_TATAL_ROB_NUM] += $num;
	}
	
	public function getRobLimit()
	{
		return $this->mObjModify[GuildRobField::TBL_FIELD_ROB_LIMIT];
	}
	
	public function syncRobGrain()
	{
		return TRUE; // 现在达阵直接结算粮草，不需要最后的同步
		
		/*if ($this->getStage() != GuildRobField::GUILD_ROB_STAGE_END) 
		{
			return FALSE;
		}

		// 获得抢夺的粮草
		$robGrain = $this->getTotalRobGrain();
		if ($robGrain > 0) 
		{
			GuildObj::addGuildGrainNum($this->getDefendGid(), -$robGrain);
			GuildObj::addGuildGrainNum($this->getGuildId(), $robGrain);
		}
		
		return TRUE;*/
	}
	
	public function sendReckonMsgWhenEnd($robDuration)
	{
		if ($this->getStage() < GuildRobField::GUILD_ROB_STAGE_END)
		{
			return FALSE;
		}
		
		// 给所有用户发送结算数据
		$rank = 0;
		$userList = GuildRobDao::getKillTopN($this->getGuildId());
		$userList = Util::arrayIndex($userList, GuildRobUserField::TBL_FIELD_UID);
		
		// 获得军团在抢粮战中获得的粮草奖励
		$guildReward = array();
		foreach($userList as $uid => $userInfo)
		{
			$guildId = $userInfo[GuildRobUserField::TBL_FIELD_GUILD_ID];
			$guildGrain = $userInfo[GuildRobUserField::TBL_FIELD_GUILD_GRAIN_NUM];
			
			if (!isset($guildReward[$guildId])) 
			{
				$guildReward[$guildId] = 0;
			}
			
			$guildReward[$guildId] += $guildGrain;
		}
		
		// 遍历发送结算消息
		foreach($userList as $uid => $userInfo)
		{
			$guildId = $userInfo[GuildRobUserField::TBL_FIELD_GUILD_ID];
			
			$rank++;
			$endMsg = array
			(
					'rank' => $rank,
					'kill' => $userInfo[GuildRobUserField::TBL_FIELD_KILL_NUM],
					'merit' => $userInfo[GuildRobUserField::TBL_FIELD_MERIT_NUM],
					'contr' => $userInfo[GuildRobUserField::TBL_FIELD_CONTR_NUM],
					'userGrain' => $userInfo[GuildRobUserField::TBL_FIELD_USER_GRAIN_NUM],
					'guildGrain' => $guildReward[$guildId],
					'duration' => $robDuration,
			);
				
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_RECKON, $endMsg);
			Logger::trace('rob battle end[%d]: send reckon msg for user[%d], msg:%s', $this->getGuildId(), $uid, $endMsg);
		}
		
		return $userList;
	}
	
	public function sendMailWhenEnd($userList)
	{
		if ($this->getStage() < GuildRobField::GUILD_ROB_STAGE_REWARD)
		{
			return FALSE;
		}
		
		$attackGuildId = $this->getGuildId();
		$defendGuildId = $this->getDefendGid();
		$attackGuildName = GuildObj::getInstance($attackGuildId)->getGuildName();
		$defendGuildName = GuildObj::getInstance($defendGuildId)->getGuildName();
		$robId = $attackGuildId;
		
		// 获得军团在抢粮战中获得的粮草奖励
		$guildReward = array();
		foreach($userList as $uid => $userInfo)
		{
			$guildId = $userInfo[GuildRobUserField::TBL_FIELD_GUILD_ID];
			$guildGrain = $userInfo[GuildRobUserField::TBL_FIELD_GUILD_GRAIN_NUM];
				
			if (!isset($guildReward[$guildId]))
			{
				$guildReward[$guildId] = 0;
			}
				
			$guildReward[$guildId] += $guildGrain;
		}
		
		$arrAttackUid = EnGuild::getMemberList($attackGuildId, array(GuildDef::USER_ID));
		foreach ($arrAttackUid as $aUid => $aInfo)
		{
			if (isset($userList[$aUid])) 
			{
				$userInfo = $userList[$aUid];
				$grainNum = $userInfo[GuildRobUserField::TBL_FIELD_USER_GRAIN_NUM];
				$meritNum = $userInfo[GuildRobUserField::TBL_FIELD_MERIT_NUM];
				$guildId = $userInfo[GuildRobUserField::TBL_FIELD_GUILD_ID];
				$guildGainGrainNum = $guildReward[$guildId];
				
				MailTemplate::endGuildRobRobber($aUid, $defendGuildName, $grainNum, $meritNum, $guildGainGrainNum);
			}
		}
		Logger::trace('rob battle end[%d]: send mail end for attacker', $robId);
	
		$arrDefendUid = EnGuild::getMemberList($defendGuildId, array(GuildDef::USER_ID));
		foreach ($arrDefendUid as $aUid => $aInfo)
		{
			if (isset($userList[$aUid]))
			{
				MailTemplate::endGuildRobLamp($aUid, $attackGuildName, $this->getTotalRobGrain());
			}
		}
		Logger::trace('rob battle end[%d]: send mail end for defender', $robId);
		
		return TRUE;
	}
	
	public function lock($lockType)
	{
		list($smallGuildId, $bigGuildId) = $this->getLockKey();
		$key1 = "guild.rob.$lockType.$smallGuildId";
		$key2 = "guild.rob.$lockType.$bigGuildId";
		
		if (empty($this->mLocker)) 
		{
			$this->mLocker = new Locker();
		}
		
		$this->mLocker->lock($key1);
		$this->mLocker->lock($key2);
	}
	
	public function unlock($lockType)
	{
		list($smallGuildId, $bigGuildId) = $this->getLockKey();
		$key1 = "guild.rob.$lockType.$smallGuildId";
		$key2 = "guild.rob.$lockType.$bigGuildId";
		
		if (empty($this->mLocker))
		{
			$this->mLocker = new Locker();
		}
		
		$this->mLocker->unlock($key1);
		$this->mLocker->unlock($key2);
	}
	
	private function getLockKey()
	{
		$attackGuildId = $this->getGuildId();
		$defendGuildId = $this->getDefendGid();
		
		$smallGuildId = $attackGuildId;
		$bigGuildId = $defendGuildId;
		if ($attackGuildId > $defendGuildId)
		{
			$smallGuildId = $defendGuildId;
			$bigGuildId = $attackGuildId;
		}
		
		return array($smallGuildId, $bigGuildId);
	}
	
	public function isInSpecBarn($uid)
	{
		for ($pos = 0; $pos < GuildRobConf::SPEC_BARN_MAX_NUM; ++$pos)
		{
			$info = $this->getSpecBarn($pos);
			if (!empty($info) && $info[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UID] == $uid) 
			{
				return $pos;
			}
		}
		
		return FALSE;
	}
	
	public function getSpecBarn($pos)
	{
		if (!isset($this->mObjModify[GuildRobField::TBL_FIELD_VA_EXTRA][GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN][$pos])) 
		{
			return array();
		}
		return $this->mObjModify[GuildRobField::TBL_FIELD_VA_EXTRA][GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN][$pos];
	}
	
	public function makeSpecBarnEmpty($pos)
	{
		$this->mObjModify[GuildRobField::TBL_FIELD_VA_EXTRA][GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN][$pos] = array();
	}
	
	public function updateSpecBarnArrHp($pos, $info)
	{
		$this->mObjModify[GuildRobField::TBL_FIELD_VA_EXTRA][GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN][$pos][GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_ARRHP] = $info;
	}
	
	public function addSpecBarnArrWinStreak($pos)
	{
		return ++$this->mObjModify[GuildRobField::TBL_FIELD_VA_EXTRA][GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN][$pos][GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_WIN_STREAK];
	}
	
	public function enterSpecBarn($pos, $uid, $guildId, $maxHp, $arrHeroHp)
	{
		$usrObj = EnUser::getUserObj($uid);
		$heroMgr = $usrObj->getHeroManager();
		
		$info = array
			(
				GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UID => $uid, 
				GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UNAME => $usrObj->getUname(),
				GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_TID => intval($heroMgr->getMasterHeroObj()->getHtid()),
				GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_GUILD_ID => $guildId,
				GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_BEGIN => Util::getTime(),
				GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_MAXHP => $maxHp,
				GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_ARRHP => $arrHeroHp,
				GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_WIN_STREAK => 0,
			);
		$this->mObjModify[GuildRobField::TBL_FIELD_VA_EXTRA][GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN][$pos] = $info;
	}
	
	public function genSpecBarnInfo($needAllField = TRUE, $filter = array())
	{
		$userInfo = array();
		
		for ($pos = 0; $pos < GuildRobConf::SPEC_BARN_MAX_NUM; ++$pos)
		{
			if (!empty($filter) && !in_array($pos, $filter)) 
			{
				continue;
			}
			
			$info = $this->getSpecBarn($pos);
			if (empty($info)) 
			{
				continue;
			}
			
			$uid = $info[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UID];
			
			$currHp = 0;
			$arrHp = $info[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_ARRHP];
			foreach ($arrHp as $hid => $hp)
			{
				$currHp += $hp;
			}
			
			$aSpecBarnInfo = array();
			if ($needAllField) 
			{
				$aSpecBarnInfo['name'] = $info[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UNAME];
				$aSpecBarnInfo['tid'] = $info[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_TID];
				$aSpecBarnInfo['guildId'] = $info[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_GUILD_ID];
				$aSpecBarnInfo['specId'] = $pos;
				$aSpecBarnInfo['maxHp'] = $info[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_MAXHP];
				$aSpecBarnInfo['endTime'] = $info[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_BEGIN] + intval(btstore_get()->GUILD_ROB['spec_barn_time_limit']);
			}
			$aSpecBarnInfo['curHp'] = $currHp;
			$aSpecBarnInfo['winStreak'] = $info[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_WIN_STREAK];
			
			
			$userInfo[$uid] = $aSpecBarnInfo;
		}
		
		return array('userInfo' => $userInfo);
	}
	
	public function leaveSpecBarn($uid)
	{
		return; // 暂时不开放蹲点粮仓功能
		
		Logger::trace("GuildRobObj::leaveSpecBarn uid[%d]: begin...", $uid);
		
		$robId = $this->getGuildId();
		$pos = $this->isInSpecBarn($uid);
		if ($pos !== FALSE)
		{
			Logger::trace("GuildRobObj::leaveSpecBarn uid[%d] leave rob battle[%d] from spec barn[%d]: in spec barn, need leave", $uid, $robId, $pos);
			
			// 获取奖励
			$rewardArr = GuildRobLogic::rewardWhenSpecBarnTimeOut($this->getGuildId(), $pos, $uid, FALSE);
		
			// 将蹲点粮仓数据置空
			$this->makeSpecBarnEmpty($pos);
		
			// 设置session
			$proxy = new PHPProxy ('lcserver');
			$userData = array('leaveBattleTime' => Util::getTime(), 'quitBattleTime' => Util::getTime());
			$ret = $proxy->setGroupBattleUserInfo($uid, $robId, $userData);
			Logger::trace("GuildRobObj::leaveSpecBarn uid[%d] leave rob battle[%d] from spec barn[%d]: reset user info of lcserver:%s, ret:%s", $uid, $robId, $pos, $userData, $ret);
		
			// 广播该蹲点粮仓离开信息
			$broadcastInfo = array
			(
					'outSpecId' => $pos,
			);
			RPCContext::getInstance()->broadcastGroupBattle($robId, $broadcastInfo, PushInterfaceDef::GUILD_ROB_SPEC);
			Logger::trace("GuildRobObj::leaveSpecBarn uid[%d] leave rob battle[%d] from spec barn[%d]: broadcast when leave ok, broadcastInfo:%s", $uid, $robId, $pos, $broadcastInfo);
		
			// 为该玩家单独发送消息，包括cd等
			$infoForCurrUser = array
			(
					'outSpecId' => $pos,
					'joinCd' => Util::getTime() + intval(btstore_get()->GUILD_ROB['join_cd']),
					'reward' => $rewardArr,
			);
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_SPEC, $infoForCurrUser);
			Logger::trace("GuildRobObj::leaveSpecBarn uid[%d] leave rob battle[%d] from spec barn[%d]: send info from curr user:%s when spec time out", $uid, $robId, $pos, $infoForCurrUser);
		}
		else 
		{
			Logger::trace("GuildRobObj::leaveSpecBarn uid[%d] leave rob battle[%d] from spec barn[%d]: not in spec barn, do not need leave", $uid, $robId, $pos);
		}
		
		Logger::trace("GuildRobObj::leaveSpecBarn uid[%d]: end...", $uid);
		return $pos;
	}
	
	public function endSpecBarn()
	{
		return; // 暂时不开放蹲点粮仓功能
		
		Logger::trace("GuildRobObj::endSpecBarn begin...");
		
		$ret = FALSE;
		for ($pos = 0; $pos < GuildRobConf::SPEC_BARN_MAX_NUM; ++$pos)
		{
			$specBarnInfo = $this->getSpecBarn($pos);
			if (!empty($specBarnInfo))
			{
				// 给奖励
				$uid = $specBarnInfo[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UID];
				$rewardArr = GuildRobLogic::rewardWhenSpecBarnTimeOut($this->getGuildId(), $pos, $uid, FALSE);
				Logger::trace("GuildRobObj::endSpecBarn uid[%d] in spec barn[%d] leave when battle", $uid, $pos);
		
				// 将蹲点粮仓数据置空
				$this->makeSpecBarnEmpty($pos);
				
				// 不需要设置session，因为抢粮战已经结束啦
					
				// 广播该蹲点粮仓离开信息
				$broadcastInfo = array
				(
						'outSpecId' => $pos,
				);
				RPCContext::getInstance()->broadcastGroupBattle($this->getGuildId(), $broadcastInfo, PushInterfaceDef::GUILD_ROB_SPEC);
		
				// 为该玩家单独发送消息，包括cd等
				$infoForCurrUser = array
				(
						'outSpecId' => $pos,
						'joinCd' => Util::getTime() + intval(btstore_get()->GUILD_ROB['join_cd']),
						'reward' => $rewardArr,
				);
				RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_SPEC, $infoForCurrUser);
				
				$ret = TRUE;
			}
		}
		
		Logger::trace("GuildRobObj::endSpecBarn end...");
		return $ret;
	} 
	
	public function update()
	{
		$arrField = array();
		foreach ($this->mObj as $key => $value)
		{
			if ($this->mObjModify[$key] != $value)
			{
				$arrField[$key] = $this->mObjModify[$key];
			}
		}

		if (empty($arrField))
		{
			Logger::debug('update GuildRobObj : no change');
			return;
		}
		
		Logger::debug("update GuildRobObj rob id:%d attack guild:%d defend guild:%d changed field:%s", $this->getGuildId(), $this->getGuildId(), $this->getDefendGid(), $arrField);
	
		$arrCond = array
		(
				array(GuildRobField::TBL_FIELD_GUILD_ID, '=', $this->getGuildId()),
		);
		GuildRobDao::updateRob($arrCond, $arrField);
	
		$this->mObj = $this->mObjModify;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */