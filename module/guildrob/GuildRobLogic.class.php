<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRobLogic.class.php 261386 2016-09-08 09:00:40Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/GuildRobLogic.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-09-08 09:00:40 +0000 (Thu, 08 Sep 2016) $
 * @version $Revision: 261386 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : GuildRobLogic
* Description : 军团抢粮战逻辑实现类
* Inherit     :
**********************************************************************************************************************/
class GuildRobLogic
{
	/*****************************************************************************************************************
	 * 
	 * 以下是抢粮战相关接口
	 * 
	 *****************************************************************************************************************/
	public static function create($uid, $defendGuildId)
	{
		Logger::trace('GuildRobLogic::create begin...');
		
		// 检查基本配置,是否在抢粮时间内，是否属于一个军团，是否有权限
		$attackGuildId = GuildRobUtil::checkBasic($uid, GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_CREATE);
		if (FALSE == $attackGuildId) 
		{
			throw new FakeException("uid[%d] can not create rob battle to guild[%d], because checkBasic is error", $uid, $defendGuildId);
		}
		Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:checkBasic is ok", $uid, $attackGuildId, $defendGuildId);
		
		// 如果抢夺军团和被抢军团是同一个军团
		if ($attackGuildId === $defendGuildId) 
		{
			throw new FakeException("uid[%d] guild[%d] can not create rob battle to guild[%d], because in same guild", $uid, $attackGuildId, $defendGuildId);
		}
		Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:diff guild, ok", $uid, $attackGuildId, $defendGuildId);
		
		
		// 给抢粮军团和被抢军团加锁，特别注意，为了防止死锁的产生，必须将要锁的军团id按从小到大排序，依次进行加锁，
		$smallGuildId = $attackGuildId;
		$bigGuildId = $defendGuildId;
		if ($attackGuildId > $defendGuildId) 
		{
			$smallGuildId = $defendGuildId;
			$bigGuildId = $attackGuildId;
		}
		
		$key1 = "guild.rob.create.$smallGuildId";
		$key2 = "guild.rob.create.$bigGuildId";
		$locker = new Locker();
		$locker->lock($key1);
		$locker->lock($key2);
		Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:lock is ok", $uid, $attackGuildId, $defendGuildId);
		
		try 
		{
			// 提前获取这两个obj
			$attackGuildObj = GuildObj::getInstance($attackGuildId, array(GuildDef::FIGHT_BOOK));
			$defendGuildObj = GuildObj::getInstance($defendGuildId);
			
			// 检查用户所在军团是否可以抢夺这个军团，如果不能，返回错误原因
			// 检查抢粮军团今天抢粮次数是否已到，军团是否有足够的战书数量，被抢粮军团剩余粮草是否高于可抢最低量
			// 检查者两个军团是否正处于别的抢粮战中，是不是在抢劫别的军团，或者被别的军团抢劫
			$canRobGrain = 0;
			$ret = GuildRobUtil::canRob($attackGuildId, $defendGuildId, $canRobGrain);
			if (GuildRobCreateRet::GUILD_ROB_CREATE_RET_OK !==  $ret) 
			{
				$locker->unlock($key1);
				$locker->unlock($key2);
				
				Logger::warning("guild[%d] can not create rob to guild[%d], because %s", $attackGuildId, $defendGuildId, $ret);
				return $ret;
			}
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:canRob is ok, can rob grain[%d]", $uid, $attackGuildId, $defendGuildId, $canRobGrain);
			
			// 抢粮军团ID做为robId
			$robId = $attackGuildId;
				
			// 所有检查完毕，通知lcserver开启战斗
			$proxy = new PHPProxy ('lcserver');
			//$startTime = Util::getTime() + GuildRobConf::ROB_BATTLE_BEGIN_OFFSET;
			$startTime = Util::getTime();
			$robConfig = GuildRobUtil::getRobConfig($attackGuildId, $defendGuildId, $canRobGrain);
			$ret = $proxy->createGroupBattle($robId, $startTime, $robConfig);
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:notify lcserver to createGroupBattle ok, param : robId[%d], startTime[%d], config[%s] ret : %d", $uid, $attackGuildId, $defendGuildId, $robId, $startTime, $robConfig, $ret);
			
			if ($ret != $robId) 
			{	
				throw new FakeException("uid[%d] can not create rob battle to guild[%d], because lcserver create rob battle error[%s]", $uid, $defendGuildId, $ret);
			}
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:lcserver createGroupBattle return, ok", $uid, $attackGuildId, $defendGuildId);
			
			// 更新进攻军团信息,增加今天的攻击次数,减少战书数量
			try
			{
				$attackGuildObj = GuildObj::getInstance($attackGuildId);
				$attackGuildObj->addAttackNum(1);
				$attackGuildObj->subFightBook(1);
				$attackGuildObj->update();
				$attackGuildObj->unlockArrField();
			}
			catch (Exception $e)
			{
				$attackGuildObj->unlockArrField();
				throw $e;
			}
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:update attack guild attackNum and fightBookNum ok", $uid, $attackGuildId, $defendGuildId);
			
			// 更新防守军团防守信息，增加今天的防守次数
			$defendGuildObj = GuildObj::getInstance($defendGuildId);
			$defendGuildObj->addDefendNum(1);
			$defendGuildObj->update();
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:update defend guild defendNum ok", $uid, $attackGuildId, $defendGuildId);	
			
			// 弄个定时器，当这场战斗结束了以后，过个几秒钟去检查一下数据库，是不是真的结束了
			$readyDuration = intval(btstore_get()->GUILD_ROB['ready_time']);
			$battleDuration = intval(btstore_get()->GUILD_ROB['battle_time']);
			$checkTime = $startTime + $readyDuration + $battleDuration + GuildRobConf::GUILD_ROB_END_CHECK_OFFSET;
			RPCContext::getInstance()->addTimer(SPECIAL_UID::GUILD_ROB, $checkTime, 'guildrob.onBattleEndByRpcfw', array($attackGuildId, $defendGuildId, $startTime), FALSE);
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:add timer ok", $uid, $attackGuildId, $defendGuildId);
			
			// 更新robObj
			$robObj = GuildRobObj::getInstance($attackGuildId);
			$robObj->start($startTime, $defendGuildId, $canRobGrain);
			$robObj->update();
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:update robObj ok", $uid, $attackGuildId, $defendGuildId);
			
			// 更新robUserObj
			$robUserObj = GuildRobUserObj::getInstance($uid);
			$robUserObj->start($robId, $attackGuildId);
			$robUserObj->update();
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:update robUserObj ok", $uid, $attackGuildId, $defendGuildId);
			
			/////////////////////////////////////////////////////////////
			// 通知平台和发送邮件相关操作，全部抛到main机器上执行
			////////////////////////////////////////////////////////////
			Util::asyncExecute('guildrob.robNotice', array($attackGuildId, $defendGuildId, $readyDuration));
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:throw to main machine, send mail and notify platform ok", $uid, $attackGuildId, $defendGuildId);			
			
			// 更新抢粮战场这两个军团的状态信息
			GuildRobUtil::guildRobInfoChanged($attackGuildId, TRUE, $robId);
			GuildRobUtil::guildRobInfoChanged($defendGuildId, TRUE, $robId);
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:update front guild info ok", $uid, $attackGuildId, $defendGuildId);
			
			// 解锁
			$locker->unlock($key1);
			$locker->unlock($key2);
			Logger::trace("uid[%d] guild[%d] create rob battle to guild[%d]:unlock is ok", $uid, $attackGuildId, $defendGuildId);
	
			Logger::trace('GuildRobLogic::create rob battle success, robId[%d]', $robId);
			return $robId;
		} 
		catch (Exception $e)
		{
			$locker->unlock($key1);
			$locker->unlock($key2);
			Logger::fatal("uid[%d] guild[%d] create rob battle to guild[%d] get exception:%s", $uid, $attackGuildId, $defendGuildId, $e->getMessage());
			throw $e;
		}
	}
	
	public static function enter($uid, $robId)
	{
		Logger::trace('GuildRobLogic::enter begin...');
		
		// 检查基本配置,是否在抢粮时间内，是否属于一个军团，是否有权限
		$currGuildId = GuildRobUtil::checkBasic($uid, GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_ENTER);
		if (FALSE == $currGuildId) 
		{
			throw new FakeException("uid[%d] can not enter rob battle[%d], because checkBasic is error", $uid, $robId);
		}
		Logger::trace("uid[%d] guild[%d] enter rob battle[%d]:checkBasic is right", $uid, $currGuildId, $robId);
		
		// 发起抢粮战的军团ID就是抢粮战ID
		$attackGuildId = $robId;
		$role = $currGuildId == $attackGuildId ? "attacker" : "defender";
		
		// 获取用户所在军团是否在这场抢粮战中
		$robObj = GuildRobObj::getInstance($robId);
		if (($currGuildId != $robObj->getGuildId() && $currGuildId != $robObj->getDefendGid())
			|| $robObj->getDefendGid() == 0)
		{
			throw new FakeException("uid[%d] guild[%d] enter rob battle[%d] error, nethor attack guild[%d] nor defend guild[%d]", $uid, $currGuildId, $robId, $robObj->getGuildId(), $robObj->getDefendGid());
		}
		Logger::trace("uid[%d] guild[%d] enter rob battle[%d]:as %s", $uid, $currGuildId, $robId, $role);
		
		// 判断是否在抢粮时间内
		if (FALSE === GuildRobUtil::checkRobTime($robId))
		{
			Logger::debug("uid[%d] guild[%d] enter rob battle[%d] error, because not in rob time, or stage not right", $uid, $currGuildId, $robId);
			return 'over';
		}
		Logger::trace("uid[%d] guild[%d] enter rob battle[%d] as %s:checkRobTime is ok", $uid, $currGuildId, $robId, $role);
		
		// 更新用户的抢粮战信息
		$robUserObj = GuildRobUserObj::getInstance($uid);
		if ($robUserObj->getRewardTime() > 0 
			|| $robUserObj->getRobId() == 0
			|| $robUserObj->getJoinTime() < $robObj->getStartTime()) 
		{
			$robUserObj->start($robId, $currGuildId);
			$robUserObj->update();
		}
		else if ($robUserObj->getRobId() != $robId) 
		{
			throw new InterException("uid[%d] guildId[%d] int robId[%d], but enter robId[%d]", $uid, $robUserObj->getGuildId(), $robUserObj->getRobId(), $robId);
		}
		Logger::trace("uid[%d] guild[%d] enter rob battle[%d] as %s:update robUserObj ok", $uid, $currGuildId, $robId, $role);
		
		// 调用lcserver进入战场，lcserver中给用户返回消息
		$usrObj = EnUser::getUserObj($uid);
		$heroMgr = $usrObj->getHeroManager();
		$userData = array
		(
				'uid' => $uid,
				'uname' => $usrObj->getUname(),
				'master_htid' => intval($heroMgr->getMasterHeroObj()->getHtid()),
				'groupId' => $currGuildId,
		);
		RPCContext::getInstance()->enterGroupBattle($robId, $userData);
		Logger::trace("uid[%d] guild[%d] enter rob battle[%d] as %s:notify lcserver to enterGroupBattle ok, param : robId[%d] userData[%s]", 
					  $uid, $currGuildId, $robId, $role, $robId, $userData);
		
		Logger::trace('GuildRobLogic::enter end...');
	}
	
	public static function getEnterInfo($uid)
	{
		Logger::trace('GuildRobLogic::getEnterInfo begin...');
		
		// 从session中获取抢粮战唯一ID
		$robId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);
		if ($robId <= 0)
		{
			throw new FakeException("uid[%d] do not have robId in session when getEnterInfo", $uid);
		}
		
		$attackGuildId = $robId;
		$currGuildId = GuildRobUtil::checkAll($uid, $robId, GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_GET_ENTER_INFO);
		$role = $currGuildId == $attackGuildId ? "attacker" : "defender";
		
		$robObj = GuildRobObj::getInstance($robId);
		$robUserObj = GuildRobUserObj::getInstance($uid);
		
		// 传给前端该玩家的一些信息，调用lcserver获取战场其他信息
		$userData = array
		(
				'info' => array // 玩家信息
				(
						'removeCdNum' => $robUserObj->getRemoveCdNum(),
						'speedUpNum' => $robUserObj->getSpeedUpNum(),
						'killNum' => $robUserObj->getKillNum(),
						'meritNum' => $robUserObj->getMeritNum(),
						'userGrainNum' => $robUserObj->getUserGrainNum(),
						'guildGrainNum' => $robUserObj->getGuildGrainNum(),
				),
				
		);
		
		// 蹲点粮仓信息，getEnterInfo的时候也需要
		$extraData = $robObj->genSpecBarnInfo();
		
		RPCContext::getInstance()->getGroupBattleEnterInfo($robId, $userData, $extraData);
		Logger::trace("uid[%d] guild[%d] getEnterInfo rob battle[%d] as %s:notify lcserver to getGroupBattleEnterInfo, param : robId[%d] userData[%s] extraData[%s]", $uid, $currGuildId, $robId, $role, $robId, $userData, $extraData);
		
		Logger::trace('GuildRobLogic::getEnterInfo end...');
	}

	public static function join($uid, $transferId)
	{
		Logger::trace('GuildRobLogic::join begin...');
		
		$robId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);
		if ($robId <= 0)
		{
			throw new FakeException("uid[%d] do not have robId in session when join", $uid);
		}
		
		$attackGuildId = $robId;
		$currGuildId = GuildRobUtil::checkAll($uid, $robId, GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_JOIN);
		$role = $currGuildId == $attackGuildId ? "attacker" : "defender";
		
		$robObj = GuildRobObj::getInstance($robId);
			
		// 该玩家如果在蹲点粮仓上，则不能加入战场
		$ret = $robObj->isInSpecBarn($uid);
		if ($ret !== FALSE)
		{
			Logger::trace("uid[%d] guild[%d] join rob battle[%d] error, because in spec barn[%d]", $uid, $currGuildId, $robId, $ret);
			return array ('ret' => 'in_spec_barn', 'spec_pos' => $ret);
		}
		Logger::trace("uid[%d] guild[%d] join rob battle[%d] as %s:not in spec barn, join is ok", $uid, $currGuildId, $robId, $role);
		
		$nowTime = Util::getTime ();
		
		// 判断等待时间
		$quitBattleTime = RPCContext::getInstance ()->getSession(GuildRobDef::SESSION_QUIT_BATTLE_TIME);
		$joinReadyTime = intval(btstore_get()->GUILD_ROB['join_cd']);
		$waitTime = $quitBattleTime + $joinReadyTime - $nowTime;
		if ($waitTime > 0)
		{
			Logger::info('uid:%d join failed: waitTime', $uid);
			return array ('ret' => 'waitTime', 'waitTime' => $waitTime);
		}
		Logger::trace("uid[%d] guild[%d] join rob battle[%d] as %s:wait time is ok", $uid, $currGuildId, $robId, $role);
		
		// 判断参战cd
		$leaveBattleTime = RPCContext::getInstance ()->getSession(GuildRobDef::SESSION_LEAVE_BATTLE_TIME);
		$joinCdTime = intval(btstore_get()->GUILD_ROB['join_cd']);
		$remainCd = $leaveBattleTime + $joinCdTime - $nowTime;
		if ($remainCd > 0)
		{
			Logger::info('uid:%d join failed: cdtime', $uid);
			return array ('ret' => 'cdtime', 'cdtime' => $remainCd);
		}
		Logger::trace("uid[%d] guild[%d] join rob battle[%d] as %s:cd time is ok", $uid, $currGuildId, $robId, $role);

		// 获得战斗数据
		$battleData = GuildRobUtil::getBattleData($uid, $role == "attacker");
		Logger::trace("uid[%d] guild[%d] join rob battle[%d] as %s: battleData:%s", $uid, $currGuildId, $robId, $role, $battleData);
		
		// 通知lcserver用户参战
		$proxy = new PHPProxy ('lcserver');
		$ret = $proxy->joinGroupBattle($uid, $robId, $transferId, $battleData);
		if($ret['ret'] != 'ok')
		{
			Logger::warning('uid:%d join failed: %s', $uid, $ret);
			return array ('ret' => $ret['ret']);
		}
		$outTime = $ret['outTime'];
		Logger::trace("uid[%d] guild[%d] join rob battle[%d] as %s:notify lcserver to join ok, outTime[%s], now[%s]", 
					$uid, $currGuildId, $robId, $role,
					strftime("%Y%m%d-%H%M%S", $outTime), strftime("%Y%m%d-%H%M%S", Util::getTime()));
		
		// 计算奖励
		$reward = self::rewardWhenJoin($uid);
		Logger::trace("uid[%d] guild[%d] join rob battle[%d] as %s:rewardWhenJoin is ok, reward:%s", $uid, $currGuildId, $robId, $role, $reward);
				
		// 返回值
		$ret = array();
		$ret['ret'] = 'ok';
		$ret['outTime'] = $outTime;
		$ret['reward'] = $reward;
		Logger::trace("uid[%d] guild[%d] join rob battle[%d] as %s:all ok, ret:%s", $uid, $currGuildId, $robId, $role, $ret);
		
		Logger::trace('GuildRobLogic::join end...');
		return $ret;
	}
	
	public static function leave($uid)
	{
		Logger::trace('GuildRobLogic::leave begin...');
		
		$robId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);		
		if ($robId == 0) 
		{
			Logger::warning("robId is 0, uid[%d] leave rob battle already", $uid);
			return 'ok';
		}
		Logger::trace("uid[%d] leave rob battle[%d]", $uid, $robId);
		
		RPCContext::getInstance()->setSession(GuildRobDef::SESSION_SPEED_UP_TIMES, 0);
		Logger::trace("uid[%d] leave rob battle[%d]:set SESSION_SPEED_UP_TIMES zero", $uid, $robId);
		
		/////////////////////////////////////////////////////////////
		// 蹲点粮仓相关操作，全部抛到SPECIAL_UID::GUILD_ROB系统用户线程去执行
		//////////////////////////////////////////////////////////// 
		// 暂时不开放蹲点粮仓功能
		//RPCContext::getInstance()->executeTask(SPECIAL_UID::GUILD_ROB, 'guildrob.leaveSpecBarn', array($uid, $robId));
		//Logger::trace("uid[%d] leave rob battle[%d]:throw into the queue of SPECIAL_UID::GUILD_ROB to leave spec barn", $uid, $robId);
	
		RPCContext::getInstance()->leaveGroupBattle();
		Logger::trace("uid[%d] leave rob battle[%d]:notify lcserver to leave", $uid, $robId);
		
		Logger::trace('GuildRobLogic::leave end...');
	}
	
	public static function logoffNotify($uid)
	{
		return ;// 暂时不开放蹲点粮仓功能
		
		Logger::trace('GuildRobLogic::logoffNotify begin...uid[%d]', $uid);
		
		$robId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);
		if ($robId == 0)
		{
			Logger::trace("GuildRobLogic::logoffNotify robId is 0, direct return.", $uid);
			return;
		}
		Logger::trace('GuildRobLogic::logoffNotify uid[%d], robId[%d]', $uid, $robId);
	
		if ($robId != 0) 
		{
			/////////////////////////////////////////////////////////////
			// 蹲点粮仓相关操作，全部抛到SPECIAL_UID::GUILD_ROB系统用户线程去执行
			//////////////////////////////////////////////////////////// 
			// 暂时不开放蹲点粮仓功能 
			//RPCContext::getInstance()->executeTask(SPECIAL_UID::GUILD_ROB, 'guildrob.leaveSpecBarn', array($uid, $robId));
			//Logger::trace("GuildRobLogic::logoffNotify uid[%d] logoff rob battle[%d]:throw into the queue of SPECIAL_UID::GUILD_ROB to leave spec barn", $uid, $robId);
		}
		
		Logger::trace('GuildRobLogic::logoffNotify end...uid[%d]', $uid);
	}
	
	public static function removeJoinCd($uid)
	{
		Logger::trace('GuildRobLogic::removeJoinCd begin...');
		
		$robId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);
		if ($robId <= 0) 
		{
			throw new FakeException("uid[%d] do not have robId in session when removeJoinCd", $uid);
		}
		
		$attackGuildId = $robId;
		$currGuildId = GuildRobUtil::checkAll($uid, $robId, GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_REMOVE_JOIN_CD);
		$role = $currGuildId == $attackGuildId ? "attacker" : "defender";
		$nowTime = Util::getTime ();
		
		// 检查cd
		$leaveBattleTime = RPCContext::getInstance ()->getSession(GuildRobDef::SESSION_LEAVE_BATTLE_TIME);
		$joinCdTime = intval(btstore_get()->GUILD_ROB['join_cd']);
		$remainCd = $leaveBattleTime + $joinCdTime - $nowTime;
		if ($remainCd <=0 )
		{
			Logger::warning("uid[%d] guild[%d] removeJoinCd in rob battle[%d] as %s:no cd, leaveBattleTime:%d cd:%d nowTime:%d", $uid, $currGuildId, $robId, $role, $leaveBattleTime, $joinCdTime, $nowTime);
			return 'nocd';
		}
		Logger::trace("uid[%d] guild[%d] removeJoinCd in rob battle[%d] as %s:can clear cd, remain cd:%d", $uid, $currGuildId, $robId, $role, $remainCd);
		
		// 获得配置和已经清除CD的次数，用于计算需要花费的金币
		$robUserObj = GuildRobUserObj::getInstance($uid);
		$removeTimes = $robUserObj->getRemoveCdNum() + 1;
		$needGold = GuildRobUtil::getRemoveCdCost($removeTimes);
		Logger::trace("uid[%d] guild[%d] removeJoinCd in rob battle[%d] as %s: remove num:%d, need gold:%d, cost config:%s", $uid, $currGuildId, $robId, $role, $removeTimes, $needGold, btstore_get()->GUILD_ROB['clear_join_cd_cost']->toArray());
		
		// 减金币
		$userObj = EnUser::getUserObj($uid);
		if (FALSE === $userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_GUILD_ROB_REMOVE_JOIN_CD))
		{
			Logger::trace("uid[%d] guild[%d] removeJoinCd in rob battle[%d] as %s:lack gold, num:%d", $uid, $currGuildId, $robId, $role, $needGold);
			return 'lack_cost';
		}
		Logger::trace("uid[%d] guild[%d] removeJoinCd in rob battle[%d] as %s:sub gold ok, num:%d", $uid, $currGuildId, $robId, $role, $needGold);
		
		// 增加rmoveCd次数
		$robUserObj->increRemoveCdNum();
		
		// 同步到数据库
		$userObj->update();
		$robUserObj->update();
		
		// 调用lcserver
		RPCContext::getInstance()->removeGroupBattleJoinCd();
		Logger::trace("uid[%d] guild[%d] removeJoinCd in rob battle[%d] as %s:notify lcserver to removeJoinCd", $uid, $currGuildId, $robId, $role, $needGold);
		
		Logger::trace('GuildRobLogic::removeJoinCd end...');
		return array ('ret' => 'ok', 'res' => $needGold);
	}
	
	public static function speedUp($uid, $multiple)
	{
		Logger::trace('GuildRobLogic::speedUp begin...');
		
		$robId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);
		if ($robId <= 0)
		{
			throw new FakeException("uid[%d] do not have robId in session when speedUp", $uid);
		}
		
		$attackGuildId = $robId;
		$currGuildId = GuildRobUtil::checkAll($uid, $robId, GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_SPEEDUP);
		$robUserObj = GuildRobUserObj::getInstance($uid);
		$role = $currGuildId == $attackGuildId ? "attacker" : "defender";
		
		// 检查加速次数是否超出限制
		$speedUpLimit = GuildRobConf::SPEEDUP_MAX_NUM_PER_JOIN;
		$speedUpTimes = RPCContext::getInstance ()->getSession(GuildRobDef::SESSION_SPEED_UP_TIMES);
		if (!empty($speedUpTimes) && $speedUpTimes >= $speedUpLimit) 
		{
			Logger::debug("uid[%d] guild[%d] speedUp in rob battle[%d] as %s:speedup time is limited, curr[%d] limit[%d]", $uid, $currGuildId, $robId, $role, $speedUpTimes, $speedUpLimit);
			return array ('ret' => 'limit', 'res' => $speedUpLimit);
		}
		Logger::trace("uid[%d] guild[%d] speedUp in rob battle[%d] as %s:limit is ok, curr[%d] limit[%d]", $uid, $currGuildId, $robId, $role, $speedUpTimes, $speedUpLimit);
		
		// 应该判断其是否处于传送阵或者通道中
		$proxy = new PHPProxy ('lcserver');
		$ret = $proxy->inGroupBattle($uid, $robId);
		if($ret === GuildRobDef::GUILD_ROB_NOT_IN_BATTLE_FIELD) // 玩家不在通道或者传送阵上不能传送
		{
			Logger::trace("uid[%d] guild[%d] speedUp in rob battle[%d] as %s: not in transfer and road, can not speed up,ret:%d", $uid, $currGuildId, $robId, $role, $ret);
			return 'not_in_transfer';
		}
		Logger::trace("uid[%d] guild[%d] speedUp in rob battle[%d] as %s: in transfer and road, can speed up,ret:%d", $uid, $currGuildId, $robId, $role, $ret);
		
		// 调用lcserver
		$multiple = intval(btstore_get()->GUILD_ROB['speedup_multiple']);
		$proxy = new PHPProxy ('lcserver');
		$ret = $proxy->speedUpGroupBattle($uid, $robId, $multiple);
		Logger::trace("uid[%d] guild[%d] speedUp in rob battle[%d]:notify lcserver to speedUpGroupBattle ok, param : multiple[%d] ret : %s", $uid, $attackGuildId, $robId, $multiple, $ret);
		
		if ($ret != 'ok')
		{
			Logger::warning("uid[%d] guild[%d] can not speedUp in rob battle[%d], because lcserver speedUpGroupBattle error[%s]", $uid, $currGuildId, $robId, $ret);
			return $ret;
		}
		Logger::trace("uid[%d] guild[%d] speedUp in rob battle[%d], lcserver speedUpGroupBattle ok", $uid, $currGuildId, $robId);
		
		// 减金币
		$needGold = intval(btstore_get()->GUILD_ROB['speedup_cost']);
		$userObj = EnUser::getUserObj($uid);
		if (FALSE === $userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_GUILD_ROB_SPEED_UP))
		{
			Logger::warning("uid[%d] guild[%d] speedUp in rob battle[%d] as %s:lack_gold", $uid, $currGuildId, $robId, $role);
			return 'lack_cost';
		}
		Logger::trace("uid[%d] guild[%d] speedUp in rob battle[%d] as %s:sub gold ok, num:%d", $uid, $currGuildId, $robId, $role, $needGold);
		
		// 更新session和增加加速次数
		RPCContext::getInstance ()->setSession(GuildRobDef::SESSION_SPEED_UP_TIMES, $speedUpTimes + 1);
		$robUserObj->increSpeedUpNum();
		Logger::trace("uid[%d] guild[%d] speedUp in rob battle[%d] as %s:setSession ok, curr speedUpTimes:%d", $uid, $currGuildId, $robId, $role, $speedUpTimes + 1);
		
		// 同步到数据库
		$userObj->update();
		$robUserObj->update();
				
		Logger::trace('GuildRobLogic::speedUp end...');
		return array ('ret' => 'ok', 'res' => $needGold);
	}
	
	public static function enterSpecBarn($uid, $robId, $pos, $quitBattleTime, $leaveBattleTime)
	{
		Logger::trace('GuildRobLogic::enterSpecBarn begin...');

		$attackGuildId = $robId;
		$currGuildId = GuildRobUtil::checkAll($uid, $robId, GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_ENTER_SPEC_BARN);
		$robUserObj = GuildRobUserObj::getInstance($uid);
		$role = ($currGuildId == $attackGuildId ? "attacker" : "defender");
			
		// 抢夺蹲点粮仓的时候，应该判断其应该不能处于传送阵或者通道中
		$proxy = new PHPProxy ('lcserver');
		$ret = $proxy->inGroupBattle($uid, $robId);
		if($ret !== GuildRobDef::GUILD_ROB_NOT_IN_BATTLE_FIELD) // 玩家已经在通道或者传送阵上
		{
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s: in transfer or road, can not enter", $uid, $currGuildId, $robId, $role);
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_ENTER_SPEC_RET, 'in_road');
			return;
		}
		Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s: not in transfer and road, ok,ret:%s", $uid, $currGuildId, $robId, $role, $ret);
			
		// 判断该玩家是不是已经在蹲点粮仓内
		$robObj = GuildRobObj::getInstance($robId);
		$ret = $robObj->isInSpecBarn($uid);
		if ($ret !== FALSE)
		{
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s:already in spec barn[%d], can not enter", $uid, $currGuildId, $robId, $role, $ret);
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_ENTER_SPEC_RET, 'in_spec_barn');
			return;
		}
		Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s: not in spec barn now, can enter", $uid, $currGuildId, $robId, $role);
			
		$nowTime = Util::getTime ();
			
		// 判断等待时间
		$joinReadyTime = intval(btstore_get()->GUILD_ROB['join_cd']);
		$waitTime = $quitBattleTime + $joinReadyTime - $nowTime;
		if ($waitTime > 0)
		{
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s:failed because in waitTime", $uid, $currGuildId, $robId, $role);
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_ENTER_SPEC_RET, 'waitTime');
			return;
		}
		Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s:wait time is ok", $uid, $currGuildId, $robId, $role);
			
		// 判断参战cd
		$joinCdTime = intval(btstore_get()->GUILD_ROB['join_cd']);
		$remainCd = $leaveBattleTime + $joinCdTime - $nowTime;
		if ($remainCd > 0)
		{
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s:failed because in cdTime", $uid, $currGuildId, $robId, $role);
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_ENTER_SPEC_RET, 'cdtime');
			return;
		}
		Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s:cd time is ok", $uid, $currGuildId, $robId, $role);

		//==================================================================================================================
		//=========================================当前蹲点粮仓没有人占领==========================================================
		//==================================================================================================================
		$specBarnInfo = $robObj->getSpecBarn($pos);
		if (empty($specBarnInfo)) // 粮仓没有人占领，直接占领
		{
			// 玩家英雄血量数组和最大血量
			$arrHeroHp = GuildRobUtil::getArrHeroHp($uid);
			$maxHp = GuildRobUtil::getTotalHp($uid);
		
			// 加定时器
			$maxSpecTime = intval(btstore_get()->GUILD_ROB['spec_barn_time_limit']);
			RPCContext::getInstance()->addTimer(SPECIAL_UID::GUILD_ROB, Util::getTime() + $maxSpecTime, 'guildrob.onSpecTimerOut', array($robId, $pos, $uid, Util::getTime()), FALSE);
		
			// 将该信息插入到数据中
			$robObj->enterSpecBarn($pos, $uid, $currGuildId, $maxHp, $arrHeroHp);
			$robObj->update();
		
			// 向战场广播蹲点粮仓有人占领的消息
			$pushInfo = $robObj->genSpecBarnInfo(TRUE, array($pos));
			RPCContext::getInstance()->broadcastGroupBattle($robId, $pushInfo, PushInterfaceDef::GUILD_ROB_SPEC);
		
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_ENTER_SPEC_RET, $pushInfo);
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s:the spec barn is empty, direct enter", $uid, $currGuildId, $robId, $role);
			return;
		}
		Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s:the spec barn is not empty", $uid, $currGuildId, $robId, $role);
		
		//==================================================================================================================
		//=========================================当前蹲点粮仓有人占领，是自己军团的其他人占领，返回========================================
		//==================================================================================================================
		// 属于同一个军团， 不用打
		$currUid = $specBarnInfo[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UID];
		$currUserRobObj = GuildRobUserObj::getInstance($currUid);
		$robUserObj = GuildRobUserObj::getInstance($uid);
		if ($currUserRobObj->getGuildId() == $robUserObj->getGuildId())
		{
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s:same guild", $uid, $currGuildId, $robId, $role);
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_ENTER_SPEC_RET, 'same_guild');
			return;
		}
		Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s:not same guild, ok", $uid, $currGuildId, $robId, $role);
				
		//==================================================================================================================
		//=========================================当前蹲点粮仓有人占领，是对方军团人占领，开打============================================
		//==================================================================================================================
		// 属于不同军团，开打
		$userBattleFormat = EnUser::getUserObj($uid)->getBattleFormation();
		$currUserBattleFormat = EnUser::getUserObj($currUid)->getBattleFormation();
				
		// 更新当前占有者的血量
		$currArrHp = $specBarnInfo[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_ARRHP];
		$currUserBattleFormat = GuildRobUtil::updateBattleFormat($currUserBattleFormat, $currArrHp);
				
		// 战斗
		$atkRet = EnBattle::doHero($userBattleFormat, $currUserBattleFormat);
		Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s: doHero end, ret:%s", $uid, $currGuildId, $robId, $role, $atkRet);
		$brid = $atkRet['server']['brid'];
				
		// 战斗结果
		$retInfo = array();
		$isSuc = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'];
		if ($isSuc) // 击败了对手
		{
			// 计算胜者的奖励
			$winnerReward = self::rewardWhenSpecBarnWin($uid);
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s: win, winner reward:%s", $uid, $currGuildId, $robId, $role, $winnerReward);
		
			// 计算败者的奖励
			$loserReward = self::rewardWhenSpecBarnLose($uid, Util::getTime() - $specBarnInfo[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_BEGIN]);
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s: win, loser[%d] reward:%s", $uid, $currGuildId, $robId, $role, $currUid, $loserReward);
		
			$terminalStreak = $specBarnInfo[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_WIN_STREAK];
			
			// 给新的占领者加定时器，既最长能占领多长时间
			$maxSpecTime = intval(btstore_get()->GUILD_ROB['spec_barn_time_limit']);
			RPCContext::getInstance()->addTimer(SPECIAL_UID::GUILD_ROB, Util::getTime() + $maxSpecTime, 'guildrob.onSpecTimerOut', array($robId, $pos, $uid, Util::getTime()), FALSE);
				
			// 获得这个蹲点粮仓新的占有者的单前血量和最大血量
			$arrHeroHp = array();
			foreach($atkRet['server']['team1'] as $index => $heroInfo)
			{
				$hid = $heroInfo['hid'];
				$hp = $heroInfo['hp'];
				$arrHeroHp[$hid] = $hp;
			}
			$maxHp = GuildRobUtil::getTotalHp($uid);
		
			$robObj->enterSpecBarn($pos, $uid, $currGuildId, $maxHp, $arrHeroHp);
			$robObj->update();
				 
			// 设置lcserver中玩家数据中的leaveBattleTime
			$userData = array('leaveBattleTime' => Util::getTime());
			$ret = $proxy->setGroupBattleUserInfo($currUid, $robId, $userData);
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s: win, reset user[%d] info of lcserver:%s, ret:%s", $uid, $currGuildId, $robId, $role, $currUid, $userData, $ret);
		
			// 向前端推送战斗相关消息 
			GuildRobUtil::pushSpecBarnFightInfo($robId, $uid, $currUid, 1, $terminalStreak, $brid, $winnerReward, $loserReward);
		
			// 向战场广播蹲点粮仓相关消息
			$pushInfo = $robObj->genSpecBarnInfo(TRUE, array($pos));
			RPCContext::getInstance()->broadcastGroupBattle($robId, $pushInfo, PushInterfaceDef::GUILD_ROB_SPEC);
		
			$retInfo = $pushInfo;
		}
		else // 依然打不过现在这个粮仓的占有者, 更新胜者的血量存放到数据库
		{
			$arrHeroHp = array();
			foreach($atkRet['server']['team2'] as $index => $heroInfo)
			{
				$hid = $heroInfo['hid'];
				$hp = $heroInfo['hp'];
				$arrHeroHp[$hid] = $hp;
			}
			$robObj->updateSpecBarnArrHp($pos, $arrHeroHp);
			$winStreak = $robObj->addSpecBarnArrWinStreak($pos);
			$robObj->update();
					
			// 设置lcserver中玩家数据中的leaveBattleTime
			$proxy = new PHPProxy ('lcserver');
			$userData = array('leaveBattleTime' => Util::getTime());
			$ret = $proxy->setGroupBattleUserInfo($uid, $robId, $userData);
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s: lose, reset user info of lcserver:%s, ret:%s", $uid, $currGuildId, $robId, $role, $userData, $ret);
					
			// 向前端推送战斗相关消息
			GuildRobUtil::pushSpecBarnFightInfo($robId, $currUid, $uid, $winStreak, 0, $brid, array(), array());
			Logger::trace("uid[%d] guild[%d] enterSpecBarn in rob battle[%d] as %s: lose", $uid, $currGuildId, $robId, $role);
		
			// 向前端推送战斗相关消息
			$pushInfo = $robObj->genSpecBarnInfo(FALSE, array($pos));
			RPCContext::getInstance()->broadcastGroupBattle($robId, $pushInfo, PushInterfaceDef::GUILD_ROB_SPEC);
		
			$retInfo = 'fail';
		}
				
		Logger::trace('GuildRobLogic::enterSpecBarn end: %s...', $retInfo);
		RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_ENTER_SPEC_RET, $retInfo);
	}
	
	public static function getRankByKill($uid, $onlyMyself = FALSE)
	{
		Logger::trace('GuildRobLogic::getRankByKill begin...');
	
		// 从session中获取robId
		$robId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);
		if ($robId <= 0)
		{
			throw new FakeException("uid[%d] do not have robId in session when getRankByKill", $uid);
		}
	
		// 从数据库拉取
		$arrField = array
		(
				GuildRobUserField::TBL_FIELD_UID,
				GuildRobUserField::TBL_FIELD_UNAME,
				GuildRobUserField::TBL_FIELD_KILL_NUM,
		);
		$allKillRank = GuildRobDao::getKillTopN($robId, -1, $arrField);
	
		$ret = array();
		$rank = 0;
		$isMyself = FALSE;
		foreach ($allKillRank as $killRank)
		{
			$rank++;
	
			$currUid = $killRank[GuildRobUserField::TBL_FIELD_UID];
			if ($currUid == $uid)
			{
				$isMyself = TRUE;
			}
				
			if ($rank > GuildRobConf::KILL_NUM_TOP_N && !$isMyself)
			{
				continue;
			}
				
			$info = array();
			$info['rank'] = $rank;
			$info['uname'] = $killRank[GuildRobUserField::TBL_FIELD_UNAME];
			$info['killNum'] = $killRank[GuildRobUserField::TBL_FIELD_KILL_NUM];
			$ret[$currUid] = $info;
				
			if ($rank > GuildRobConf::KILL_NUM_TOP_N && $isMyself)
			{
				break;
			}
		}
	
		if ($onlyMyself)
		{
			$ret = array($uid => $ret[$uid]);
		}
		
		Logger::trace('GuildRobLogic::getRankByKill end...ret:%s', $ret);
		return $ret;
	}
	
	
	
	
	/*****************************************************************************************************************
	 * 
	 * 以下是军团抢粮战中拉取其他军团状态的相关接口
	 * 
	 *****************************************************************************************************************/
	public static function getGuildRobAreaInfo($uid, $areaId, $pattern)
	{
		Logger::trace('GuildRobLogic::getGuildRobAreaInfo begin...');
	
		RPCContext::getInstance()->setSession(SPECIAL_ARENA_ID::SESSION_KEY, SPECIAL_ARENA_ID::GUILDROB);
		
		// 获取玩家自己的军团ID，再得到军团抢夺列表的时候要将自己所在的军团去除掉
		$myGuildId = EnGuild::getGuildId($uid);
		if ($myGuildId <= 0) 
		{
			throw new FakeException('uid[%d] guildId[%d] do not in any guild but getGuildRobAreaInfo', $uid, $myGuildId);
		}
		
		// 军团抢粮相关信息
		$arrField = array
		(
				GuildDef::GUILD_ID,
				GuildDef::GUILD_NAME,
				GuildDef::GRAIN_NUM,
				GuildDef::VA_INFO,
		);
		
		$guildCount = 0;
		$guildList = array();
		
		$pattern = trim($pattern);
		if ($pattern === '') 
		{
			$arrCond = array
			(
					array(GuildDef::GUILD_ID, '!=', $myGuildId),
					array(GuildDef::GRAIN_NUM, '>', 0),
					array(GuildDef::STATUS, '=', GuildStatus::OK)
			);
			$guildCount = GuildDao::getGuildCount($arrCond) + 1;//军团数要加1，还有自己的军团
			
			$arrCond = array
			(
					array(GuildDef::GUILD_ID, '!=', $myGuildId),
					array(GuildDef::GRAIN_NUM, '>', 0),
			);
			
			if ($areaId == 1) 
			{
				$guildList = GuildDao::getGuildList($arrCond, $arrField, 0, GuildRobConf::ROB_AREA_NUM - 1);
			}
			else 
			{
				$guildList = GuildDao::getGuildList($arrCond, $arrField, ($areaId - 1) * GuildRobConf::ROB_AREA_NUM - 1, GuildRobConf::ROB_AREA_NUM);
			}
		}
		else 
		{
			$arrCond = array
			(
					array(GuildDef::GRAIN_NUM, '>', 0),
					array(GuildDef::GUILD_NAME, 'LIKE', "%$pattern%"),
					array(GuildDef::STATUS, '=', GuildStatus::OK)
			);
			$guildCount = GuildDao::getGuildCount($arrCond);
			
			$arrCond = array
			(
					array(GuildDef::GUILD_ID, '!=', $myGuildId),
					array(GuildDef::GRAIN_NUM, '>', 0),
					array(GuildDef::GUILD_NAME, 'LIKE', "%$pattern%"),
			);
			
			$myGuildObj = GuildObj::getInstance($myGuildId);
			$myGuildName = $myGuildObj->getGuildName();	
			if (FALSE === strpos($myGuildName, $pattern))	//自己军团不匹配
			{
				$guildList = GuildDao::getGuildList($arrCond, $arrField, ($areaId - 1) * GuildRobConf::ROB_AREA_NUM, GuildRobConf::ROB_AREA_NUM);
			}
			else	//自己军团匹配
			{
				if ($areaId == 1)
				{
					$guildList = GuildDao::getGuildList($arrCond, $arrField, 0, GuildRobConf::ROB_AREA_NUM - 1);
					$myGuildInfo = GuildDao::getGuildList(array(array(GuildDef::GUILD_ID, '=', $myGuildId)), $arrField, 0, GuildRobConf::ROB_AREA_NUM - 1);
					$guildList = array_merge($myGuildInfo, $guildList);
				}
				else 
				{
					$guildList = GuildDao::getGuildList($arrCond, $arrField, ($areaId - 1) * GuildRobConf::ROB_AREA_NUM - 1, GuildRobConf::ROB_AREA_NUM);
				}
			}
		}
		Logger::trace('GuildRobLogic::getGuildRobAreaInfo guildList:%s...', $guildList);
	
		$totalBattleTime = intval(btstore_get()->GUILD_ROB['battle_time']) + intval(btstore_get()->GUILD_ROB['ready_time']);
		$inRobEffectDay = TRUE;
		$todayBeginTimeStamp = GuildRobUtil::getTodayBeginEffectTime();
		if (FALSE === $todayBeginTimeStamp) 
		{
			$inRobEffectDay = FALSE;
			Logger::trace("GuildRobLogic::getGuildRobAreaInfo today is not rob day");
		}
		else 
		{
			Logger::trace("GuildRobLogic::getGuildRobAreaInfo today begin effect time : %s, total battle duration : %d", strftime("%Y%m%d-%H%M%S", $todayBeginTimeStamp), $totalBattleTime);
		}
		
		$guildInRobList = array();
		if ($inRobEffectDay && !empty($guildList))
		{
			$arrRobField = array
			(
					GuildRobField::TBL_FIELD_GUILD_ID,
					GuildRobField::TBL_FIELD_DEFEND_GUILD_ID,
					GuildRobField::TBL_FIELD_START_TIME,
					GuildRobField::TBL_FIELD_STAGE,
			);
			
			$arrRobCond = array//正在抢粮的军团
			(
					array(GuildRobField::TBL_FIELD_GUILD_ID, 'in', Util::arrayExtract($guildList, GuildDef::GUILD_ID)),
					array(GuildRobField::TBL_FIELD_START_TIME, '>=', $todayBeginTimeStamp),
					array(GuildRobField::TBL_FIELD_STAGE, 'IN', array(GuildRobField::GUILD_ROB_STAGE_START, GuildRobField::GUILD_ROB_STAGE_END, GuildRobField::GUILD_ROB_STAGE_SYNC)),
			);
			$robAttackerList = GuildRobDao::selectMultiRob($arrRobCond, $arrRobField);
			Logger::trace('GuildRobLogic::getGuildRobAreaInfo find attacker:%s...', $robAttackerList);
			
			$arrRobCond = array//正在被抢的军团
			(
					array(GuildRobField::TBL_FIELD_DEFEND_GUILD_ID, 'in', Util::arrayExtract($guildList, GuildDef::GUILD_ID)),
					array(GuildRobField::TBL_FIELD_START_TIME, '>=', $todayBeginTimeStamp),
					array(GuildRobField::TBL_FIELD_STAGE, 'IN', array(GuildRobField::GUILD_ROB_STAGE_START, GuildRobField::GUILD_ROB_STAGE_END, GuildRobField::GUILD_ROB_STAGE_SYNC)),
			);
			$robDefenderList = GuildRobDao::selectMultiRob($arrRobCond, $arrRobField);
			Logger::trace('GuildRobLogic::getGuildRobAreaInfo find defender:%s...', $robDefenderList);
						
			foreach ($robAttackerList as $key => $value)
			{
				if ((Util::getTime() - $value[GuildRobField::TBL_FIELD_START_TIME]) >= $totalBattleTime + GuildRobConf::GUILD_ROB_END_CHECK_OFFSET)
				{
					// do nothing
					//Logger::warning("GuildRobLogic::getGuildRobAreaInfo curr guild[%d] attack guild[%d] before, but not end successfully, stage:%d", $key, $value[GuildRobField::TBL_FIELD_DEFEND_GUILD_ID], $value[GuildRobField::TBL_FIELD_STAGE]);
				}
				else 
				{
					$guildInRobList[$key] = $key;
				}
			}
			
			foreach ($robDefenderList as $key => $value)
			{
				if ((Util::getTime() - $value[GuildRobField::TBL_FIELD_START_TIME]) >= $totalBattleTime + GuildRobConf::GUILD_ROB_END_CHECK_OFFSET)
				{
					// do nothing
					//Logger::warning("GuildRobLogic::getGuildRobAreaInfo curr guild[%d] is attacked by guild[%d] before, but not end successfully, stage:%d", $value[GuildRobField::TBL_FIELD_DEFEND_GUILD_ID], $key, $value[GuildRobField::TBL_FIELD_STAGE]);
				}
				else
				{
					$guildInRobList[$value[GuildRobField::TBL_FIELD_DEFEND_GUILD_ID]] = $key;
				}
			}
		}
		Logger::trace('GuildRobLogic::getGuildRobAreaInfo all is in guild rob:%s...', $guildInRobList);
	
		$robList = array();
		foreach ($guildList as $guildInfo)
		{
			$info = array();
			
			$canRobGrain = 0;
			$currGrainNum = $guildInfo[GuildDef::GRAIN_NUM];
			$level = 0;
			if (isset($guildInfo[GuildDef::VA_INFO][GuildDef::BARN][GuildDef::LEVEL])) 
			{
				$level = $guildInfo[GuildDef::VA_INFO][GuildDef::BARN][GuildDef::LEVEL];
			}
			else 
			{
				$level = GuildConf::$GUILD_BUILD_DEFAULT[GuildDef::BARN][GuildDef::LEVEL];
			}
			$grainUpperLimit = btstore_get()->GUILD_BARN[GuildDef::GUILD_GRAIN_CAPACITY][$level];
			
			$canRobGrain = GuildRobUtil::getCanRobGrain($currGrainNum, $grainUpperLimit);
			if ($canRobGrain < 0)
			{
				$canRobGrain = 0;
			}
			
			$currGuildId = $guildInfo[GuildDef::GUILD_ID];
			
			$info['name'] = $guildInfo[GuildDef::GUILD_NAME];
			$info['grain'] = $canRobGrain;
			$info['barn_level'] = isset($guildInfo[GuildDef::VA_INFO][GuildDef::BARN][GuildDef::LEVEL]) ? $guildInfo[GuildDef::VA_INFO][GuildDef::BARN][GuildDef::LEVEL] : 0;
			$info['robId'] = isset($guildInRobList[$currGuildId]) ? $guildInRobList[$currGuildId] : 0;
			
			$lastDefendTime = GuildRobUtil::getLastDefendTime($currGuildId);
			if ($lastDefendTime == 0 || ($lastDefendTime + intval(btstore_get()->GUILD_ROB['after_defend_cd_time'])) <= Util::getTime())
			{
				$info['shelterTime'] = 0;
			}
			else
			{
				$info['shelterTime'] = $lastDefendTime + intval(btstore_get()->GUILD_ROB['after_defend_cd_time']);
			}
	
			$robList[$currGuildId] = $info;
		}
	
		// 返回值
		$ret = array();
		$ret['inRob'] = GuildRobUtil::checkEffectTime() ? 1 : 0;
		$ret['areaNum'] = ceil($guildCount / GuildRobConf::ROB_AREA_NUM);
		$ret['guildInfo'] = $robList;
	
		Logger::trace('GuildRobLogic::getGuildRobAreaInfo end:%s...', $ret);
		return $ret;
	}
	
	public static function getGuildRobInfo($uid)
	{
		Logger::trace('GuildRobLogic::getGuildRobInfo begin...');
		
		$guildId = EnGuild::getGuildId($uid);
		if ($guildId == 0) 
		{
			Logger::trace('GuildRobLogic::getGuildRobInfo: guildId is 0');
			return array();
		}
		Logger::trace('GuildRobLogic::getGuildRobInfo: guildId is %d', $guildId);
		
		$guildRobInfo = GuildRobUtil::getGuildRobInfo($guildId);
		
		Logger::trace('GuildRobLogic::getGuildRobInfo end...,ret:%s', $guildRobInfo);
		return $guildRobInfo;
	}
	
	public static function leaveGuildRobArea($uid)
	{
		Logger::trace('GuildRobLogic::leaveGuildRobArea begin...');
		RPCContext::getInstance()->unsetSession(SPECIAL_ARENA_ID::SESSION_KEY);
		Logger::trace('GuildRobLogic::leaveGuildRobArea end...');
	}
	
	
	
	/*****************************************************************************************************************
	 * 
	 * 以下是给玩家或者公会发奖，
	 * 当击杀，当达阵，当抢粮战结束，
	 * 当蹲点粮仓超时
	 * 当参战，但攻占蹲点粮仓胜利，当防守蹲点粮仓失败
	 * 
	******************************************************************************************************************/
	public static function rewardWhenKill($attackerId, $winnerId, $loserId, $winnerGuildId, $loserGuildId, $winStreak, $terminalStreak, $fightEndTime, $isNpcWinner=0, $isNpcLoser=0)
	{
		// 根据是抢夺方胜还是被抢方胜利，获取配置
		$rewarcConfig = array();
		$isAttackerWin = FALSE;
		if ($attackerId == $winnerId) // 抢夺方胜
		{
			$rewarcConfig = btstore_get()->GUILD_ROB['attack_kill_user_reward']->toArray();
			$isAttackerWin = TRUE;
		}
		else
		{
			$rewarcConfig = btstore_get()->GUILD_ROB['defend_kill_user_reward']->toArray();
		}
		
		// 获得奖励
		$count = count($rewarcConfig);
		$index = 0;
		$rewardMerit = 0;
		$rewardContr = 0;
		foreach ($rewarcConfig as $streakIndex => $rewardValue)
		{
			++$index;
			if ($winStreak <= $streakIndex || $index == $count) 
			{
				$rewardMerit = intval($rewardValue[0]);
				$rewardContr = intval($rewardValue[1]);
				break;
			}
		}
		Logger::trace('GuildRobLogic::rewardWhenKill uid:%d win uid:%d. winStreak:%d, rewardMerit:%d, rewardContr:%d', $winnerId, $loserId, $winStreak, $rewardMerit, $rewardContr);
		
		// 根据被终结的连杀次数而被掠夺功勋值
		$terminalConfig = btstore_get()->GUILD_ROB['terminal_reward']->toArray();
		$count = count($terminalConfig);
		$index = 0;
		$robMeritPercent = 0;
		foreach ($terminalConfig as $terminalStreakIndex => $rewardValue)
		{
			++$index;
			if ($terminalStreak <= $terminalStreakIndex || $index == $count)
			{
				$robMeritPercent = intval($rewardValue);
				break;
			}
		}
		
		$baseMerit = GuildRobUserObj::getInstance($loserId)->getMeritNum();
		$robMerit = intval($baseMerit * $robMeritPercent / UNIT_BASE);
		Logger::trace('GuildRobLogic::rewardWhenKill uid:%d win uid:%d. terminalStreak:%d, robMeritPercent:%d, adversaryMerit:%d, robMerit:%d', $winnerId, $loserId, $terminalStreak, $robMeritPercent, $baseMerit, $robMerit);
	
		// 胜者奖励信息
		$winRewardInfo = array
		(
				'userGrain' => 0,
				'guildGrain' => 0,
				'merit' => $rewardMerit + $robMerit,
				'contr' => $rewardContr,
		);
	
		// 败者奖励信息
		$loseRewardInfo = array();
		if ($isAttackerWin) 
		{
			$beKilledConfig = btstore_get()->GUILD_ROB['defend_be_killed_user_reward']->toArray();
			$beKilledRewardGrain = isset($beKilledConfig[0]) ? intval($beKilledConfig[0]) : 0;
			$beKilledRewardMerit = isset($beKilledConfig[1]) ? intval($beKilledConfig[1]) : 0;
			$beKilledRewardContr = isset($beKilledConfig[2]) ? intval($beKilledConfig[2]) : 0;
			
			$loseRewardInfo = array
			(
					'userGrain' => $beKilledRewardGrain,
					'guildGrain' => 0,
					'merit' =>  ($robMerit > 0 ? $beKilledRewardMerit - $robMerit : $beKilledRewardMerit),
					'contr' => $beKilledRewardContr,
			);
		}
		else 
		{
			$loseRewardInfo = array
			(
					'userGrain' => 0,
					'guildGrain' => 0,
					'merit' =>  ($robMerit > 0 ? -$robMerit : 0),
					'contr' => 0,
			);
		}
	
		// 给胜者加奖励，击杀只给用户奖励，军团没有奖励,就是在胜者的线程里，不需要抛出去
		self::addUserReward($winnerId, $winRewardInfo, $fightEndTime, $isNpcWinner);
		
		// 给败者加奖励，需要放到败者的线程去执行
		RPCContext::getInstance()->executeTask($loserId, 'guildrob.addUserReward', array($loserId, $loseRewardInfo, 0, $isNpcLoser));
	
		return array('winner' => $winRewardInfo, 'loser' => $loseRewardInfo);
	}
	
	public static function rewardWhenTouchDown($robId, $guildId, $uid, $isNpc=0)
	{
		// 根据是抢夺方达阵还是被抢方达阵，获取配置
		$isAttacker = ($robId == $guildId);
		$config = array();
		if ($isAttacker) // 抢夺方达阵
		{
			$config = btstore_get()->GUILD_ROB['attack_touch_down_user_reward']->toArray();
		}
		else
		{
			$config = btstore_get()->GUILD_ROB['defend_touch_down_user_reward']->toArray();
		}
		
		$robObj = GuildRobObj::getInstance($robId);
		$robLimit = $robObj->getRobLimit();
		$defendGuildId = $robObj->getDefendGid();
		GuildRobObj::releaseInstance($robId);//这里必须release，因为后面给军团加粮草时候会并发，如果不release会有问题
	
		// 获得达阵奖励
		$robGrain = 0;
		$userGrain = 0;
		$guildGrain = 0;
		if ($isAttacker) 
		{
			$robGrainPercent = intval($config[0]);
			$guildRobPercent = intval($config[3]);
			$robGrain = intval(ceil($robLimit * $robGrainPercent / UNIT_BASE));//保证达阵后，最少能抢到一个粮草
			if ($robGrain < intval(btstore_get()->GUILD_ROB['rob_grain_least']))
			{
				$robGrain = intval(btstore_get()->GUILD_ROB['rob_grain_least']);
			}
			
			$guildGrain = intval($robGrain * $guildRobPercent / UNIT_BASE);
			$userGrain = $robGrain - $guildGrain;
		}
		else 
		{
			// do nothing 防守方达阵自己和公会都没有粮草奖励
		}
		
		$rewardMerit = isset($config[1]) ? intval($config[1]) : 0;
		$rewardContr = isset($config[2]) ? intval($config[2]) : 0;
	
		// 奖励信息
		$rewardArr = array
		(
				'userGrain' => $userGrain,
				'guildGrain' => $guildGrain,
				'merit' => $rewardMerit,
				'contr' => $rewardContr,
		);
		
		// 给玩家自己加抢夺到的粮草，功勋和贡献，在达阵者自己的线程里面，不用抛出去
		if (!empty($rewardArr)) 
		{
			self::addUserReward($uid, $rewardArr,0,$isNpc);
		}
		
		// 给抢夺军团加抢到的粮草,减少被抢军团粮草，增加rob表中粮草记录
		if ($isAttacker && $robGrain > 0)
		{
			GuildObj::addGuildGrainNum($guildId, $guildGrain);
			GuildObj::addGuildGrainNum($defendGuildId, -$robGrain);
			GuildRobObj::addRobGrain($robId, $robGrain);
		}
		
		return $rewardArr;
	}
	
	public static function rewardWhenEnd($uid, $rank)
	{
		// 抢粮战结束以后，不需要发送击杀奖励啦，先把这注释掉，万一以后又要发。。。
		
		/*$rankRewardConfig = btstore_get()->GUILD_ROB_REWARD;
		$rankRewardConfig->toArray();
		if (isset($rankRewardConfig[$rank])) 
		{
			$rewardArr = $rankRewardConfig[$rank];
			RewardUtil::reward3DArr($uid, $rewardArr, StatisticsDef::ST_FUNCKEY_GUILD_ROB_KILL_RANK);
			return $rewardArr;
		}
		
		return array();*/
	}
	
	public static function rewardWhenJoin($uid, $isNpc=0)
	{
		Logger::trace("uid[%d] rewardWhenJoin begin...", $uid);
		
		$rewardMerit = intval(btstore_get()->GUILD_ROB['join_reward_merit']);
		
		$rewardArr = array
		(
				'merit' => $rewardMerit,
		);
		
		// 给玩家发参战奖励，就在玩家自己的线程里，不需要抛出去
		self::addUserReward($uid, $rewardArr, 0, $isNpc);
		
		Logger::trace("uid[%d] rewardWhenJoin end...: %s", $uid, $rewardArr);
		return $rewardArr;
	}
	
	public static function rewardWhenSpecBarnWin($uid)
	{
		Logger::trace("uid[%d] rewardWhenSpecBarnWin begin...", $uid);
	
		$config = btstore_get()->GUILD_ROB['spec_barn_win_merit']->toArray();
		$rewardGrain = isset($config[0]) ? intval($config[0]) : 0;
		$rewardMerit = isset($config[1]) ? intval($config[1]) : 0;
		$rewardContr = isset($config[2]) ? intval($config[2]) : 0;
		
		// 奖励信息
		$rewardArr = array
		(
				'userGrain' => $rewardGrain,
				'guildGrain' => 0,
				'merit' => $rewardMerit,
				'contr' => $rewardContr,
		);
		
		// 在玩家自己的线程里，直接加
		self::addUserReward($uid, $rewardArr);
	
		Logger::trace("uid[%d] rewardWhenSpecBarnWin end:...: %s", $uid, $rewardArr);
		return $rewardArr;
	}
	
	public static function rewardWhenSpecBarnLose($uid, $rewardTime)
	{
		Logger::trace("uid[%d] rewardWhenSpecBarnLose lastTime[%d] begin...", $uid, $rewardTime);
	
		if ($rewardTime <= 0)
		{
			return array();
		}
		
		$config = btstore_get()->GUILD_ROB['spec_barn_reward']->toArray();
		if (count($config) < 3 || intval($config[0]) <= 0)
		{
			throw new FakeException("config GUILD_ROB spec_barn_reward error, config:%s", $config);
		}
			
		// 配置的奖励
		$timeGap = intval($config[0]);
		$baseGrain = intval($config[1]);
		$baseMerit = intval($config[2]);
			
		// 计算奖励
		$multiple = floor($rewardTime / $timeGap);
		$rewardMerit = $baseMerit * $multiple;
		$rewardGrain = $baseGrain * $multiple;
		
		$rewardArr = array
		(
				'userGrain' => $rewardGrain,
				'merit' => $rewardMerit,
		);
		
		// 给已经在蹲点粮仓蹲着，然后被击败的玩家加奖励，需要放到败者的线程去执行，因为现在是在击败他的玩家线程里
		RPCContext::getInstance()->executeTask($uid, 'guildrob.addUserReward', array($uid, $rewardArr));
	
		Logger::trace("uid[%d] rewardWhenSpecBarnLose last time[%d] end...: %s", $uid, $rewardTime, $rewardArr);
		return $rewardArr;
	}
	
	public static function rewardWhenSpecBarnTimeOut($robId, $pos, $uid, $isTimerTrigger)
	{
		Logger::trace("GuildRobLogic::rewardWhenSpecTimeOut uid[%d] begin...", $uid);
		
		$robObj = GuildRobObj::getInstance($robId);
		$specBarnInfo = $robObj->getSpecBarn($pos);
		Logger::trace("GuildRobLogic::rewardWhenSpecTimeOut uid[%d] pos[%d] get specInfo ok:[%s]", $uid, $pos, $specBarnInfo);
		
		$rewardArr = array();
		if (!empty($specBarnInfo) && $uid == $specBarnInfo[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UID])
		{
			$maxSpecTime = 0;
			if ($isTimerTrigger) 
			{
				$maxSpecTime = intval(btstore_get()->GUILD_ROB['spec_barn_time_limit']);
				Logger::trace("GuildRobLogic::rewardWhenSpecTimeOut uid[%d] pos[%d] max spec time[%d], timer trigger.", $uid, $pos, $maxSpecTime);
			}
			else 
			{
				$maxSpecTime = Util::getTime() - $specBarnInfo[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_BEGIN];
				Logger::trace("GuildRobLogic::rewardWhenSpecTimeOut uid[%d] pos[%d] max spec time[%d], not timer trigger", $uid, $pos, $maxSpecTime);
			}
			Logger::trace("GuildRobLogic::rewardWhenSpecTimeOut uid[%d] pos[%d] max spec time[%d]", $uid, $pos, $maxSpecTime);
			
			$config = btstore_get()->GUILD_ROB['spec_barn_reward']->toArray();
			if (count($config) < 3 || intval($config[0]) <= 0)
			{
				throw new FakeException("config GUILD_ROB spec_barn_reward error, config:%s", $config);
			}
			 
			// 根据配置给奖励
			$timeGap = intval($config[0]);
			$baseGrain = intval($config[1]);
			$baseMerit = intval($config[2]);
				
			// 计算奖励
			$multiple = floor($maxSpecTime / $timeGap);
			$rewardMerit = 0;
			$rewardGrain = 0;
			if ($multiple > 0) 
			{
				$rewardMerit = $baseMerit * $multiple;
				$rewardGrain = $baseGrain * $multiple;
			
				$rewardArr = array
					(
						'userGrain' => $rewardGrain,
						'merit' => $rewardMerit,
					);
				
				/*
				 * 如果是定时器触发的，说明是在系统线程里，应该将其抛在玩家的线程里进行奖励
				 * 如果不是定时器触发的，说明是在玩家自己的线程里，直接加奖励就行啦
				 */
				if ($isTimerTrigger) 
				{
					RPCContext::getInstance()->executeTask($uid, 'guildrob.addUserReward', array($uid, $rewardArr));
				}
				else
				{
					self::addUserReward($uid, $rewardArr);
				}
				
				Logger::trace("GuildRobLogic::rewardWhenSpecTimeOut uid[%d] pos[%d] addUserReward ok, rewardArr:%s", $uid, $pos, $rewardArr);
			}
			else 
			{
				Logger::trace("GuildRobLogic::rewardWhenSpecTimeOut uid[%d] pos[%d] spec time is too less, no need to addUserReward, specTime[%d], timeGap[%d]", $uid, $pos, $maxSpecTime, $timeGap);
			}
			Logger::trace('onSpecTimerOut reward for user[%d], reward grain[%d], reward merit[%d]', $uid, $rewardGrain, $rewardMerit);
		}

		Logger::trace("GuildRobLogic::rewardWhenSpecTimeOut uid[%d] end...", $uid);
		return $rewardArr;
	}
	
	public static function addUserReward($uid, $rewardArr, $killTime = 0, $isNpc = 0)
	{
		if ($uid <= 0 || empty($rewardArr))
		{
			return;
		}
		
		Logger::debug("isNpc:%d uid:%d guid:%d ", $isNpc, $uid, RPCContext::getInstance()->getUid());
		
		if ( $isNpc && $uid != RPCContext::getInstance()->getUid() )
		{
		    RPCContext::getInstance()->executeTask($uid, 'guildrob.addUserReward', array($uid, $rewardArr, $killTime, 0));
		    return ;
		}
	
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$robUserObj = GuildRobUserObj::getInstance($uid);
	
		if (isset($rewardArr['userGrain']))
		{
			$num = $rewardArr['userGrain'];
			$guildMemberObj->addGrainNum($num);
			$robUserObj->addUserGrainNum($num);
		}
	
		if (isset($rewardArr['guildGrain']))
		{
			$num = $rewardArr['guildGrain'];
			$robUserObj->addGuildGrainNum($num);
		}
	
		if (isset($rewardArr['merit']))
		{
			$num = $rewardArr['merit'];
			$guildMemberObj->addMeritNum($num);
			$robUserObj->addMeritNum($num);
		}
	
		if (isset($rewardArr['contr']))
		{
			$num = $rewardArr['contr'];
			$guildMemberObj->addContriPoint($num);
			$robUserObj->addContrNum($num);
		}
		
		if ($killTime > 0) 
		{
			$robUserObj->increKillNum($killTime);
		}
	
		$guildMemberObj->update();
		$robUserObj->update();
	}
	
	public static function getInfo($uid)
	{
	    $guildRobUserObj = GuildRobUserObj::getInstance($uid);
	    $offlineTime = $guildRobUserObj->getOfflineTime();
	    return $offlineTime;
	}
	
	public static function offline($uid, $type)
	{
	    self::checkOffline($uid);
	    
	    $guildRobUserObj = GuildRobUserObj::getInstance($uid);
	    
	    $offlineTime = $guildRobUserObj->getOfflineTime();
	    
	    if ( GuildRobDef::GUILD_ROB_OFFLINE_TYPE_CONFIM == $type )
	    {
	        if ( !empty( $offlineTime ) )
	        {
	            throw new FakeException("user:%d has selected offline, time:%d ", $uid, $offlineTime);
	        }
	        
	        $now = intval( Util::getTime() );
	        $guildRobUserObj->setOffline($now);
	    }
	    else if ( GuildRobDef::GUILD_ROB_OFFLINE_TYPE_CANCEL == $type )
	    {
	        if ( empty( $offlineTime ) )
	        {
	            throw new FakeException("user:%d does not select offline, time:%d ", $uid, $offlineTime);
	        }
	        
	        $guildRobUserObj->setOffline(0);
	    }
	    
	    $guildRobUserObj->update();
	    
	    return intval( $guildRobUserObj->getOfflineTime() );
	}
	
	public static function checkOffline($uid)
	{
	    $userObj = EnUser::getUserObj($uid);
	    $userLevel = $userObj->getLevel();
	    
	    $needLevel = intval( btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GUILDROB_OFFLINE_NEED_LEVEL] );
	    
	    if ( $userLevel < $needLevel )
	    {
	        throw new FakeException("level not enough. level:%d need:%d ", $userLevel, $needLevel);
	    }
	    
	    return 'ok';
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
