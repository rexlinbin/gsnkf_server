<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRob.class.php 261387 2016-09-08 09:03:58Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/GuildRob.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-09-08 09:03:58 +0000 (Thu, 08 Sep 2016) $
 * @version $Revision: 261387 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : GuildRob
* Description : 军团抢粮战内部实现类
* Inherit     :
**********************************************************************************************************************/
class GuildRob implements IGuildRob
{
	private $uid;
	
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	public function create($defendGuildId)
	{
		$defendGuildId = intval($defendGuildId);
		
		if($defendGuildId <= 0)
		{
			throw new FakeException('guild rob create invalid params defend guild id[%d].', $defendGuildId);
		}
		
		return GuildRobLogic::create($this->uid, $defendGuildId);
	}
	
	public function enter($robId)
	{
		$robId = intval($robId);
		
		if($robId <= 0)
		{
			throw new FakeException('guild rob enter invalid params rob id[%d].', $robId);
		}
		
		return GuildRobLogic::enter($this->uid, $robId);
	}
	
	public function getEnterInfo()
	{
		return GuildRobLogic::getEnterInfo($this->uid);
	}
	
	public function join($transferId)
	{
		$transferId = intval($transferId);
		
		if($transferId < 0 || $transferId >= 2 * GuildRobConf::ROAD_NUM)
		{
			throw new FakeException('guild rob join invalid params transfer id[%d].', $transferId);
		}
		
		return GuildRobLogic::join($this->uid, $transferId);
	}
	
	public function leave()
	{
		return GuildRobLogic::leave($this->uid);
	}
	
	public function leaveSpecBarn($uid, $robId)
	{
		$robObj = GuildRobObj::getInstance($robId);
		if (FALSE !== $robObj->leaveSpecBarn($uid))
		{
			$robObj->update();
		}
	}
	
	public function removeJoinCd()
	{
		return GuildRobLogic::removeJoinCd($this->uid);
	}
	
	public function speedUp($multiple)
	{
		return 'ok';// 暂时不开放加速功能
		
		$multiple = intval($multiple);
		
		if($multiple <= 0)
		{
			throw new FakeException('guild rob speedUp invalid params multiple[%d].', $multiple);
		}
		
		return GuildRobLogic::speedUp($this->uid, $multiple);
	}
	
	public function enterSpecBarn($pos)
	{
		return 'ok';// 暂时不开放蹲点粮仓功能
		
		$pos = intval($pos);
		if($pos < 0 || $pos >= GuildRobConf::MAX_SPEC_BARN_NUMBER)
		{
			throw new FakeException('guild rob enterSpecBarn invalid params pos[%d] maxNum[%d]', $pos, GuildRobConf::MAX_SPEC_BARN_NUMBER);
		}
		
		$robId = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_GROUP_BATTLE_ID);
		if ($robId <= 0)
		{
			throw new FakeException("uid[%d] do not have robId in session when enterSpecBarn", $this->uid);
		}
		
		$quitBattleTime = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_QUIT_BATTLE_TIME);
		$leaveBattleTime = RPCContext::getInstance()->getSession(GuildRobDef::SESSION_LEAVE_BATTLE_TIME);
		
		/////////////////////////////////////////////////////////////
		// 蹲点粮仓相关操作，全部抛到SPECIAL_UID::GUILD_ROB系统用户线程去执行
		////////////////////////////////////////////////////////////
		// 暂时不开放蹲点粮仓功能
		//RPCContext::getInstance()->executeTask(SPECIAL_UID::GUILD_ROB, 'guildrob.enterSpecBarnImplement', array($this->uid, $robId, $pos, $quitBattleTime, $leaveBattleTime));
		
		return 'ok';
	}
	
	public function enterSpecBarnImplement($uid, $robId, $pos, $quitBattleTime, $leaveBattleTime)
	{
		return GuildRobLogic::enterSpecBarn($uid, $robId, $pos, $quitBattleTime, $leaveBattleTime);
	}
	
	public function getRankByKill($onlyMysql = FALSE)
	{
		return GuildRobLogic::getRankByKill($this->uid, $onlyMysql);
	}
	
	public function getGuildRobAreaInfo($areaId, $pattern = '')
	{
		$areaId = intval($areaId);
		if($areaId <= 0)
		{
			throw new FakeException('guild rob getGuildRobList invalid params areaId[%d].', $areaId);
		}
		
		return GuildRobLogic::getGuildRobAreaInfo($this->uid, $areaId, $pattern);
	}
	
	public function getGuildRobInfo()
	{
		return GuildRobLogic::getGuildRobInfo($this->uid);
	}
	
	public function leaveGuildRobArea()
	{
		return GuildRobLogic::leaveGuildRobArea($this->uid);
	}
	
	public function getInfo()
	{
	    return GuildRobLogic::getInfo($this->uid);
	}
	
	public function offline($type=1)
	{
	    return GuildRobLogic::offline($this->uid, $type);
	}
	
	public function addUserReward($uid, $rewardArr, $killTime=0, $isNpc=0)
	{
	    $guid = RPCContext::getInstance()->getSession('global.uid');
	    
	    if($guid == null)
	    {
	        RPCContext::getInstance()->setSession('global.uid', $uid);
	    }
	    else if($uid != $guid)
	    {
	        Logger::fatal('GuildRob addUserReward, uid:%d, guid:%d', $uid, $guid);
	        return;
	    }
	    
		return GuildRobLogic::addUserReward($uid, $rewardArr, $killTime, $isNpc);
	}
	
	/*
	 * 以下是lcserver的回调
	 */
	public function onSpecTimerOut($robId, $pos, $uid, $beginTime)
	{
		Logger::trace("GuildRob::onSpecTimerOut uid[%d] begin...", $uid);
	
		$robObj = GuildRobObj::getInstance($robId);
		$specBarnInfo = $robObj->getSpecBarn($pos);
		Logger::trace("GuildRob::onSpecTimerOut uid[%d] rob battle[%d] spec barn[%d] begin time[%d]: get specInfo ok:[%s]", $uid, $robId, $pos, $beginTime, $specBarnInfo);
	
		if (!empty($specBarnInfo)
		&& $uid == $specBarnInfo[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_UID]
		&& $beginTime == $specBarnInfo[GuildRobField::TBL_VA_EXTRA_SUBFIELD_SPEC_BARN_BEGIN])
		{
			Logger::trace("GuildRob::onSpecTimerOut uid[%d] rob battle[%d] spec barn[%d] begin time[%d]: timer is effect, curr spec info:%s", $uid, $robId, $pos, $beginTime, $specBarnInfo);
				
			// 占领到期，领奖
			$rewardArr = GuildRobLogic::rewardWhenSpecBarnTimeOut($robId, $pos, $uid, TRUE);
			Logger::trace("GuildRob::onSpecTimerOut uid[%d] rob battle[%d] spec barn[%d] begin time[%d]: spec reward:%s", $uid, $robId, $pos, $beginTime, $rewardArr);
				
			// 设置Lcserver中的leaveBattleTime
			$proxy = new PHPProxy('lcserver');
			$userData = array('leaveBattleTime' => Util::getTime());
			$ret = $proxy->setGroupBattleUserInfo($uid, $robId, $userData);
			Logger::trace("GuildRob::onSpecTimerOut uid[%d] rob battle[%d] spec barn[%d] begin time[%d]: reset user info of lcserver:%s, ret:%s", $uid, $robId, $pos, $beginTime, $userData, $ret);
				
			// 广播该蹲点粮仓占领到期消息
			$broadcastInfo = array
			(
					'outSpecId' => $pos,
			);
			RPCContext::getInstance()->broadcastGroupBattle($robId, $broadcastInfo, PushInterfaceDef::GUILD_ROB_SPEC);
			Logger::trace("GuildRob::onSpecTimerOut uid[%d] rob battle[%d] spec barn[%d] begin time[%d]: broadcast when spec time out ok, broadcastInfo:%s", $uid, $robId, $pos, $beginTime, $broadcastInfo);
				
			// 为该玩家单独发送消息，包括cd等
			$infoForCurrUser = array
			(
					'joinCd' => Util::getTime() + intval(btstore_get()->GUILD_ROB['join_cd']),
					'reward' => $rewardArr,
			);
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_ROB_SPEC, $infoForCurrUser);
			Logger::trace("GuildRob::onSpecTimerOut uid[%d] rob battle[%d] spec barn[%d] begin time[%d]: send info from curr user:%s when spec time out", $uid, $robId, $pos, $beginTime, $infoForCurrUser);
				
			// 更新GuildRobObj
			$robObj->makeSpecBarnEmpty($pos);
			$robObj->update();
		}
		else
		{
			Logger::trace("GuildRob::onSpecTimerOut uid[%d] rob battle[%d] spec barn[%d] begin time[%d]: timer is skip, curr spec info:%s", $uid, $robId, $pos, $beginTime, $specBarnInfo);
		}
	
		Logger::trace("GuildRob::onSpecTimerOut uid[%d] end...", $uid);
	}
	
	public function onFightWin($robId, $attackerId, $winnerId, $loserId, $winStreak, $terminalStreak, $brid, $replayData, $fightEndTime, $isWinnerOut, $isNpcWinner=0, $isNpcLoser=0)
	{
		Logger::trace('onFightWin uid[%d] win uid[%d] in battleId[%d], winStreak[%d] terminalStreak[%d] brid[%d] begin...', 
						$winnerId, $loserId, $robId, $winStreak, $terminalStreak, $brid);
		
		// 记录战报数据,  发一下系统消息
		if($brid > 0)
		{
			EnBattle::addRecord($brid, $replayData);

			if($attackerId == $winnerId)
			{
				$defenderId = $loserId;
			}
			else
			{
				$defenderId = $winnerId;
			}
			
			$atkUser = EnUser::getUserObj($attackerId);
			$defUser = EnUser::getUserObj($defenderId);
		}
		Logger::trace('onFightWin uid:%d win uid:%d. send msg ok', $winnerId, $loserId);
		
		// 增加玩家个人击杀次数
		$loserRobUserObj = GuildRobUserObj::getInstance($loserId);
		$winnerRobUsrObj = GuildRobUserObj::getInstance($winnerId);
		
		// 获得击杀和被击杀玩家所在军团ID
		$loserGuildId = $loserRobUserObj->getGuildId();
		$winnerGuildId = $winnerRobUsrObj->getGuildId();
		
		// 发奖励
		$reward = GuildRobLogic::rewardWhenKill($attackerId, $winnerId, $loserId, $winnerGuildId, $loserGuildId, $winStreak, $terminalStreak, $fightEndTime, $isNpcWinner, $isNpcLoser);
		$winnerReward = $reward['winner'];
		$loserReward = $reward['loser'];
		Logger::trace('onFightWin uid:%d win uid:%d. reward:%s', $winnerId, $loserId, $reward);
		
		// 广播击杀排行榜
		GuildRobUtil::broadcastKillTopN($robId, $winnerId);
		Logger::trace('onFightWin uid:%d win uid:%d. broadcastKillTopN end', $winnerId, $loserId);
		
		// 给败者发送消息
		$loserMsg = array
			(
				'reward' => $loserReward,
				'extra' => array
					(
						'adversaryName' => $winnerRobUsrObj->getUname(),
						'joinCd' => $fightEndTime + intval(btstore_get()->GUILD_ROB['join_cd']),
					),
			);
		Logger::trace('onFightWin uid:%d win uid:%d. send loser message:%s', $winnerId, $loserId, $loserMsg);
		RPCContext::getInstance()->sendMsg(array($loserId), PushInterfaceDef::GUILD_ROB_FIGHT_LOSE, $loserMsg);
		
		// 返回胜者消息
		$winnerMsg = array
			(
				'reward' => $winnerReward,
				'extra' => array
					(
						'adversaryName' => $loserRobUserObj->getUname(),
					),
			);
		if ($isWinnerOut)
		{
			$winnerMsg['extra']['winnerOut'] = TRUE;
			$winnerMsg['extra']['joinCd'] = $fightEndTime + intval(btstore_get()->GUILD_ROB['join_cd']);
		}
		Logger::trace('onFightWin uid:%d win uid:%d. send winner message:%s', $winnerId, $loserId, $winnerMsg);
		
		Logger::trace('onFightWin uid[%d] win uid[%d] in battleId[%d], winStreak[%d] terminalStreak[%d] brid[%d] end...', $winnerId, $loserId, $robId, $winStreak, $terminalStreak, $brid);
		return $winnerMsg;
	}
	
	public function onFightLose($uid, $fightEndTime, $isNpc=0)
	{
		Logger::trace('uid:%d lose fight at time:%d begin...', $uid, $fightEndTime);
		RPCContext::getInstance()->setSession(GuildRobDef::SESSION_SPEED_UP_TIMES, 0);
		Logger::trace('uid:%d lose fight at time:%d end...', $uid, $fightEndTime);
	}
	
	public function onTouchDown($robId, $guildId, $uid, $touchDownTime, $isNpc=0)
	{
		Logger::trace('uid:%d guildId:%d touch down at time:%d in battle:%d begin...', $uid, $guildId, $touchDownTime, $robId);
		
		// 设置session
		RPCContext::getInstance()->setSession(GuildRobDef::SESSION_SPEED_UP_TIMES, 0);
		
		// 发奖励
		$rewardInfo = GuildRobLogic::rewardWhenTouchDown($robId, $guildId, $uid, $isNpc);
		Logger::trace('uid:%d guildId:%d touch down at time:%d in battle:%d: rewardInfo:%s', $uid, $guildId, $touchDownTime, $robId, $rewardInfo);
		
		Logger::trace('uid:%d guildId:%d touch down at time:%d in battle:%d end...', $uid, $guildId, $touchDownTime, $robId);
		$ret = array
			(
				'reward' => $rewardInfo,
				'extra' => array
					(
						'joinCd' => $touchDownTime + intval(btstore_get()->GUILD_ROB['join_cd']),
					),
			);
		return $ret;
	}
	
	public function npcJoin($battleId, $uid, $guildId)
	{
	    GuildRobLogic::rewardWhenJoin($uid, 1);
	    
	    $robObj = GuildRobObj::getInstance($battleId);
	    $robUserObj = GuildRobUserObj::getInstance($uid);
	    if ($robUserObj->getRewardTime() > 0
	        || $robUserObj->getRobId() == 0
	        || $robUserObj->getJoinTime() < $robObj->getStartTime())
	    {
	        $robUserObj->start($battleId, $guildId);
	        $robUserObj->update();
	    }
	}
	
	public function getBattleData($battleId, $uid, $guildId)
	{
	    $type = FALSE;
	    if ( $guildId == $battleId )
	    {
	        $type = TRUE;
	    }
	    
	    $battleData = GuildRobUtil::getBattleData($uid, $type);
	
	    RPCContext::getInstance()->setOfflineBattleData($uid, $battleId, $battleData);
	}
	
	///////////////////////////////////////////////////////////////////////
	// lcserver将这个请求放在SPECIAL_UID::GUILD_ROB系统用户线程执行
	//////////////////////////////////////////////////////////////////////
	public function onBattleEnd($robId, $robDuration, $robGrainFromLcserver)
	{
		Logger::trace('rob battle end[%d], begin...', $robId);
		
		// 获得整场战斗持续时间， 如果从lcserver获得的结束时间不为0，抢粮战是属于提前结束，$robDuration就是抢粮战用时，否则打完整场战斗，持续时间是配置的时间
		if ($robDuration == 0)
		{
			$robDuration = intval(btstore_get()->GUILD_ROB['battle_time']);
		}
		Logger::trace('rob battle end[%d]: rob duration[%d]', $robId, $robDuration);
		
		// 更新抢夺表中的状态，让其结束这场抢粮战
		$robObj = GuildRobObj::getInstance($robId);
		$robGrain = $robObj->getTotalRobGrain();
		$robObj->end();
		$robObj->update();
		Logger::trace('rob battle end[%d]: update robObj, endSpecBarn, cancel timer, sync grain all ok', $robId);
		
		// 记录一下lcserver和rpcfw记录的抢夺粮草是否一致
		if ($robGrainFromLcserver == $robGrain) 
		{
			Logger::trace('rob battle end[%d]: rob grain is same:%d', $robId, $robGrain);
		}
		else 
		{
			Logger::warning('rob battle end[%d]: rob grain is diff, lcserver rob grain:%d, rpcfw rob grain:%d', $robId, $robGrainFromLcserver, $robGrain);
		}
		
		// 给所有用户发送结算数据
		$userList = $robObj->sendReckonMsgWhenEnd($robDuration);
		Logger::trace('rob battle end[%d]: send reckon msg all ok', $robId);
		
		// 通知lcserver释放战斗
		RPCContext::getInstance()->freeGroupBattle($robId);
		Logger::trace('rob battle end[%d]: notify lcserver to free rob battle', $robId);
		
		// 活动结束后会根据玩家击杀的排名再发一份击杀排名奖励
		Util::asyncExecute('guildrob.doRewardOnEnd', array($robId, $userList));
		Logger::trace('rob battle end[%d]: do rank reward when rob end on main machine', $robId);
		
		Logger::trace('rob battle end[%d], end...', $robId);
	}
	
	public function doRewardOnEnd($robId, $userList)
	{
		Logger::trace('doRewardOnEnd rob battle[%d], begin...', $robId);
		
		$robObj = GuildRobObj::getInstance($robId);
		$attackGuildId = $robId;
		$defendGuildId = $robObj->getDefendGid();
		$totalRobGrain = $robObj->getTotalRobGrain();
		
		// 根据击杀排行榜给每一个玩家发送一份击杀奖励，-----------  目前抢粮战结束以后不发击杀奖，先把这注释掉，万一以后又要发呢
		/*$rank = 0;
		foreach($userList as $uid => $userInfo)
		{
			// 发奖
			$rank++;
			$rewardArr = GuildRobLogic::rewardWhenEnd($uid, $rank);
			Logger::trace('doRewardOnEnd rob battle[%d]:send rank reward for user[%d], rank[%d], reward:%s', $robId, $uid, $rank, $rewardArr);
		}
		Logger::trace('doRewardOnEnd rob battle[%d]:send rank reward over', $robId);*/
		
		// 将状态置为发奖状态
		$robObj->setStage(GuildRobField::GUILD_ROB_STAGE_REWARD);
		$robObj->update();
		
		// 更新GuildRobUserObj中的reward_time字段，直接批量更新
		if (!empty($userList)) 
		{
			$arrUid = Util::arrayExtract($userList, GuildRobUserField::TBL_FIELD_UID);
			$arrCond = array(array(GuildRobUserField::TBL_FIELD_UID, 'in', $arrUid));
			$arrField = array(GuildRobUserField::TBL_FIELD_REWARD_TIME => Util::getTime());
			GuildRobDao::updateUser($arrCond, $arrField);
			Logger::trace('doRewardOnEnd rob battle[%d]:update all GuildRobUserObj ok', $robId);
		}
		else 
		{
			Logger::trace('doRewardOnEnd rob battle[%d]:userList empty, no need to update all GuildRobUserObj', $robId);
		}
		
		// 更新抢粮战场这两个军团的状态信息
		GuildRobUtil::guildRobInfoChanged($attackGuildId, TRUE, 0);
		GuildRobUtil::guildRobInfoChanged($defendGuildId, TRUE, 0);
		Logger::trace('doRewardOnEnd rob battle[%d]: notify front to update guild rob info', $robId);
		
		// 给所有用户发送邮件，在main机器上执行
		if (!empty($userList))
		{
			$robObj->sendMailWhenEnd($userList);
			Logger::trace('doRewardOnEnd rob battle[%d]: send mail for all ok', $robId);
		}
		else
		{
			Logger::trace('doRewardOnEnd rob battle[%d]:userList empty, no need to send mail', $robId);
		}
	
		Logger::trace('doRewardOnEnd rob battle[%d], end...', $robId);
	}
	
	public function onBattleEndByRpcfw($robId, $defendGuildId, $startTime)
	{
		Logger::trace('onBattleEndByRpcfw recheck guild[%d] rob guild[%d] at time[%d] begin...', $robId, $defendGuildId, $startTime);
		
		$robObj = GuildRobObj::getInstance($robId);
		if ($defendGuildId == $robObj->getDefendGid() && $startTime == $robObj->getStartTime()) 
		{
			if (!$robObj->isEnd())
			{
				Logger::warning('onBattleEndByRpcfw recheck guild[%d] rob guild guild[%d] at time[%d] is not end successfully', $robId, $defendGuildId, $startTime);
			}
			else 
			{
				Logger::trace('onBattleEndByRpcfw recheck guild[%d] rob guild guild[%d] at time[%d] end successfully', $robId, $defendGuildId, $startTime);
			}
		}
		else 
		{
			Logger::trace('onBattleEndByRpcfw recheck guild[%d] rob guild guild[%d] at time[%d], may be guild[%d] attack guild[%d] at time[%d], skip it', $robId, $defendGuildId, $startTime, $robObj->getDefendGid(), $robObj->getStartTime());
		}
		
		Logger::trace('onBattleEndByRpcfw recheck guild[%d] rob guild[%d] at time[%d] end...', $robId, $defendGuildId, $startTime);
	}
	
	public function robNotice($attackGuildId, $defendGuildId, $readyDuration)
	{
		Logger::trace("GuildRob::robNotice guild[%d] rob guild[%d] after [%d] seconds begin...", $attackGuildId, $defendGuildId, $readyDuration);
		
		// 发送邮件
		MailTemplate::guildrobNotice($attackGuildId, $defendGuildId, $readyDuration);
		Logger::trace("GuildRob::robNotice guild[%d] rob guild[%d] after [%d] seconds : send mail", $attackGuildId, $defendGuildId, $readyDuration);
	
		// 通知平台
		if (defined('PlatformConfig::SEND_GUILD_ROB_MESSAGE') && PlatformConfig::SEND_GUILD_ROB_MESSAGE === TRUE)
		{
			Logger::trace("GuildRob::robNotice guild[%d] rob guild[%d] after [%d] seconds : need to notify platform", $attackGuildId, $defendGuildId, $readyDuration);
			
			$platfrom = ApiManager::getApi();
			$arrPid = GuildRobUtil::getPids($attackGuildId, $defendGuildId);
			$arrAttackPids = $arrPid[$attackGuildId];
			$arrDefendPids = $arrPid[$defendGuildId];
			
			$attackGuildName = GuildObj::getInstance($attackGuildId)->getGuildName();
			$defendGuildName = GuildObj::getInstance($defendGuildId)->getGuildName();
			
			$attackerMsg = sprintf(I18nDef::GUILD_ROB_ATTACKER_MSG, $defendGuildName);
			$defenderMsg = sprintf(I18nDef::GUILD_ROB_DEFENDER_MSG, $attackGuildName);
			
			$attackPids = '';
			foreach ($arrAttackPids as $aPid)
			{
				if (!empty($attackPids))
				{
					$attackPids .= ',';
				}
				$attackPids .= $aPid;
			}
			$argv = array
			(
					'pid' => $attackPids,
					'msg' => $attackerMsg,
					'devflag' => FrameworkConfig::DEBUG ? 1 : 0,
			);
			$platfrom->users('sendmsg', $argv);
			Logger::trace("GuildRob::robNotice guild[%d] rob guild[%d] after [%d] seconds : notify attackers, msg:%s, pids:%s", $attackGuildId, $defendGuildId, $readyDuration, $attackerMsg, $attackPids);
			
			$defendPids = '';
			foreach ($arrDefendPids as $aPid)
			{
				if (!empty($defendPids))
				{
					$defendPids .= ',';
				}
				$defendPids .= $aPid;
			}
			$argv = array
			(
					'pid' => $defendPids,
					'msg' => $defenderMsg,
					'devflag' => FrameworkConfig::DEBUG ? 1 : 0,
			);
			$platfrom->users('sendmsg', $argv);
			Logger::trace("GuildRob::robNotice guild[%d] rob guild[%d] after [%d] seconds : notify defenders, msg:%s, pids:%s", $attackGuildId, $defendGuildId, $readyDuration, $defenderMsg, $defendPids);
		}
		else 
		{
			Logger::trace("GuildRob::robNotice guild[%d] rob guild[%d] after [%d] seconds : no need to notify platform, not define PlatformConfig::SEND_GUILD_ROB_MESSAGE or FALSE", $attackGuildId, $defendGuildId, $readyDuration);
		}
		
		Logger::trace("GuildRob::robNotice guild[%d] rob guild[%d] after [%d] seconds end...", $attackGuildId, $defendGuildId, $readyDuration);
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */