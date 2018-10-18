<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyLogic.class.php 234198 2016-03-22 12:43:37Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/GuildCopyLogic.class.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-22 12:43:37 +0000 (Tue, 22 Mar 2016) $
 * @version $Revision: 234198 $
 * @brief 
 *  
 **/
 
class GuildCopyLogic
{
	/**
	 * 获得玩家的军团副本相关信息，如果玩家还不在一个军团，则返回空
	 * 
	 * @param int $uid
	 * @return array
	 */
	public static function getUserInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		$ret = array();
		
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			$ret = array();
		}
		else 
		{
			$guildCopyObj = GuildCopyObj::getInstance($guildId);
			$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
			
			$ret['curr'] = $guildCopyObj->getCurrCopy();
			$ret['next'] = $guildCopyObj->getNextCopy();
			$ret['max_pass_copy'] = $guildCopyObj->getMaxPassCopy();
			$ret['refresh_num'] = $guildCopyObj->getRefreshNum();
			$ret['refresh_time'] = $guildCopyUserObj->isRefresh() ? $guildCopyUserObj->getRefreshTime() : 0;
			$ret['pass_time'] = $guildCopyObj->isCurrCopyDown() ? $guildCopyObj->getPassTime() : 0;
			$ret['atk_damage'] = $guildCopyUserObj->getAtkDamage();
			$ret['atk_num'] = $guildCopyUserObj->getAtkNum();
			$ret['buy_num'] = $guildCopyUserObj->getBuyNum();
			$ret['recv_pass_reward_time'] = $guildCopyUserObj->isRecvPassReward() ? $guildCopyUserObj->getRecvPassRewardTime() : 0;
			$ret['recv_box_reward_time'] = $guildCopyUserObj->isRecvBoxReward() ? $guildCopyUserObj->getRecvBoxRewardTime() : 0;
			$ret['total_hp'] = $guildCopyObj->getTotalHp();
			$ret['curr_hp'] = $guildCopyObj->getCurrHp();
			$ret['refresher'] = $guildCopyObj->getRefresher();
			
			$ret['boss_info'] = self::bossInfo($uid);
		}
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	/**
	 * 获得当前军团副本据点的详细信息
	 * 
	 * @param int $uid
	 * @param int $copyId
	 * @throws FakeException
	 * @return array
	 */
	public static function getCopyInfo($uid, $copyId)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}

		$guildCopyObj = GuildCopyObj::getInstance($guildId);
		if ($guildCopyObj->getCurrCopy() != $copyId) 
		{
			throw new FakeException('not curr copy id, param copy id[%d], curr copy id[%d]', $copyId, $guildCopyObj->getCurrCopy());
		}
		
		/*
		 * 第一次调用getCopyInfo，会初始化所有据点的国家类型，这个国家类型是随机的。
		 * 如果发现是第一次调用，需要将这个随机出来的国家类型保存下来,注意同步的问题!!!!
		 */
		list($ret, $isInit) = $guildCopyObj->getCopyInfo();
		if ($isInit) 
		{
			GuildCopyObj::releaseInstance($guildId);//先释放缓存
			try
			{
				$locker = new Locker();
				$key = 'guildcopy_' . $guildId;
				$locker->lock($key);
				
				$guildCopyObj = GuildCopyObj::getInstance($guildId);
				list($ret, $isInit) = $guildCopyObj->getCopyInfo();
				if ($isInit)
				{
					$guildCopyObj->update();
				}
				$locker->unlock($key);
			}
			catch (Exception $e)
			{
				$locker->unlock($key);
				throw $e;
			}
		}
		
		Logger::trace('function[%s] param[%s] raw copy info[%s]', __FUNCTION__, func_get_args(), $ret);
		
		// 将血量信息加上总血量
		for ($i = 1; $i <= GuildCopyCfg::BASE_COUNT; ++$i)
		{
			// 计算血量
			if (isset($ret[$i]['hp'])) 
			{
				list($fmt, $battleType, $endCondition) = $guildCopyObj->getBattleFormation($i);
				foreach ($fmt['arrHero'] as $pos => $aHeroInfo)
				{
					$maxHp = $aHeroInfo['maxHp'];
					if (isset($ret[$i]['hp'][$aHeroInfo['hid']]))
					{
						$ret[$i]['hp'][$aHeroInfo['hid']] = array('total' => $maxHp, 'curr' => $ret[$i]['hp'][$aHeroInfo['hid']]);//当前血量为记录的值
					}
					else 
					{
						$ret[$i]['hp'][$aHeroInfo['hid']] = array('total' => $maxHp, 'curr' => 0);//当前血量为0
					}
				}
			}
			
			// 去掉据点最大血量maxHp字段
			if (isset($ret[$i]['maxHp'])) 
			{
				unset($ret[$i]['maxHp']);
			}
		}
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	/**
	 * 设置明天的攻打目标
	 * 
	 * @param int $uid
	 * @param int $copyId
	 * @throws FakeException
	 * @return string
	 */
	public static function setTarget($uid, $copyId)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		// 检查是否有权限
		if (GuildMemberType::PRESIDENT != $guildMemberObj->getMemberType()) 
		{
			throw new FakeException('no right to set target, member type[%d]', $guildMemberObj->getMemberType());
		}
		
		try
		{
			$locker = new Locker();
			$key = 'guildcopy_' . $guildId;
			$locker->lock($key);
			
			// 检查军团是否可以攻打这个副本
			$guildCopyObj = GuildCopyObj::getInstance($guildId);
			if (!$guildCopyObj->canBeTarget($copyId)) 
			{
				throw new FakeException('copy id[%d] not open for guild, max pass copy[%d]', $copyId, $guildCopyObj->getMaxPassCopy());
			}
		
			// 设置明天的攻打目标
			$guildCopyObj->setNextCopy($copyId);
			$guildCopyObj->update();
			
			$ret = 'ok';
			
			$locker->unlock($key);
		}
		catch (Exception $e)
		{
			$locker->unlock($key);
			throw $e;
		}
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 攻打军团副本的某一个据点
	 * 
	 * @param int $uid
	 * @param int $copyId
	 * @param int $baseIndex
	 * @throws FakeException
	 * @throws Exception
	 * @return array
	 */
	public static function attack($uid, $copyId, $baseIndex)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		try 
		{
			$locker = new Locker();
			$key = 'guildcopy_' . $guildId;
			$locker->lock($key);
			
			// 检查copyId是否是当前军团攻打目标
			$guildCopyObj = GuildCopyObj::getInstance($guildId);
			if ($guildCopyObj->getCurrCopy() != $copyId)
			{
				throw new FakeException('not curr copy id, param copy id[%d], curr copy id[%d]', $copyId, $guildCopyObj->getCurrCopy());
			}
			
			// 检查当前据点是否已经被攻破
			if ($guildCopyObj->isBaseDown($baseIndex))
			{
				$locker->unlock($key);
				Logger::warning('copy id[%d] base index[%d] is already down when attack.', $copyId, $baseIndex);
				
				return array('ret' => "dead");
			}
			
			// 检查玩家是否还有攻击次数
			$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
			if ($guildCopyUserObj->getAtkNum() <= 0)
			{
				throw new FakeException('no attack num, curr atk num[%d]', $guildCopyUserObj->getAtkNum());
			}
			
			// **************************************开始攻打*******************************************************
			
			// 1.获得玩家的战斗数据
			$userObj = EnUser::getUserObj($uid);
			$userBattleFormation = $userObj->getBattleFormation();
			$userBattleFormation = GuildCopyUtil::addCountryAddition($userBattleFormation, $guildCopyObj->getBaseCountryType($baseIndex));
			
			// 2.获得这个据点的战斗数据，战斗类型，结束条件
			list($baseBattleFormation, $battleType, $endCondition) = $guildCopyObj->getBattleFormation($baseIndex);
		
			// 3.战斗
			$atkRet = EnBattle::doHero($userBattleFormation, $baseBattleFormation, $battleType, NULL, $endCondition);
			
			// 4.获取这次战斗的伤害，以每个武将的costHp为准
			$damage = 0;
			foreach ($atkRet['server']['team2'] as $aHeroInfo)
			{
				$damage += $aHeroInfo['costHp'];
			}
			if ($damage < 0) 
			{
				Logger::warning('GUILD_COPY_ATTACK : can not have army which resume hp, cehua!!!!!!!!!!!!!!!!');
				$damage = 0;
			}
			
			// 5.是否击杀
			$kill = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
			
			// 6.发攻击奖励，发击杀奖励(如果有的话)
			$arrReward = btstore_get()->GUILD_COPY_INFO[$copyId]['attack_reward']->toArray();
			if ($kill) 
			{
				$arrReward = array_merge($arrReward, btstore_get()->GUILD_COPY_INFO[$copyId]['kill_reward']->toArray());
			}
			$rewardRet = RewardUtil::reward3DArr($uid, $arrReward, StatisticsDef::ST_FUNCKEY_GUILD_COPY_ATTACK_REWARD);
			if ($rewardRet[UpdateKeys::USER]) 
			{
				$userObj->update();
			}
			if ($rewardRet[UpdateKeys::BAG]) 
			{
				BagManager::getInstance()->getBag($uid)->update();
			}
			
			// 7.减少玩家的攻击次数，增加玩家对这个据点的伤害，增加玩家今天的总伤害
			$guildCopyUserObj->decreAtkNum();
			$guildCopyUserObj->addBaseDamage($baseIndex, $damage);
			$guildCopyUserObj->addAtkDamage($damage);
			$guildCopyUserObj->update();
			
			// 8.更新这个base的血量信息
			$guildCopyObj->updateBaseHp($baseIndex, $atkRet['server']['team2'], $kill);
			
			// 9.更新这个base的最大伤害玩家
			$curMaxDamage = $guildCopyObj->getMaxDamage($baseIndex);
			$curUserDamage = $guildCopyUserObj->getBaseDamage($baseIndex);
			if ($curUserDamage > $curMaxDamage) 
			{
				$guildCopyObj->setMaxDamager($baseIndex, $uid, $userObj->getHeroManager()->getMasterHeroObj()->getHtid(), $userObj->getUname(), $curUserDamage);
			}
			
			// 10.判断这个副本是否通关
			if ($guildCopyObj->isCurrCopyDown()) 
			{
				$guildCopyObj->passCurrCopy();
				Logger::info('GUILD_COPY_ATTACK : guild[%d] cur copy id[%d] is passed at time[%s], last attack uid[%d]', $guildId, $guildCopyObj->getCurrCopy(), strftime('%Y%m%d-%H%M%S', $guildCopyObj->getPassTime()), $uid);
				// 通关之后要广播一下，告诉军团成员今天副本已经通过啦，不要再搞乱七八糟的东西啦
				RPCContext::getInstance()->sendFilterMessage('guild', $guildId, PushInterfaceDef::GUILD_COPY_CURR_COPY_PASS, array('uname' => $userObj->getUname()));
			}
			$guildCopyObj->update();
			
			// 11.返回值
			$ret = array();
			$ret['ret'] = 'ok';
			$ret['fight_ret'] = $atkRet['client'];
			$ret['damage'] = $damage;
			$ret['kill'] = $kill ? 1 : 0;
			$baseInfo = $guildCopyObj->getBaseInfo($baseIndex);
			foreach ($baseBattleFormation['arrHero'] as $pos => $aHeroInfo)
			{
				$maxHp = $aHeroInfo['maxHp'];
				if (isset($baseInfo['hp'][$aHeroInfo['hid']]))
				{
					$ret['hp'][$aHeroInfo['hid']] = array('total' => $maxHp, 'curr' => $baseInfo['hp'][$aHeroInfo['hid']]);
				}
				else
				{
					$ret['hp'][$aHeroInfo['hid']] = array('total' => $maxHp, 'curr' => 0);
				}
			}
			Logger::trace('GUILD_COPY_ATTACK : uid[%d] guildId[%d] attact ok, damage[%d], kill[%d], reward[%s]', $uid, $guildId, $damage, ($kill ? 'yes' : 'no'), $arrReward);
			
			// 添加每日任务
			EnActive::addTask(ActiveDef::GUILDCOPY);
			
			$locker->unlock($key);
		} 
		catch (Exception $e) 
		{
			$locker->unlock($key);
			throw $e;
		}
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	/**
	 * 获得军团副本的全服排行和军团成员排行
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function getRankList($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		// ************************************* 生成全服排行数据  *****************************************************************
		
		// 获得玩家伤害信息
		$arrCond = array
		(
				array(GuildCopyUserField::TBL_FIELD_ATK_DAMAGE, '>', 0),
				array(GuildCopyUserField::TBL_FIELD_UPDATE_TIME, '>', strtotime(date('Ymd', Util::getTime()))),
		);
		$arrField = array
		(
				GuildCopyUserField::TBL_FIELD_UID,
				GuildCopyUserField::TBL_FIELD_ATK_DAMAGE,
		);
		// 现在是一下把所有玩家都拉出来,如果排行帮人数超过100，就显示100个
		$arrDamageInfo = GuildCopyDao::getRankListByDamage($arrCond, $arrField, (GuildCopyCfg::ALL_RANK_COUNT > DataDef::MAX_FETCH ? DataDef::MAX_FETCH : GuildCopyCfg::ALL_RANK_COUNT));
		$arrDamageInfo = Util::arrayIndex($arrDamageInfo, GuildCopyUserField::TBL_FIELD_UID);
		
		// 获得玩家的一些基本信息
		$arrUid = Util::arrayExtract($arrDamageInfo, GuildCopyUserField::TBL_FIELD_UID);
		$arrUserInfo = array();
		$i = 0;
		$count = CData::MAX_FETCH_SIZE;
		while ($count >= CData::MAX_FETCH_SIZE)
		{
			$arrPartUid = array_slice($arrUid, $i * CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE);
			if (empty($arrPartUid))
			{
				break;
			}
			$ret = EnUser::getArrUserBasicInfo($arrPartUid, array('uid', 'htid', 'vip', 'level', 'fight_force', 'dress', 'uname', 'guild_id'));
			$arrUserInfo = array_merge($arrUserInfo, $ret);
			$count = count($ret);
			$i++;
		}
		$arrUserInfo = Util::arrayIndex($arrUserInfo, 'uid');

		// 获得军团名称信息
		$arrGuildName = EnGuild::getArrGuildInfo(Util::arrayExtract($arrUserInfo, 'guild_id'), array(GuildDef::GUILD_NAME));
		
		$allRank = array();
		$rank = 0;
		foreach ($arrDamageInfo as $aUid => $aInfo)
		{
			if (!isset($arrUserInfo[$aUid])) 
			{
				throw new InterException('uid[%d] attacked in guild copy, but no user info', $aUid);
			}
			
			$damage = $aInfo[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE];
			$aInfo = $arrUserInfo[$aUid];
			$aInfo['guild_name'] = ($aInfo['guild_id'] == 0 ? '' : $arrGuildName[$aInfo['guild_id']][GuildDef::GUILD_NAME]);
			$aInfo['damage'] = $damage;
			$aInfo['rank'] = ++$rank;
			unset($aInfo['guild_id']);
			
			$allRank[] = $aInfo;
		}
		
		// ************************************* 生成军团成员排行数据  *****************************************************************
		$memberList = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID));
		$arrUid = Util::arrayExtract($memberList, GuildDef::USER_ID);
		$arrCond = array
		(
				array(GuildCopyUserField::TBL_FIELD_ATK_DAMAGE, '>', 0),
				array(GuildCopyUserField::TBL_FIELD_UID, 'IN', $arrUid),
				array(GuildCopyUserField::TBL_FIELD_UPDATE_TIME, '>', strtotime(date('Ymd', Util::getTime()))),
		);
		$arrField = array
		(
				GuildCopyUserField::TBL_FIELD_UID,
				GuildCopyUserField::TBL_FIELD_ATK_DAMAGE,
				GuildCopyUserField::TBL_FIELD_UPDATE_TIME,
		);
		$arrDamageInfo = GuildCopyDao::getRankListByDamage($arrCond, $arrField, DataDef::MAX_FETCH);
		$arrDamageInfo = Util::arrayIndex($arrDamageInfo, GuildCopyUserField::TBL_FIELD_UID);
		$arrUserInfo = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'htid', 'vip', 'level', 'fight_force', 'dress', 'uname'));
		
		$guildRank = array();
		$rank = 0;
		foreach ($arrDamageInfo as $aUid => $aInfo)
		{
			if (!isset($arrUserInfo[$aUid]))
			{
				throw new InterException('uid[%d] attacked in guild copy, but no user info', $aUid);
			}

			// sql中已经保证了肯定是同一天的，这不用管啦
			$damage = Util::isSameDay($arrDamageInfo[$aUid][GuildCopyUserField::TBL_FIELD_UPDATE_TIME]) ? $arrDamageInfo[$aUid][GuildCopyUserField::TBL_FIELD_ATK_DAMAGE] : 0;
			$aInfo = $arrUserInfo[$aUid];
			$aInfo['damage'] = $damage;
			$aInfo['rank'] = ++$rank;
				
			$guildRank[] = $aInfo;
		}
		
		// ************************************* 生成军团副本排行数据  *****************************************************************
		// 获得原始数据
		$arrCond = array
		(
				array(GuildCopyField::TBL_FIELD_MAX_PASS_COPY, '>', 0),
				array(GuildCopyField::TBL_FIELD_PASS_TIME, '>', 0),
		);
		$arrField = array
		(
				GuildCopyField::TBL_FIELD_GUILD_ID,
				GuildCopyField::TBL_FIELD_MAX_PASS_COPY,
				GuildCopyField::TBL_FIELD_MAX_PASS_TIME,
		);
		$arrGuildCopyInfo = GuildCopyDao::getGuildList($arrCond, $arrField, GuildCopyCfg::GUILD_RANK_COUNT);
		$arrGuildInfo = EnGuild::getArrGuildInfo(Util::arrayExtract($arrGuildCopyInfo, GuildCopyField::TBL_FIELD_GUILD_ID), array(GuildDef::GUILD_NAME, GuildDef::GUILD_LEVEL, GuildDef::FIGHT_FORCE));
		// 整理数据
		$rank = 0;
		foreach ($arrGuildCopyInfo as $index => $aCopyInfo)
		{
			$curGuildId = $aCopyInfo[GuildCopyField::TBL_FIELD_GUILD_ID];
			if (!isset($arrGuildInfo[$curGuildId])) 
			{
				throw new InterException('not found guild info of guild[%d]', $curGuildId);
			}
			
			$arrGuildCopyInfo[$index]['rank'] = ++$rank;
			$arrGuildCopyInfo[$index]['guild_name'] = $arrGuildInfo[$curGuildId][GuildDef::GUILD_NAME];
			$arrGuildCopyInfo[$index]['guild_level'] = $arrGuildInfo[$curGuildId][GuildDef::GUILD_LEVEL];
			$arrGuildCopyInfo[$index]['fight_force'] = $arrGuildInfo[$curGuildId][GuildDef::FIGHT_FORCE];
			$arrGuildCopyInfo[$index]['pass_time'] = $arrGuildCopyInfo[$index][GuildCopyField::TBL_FIELD_MAX_PASS_TIME];
			unset($arrGuildCopyInfo[$index][GuildCopyField::TBL_FIELD_MAX_PASS_TIME]);
		}
		
		$ret = array('all' => $allRank, 'guild' => $guildRank, 'guild_copy' => $arrGuildCopyInfo);
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	public static function addAtkNum($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		// 检查军团是否已经通关了副本
		$guildCopyObj = GuildCopyObj::getInstance($guildId);
		if ($guildCopyObj->isCurrCopyDown()) 
		{
			Logger::warning('curr copy is down at pass time[%s]', strftime("%Y%m%d-%H%M%S", $guildCopyObj->getPassTime()));
			return 'already_pass';
		}
		
		// 检查玩家购买次数是否超限
		$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
		$curBuyNum = $guildCopyUserObj->getBuyNum();
		$maxBuyNum = intval(btstore_get()->GUILD_COPY_RULE['max_buy_num']);
		if ($curBuyNum >= $maxBuyNum) 
		{
			throw new FakeException('cur buy num[%d] exceed max buy num[%d]', $curBuyNum, $maxBuyNum);
		}
		
		// 扣金币
		$cost = GuildCopyUtil::getBuyCostByNum($curBuyNum + 1);
		$userObj = EnUser::getUserObj($uid);
		if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_GUILD_COPY_COST)) 
		{
			throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
		}
		$userObj->update();
		
		// 任性，买！
		$guildCopyUserObj->buy();
		$guildCopyUserObj->update();
		
		$ret = 'ok';
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	public static function refresh($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		try 
		{
			$locker = new Locker();
			$key = 'guildcopy_' . $guildId;
			$locker->lock($key);
			
			// 检查vip是否开启
			if (intval(btstore_get()->VIP[EnUser::getUserObj($uid)->getVip()]['allAttackOpen']) != 1)
			{
				throw new FakeException('vip[%d] too low when all attack', EnUser::getUserObj($uid)->getVip());
			}
			
			// 检查军团当天的副本是否通关
			$guildCopyObj = GuildCopyObj::getInstance($guildId);
			if ($guildCopyObj->isCurrCopyDown()) 
			{
				$locker->unlock($key);
				Logger::warning('curr copy is down at pass time[%s]', strftime("%Y%m%d-%H%M%S", $guildCopyObj->getPassTime()));
				return 'already_pass';
			}
			
			// 检查次数是否超限
			$curRefreshNum = $guildCopyObj->getRefreshNum();
			$maxRefreshNum = intval(btstore_get()->GUILD_COPY_RULE['all_attack_limit']);
			if ($curRefreshNum >= $maxRefreshNum)
			{
				$locker->unlock($key);
	        	Logger::warning('not enough refresh num, curr[%d], limit[%d]', $curRefreshNum, $maxRefreshNum);
	        	return 'lack';
			}
			
			// 检查玩家今天是否已经“全团突击”过
			$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
			if ($guildCopyUserObj->isRefresh()) 
			{
				throw new FakeException('already refresh at time[%s]', strftime("%Y%m%d-%H%M%S", $guildCopyUserObj->getRefreshTime()));
			}
			
			// 扣金币
			$cost = intval(btstore_get()->GUILD_COPY_RULE['all_attack_cost']);
			$userObj = EnUser::getUserObj($uid);
			if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_GUILD_REFRESH_COST))
			{
				throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
			}
			$userObj->update();
			
			// 增加军团当天次数，记录一下这个使用“全团突击”的成员名字
			$guildCopyObj->increRefreshNum();
			$guildCopyObj->addRefresher($userObj->getUname());
			$guildCopyObj->update();
			$locker->unlock($key);
			
			// t_guild_record表中需要增加记录
			EnGuild::recordGuildCopyAllAttack($uid, $guildId);
			
			// 设置玩家当天刷新时间, 给当前玩家增加次数
			$guildCopyUserObj->setRefreshTime();
			$addAtkNum = intval(btstore_get()->GUILD_COPY_RULE['all_attack_add_num']);
			$guildCopyUserObj->addAtkNum($addAtkNum);
			$guildCopyUserObj->update();
			RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_COPY_UPDATE_REFRESH_NUM, array('total' => $guildCopyUserObj->getAtkNum(), 'uname' => $userObj->getUname()));
			
			// 给军团每个成员线程中抛一个增加攻击次数的请求
			$memberList = GuildDao::getMemberList($guildId, array(GuildDef::USER_ID));
			$arrUid = Util::arrayExtract($memberList, GuildDef::USER_ID);
			foreach ($arrUid as $aUid)
			{
				if ($aUid == $uid)
				{
					continue;
				}
				RPCContext::getInstance()->executeTask($aUid, 'guildcopy.addAtkNumFromOther', array($aUid, $userObj->getUname(), $addAtkNum), false);
				Logger::trace('GUILD_COPY_REFRESH : execute task for member[%d] of guild[%d] at time[%s]', $aUid, $guildId, strftime('%Y%m%d-%H%M%S', Util::getTime()));
			}
			Logger::trace('GUILD_COPY_REFRESH : user[%d] refresh at time[%s]', $uid, strftime('%Y%m%d-%H%M%S', Util::getTime()));
		} 
		catch (Exception $e) 
		{
			$locker->unlock($key);
			throw $e;
		}
		
		$ret = 'ok';
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	/**
	 * 有军团成员执行了全团突击功能，增加自己的攻击次数
	 * 
	 * @param int $uid
	 * @param string $addUname	使用全团突击功能的玩家
	 * @param int $addAtkNum	增加的次数
	 */
	public static function addAtkNumFromOther($uid, $addUname, $addAtkNum)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
		$guildCopyUserObj->addAtkNum($addAtkNum);
		$guildCopyUserObj->update();
		RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GUILD_COPY_UPDATE_REFRESH_NUM, array('total' => $guildCopyUserObj->getAtkNum(), 'uname' => $addUname));
		
		Logger::trace('function[%s] param[%s] end...', __FUNCTION__, func_get_args());
	}
	
	/**
	 * 军团副本通关后，军团成员领取“阳光普照奖”
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return string
	 */
	public static function recvPassReward($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		// 检查军团副本是否通关
		$guildCopyObj = GuildCopyObj::getInstance($guildId);
		if (!$guildCopyObj->isCurrCopyDown()) 
		{
			throw new FakeException('curr copy not down!');
		}
		
		// 检查玩家是否已经领取过
		$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
		if ($guildCopyUserObj->isRecvPassReward()) 
		{
			throw new FakeException('already recv pass reward at[%s]', strftime('%Y%m%d-%H%M%S', $guildCopyUserObj->getRecvPassRewardTime()));
		}
		
		// 检查玩家是不是通关以后才加入的军团
		$passTime = $guildCopyObj->getPassTime();
		$arrCond = array
		(
				array(GuildDef::GUILD_ID, '=', $guildId),
				array(GuildDef::USER_ID, '=', $uid),
				array(GuildDef::RECORD_TYPE, '=', GuildRecordType::JOIN_GUILD),
				array(GuildDef::RECORD_TIME, '>=', $passTime),
		);
		$arrField = array(GuildDef::RECORD_TIME);
		$arrRet = GuildDao::getRecord($arrCond, $arrField);
		if (!empty($arrRet)) 
		{
			$newJoinTime = 0;
			foreach ($arrRet as $aRet) // 可能有多条加入本军团的记录，取最新的一条作为加入军团的时间
			{
				if ($aRet[GuildDef::RECORD_TIME] > $newJoinTime)
				{
					$newJoinTime = $aRet[GuildDef::RECORD_TIME];
				}
			}
			Logger::info('can not recv pass reward, join guild after pass curr copy, pass time[%s], join guild time[%s]',
							strftime('%Y%m%d-%H%M%S', $passTime), strftime('%Y%m%d-%H%M%S', $newJoinTime));
			return 'after_pass';
		}
		
		// 领取奖励，更新领取奖励时间 ,如果玩家还有剩余的攻击次数，奖励还要加上额外的奖励(基础奖励*剩余次数)
		$arrPassReward = btstore_get()->GUILD_COPY_INFO[$guildCopyObj->getCurrCopy()]['pass_reward']->toArray();
		if ($guildCopyUserObj->getAtkNum() > 0) 
		{
			$multi = $guildCopyUserObj->getAtkNum();
			$arrExtraReward = btstore_get()->GUILD_COPY_INFO[$guildCopyObj->getCurrCopy()]['extra_reward']->toArray();
			foreach ($arrExtraReward as $index => $aExtraReward)
			{
				$aExtraReward[2] = $multi * $aExtraReward[2];
				$arrExtraReward[$index] = $aExtraReward;
			}
			$arrPassReward = array_merge($arrPassReward, $arrExtraReward);
		}
		$guildCopyUserObj->recvPassReward();
		$guildCopyUserObj->update();	// 先标记，再领奖，切记！
		$rewardRet = RewardUtil::reward3DArr($uid, $arrPassReward, StatisticsDef::ST_FUNCKEY_GUILD_COPY_PASS_REWARD);
		if ($rewardRet[UpdateKeys::USER])
		{
			EnUser::getUserObj($uid)->update();
		}
		if ($rewardRet[UpdateKeys::BAG])
		{
			BagManager::getInstance()->getBag($uid)->update();
		}
		
		$ret = 'ok';
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	/**
	 * 获得军团副本的宝箱信息
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function getBoxInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		// 获取宝箱信息
		$guildCopyObj = GuildCopyObj::getInstance($guildId);
		$ret = $guildCopyObj->getBoxInfo();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	/**
	 * 获得军团副本的昨天的宝箱信息
	 *
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function getLastBoxInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		// 获取宝箱信息
		$guildCopyObj = GuildCopyObj::getInstance($guildId);
		$ret = $guildCopyObj->getLastBoxInfo();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	public static function openBox($uid, $boxId)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		// 检查宝箱Id在不在有效范围内，从1开始
		if ($boxId <= 0 || $boxId > GuildCopyCfg::BOX_COUNT) 
		{
			throw new FakeException('invalid box id[%d], box count[%d]', $boxId, GuildCopyCfg::BOX_COUNT);
		}
		
		// 检查背包是否满了
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->isFull())
		{
			throw new FakeException('bag is full');
		}
		
		try 
		{
			$locker = new Locker();
			$key = 'guildcopy_' . $guildId;
			$locker->lock($key);
			
			// 检查军团副本是否通关
			$guildCopyObj = GuildCopyObj::getInstance($guildId);
			if (!$guildCopyObj->isCurrCopyDown())
			{
				throw new FakeException('curr copy not down!');
			}
			
			// 检查玩家是否已经领取过
			$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
			if ($guildCopyUserObj->isRecvBoxReward())
			{
				throw new FakeException('already recv box reward at[%s]', strftime('%Y%m%d-%H%M%S', $guildCopyUserObj->getRecvBoxRewardTime()));
			}
			
			// 检查玩家是不是通关以后才加入的军团
			$passTime = $guildCopyObj->getPassTime();
			$arrCond = array
			(
					array(GuildDef::GUILD_ID, '=', $guildId),
					array(GuildDef::USER_ID, '=', $uid),
					array(GuildDef::RECORD_TYPE, '=', GuildRecordType::JOIN_GUILD),
					array(GuildDef::RECORD_TIME, '>=', $passTime),
			);
			$arrField = array(GuildDef::RECORD_TIME);
			$arrRet = GuildDao::getRecord($arrCond, $arrField);
			if (!empty($arrRet))
			{
				$newJoinTime = 0;
				foreach ($arrRet as $aRet) // 可能有多条加入本军团的记录，取最新的一条作为加入军团的时间
				{
					if ($aRet[GuildDef::RECORD_TIME] > $newJoinTime)
					{
						$newJoinTime = $aRet[GuildDef::RECORD_TIME];
					}
				}
				$locker->unlock($key);
				Logger::info('can not recv box reward, join guild after pass curr copy, pass time[%s], join guild time[%s]',
								strftime('%Y%m%d-%H%M%S', $passTime), strftime('%Y%m%d-%H%M%S', $newJoinTime));
				
				return array('ret' => 'after_pass');
			}
			
			// 检查这个宝箱是否已经被领取
			$reciver = $guildCopyObj->boxReceiver($boxId);
			if (!empty($reciver)) 
			{
				$locker->unlock($key);
				Logger::warning('can not recv box reward, box[%d] already received by uid[%d] uname[%s]', $boxId, $reciver['uid'], $reciver['uname']);
				
				return array('ret' => 'already', 'extra' => $reciver);
			}
			
			// 玩家领取奖励
			$arrReceivedReward = $guildCopyObj->getAllReceivedBoxReward();
			list($curRewardId, $curRewardContent) = GuildCopyUtil::randBoxReward($guildCopyObj->getCurrCopy(), $arrReceivedReward);
			Logger::trace('OPEN_BOX : already received reward[%s], cur reward id[%d], cur reward content[%s]', $arrReceivedReward, $curRewardId, $curRewardContent);
			
			$guildCopyUserObj->recvBoxReward();
			$guildCopyUserObj->update();	// 先标记，再领奖，切记！
			$rewardRet = RewardUtil::reward3DArr($uid, array($curRewardContent), StatisticsDef::ST_FUNCKEY_GUILD_COPY_BOX_REWARD, TRUE);
			if ($rewardRet[UpdateKeys::USER])
			{
				EnUser::getUserObj($uid)->update();
			}
			if ($rewardRet[UpdateKeys::BAG])
			{
				BagManager::getInstance()->getBag($uid)->update();
			}
			
			// 设置宝箱领取者信息
			$guildCopyObj->setBoxReceiver($boxId, $uid, EnUser::getUserObj($uid)->getHeroManager()->getMasterHeroObj()->getHtid(), EnUser::getUserObj($uid)->getUname(), $curRewardId);
			$guildCopyObj->update();
			
			// 返回值
			$ret = array('ret' => 'ok', 'extra' => $curRewardId);
			
			$locker->unlock($key);
		} 
		catch (Exception $e) 
		{
			$locker->unlock($key);
			throw $e;
		}
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	public static function bossInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		// copyId直接读取自数据库,baseId则读取自配置
		$guildCopyObj = GuildCopyObj::getInstance($guildId);
		
		$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
		
		$bossInfo = $guildCopyObj->getBossInfo();

	 	$hp = 0;
	 	$maxHp = 0;
	 	foreach($bossInfo['arrHero']  as $bossHero)
	 	{
	 		$hp += $bossHero['hp'];
	 		$maxHp += $bossHero['max_hp'];
	 	}
	 
		$ret = array(
			'hp' => $hp,
			'max_hp' => $maxHp,
			'cd' => $bossInfo['cd'],
		
			'atk_boss_num' => $guildCopyUserObj->getBossAtkNum(),
			'buy_boss_num' => $guildCopyUserObj->getBuyBossNum(),
		);
		
		return $ret;
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
	}
	
	public static function buyBoss($uid, $count)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
		$confGuildRule = btstore_get()->GUILD_COPY_RULE;
		
		$buyNum = $guildCopyUserObj->getBuyBossNum();
		
		$GoldNeed = 0;
		for($i = $buyNum + 1; $i <= $buyNum + $count; ++ $i)
		{
			if(!isset($confGuildRule['price'][$i]))
			{
				throw new FakeException('over limit of configuration. curr: %d, buy: %d',$buyNum, $count);
			}
			else
			{
				$GoldNeed += $confGuildRule['price'][$i];
			}
		}
		
		Logger::debug('gold need %d', $GoldNeed);
		$userObj = EnUser::getUserObj($uid);
		if (!$userObj->subGold($GoldNeed, StatisticsDef::ST_FUNCKEY_GUILD_COPY_BUY_BOSS)) 
		{
			throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
		}
		$userObj->update();
		
		$guildCopyUserObj->addBuyBossNum($count);
		$guildCopyUserObj->update();
		Logger::trace('function[%s] param[%s] end...', __FUNCTION__, func_get_args());
		return 'ok';
	}
	
	public static function attackBoss($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		//基本复制自Attack的代码， 在检查条件部分所有不同
		
		// 检查是否在一个军团
		$guildMemberObj = GuildMemberObj::getInstance($uid);
		$guildId = $guildMemberObj->getGuildId();
		if (empty($guildId))
		{
			throw new FakeException('not in any guild of user[%d]', $uid);
		}
		
		// copyId直接读取自数据库,baseId则读取自配置
		$guildCopyObj = GuildCopyObj::getInstance($guildId);
		$copyId = $guildCopyObj->getCurrCopy();
		$baseId = Guildcopyutil::getBossBaseId($copyId);
		
		//配置
		$confGuildRule = btstore_get()->GUILD_COPY_RULE;
		$confGuildCopyInfo = btstore_get()->GUILD_COPY_INFO[$copyId];
		
		// 检查开启条件
		$guildObj = GuildObj::getInstance($guildId);
		foreach($confGuildRule['boss_open'] as $buildType => $NeedLevel)
		{
			if ($guildObj->getBuildLevel($buildType) < $NeedLevel)
			{
				throw new FakeException('build level not enough.');
			}
		}
		// 检查玩家是否还有攻击次数
		$guildCopyUserObj = GuildCopyUserObj::getInstance($uid);
		$FreeTime = $confGuildRule['num'];
		
		if ($guildCopyUserObj->getBossAtkNum() >= $guildCopyUserObj->getBuyBossNum() + $FreeTime)
		{
			throw new FakeException('no attack num, curr atk num[%d]', $guildCopyUserObj->getBossAtkNum());
		}
		
		try 
		{
			$locker = new Locker();
			$key = 'guildcopy_boss_' . $guildId;
			$locker->lock($key);
			
			$bossInfo = $guildCopyObj->getBossInfo();
			// 检查boss是否已经被打死
			if ($bossInfo['cd'] > Util::getTime())
			{
				$locker->unlock($key);
				Logger::warning('boss CD');
				
				return array('ret' => "cd");
			}
			
			// **************************************开始攻打*******************************************************
			
			// 1.获得玩家的战斗数据
			$userObj = EnUser::getUserObj($uid);
			$userBattleFormation = $userObj->getBattleFormation();
				
			// 2.获得这个据点的战斗数据
			$baseBattleFormation = $guildCopyObj->getBossFormation();
		
			// 3.战斗
			$ArmyId = GuildCopyUtil::getBossBaseId($copyId);
			$BtType	= btstore_get()->ARMY[$ArmyId]['fight_type'];
			$EndCond = CopyUtil::getVictoryConditions($ArmyId);
			$atkRet = EnBattle::doHero($userBattleFormation, $baseBattleFormation, $BtType, null, $EndCond);
			
			// 4.检查BOSS血量。
			
			// 5.是否击杀
			$kill = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
			
			$rewardArr = array();
			if($kill)
			{
				/*击杀奖励，重置BOSS CD，提高BOSS血量*/
				$guildCopyObj->refreshBoss();
				$rewardArr = $confGuildRule['bonus']->toArray();
			}
			else
			{
				/*更新BOSS血量*/
				
				$bossHp = array();
				foreach($atkRet['server']['team2'] as $pos => $hero)
				{
					$bossHp[$hero['hid']] = $hero['hp'];
				}
				
				$guildCopyObj->setBossHp($bossHp);
			}
			
			// 6. 固定奖励
			$rewardArr = array_merge( $rewardArr , $confGuildCopyInfo['boss_reward']->toArray());
			$rewardRet = RewardUtil::reward3DArr($uid, $rewardArr, StatisticsDef::ST_FUNCKEY_GUILD_COPY_BOSS_REWARD);
			Logger::debug('Guildcopy reward info %s',$rewardArr);
			
			if ($rewardRet[UpdateKeys::USER]) 
			{
				$userObj->update();
			}
			if ($rewardRet[UpdateKeys::BAG]) 
			{
				BagManager::getInstance()->getBag($uid)->update();
			}
			
			// 7.增加攻击次数
			$guildCopyUserObj->addBossAtkNum(1);
			$guildCopyUserObj->update();
				
			// 8.更新BOSS信息
			$guildCopyObj->update();
			
			// 11.返回值
			$bossInfo = $guildCopyObj->getBossInfo();
			$hp = 0;
		 	$maxHp = 0;
		 	foreach($bossInfo['arrHero']  as $bossHero)
		 	{
		 		$hp += $bossHero['hp'];
		 		$maxHp += $bossHero['max_hp'];
		 	}
			
			$ret = array();
			$ret['ret'] = 'ok';
			$ret['fight_ret'] = $atkRet['client'];
			$ret['kill'] = $kill ? 1 : 0;
			$ret['boss_info'] = array(
				'hp' => $hp,
				'cd' => $bossInfo['cd'],
				'max_hp' => $maxHp,
			);
			
			
			Logger::trace('GUILD_BOSS_ATTACK : uid[%d] guildId[%d] attact ok,  kill[%d], reward[%s]', $uid, $guildId, $kill, $rewardArr);
			
			$locker->unlock($key);
		} 
		catch (Exception $e) 
		{
			$locker->unlock($key);
			
			throw new InterException('%s', $e->getMessage());
		}
		
		EnActive::addTask(ActiveDef::GUILDCOPY_BOSS);
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
}

class GuildCopyScriptLogic
{
	/**
	 * 发放昨天全服军团副本伤害排名奖
	 */
	public static function reward($commit = TRUE, $arrUid = array())
	{
		/**
		 *  不会中断玩家游戏过程，发放昨天的排名奖励
		 *  
		 *	取update_time是昨天和今天的玩家记录，只有这两种玩家才有可能在昨天的的全服排行榜上 
		 *  update_time是昨天的玩家，因为还没有登陆，取atk_damage字段作为排行的键值
		 *  update_time是今天的玩家，今天第一次 登陆的时候就将昨天的atk_damage字段值拷贝到atk_damage_last，以atk_damage_last字段作为排行的键值
		 *  
		 */
		
		// 拉取更新时间是昨天的今天的所有玩家
		$tryConnt = 0;
		$fetchUserSucc = FALSE;
		while (++$tryConnt <= 3)
		{
			try
			{
				$currMaxUid = 0;
				$arrDamageInfo = array();
				$arrField = array
				(
						GuildCopyUserField::TBL_FIELD_UID,
						GuildCopyUserField::TBL_FIELD_ATK_DAMAGE,
						GuildCopyUserField::TBL_FIELD_ATK_DAMAGE_LAST,
						GuildCopyUserField::TBL_FIELD_UPDATE_TIME,
						GuildCopyUserField::TBL_FIELD_RECV_RANK_REWARD_TIME,
				);
				
				while(TRUE)
				{
					$arrCond = array
					(
							array(GuildCopyUserField::TBL_FIELD_UPDATE_TIME, '>=', strtotime(date('Ymd', Util::getTime())) - SECONDS_OF_DAY),
							array(GuildCopyUserField::TBL_FIELD_UID, '>', $currMaxUid),
					);
						
					$data = new CData();
					$data->select($arrField)->from(GuildCopyDao::GuildCopyUserTable);
					foreach ($arrCond as $aCond)
					{
						$data->where($aCond);
					}
					$data->orderBy(GuildCopyUserField::TBL_FIELD_UID, TRUE);
					$data->limit(0, DataDef::MAX_FETCH);
					$arrRet = $data->query();
					$arrDamageInfo = array_merge($arrDamageInfo, $arrRet);
						
					if (count($arrRet) < DataDef::MAX_FETCH)
					{
						break;
					}
					else
					{
						$last = end($arrRet);
						$currMaxUid = $last[GuildCopyUserField::TBL_FIELD_UID];
					}
				}
				
				$fetchUserSucc = TRUE; 
				break;
			} 
			catch (Exception $e)
			{
				Logger::fatal('GUILD_COPY_REWARD : exception[%s] when try [%d] times', $e->getMessage(), $tryConnt);
			}
		}
		
		// 有异常，返回
		if (!$fetchUserSucc) 
		{
			Logger::fatal('GUILD_COPY_REWARD : can not fetch user when rank reward');
			return;
		}
		Logger::trace('GUILD_COPY_REWARD : all user info after fetch [%s]', $arrDamageInfo);
		
		// 去掉无效的玩家
		foreach ($arrDamageInfo as $index => $aUser)
		{
			if (Util::isSameDay($aUser[GuildCopyUserField::TBL_FIELD_UPDATE_TIME])) 
			{
				if ($aUser[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE_LAST] <= 0) 
				{
					unset($arrDamageInfo[$index]);
					continue;
				}
				else 
				{
					$arrDamageInfo[$index]['damage'] = $aUser[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE_LAST];
				}
			}
			else 
			{
				if ($aUser[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE] <= 0) 
				{
					unset($arrDamageInfo[$index]);
					continue;
				}
				else 
				{
					$arrDamageInfo[$index]['damage'] = $aUser[GuildCopyUserField::TBL_FIELD_ATK_DAMAGE];
				}
			}
		}
		Logger::trace('GUILD_COPY_REWARD : all user info after filter [%s]', $arrDamageInfo);
		
		// 按照damage排序，damage相同，uid升序
		$sortCmp = new SortByFieldFunc(array('damage' => SortByFieldFunc::DESC, GuildCopyUserField::TBL_FIELD_UID => SortByFieldFunc::ASC));
		usort($arrDamageInfo, array($sortCmp, 'cmp'));
		Logger::trace('GUILD_COPY_REWARD : all user info after sort [%s]', $arrDamageInfo);
		
		/******************************** 开始发奖   *****************************/
		$arrNotifyUid = array();
		RewardCfg::$NO_CALLBACK = TRUE;
		$rank = 0;
		foreach ($arrDamageInfo as $aDamage)
		{
			++$rank;
			
			$aUid = $aDamage[GuildCopyUserField::TBL_FIELD_UID];
			if (!empty($arrUid) && !in_array($aUid, $arrUid)) 
			{
				continue;
			}
			
			// 因为发排名奖时候，不会暂停军团副本玩家的操作，所以这里不能用GuildCopyUserObj，调用GuildCopyUserObj会自动刷新，有可能会造成同步问题。
			// 发完奖只能直接更新玩家的“领取排名奖时间字段”，这个字段只有脚本中会操作，Obj刷新时候也不改变
			if (Util::isSameDay($aDamage[GuildCopyUserField::TBL_FIELD_RECV_RANK_REWARD_TIME])) 
			{
				Logger::warning('GUILD_COPY_REWARD : already send all rank reward for user[%d], damage[%d], update time[%s], rank[%d] at time[%s]', $aUid, $aDamage['damage'],
							strftime("%Y%m%d-%H%M%S", $aDamage[GuildCopyUserField::TBL_FIELD_UPDATE_TIME]), $rank, strftime("%Y%m%d-%H%M%S", $aDamage[GuildCopyUserField::TBL_FIELD_RECV_RANK_REWARD_TIME]));
			}
			else 
			{
				$arrReward = GuildCopyUtil::getRewardByRank($rank);
				Logger::info('GUILD_COPY_REWARD : send all rank reward for user[%d], damage[%d], update time[%s], rank[%d], reward[%s]', $aUid, $aDamage['damage'],
								strftime("%Y%m%d-%H%M%S", $aDamage[GuildCopyUserField::TBL_FIELD_UPDATE_TIME]), $rank, $arrReward);
				
				if ($commit) 
				{
					$arrNotifyUid[] = $aUid;
					RewardUtil::reward3DtoCenter($aDamage[GuildCopyUserField::TBL_FIELD_UID], array($arrReward), RewardSource::GUILDCOPY_RANK_REWARD, array('rank' => $rank));
					
					$arrCond = array
					(
							array(GuildCopyUserField::TBL_FIELD_UID, '=', $aUid),
					);
					$arrField = array
					(
							GuildCopyUserField::TBL_FIELD_RECV_RANK_REWARD_TIME => Util::getTime(),
					);
					GuildCopyDao::updateUser($arrCond, $arrField);
				}
			}		
		}
		
		// 统一推送
		if ($commit)
		{
			RPCContext::getInstance()->sendMsg($arrNotifyUid, PushInterfaceDef::REWARD_NEW, array());
		}
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */