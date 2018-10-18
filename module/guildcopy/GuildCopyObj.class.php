<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyObj.class.php 234215 2016-03-22 13:21:06Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/GuildCopyObj.class.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-22 13:21:06 +0000 (Tue, 22 Mar 2016) $
 * @version $Revision: 234215 $
 * @brief 
 *  
 **/
 
class GuildCopyObj
{
	private static $sArrInstance = array();
	private $mObj = array();
	private $mObjModify = array();

	/**
	 * getInstance 获取实例
	 *
	 * @param int $guild_id 军团id
	 * @static
	 * @access public
	 * @return GuildCopyObj
	 */
	public static function getInstance($guildId)
	{
		if ($guildId == 0) 
		{
			$guildId = RPCContext::getInstance()->getSession(GuildDef::SESSION_GUILD_ID);
			if ($guildId == null) 
			{
				throw new FakeException('guildId and global.guildId are 0');
			}
		}

		if (!isset(self::$sArrInstance[$guildId]))
		{
			self::$sArrInstance[$guildId] = new GuildCopyObj($guildId);
		}

		return self::$sArrInstance[$guildId];
	}

	public static function releaseInstance($guildId)
	{
		if ($guildId == 0) 
		{
			$guildId = RPCContext::getInstance()->getSession(GuildDef::SESSION_GUILD_ID);
			if ($guildId == null) 
			{
				throw new FakeException('guildId and global.guildId are 0');
			}
		}

		if (isset(self::$sArrInstance[$guildId]))
		{
			unset(self::$sArrInstance[$guildId]);
		}
	}
	
	private static function isGuildCompensated($guildId)
	{
		$arrCond = array
		(
				array(GuildCopyField::TBL_FIELD_GUILD_ID, '=', $guildId),
		);
		$arrBody = array
		(
				GuildCopyField::TBL_FIELD_VA_LAST_BOX,
		);
		$arrRet = GuildCopyDao::selectGuild($arrCond, $arrBody);
		
		$lastDay = date('Ymd', Util::getTime() - SECONDS_OF_DAY);
		if (empty($arrRet) || !isset($arrRet[GuildCopyField::TBL_FIELD_VA_LAST_BOX][$lastDay])) 
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
	private static function guildCompensate($guildId, $boxInfo, $lastCopy)
	{
		$lastDay = date('Ymd', Util::getTime() - SECONDS_OF_DAY);
		$arrCond = array
		(
				array(GuildCopyField::TBL_FIELD_GUILD_ID, '=', $guildId),
		);
		$arrField = array
		(
				GuildCopyField::TBL_FIELD_VA_LAST_BOX => array($lastDay => array('last' => $lastCopy, 'box' => $boxInfo)),
		);
		GuildCopyDao::updateGuild($arrCond, $arrField);
	}
	
	private function __construct($guildId)
	{
		$this->mObj = $this->getGuildCopyInfo($guildId);
		if (empty($this->mObj))
		{
			$this->mObj = $this->createGuildCopyInfo($guildId);
		}
		
		if (empty($this->mObj[GuildCopyField::TBL_FIELD_VA_BOSS]))
		{
			$copyId = $this->mObj[GuildCopyField::TBL_FIELD_CURR];
			$this->mObj[GuildCopyField::TBL_FIELD_VA_BOSS] = GUildcopyutil::getBossInitInfo($copyId);
		}
		
		$this->mObjModify = $this->mObj;
		$this->refresh();
	}
	
	public function refresh()
	{
		if (!Util::isSameDay($this->mObjModify[GuildCopyField::TBL_FIELD_UPDATE_TIME]))
		{
			// 操蛋的补发，只有昨天【打过】并且【通关】的军团，才会给军团成员分配昨天未领取的奖励，包括【通关奖】和【宝箱将】
			if (Util::getDaysBetween($this->mObjModify[GuildCopyField::TBL_FIELD_UPDATE_TIME]) == 1
				&& Util::getDaysBetween($this->mObjModify[GuildCopyField::TBL_FIELD_PASS_TIME]) == 1)
			{
				$this->compensate();
			}
			
			// 先校验一下next的有效性
			$maxPassCopy = $this->getMaxPassCopy();
			$next = $this->getNextCopy();
			if ($next > ($maxPassCopy + 1)) 
			{
				throw new InterException('refresh failed, next copy[%d], max pass copy[%d]', $next, $maxPassCopy);
			}
			
			// 重置字段
			$this->mObjModify[GuildCopyField::TBL_FIELD_CURR] = $next;
			$this->mObjModify[GuildCopyField::TBL_FIELD_REFRESH_NUM] = 0;
			$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA] = array();
			$this->mObjModify[GuildCopyField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
			
			$this->mObjModify[GuildCopyField::TBL_FIELD_VA_BOSS] = GuildCopyUtil::getBossInitInfo($next);
		}
	}
	
	/**
	 * 通过奖励中心补发军团成员的【宝箱将】和【通关奖】
	 * 
	 * 1. 每天第一次调用军团obj的refresh方法时候进行补发
	 * 2. 昨天通关的军团才需要补发
	 * 3. 通关后新加入的成员不在补发范围内
	 * 4. 退团或者换军团的军团成员不在补发范围内
	 * 5. 昨天没有攻打副本的成员在补发范围内
	 * 6. 宝箱奖是随机的一个宝箱，通关奖包括基本奖励和额外奖
	 * 
	 * @throws InterException
	 */
	public function compensate()
	{
		try
		{
			if (!self::isGuildCompensated($this->getGuildId())) // 昨天的宝箱奖励没有补发
			{
				$locker = new Locker();
				$key = 'guildcopy_compensate_' . $this->getGuildId();
				$locker->lock($key);
		
				if (!self::isGuildCompensated($this->getGuildId())) // 再判断一次
				{
					Logger::info('GUILD_COPY_COMPENSATE : trigger for guild[%d]', $this->getGuildId());
					
					// 获得军团所有成员uid
					$memberList = GuildDao::getMemberList($this->getGuildId(), array(GuildDef::USER_ID));
					$arrUid = Util::arrayExtract($memberList, GuildDef::USER_ID);
					Logger::trace('GUILD_COPY_COMPENSATE : guild[%d] member[%s]', $this->getGuildId(), $arrUid);
					
					//　获得军团成员的基本信息
					$arrBasicInfo = EnUser::getArrUserBasicInfo($arrUid, array('uid', 'htid', 'uname'));
					$arrBasicInfo = Util::arrayIndex($arrBasicInfo, 'uid');
					Logger::trace('GUILD_COPY_COMPENSATE : guild[%d] basicInfo[%s]', $this->getGuildId(), $arrBasicInfo);
						
					// 获得军团从昨天通关到现在新加入的所有成员uid，并且过滤掉
					$arrNewUid = array();
					$offset = 0;
					for ($i = 0; $i < 65536; ++$i)
					{
						$arrRet = GuildDao::getRecordList($this->getGuildId(), array(GuildRecordType::JOIN_GUILD), $offset, DataDef::MAX_FETCH, $this->getPassTime());
						$arrRet = Util::arrayExtract($arrRet, GuildDef::USER_ID);
						$arrNewUid = array_merge($arrNewUid, $arrRet);
						if (count($arrRet) < DataDef::MAX_FETCH)
						{
							break;
						}
						$offset += DataDef::MAX_FETCH;
					}
					$arrUid = array_diff($arrUid, $arrNewUid);
					Logger::trace('GUILD_COPY_COMPENSATE : guild[%d] new member[%s], effect member[%s]', $this->getGuildId(), $arrNewUid, $arrUid);
						
					// 获得军团所有成员的领取奖励的信息，包括通关奖领取时间和宝箱奖领取时间，还有攻击次数（用于补发额外的通关奖）
					$arrCond = array
					(
							array(GuildCopyUserField::TBL_FIELD_UID, 'IN', $arrUid),
					);
					$arrField = array
					(
							GuildCopyUserField::TBL_FIELD_UID,
							GuildCopyUserField::TBL_FIELD_ATK_NUM,
							GuildCopyUserField::TBL_FIELD_RECV_PASS_REWARD_TIME,
							GuildCopyUserField::TBL_FIELD_RECV_BOX_REWARD_TIME,
					);
					$arrGuildCopyUserInfo = GuildCopyDao::getRankListByDamage($arrCond, $arrField, DataDef::MAX_FETCH);
					$arrGuildCopyUserInfo = Util::arrayIndex($arrGuildCopyUserInfo, GuildCopyUserField::TBL_FIELD_UID);
					
					// 获得应该补发的玩家uid，包括未领取【通关奖】和【宝箱奖】两部分
					$arrNoBoxRewardUid = array();
					$arrNoPassRewardUid = array();
					foreach ($arrGuildCopyUserInfo as $aUid => $aUserInfo)
					{
						if ($aUserInfo[GuildCopyUserField::TBL_FIELD_RECV_BOX_REWARD_TIME] < $this->getPassTime()) 
						{
							$arrNoBoxRewardUid[] = $aUid;
						}
						if ($aUserInfo[GuildCopyUserField::TBL_FIELD_RECV_PASS_REWARD_TIME] < $this->getPassTime())
						{
							$arrNoPassRewardUid[] = $aUid;
						}
					}
					Logger::info('GUILD_COPY_COMPENSATE : guild[%d] compensate box uid[%s] compensate pass uid[%s]', $this->getGuildId(), $arrNoBoxRewardUid, $arrNoPassRewardUid);
						
					// 开始生成奖励并且补发
					$boxInfo = $this->getBoxInfo();
					foreach ($arrUid as $aUid)
					{
						// 需要补发的奖励
						$arrReward = array();
						
						// 看看是否需要补发宝箱奖
						if (in_array($aUid, $arrNoBoxRewardUid, TRUE)) 
						{
							// 随机一个宝箱
							$aBoxId = -1;
							for ($i = 1; $i <= GuildCopyCfg::BOX_COUNT; ++$i)
							{
								if (!isset($boxInfo[$i])) 
								{
									$aBoxId = $i;
									break;
								}
							}
							if ($aBoxId == -1) 
							{
								throw new InterException('want to compensate box reward, but no enough box, guild[%d], uid[%d], boxInfo[%s]', $this->getGuildId(), $aUid, $boxInfo);
							}
							
							// 获得已经领取的奖励
							$arrAllReceivedReward = array(); 
							foreach ($boxInfo as $boxId => $receiver)
							{
								if (!isset($arrAllReceivedReward[$receiver['reward']])) 
								{
									$arrAllReceivedReward[$receiver['reward']] = 0;
								}
								++$arrAllReceivedReward[$receiver['reward']];
							}
							
							// 获得一个宝箱奖励
							list($curRewardId, $curRewardContent) = GuildCopyUtil::randBoxReward($this->getCurrCopy(), $arrAllReceivedReward, $this->mObjModify[GuildCopyField::TBL_FIELD_UPDATE_TIME] - SECONDS_OF_DAY);
							$boxInfo[$aBoxId] = array('uid' => $aUid, 'htid' => $arrBasicInfo[$aUid]['htid'], 'uname' => $arrBasicInfo[$aUid]['uname'], 'reward' => $curRewardId);
							
							$arrReward = array($curRewardContent);
						}
						
						// 看看是否需要补发通关奖
						if (in_array($aUid, $arrNoPassRewardUid, TRUE))
						{
							$arrPassReward = btstore_get()->GUILD_COPY_INFO[$this->getCurrCopy()]['pass_reward']->toArray();
							if ($arrGuildCopyUserInfo[$aUid][GuildCopyUserField::TBL_FIELD_ATK_NUM] > 0)
							{
								$multi = $arrGuildCopyUserInfo[$aUid][GuildCopyUserField::TBL_FIELD_ATK_NUM];
								$arrExtraReward = btstore_get()->GUILD_COPY_INFO[$this->getCurrCopy()]['extra_reward']->toArray();
								foreach ($arrExtraReward as $index => $aExtraReward)
								{
									$aExtraReward[2] = $multi * $aExtraReward[2];
									$arrExtraReward[$index] = $aExtraReward;
								}
								$arrPassReward = array_merge($arrPassReward, $arrExtraReward);
							}
							
							$arrReward = array_merge($arrReward, $arrPassReward);
						}
						
						if (!empty($arrReward)) 
						{
							// 检查一下奖励中心是否已经发啦，防止重发
							$reward = EnReward::getRewardByUidTime($aUid, RewardSource::GUILDCOPY_COMPENSATIONE_REWARD, strtotime(date('Ymd', Util::getTime())));
							if (!empty($reward))
							{
								Logger::warning('GUILD_COPY_COMPENSATE : guild[%d] uid[%s] need compensate, reward[%s], but already reward at time[%s],reward[%s], why??!!', $this->getGuildId(), $aUid, $arrReward, strftime('%Y%m%d %H%M%S', $reward[RewardDef::SQL_SEND_TIME]), $reward[RewardDef::SQL_VA_REWARD]);
							}
							else 
							{
								Logger::trace('GUILD_COPY_COMPENSATE : guild[%d] uid[%s] need compensate, reward[%s]', $this->getGuildId(), $aUid, $arrReward);
								RewardUtil::reward3DtoCenter($aUid, array($arrReward), RewardSource::GUILDCOPY_COMPENSATIONE_REWARD, array());
							}
						}
						else 
						{
							Logger::trace('GUILD_COPY_COMPENSATE : guild[%d] uid[%s] no need compensate', $this->getGuildId(), $aUid);
						}
					}	
					
					// 标记
					self::guildCompensate($this->getGuildId(), $boxInfo, $this->getCurrCopy());
				}
				
				$locker->unlock($key);
			}					
		} 
		catch (Exception $e)
		{
			$locker->unlock($key);
			Logger::warning('GUILD_COPY_COMPENSATE : guild[%d] compensate get exception[%s]', $this->getGuildId(), $e->getMessage());
			
			// ！！！发生异常同样标记，不能影响今天的数据，大不了前端显示昨天的宝箱领取信息有异常，
			self::guildCompensate($this->getGuildId(), array(), $this->getCurrCopy());
		}
	}
	
	public function getGuildCopyInfo($guildId)
	{
		$arrCond = array
		(
				array(GuildCopyField::TBL_FIELD_GUILD_ID, '=', $guildId),
		);
		$arrBody = GuildCopyField::$GUILD_COPY_ALL_FIELDS;
	
		return GuildCopyDao::selectGuild($arrCond, $arrBody);
	}
	
	public function createGuildCopyInfo($guildId)
	{
		$arrField = array
		(
				GuildCopyField::TBL_FIELD_GUILD_ID => $guildId,
				GuildCopyField::TBL_FIELD_CURR => 1,
				GuildCopyField::TBL_FIELD_NEXT => 1,
				GuildCopyField::TBL_FIELD_MAX_PASS_COPY => 0,
				GuildCopyField::TBL_FIELD_REFRESH_NUM => 0,
				GuildCopyField::TBL_FIELD_PASS_TIME => 0,
				GuildCopyField::TBL_FIELD_MAX_PASS_TIME => 0,
				GuildCopyField::TBL_FIELD_UPDATE_TIME => Util::getTime(),
				GuildCopyField::TBL_FIELD_VA_EXTRA => array(),
				GuildCopyField::TBL_FIELD_VA_LAST_BOX => array(),
		);
	
		if (!GuildCopyDao::insertGuild($arrField))
		{
			$arrField = $this->getGuildCopyInfo($guildId);
			if (empty($arrField)) 
			{
				throw new InterException('empty field!!!!!');
			}
		}
	
		return $arrField;
	}
	
	public function getGuildId()
	{
		return $this->mObjModify[GuildCopyField::TBL_FIELD_GUILD_ID];
	}
	
	public function getCurrCopy()
	{
		return $this->mObjModify[GuildCopyField::TBL_FIELD_CURR];
	}
	
	/**
	 * 通关当前的副本，设置通关时间，如果是以前没有通过的副本，则需要设置maxPassCopy
	 */
	public function passCurrCopy()
	{
		if (!$this->isCurrCopyDown()) 
		{
			return;
		}
		
		$this->mObjModify[GuildCopyField::TBL_FIELD_PASS_TIME] = Util::getTime();
		$currCopyId = $this->getCurrCopy();
		if ($currCopyId == $this->getMaxPassCopy() + 1) 
		{
			$this->increMaxPassCopy();
			$this->setMaxPassTime();//通过了新副本，需要设置一下最大通关副本的通关时间，用于排序
		}
	}
	
	/**
	 * 判断当前副本是否通关啦
	 * 
	 * @return boolean
	 */
	public function isCurrCopyDown()
	{
		for ($i = 1; $i <= GuildCopyCfg::BASE_COUNT; ++$i)
		{
			if (!$this->isBaseDown($i)) 
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	/**
	 * 判断一个据点是否被击破
	 * 
	 * @param unknown $baseIndex
	 * @return boolean
	 */
	public function isBaseDown($baseIndex)
	{
		$baseInfo = $this->getBaseInfo($baseIndex);
		
		// 没有设置hp代表是满血
		if (!isset($baseInfo['hp'])) 
		{
			return FALSE;
		}
		
		// 是个空数组，代表死光啦
		if (is_array($baseInfo['hp']) && empty($baseInfo['hp'])) 
		{
			return TRUE;
		}
		
		// 再看血量
		foreach ($baseInfo['hp'] as $hid => $hp)
		{
			if ($hp > 0) 
			{
				return FALSE;
			}
		}
		
		return TRUE;
	}
	
	public function getNextCopy()
	{
		return $this->mObjModify[GuildCopyField::TBL_FIELD_NEXT];
	}
	
	/**
	 * 已通关的副本的最大Id
	 * 
	 * @return int
	 */
	public function getMaxPassCopy()
	{
		return $this->mObjModify[GuildCopyField::TBL_FIELD_MAX_PASS_COPY];
	}
	
	/**
	 * 通过了一个最新的副本，这里依赖副本Id必须是递增且连续的
	 */
	public function increMaxPassCopy()
	{
		++$this->mObjModify[GuildCopyField::TBL_FIELD_MAX_PASS_COPY];
	}
	
	/**
	 * 参数中的副本Id是否能作为一个攻打目标，，这里依赖副本Id必须是递增且连续的
	 * 
	 * @param int $copyId
	 * @return boolean
	 */
	public function canBeTarget($copyId)
	{
		return $copyId <= ($this->getMaxPassCopy() + 1);
	}
	
	/**
	 * 今天军团全团突击的次数
	 * 
	 * @return 
	 */
	public function getRefreshNum()
	{
		return $this->mObjModify[GuildCopyField::TBL_FIELD_REFRESH_NUM];
	}
	
	/**
	 * 增加军团全团突击的次数一次
	 */
	public function increRefreshNum()
	{
		++$this->mObjModify[GuildCopyField::TBL_FIELD_REFRESH_NUM];
	}
	
	/**
	 * 获得使用过全团突击的军团成员姓名
	 */
	public function getRefresher()
	{
		if (!isset($this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_REFRESHER]))
		{
			$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_REFRESHER] = array();
		}
		
		return $this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_REFRESHER];
	}
	
	/**
	 * 增加一个使用全团突击的成员名字   
	 * 这里就存姓名，坏处是玩家改名字后依然显示旧的，可以存uid，拉请求时候再取uname，但这样多一次数据请求
	 * 没啥必要，都军团自己人，谁不知道谁
	 * 
	 * @param string $aRefresher
	 */
	public function addRefresher($aRefresher)
	{
		if (!isset($this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_REFRESHER]))
		{
			$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_REFRESHER] = array();
		}
		
		$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_REFRESHER][] = $aRefresher;
	}
	
	/**
	 * 军团副本的通关时间，这个时间不会被refresh掉
	 * 只代表这个时间所在的那一天的通关时间（如果通关的话）
	 * 
	 * @return int
	 */
	public function getPassTime()
	{
		return $this->mObjModify[GuildCopyField::TBL_FIELD_PASS_TIME];
	}
	
	/**
	 * 军团已经通关的最大副本的通关时间，用于排行
	 * 
	 * @return int
	 */
	public function getMaxPassTime()
	{
		return $this->mObjModify[GuildCopyField::TBL_FIELD_MAX_PASS_TIME];
	}
	
	/**
	 * 设置军团已经通关的最大副本的通关时间，用于排行
	 * 
	 * @param number $time
	 */
	private function setMaxPassTime($time = 0)
	{
		$this->mObjModify[GuildCopyField::TBL_FIELD_MAX_PASS_TIME] = ($time == 0 ? Util::getTime() : $time);
	}
	
	/**
	 * 设置攻打目标
	 * 
	 * @param int $copyId
	 */
	public function setNextCopy($copyId)
	{
		if ($this->canBeTarget($copyId)) 
		{
			$this->mObjModify[GuildCopyField::TBL_FIELD_NEXT] = $copyId;   
		}
		else 
		{
			throw new InterException('set next copy failed, copy id[%d], max pass copy[%d]', $copyId, $this->getMaxPassCopy());
		}
	}
	
	/**
	 * 从va中获取副本信息，如果被refresh掉，需要初始化
	 * 
	 * @return array
	 */
	public function getCopyInfo()
	{
		$isInit = FALSE;
		if (!isset($this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_COPY])) 
		{
			$isInit = TRUE;
			$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_COPY] = $this->initCopyInfo();
		}
		
		return array($this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_COPY], $isInit);
	}
	
	/**
	 * 设置副本信息
	 * 
	 * @param array $copyInfo
	 */
	public function setCopyInfo($copyInfo)
	{
		$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_COPY] = $copyInfo;
	}
	
	/**
	 * 获得宝箱信息
	 * 
	 * @return array
	 */
	public function getBoxInfo()
	{
		// 当前副本没有通关，返回空数组，代表没有任何人领取奖励
		if (!$this->isCurrCopyDown()) 
		{
			return array();
		}
		
		if (!isset($this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_BOX]))
		{
			$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_BOX] = array();
		}
		
		return $this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_BOX];
	}
	
	/**
	 * 获得昨天的宝箱信息
	 *
	 * @return array
	 */
	public function getLastBoxInfo()
	{
		$lastDay = date('Ymd', Util::getTime() - SECONDS_OF_DAY);
		if (empty($this->mObjModify[GuildCopyField::TBL_FIELD_VA_LAST_BOX])
			|| !isset($this->mObjModify[GuildCopyField::TBL_FIELD_VA_LAST_BOX][$lastDay]))
		{
			return array();
		}
		
		return $this->mObjModify[GuildCopyField::TBL_FIELD_VA_LAST_BOX][$lastDay];
	}
	
	/**
	 * 设置宝箱信息
	 * 
	 * @param array $boxInfo
	 */
	public function setBoxInfo($boxInfo)
	{
		$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_BOX] = $boxInfo;
	}
	
	/**
	 * 返回所有已经被领取的宝箱
	 * 
	 * @return array(1 => 5, 2 => 6) 代表奖励1被领走5份，奖励2被领走6份
	 */
	public function getAllReceivedBoxReward()
	{
		$ret = array();
		
		$boxInfo = $this->getBoxInfo();
		foreach ($boxInfo as $boxId => $receiver)
		{
			if (!isset($ret[$receiver['reward']])) 
			{
				$ret[$receiver['reward']] = 0;
			}
			++$ret[$receiver['reward']];
		}
		
		return $ret;
	}
	
	/**
	 * 获得某个宝箱的领取信息，没有被领取，返回NULL
	 * 
	 * @param int $boxId
	 * @return array
	 */
	public function boxReceiver($boxId)
	{
		$boxInfo = $this->getBoxInfo();
		if (isset($boxInfo[$boxId])) 
		{
			return $boxInfo[$boxId];
		}
		else
		{
			return NULL;
		}
	}
	
	/**
	 * 设置某个宝箱的开启者信息
	 * 
	 * @param int $boxId 宝箱Id
	 * @param int $uid	uid
	 * @param int $htid htid
	 * @param string $uname 玩家名称
	 * @param int $rewardId 宝箱里的奖励
	 * @throws InterException
	 */
	public function setBoxReceiver($boxId, $uid, $htid, $uname, $rewardId)
	{
		// 宝箱已经被开启啦，肯定不能再开啦
		$receiver = $this->boxReceiver($boxId);
		if (!empty($receiver)) 
		{
			throw new InterException('box[%d] already received by[%s]', $boxId, $receiver);
		}
		
		$boxInfo = $this->getBoxInfo();
		$boxInfo[$boxId] = array('uid' => $uid, 'htid' => $htid, 'uname' => $uname, 'reward' => $rewardId);
		$this->setBoxInfo($boxInfo);
	}
	
	/**
	 * 获取某个据点的信息
	 * 
	 * @param int $baseIndex   从1开始
	 * @throws InterException
	 * @return array
	 */
	public function getBaseInfo($baseIndex)
	{
		if ($baseIndex <= 0 || $baseIndex > GuildCopyCfg::BASE_COUNT) 
		{
			throw new InterException('invalid base id[%d]', $baseIndex);
		}
		
		list($copyInfo, $isInit) = $this->getCopyInfo();
		return $copyInfo[$baseIndex];
	}
	
	/**
	 * 设置某个据点的信息
	 * 
	 * @param int $baseIndex
	 * @param array $baseInfo
	 * @throws InterException
	 */
	public function setBaseInfo($baseIndex, $baseInfo)
	{
		if ($baseIndex <= 0 || $baseIndex > GuildCopyCfg::BASE_COUNT)
		{
			throw new InterException('invalid base id[%d]', $baseIndex);
		}
		
		list($copyInfo, $isInit) = $this->getCopyInfo();
		$copyInfo[$baseIndex] = $baseInfo;
		$this->setCopyInfo($copyInfo);
	}
	
	/**
	 * 获得某个据点的国家类型，属于这个国家类型的武将攻打它伤害加成
	 * 
	 * @param int $baseIndex
	 * @return array 类似array(1,2)代表魏蜀
	 */
	public function getBaseCountryType($baseIndex)
	{
		$baseInfo = $this->getBaseInfo($baseIndex);
		return $baseInfo['type'];
	}
	
	/**
	 * 获得对某个据点造成最大伤害的玩家所造成的伤害，咋这么拗口
	 * 
	 * @param int $baseIndex
	 * @return number
	 */
	public function getMaxDamage($baseIndex)
	{
		$baseInfo = $this->getBaseInfo($baseIndex);
		
		if (!isset($baseInfo['max_damager']['damage'])) 
		{
			return 0;
		}
		return $baseInfo['max_damager']['damage'];
	}
	
	/**
	 * 设置最大伤害者的信息
	 * 
	 * @param int $baseIndex
	 * @param int $uid
	 * @param int $htid
	 * @param string $uname
	 * @param int $damage
	 */
	public function setMaxDamager($baseIndex, $uid, $htid, $uname, $damage)
	{
		$curMaxDamage = $this->getMaxDamage($baseIndex);
		if ($curMaxDamage >= $damage) 
		{
			throw new InterException('set max damager failed, cur max damage[%d], wanna set max damage[%d]', $curMaxDamage, $damage);
		}
		
		$baseInfo = $this->getBaseInfo($baseIndex);
		$baseInfo['max_damager'] = array('uid' => $uid, 'htid' => $htid, 'uname' => $uname, 'damage' => $damage);
		$this->setBaseInfo($baseIndex, $baseInfo);
	}
	
	/**
	 * 更新这个base的血量信息
	 * 
	 * @param int $baseIndex
	 * @param array $hpInfo
	 * @param boolean $kill
	 */
	public function updateBaseHp($baseIndex, $hpInfo, $kill)
	{
		$baseInfo = $this->getBaseInfo($baseIndex);
		
		$baseHpInfo = array();
		if (!$kill) 
		{
			foreach ($hpInfo as $aInfo)
			{
				if ($aInfo['hp'] > 0)
				{
					$baseHpInfo[$aInfo['hid']] = $aInfo['hp'];
				}
			}
		}
		$baseInfo['hp'] = $baseHpInfo;
		
		$this->setBaseInfo($baseIndex, $baseInfo);
	}
	
	/**
	 * 刷新的时候要初始化副本的信息，主要是生成每个据点所属的国家类型
	 * 
	 * @throws InterException
	 * @return array
	 */
	public function initCopyInfo()
	{
		$init = array();
		
		$arrRandCountryType = GuildCopyUtil::randCountryType();
		if (count($arrRandCountryType) != GuildCopyCfg::BASE_COUNT * 2) 
		{
			throw new InterException('not enough rand country type[%s]', $arrRandCountryType);
		}
		
		for ($i = 1; $i <= GuildCopyCfg::BASE_COUNT; ++$i)
		{
			$init[$i]['type'] = array($arrRandCountryType[2 * $i - 2], $arrRandCountryType[2 * $i - 1]);
		}
		
		return $init;
	}
	
	/**
	 * 获得当前军团副本的总的血量
	 * 
	 * @return int
	 */
	public function getTotalHp()
	{
		$totalHp = 0;
		for ($i = 1; $i <= GuildCopyCfg::BASE_COUNT; ++$i)
		{
			$totalHp += $this->getBaseMaxHp($i);
		}
		
		return $totalHp;
	}
	
	/**
	 * 获得一个据点的总血量
	 * 
	 * @param int $baseIndex
	 * @return int
	 */
	public function getBaseMaxHp($baseIndex)
	{
		$baseInfo = $this->getBaseInfo($baseIndex);
		if (isset($baseInfo['maxHp'])) 
		{
			return $baseInfo['maxHp'];
		}
		
		$maxHp = GuildCopyUtil::getTotalHpByBase($this->getCurrCopy(), $baseIndex);
		$baseInfo['maxHp'] = $maxHp;
		$this->setBaseInfo($baseIndex, $baseInfo);
		return $maxHp;
	}
	
	/**
	 * 获得当前军团副本总的剩余血量
	 * 
	 * @return int
	 */
	public function getCurrHp()
	{
		list($copyInfo, $isInit) = $this->getCopyInfo();
		$copyId = $this->getCurrCopy();
		
		// 空，代表满血，按理说不应该，refresh的时候都设置了初始化信息
		if (empty($copyInfo)) 
		{
			return $this->getTotalHp();
		}
		
		$currHp = 0;
		for ($i = 1; $i <= GuildCopyCfg::BASE_COUNT; ++$i)
		{
			if (isset($copyInfo[$i]['hp'])) // 设置了这个据点的血量信息，就用它 
			{
				foreach ($copyInfo[$i]['hp'] as $hid => $aHp)
				{
					$currHp += $aHp;
				}
			}
			else // 没有设置这个据点，这个据点就是满血
			{
				$currHp += $this->getBaseMaxHp($i);
			}
		}
		
		return $currHp;
	}
	
	/**
	 * 获取某一个副本的当前血量
	 * 
	 * @param int $baseIndex
	 * @return int
	 */
	public function getBaseCurrHp($baseIndex)
	{
		$baseInfo = $this->getBaseInfo($baseIndex);
		
		$currHp = 0;
		if (isset($baseInfo['hp'])) // 设置了这个据点的血量信息，就用它
		{
			foreach ($baseInfo['hp'] as $hid => $aHp)
			{
				$currHp += $aHp;
			}
		}
		else // 没有设置这个据点，这个据点就是满血
		{
			$currHp = $this->getBaseMaxHp($baseIndex);
		}
		
		return $currHp;
	}
	
	/**
	 * 获得当前目标副本这个据点的战斗数据，已经更新过血量信息啦
	 * 
	 * @param int $baseIndex
	 * @throws InterException
	 * @throws ConfigException
	 */
	public function getBattleFormation($baseIndex)
	{
		// $baseIndex从1开始
		if ($baseIndex <= 0 || $baseIndex > GuildCopyCfg::BASE_COUNT) 
		{
			throw new InterException('invalid curr base id[%d], base count[%d]', $baseIndex, GuildCopyCfg::BASE_COUNT);
		}
		
		// 战斗数据，战斗类型，结束条件
		list($formation, $battleType, $endCondition) = GuildCopyUtil::getBaseBattleFormation($this->getCurrCopy(), $baseIndex);
		
		// 更新血量
		$baseInfo = $this->getBaseInfo($baseIndex);
		if (isset($baseInfo['hp'])) 
		{
			$hpInfo = $baseInfo['hp'];
			foreach ($formation['arrHero'] as $pos => $heroInfo)
			{
				if (empty($hpInfo[$heroInfo['hid']]))
				{
					unset($formation['arrHero'][$pos]);
				}
				else
				{
					$formation['arrHero'][$pos]['currHp'] = $hpInfo[$heroInfo['hid']];
				}
			}
		}
		
		return array($formation, $battleType, $endCondition);
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
			Logger::debug('update GuildCopyObj : no change');
			return;
		}
		
		if (!isset($arrField[GuildCopyField::TBL_FIELD_UPDATE_TIME]))
		{
			$arrField[GuildCopyField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
		}
	
		Logger::debug("update GuildCopyObj guild id:%d changed field:%s", $this->getGuildId(), $arrField);
	
		$arrCond = array
		(
				array(GuildCopyField::TBL_FIELD_GUILD_ID, '=', $this->getGuildId()),
		);
		GuildCopyDao::updateGuild($arrCond, $arrField);
	
		$this->mObj = $this->mObjModify;
	}
	
	/*********************************************
	 * 只有在测试或者Console模式下才能调用的函数
	 ********************************************/
	 public function resetForTest()
	 {
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_CURR] = 1;
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_NEXT] = 1;
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_MAX_PASS_COPY] = 0;
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_REFRESH_NUM] = 0;
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_PASS_TIME] = 0;
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_UPDATE_TIME] = Util::getTime();
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA] = array();
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_VA_BOSS] = array();
	 }
	 /*
	  * 测试用
	  * */
	 public function resetGuildBoss()
	 {
	 	$copyId = $this->getCurrCopy();
	 	
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_VA_BOSS] = GuildCopyUtil::getBossInitInfo($copyId);
	 }
	 
	 public function resetPassTimeForTest()
	 {
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_PASS_TIME] = 0;
	 }
	 
	 public function setMaxPassCopyForTest($num)
	 {
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_MAX_PASS_COPY] = $num;
	 }
	 
	 public function setCurrCopyForTest($num)
	 {
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_CURR] = $num;
	 }
	 
	 public function resetRefreshNumForTest()
	 {
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_REFRESH_NUM] = 0;
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_VA_EXTRA][GuildCopyField::TBL_VA_EXTRA_SUBFIELD_REFRESHER] = array();
	 }
	 
	 public function setPassTimeForTest($time)
	 {
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_PASS_TIME] = $time;
	 }
	 
	 public function setUpdateTimeForTest($time)
	 {
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_UPDATE_TIME] = $time;
	 }
	 
	 public function setBossHp($Hp)
	 {
	 	foreach ($this->mObjModify[GuildCopyField::TBL_FIELD_VA_BOSS]['arrHero'] as $key => &$val)
	 	{
	 		if(!empty($Hp[$key]))
	 		{
	 			$val['hp'] = $Hp[$key];
	 		}
	 		else 
	 		{
	 			$val['hp'] = 0;
	 		}
	 	}
	 }
	 
	 public function getBossInfo()
	 {
	 	return $this->mObjModify[GuildCopyField::TBL_FIELD_VA_BOSS];
	 }
	 
	 public function getBossFormation()
	 {
	 	$CopyId = $this->getCurrCopy();
	 	$Formation  = GuildCopyUtil::getBossFormation($CopyId);
	 	$HeroKeys = array_keys($Formation[ 'arrHero' ]);
	 	
	 	//重新写boss血量
	 	$Info = $this->getBossInfo();
	 	$Info = $Info['arrHero'];
		foreach ( $HeroKeys  as $key )
		{
			$BossHid = $Formation[ 'arrHero' ][$key][PropertyKey::HID];
			if(isset($Info[$BossHid]['hp']) && isset($Info[$BossHid]['max_hp']))
			{
				if($Info[$BossHid]['hp'] != 0)
				{
					$Formation[ 'arrHero' ][$key][PropertyKey::CURR_HP] = $Info[$BossHid]['hp'];
					$Formation[ 'arrHero' ][$key][PropertyKey::MAX_HP] = $Info[$BossHid]['max_hp'];
				}
				else
				{
					unset($Formation[ 'arrHero' ][$key]);
				}
			}
		}
	 	return $Formation;
	 }
	 
	 public function refreshBoss()
	 {
	 	$copyId = $this->getCurrCopy();
	 	
	 	$conf = btstore_get()->GUILD_COPY_RULE; 	
	 	$oldBossInfo = $this->getBossInfo();
	 	// 增加血量，重置刷新CD
	 	$newBossInfo['cd'] = Util::getTime() + $conf['cd'];
	 	
	 	$newBossInfo['arrHero'] = array();
	 	foreach($oldBossInfo['arrHero'] as $key => $hero)
	 	{
	 		$newHp = intval ($hero['max_hp'] * ((10000 + $conf['rise'])/10000));
	 		$newBossInfo['arrHero'][$key] = array(
	 			'hp' => $newHp,
	 			'max_hp' => $newHp,
	 		);
	 	}
	 	
	 	$this->mObjModify[GuildCopyField::TBL_FIELD_VA_BOSS] = $newBossInfo;
	 	
	 	return $newBossInfo;
	 }
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */