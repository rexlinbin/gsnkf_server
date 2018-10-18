<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BossUtil.class.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/BossUtil.class.php $
 * @author $Author: BaoguoMeng $(jhd@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief
 *
 **/

class BossUtil
{
	
	/**
	 * 获得该用户的排名 0 是没有参加，也即没有名次
	 * @param unknown $atkHp
	 * @param unknown $bossId
	 * @param unknown $startTime
	 * @param unknown $endTime
	 * @return number
	 */
	public static function getAtkerRank( $atkHp, $bossId, $startTime, $endTime )
	{
		if ( $atkHp <= 0 )
		{
			return 0;
		}
		else
		{
			return BossDAO::getAtkerRank( $atkHp, $bossId, $startTime, $endTime );
		}
	}
	
	
	public static function getBtlExInfo( $baseId )
	{
		$type = btstore_get()->BASE[$baseId]['type'];
		return array(
				'type' => $type,
		);
		
	}
	/**
	 *
	 * 检测boss id是否合法
	 *
	 * @param int $boss_id
	 *
	 * @return boolean
	 */
	public static function checkBossIdValidate($boss_id)
	{
		if ( empty( $boss_id ) )
		{
			throw new FakeException( 'empty bossId' );
		}
		if ( isset(btstore_get()->BOSS[$boss_id]) == false )
		{
			throw new FakeException( 'invalid bossid: %d',$boss_id );
		}
	}


	/**
	 *
	 * 得到boss的攻击的前N名
	 *
	 * @param int $boss_id
	 * @param int $boss_start_time
	 * @param int $boss_end_time
	 * $param int $topN
	 *
	 * @return
	 */
	public static function getBossAttackHpTop($boss_id, $boss_start_time,
		 $boss_end_time, $topN )
	{
		return BossDAO::getBossAttackHpTop($boss_id,
			 $boss_start_time, $boss_end_time, $topN);
	}

	/**
	 *
	 * 得到排序后的列表
	 *
	 * @param int $boss_id
	 * @param int $boss_start_time
	 * @param int $boss_end_time
	 *
	 * @return NULL
	 */
	public static function getBossAttackListSorted($boss_id, $boss_start_time, $boss_end_time)
	{
		$array = BossDAO::getBossAttackList($boss_id, $boss_start_time, $boss_end_time);
		$attack_list = Util::arrayIndexCol($array, BossDef::ATK_UID, BossDef::ATK_HP);
		arsort($attack_list);
		return $attack_list;
	}

	/**
	 *
	 * boss战的开始时间
	 *
	 * @param int $boss_id
	 * @param int $time
	 *
	 * @return int
	 *
	 */
	public static function getBossStartTime($boss_id, $time=NULL)
	{
		$interval = self::getBossTime($boss_id, $time);
		if ( empty($interval) )
		{
			return 0;
		}
		else
		{
			Logger::DEBUG('boss start time:%s', date('Y-m-d H:i:s', $interval[0]));
			return $interval[0];
		}
	}

	/**
	 *
	 * boss战的结束时间
	 *
	 * @param int $boss_id
	 * @param int $time
	 *
	 * @return int
	 *
	 */
	public static function getBossEndTime($boss_id, $time=NULL)
	{
		$interval = self::getBossTime($boss_id, $time);
		if ( empty($interval) )
		{
			return 0;
		}
		else
		{
			Logger::DEBUG('boss end time:%s', date('Y-m-d H:i:s', $interval[1]));
			return $interval[1];
		}
	}

	private static function getBossTime($boss_id, $time=NULL)
	{
		if ( $time === NULL )
		{
			$cur_time = Util::getTime();
		}
		else
		{
			$cur_time = $time;
		}
		$start_time = btstore_get()->BOSS[$boss_id][BossDef::BOSS_START_TIME];
		$end_time = btstore_get()->BOSS[$boss_id][BossDef::BOSS_END_TIME];
		$day_start_times = btstore_get()->BOSS[$boss_id][BossDef::BOSS_DAY_START_TIMES]->toArray();
		
		$bossOffset = GameConf::BOSS_OFFSET;
		Logger::debug('bossoffset: %d', $bossOffset);
		foreach ( $day_start_times as $key => $value )
		{
			$day_start_times[$key] = $value + $bossOffset;
		}
		$day_end_times = btstore_get()->BOSS[$boss_id][BossDef::BOSS_DAY_END_TIMES]->toArray();
		foreach ( $day_end_times as $key => $value )
		{
			$day_end_times[$key] = $value + $bossOffset;
		}
		$day_list = btstore_get()->BOSS[$boss_id][BossDef::BOSS_DAY_LIST]->toArray();
		$week_list = btstore_get()->BOSS[$boss_id][BossDef::BOSS_WEEK_LIST]->toArray();

		sort($day_start_times);
		sort($day_end_times);
		sort($day_list);
		sort($week_list);

		$interval = TimeInterval::getTimeInterval($cur_time, $start_time, $end_time,
			 $day_start_times, $day_end_times, $day_list, $week_list);

		Logger::DEBUG('boss time:%s', $interval);
		return $interval;
	}

	/**
	 *
	 * 前一个boss战的开始时间
	 *
	 * @param int $boss_id
	 * @param int $time
	 *
	 * @return int
	 *
	 */
	public static function getBeforeBossStartTime($boss_id, $time=NULL)
	{
		$interval = self::getBeforeBossTime($boss_id, $time);
		if ( empty($interval) )
		{
			return 0;
		}
		else
		{
			Logger::DEBUG('boss start time:%s', date('Y-m-d H:i:s', $interval[0]));
			return $interval[0];
		}
	}

	/**
	 *
	 * 前一个boss战的结束时间
	 *
	 * @param int $boss_id
	 * @param int $time
	 *
	 * @return int
	 *
	 */
	public static function getBeforeBossEndTime($boss_id, $time=NULL)
	{
		$interval = self::getBeforeBossTime($boss_id, $time);
		if ( empty($interval) )
		{
			return 0;
		}
		else
		{
			Logger::DEBUG('boss end time:%s', date('Y-m-d H:i:s', $interval[1]));
			return $interval[1];
		}
	}

	public static function getBeforeBossTime($boss_id, $time=NULL)
	{
		if ( $time === NULL )
		{
			$cur_time = Util::getTime();
		}
		else
		{
			$cur_time = $time;
		}
		$start_time = btstore_get()->BOSS[$boss_id][BossDef::BOSS_START_TIME];
		$end_time = btstore_get()->BOSS[$boss_id][BossDef::BOSS_END_TIME];
		$day_start_times = btstore_get()->BOSS[$boss_id][BossDef::BOSS_DAY_START_TIMES]->toArray();
		foreach ( $day_start_times as $key => $value )
		{
			$day_start_times[$key] = $value + GameConf::BOSS_OFFSET;
		}
		$day_end_times = btstore_get()->BOSS[$boss_id][BossDef::BOSS_DAY_END_TIMES]->toArray();
		foreach ( $day_end_times as $key => $value )
		{
			$day_end_times[$key] = $value + GameConf::BOSS_OFFSET;
		}
		$day_list = btstore_get()->BOSS[$boss_id][BossDef::BOSS_DAY_LIST]->toArray();
		$week_list = btstore_get()->BOSS[$boss_id][BossDef::BOSS_WEEK_LIST]->toArray();

		rsort($day_start_times);
		rsort($day_end_times);
		rsort($day_list);
		rsort($week_list);

		$interval = TimeInterval::getTimeIntervalBefore($cur_time, $start_time, $end_time,
			 $day_start_times, $day_end_times, $day_list, $week_list);

		Logger::DEBUG('boss time:%s', $interval);
		return $interval;
	}

	/**
	 *
	 * 是否可以进行boss战
	 *
	 * @param int $boss_id
	 *
	 * @return boolean
	 */
	public static function isBossTime($boss_id)
	{
		$time = Util::getTime();
		if ( $time >= self::getBossStartTime($boss_id) && $time < self::getBossEndTime($boss_id) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	public static function checkBossTime($boss_id)
	{
		$time = Util::getTime();
		if ( $time < self::getBossStartTime($boss_id) || $time > self::getBossEndTime($boss_id) )
		{
			throw new FakeException( 'not in boss time!boss id:%d', $boss_id );
		}
	}
	
	

	/**
	 *
	 * 得到boss对应的army id
	 *
	 * @param int $boss_id
	 * @throws Exception
	 *
	 * @return int
	 */
	public static function getBossBaseId($boss_id , $boss_lv)
	{
		
		$bossConf = btstore_get()->BOSS[ $boss_id ];
		if(self::isNewBoss($boss_id, $boss_lv))
		{
			$baseId = $bossConf[BossDef::NEWBASE_ID];
		}
		else 
		{
			$baseId = $bossConf[BossDef::BASE_ID];
		}
		
		return $baseId;
	}

	public static function isNewBoss( $boss_id, $boss_lv  )
	{
		$time = Util::getTime();
		
		$bossConf = btstore_get()->BOSS[ $boss_id ];
		$day = date( "w", $time );
		/* if( $day === 0 )
		{
			$day = 7;
		} */
		$newBossDays = $bossConf[ BossDef::NEWBOSS_DAY ]->toArray();
		if( in_array( $day, $newBossDays ) && $boss_lv >= $bossConf[BossDef::NEWBOSS_NEEDLV] )
		{
			return true;
		}
		return false;
	}
	
	/**
	 *
	 * 得到boss的最大血量
	 *
	 * @param int $boss_id
	 * @throws Exception
	 *
	 * @return int
	 */
	public static function getBossMaxHp($boss_id, $level)
	{
		$enemyFormation = self::getBossFormationInfo($boss_id, $level);

		$boss_max_hp = 0;
		$count = 0;
		$arrHero = $enemyFormation['arrHero'];
		foreach ( $arrHero as $key => $value )
		{
			if ( !empty( $value ) )
			{
				$boss_max_hp = $value[PropertyKey::MAX_HP];
				$count++;
			}
		}

		if ( $count != 1 )
		{
			Logger::fatal('invalid boss max hp!boss id:%d, count: %d', $boss_id, $count);
		}
		
		return $boss_max_hp;
	}

	/**
	 *
	 * 得到boss的阵型信息
	 *
	 * @param int $boss_id
	 * @param int $level
	 *
	 */
	public static function getBossFormationInfo($boss_id, $level)
	{
		$baseId = self::getBossBaseId($boss_id, $level);
		$armyArr = CopyUtil::getArmyInBase($baseId);
		$armyId = $armyArr[0];
		$level_array = array_fill(0, FormationDef::FORMATION_SIZD, $level);

		// 敌人信息
		return EnFormation::getMonsterBattleFormation( $armyId,1, $level_array  );
	}

	/**
	 *
	 * 得到boss的最低等级
	 *
	 * @param int $boss_id
	 * @throws Exception
	 */
	public static function getBossMinLevel($boss_id)
	{
		if ( !isset(btstore_get()->BOSS[$boss_id]) || !isset(btstore_get()->BOSS[$boss_id][BossDef::BOSS_MIN_LEVEL]) )
		{
			Logger::FATAL('invalid boss min level!boss id:%d', $boss_id);
			throw new Exception('config');
		}
		return btstore_get()->BOSS[$boss_id][BossDef::BOSS_MIN_LEVEL];
	}

	/**
	 *
	 * 得到boss的最大等级
	 *
	 * @param int $boss_id
	 * @throws Exception
	 *
	 * return int
	 */
	public static function getBossMaxLevel($boss_id)
	{
		if ( !isset(btstore_get()->BOSS[$boss_id]) || !isset(btstore_get()->BOSS[$boss_id][BossDef::BOSS_MAX_LEVEL]) )
		{
			Logger::FATAL('invalid boss max level!boss id:%d', $boss_id);
			throw new Exception('config');
		}
		return btstore_get()->BOSS[$boss_id][BossDef::BOSS_MAX_LEVEL];
	}

	/**
	 *
	 * 得到boss的初始等级
	 *
	 * @param int $boss_id
	 * @throws Exception
	 */
	public static function getBossInitLevel($boss_id)
	{
		if ( !isset(btstore_get()->BOSS[$boss_id]) || !isset(btstore_get()->BOSS[$boss_id][BossDef::BOSS_INIT_LEVEL]) )
		{
			Logger::FATAL('invalid boss init level!boss id:%d', $boss_id);
			throw new Exception('config');
		}
		return btstore_get()->BOSS[$boss_id][BossDef::BOSS_INIT_LEVEL];
	}

	/**
	 *
	 * 得到boss的奖励ID
	 *
	 * @param int $boss_id
	 *
	 * @throws Exception
	 */
	public static function getBossRewardId($boss_id ,$boss_lv)
	{
		$bossConf = btstore_get()->BOSS[$boss_id];
		$isNewBoss = self::isNewBoss($boss_id, $boss_lv);
		if( $isNewBoss )
		{
			if( $boss_lv >= $bossConf[BossDef::CHANGE_REWARD2][0] )
			{
				$rewardId = $bossConf[BossDef::CHANGE_REWARD2][1];
			}
			else 
			{
				$rewardId = $bossConf[ BossDef::NEWREWARD_ID ];
			}
		}
		else 
		{
			if( $boss_lv >= $bossConf[BossDef::CHANGE_REWARD1][0] )
			{
				$rewardId = $bossConf[BossDef::CHANGE_REWARD1][1];
			}
			else 
			{
				$rewardId = $bossConf[ BossDef::REWARD_ID ];
			}
		}
		
		return $rewardId;
	}

	/**
	 *
	 * 得到boss的奖励
	 *
	 * @param int $boss_id
	 * @param int $order
	 *
	 * @throws Exception
	 */
	public static function getBossReward($boss_id, $order,$boss_level, $userLevel)
	{
		$reward_id = self::getBossRewardId($boss_id,$boss_level );
		
		if ( !isset(btstore_get()->BOSS_REWARD[$reward_id]) || !isset(btstore_get()->BOSS_REWARD[$reward_id][BossDef::REWARD_ORDER_LIST])
			|| !isset(btstore_get()->BOSS_REWARD[$reward_id][BossDef::REWARD_ORDER_LIST_NUM]) )
		{
			throw new ConfigException( 'invalid reward id!reward id:%d', $reward_id );
		}

		$order_list = btstore_get()->BOSS_REWARD[$reward_id][BossDef::REWARD_ORDER_LIST];
		$order_list_num = btstore_get()->BOSS_REWARD[$reward_id][BossDef::REWARD_ORDER_LIST_NUM];
		
		$rewardOrderList = array();
		foreach ( $order_list as $value )
		{
			if ( $value[BossDef::REWARD_ORDER_LOW] <= $order &&
				$value[BossDef::REWARD_ORDER_UP] >= $order )
			{
				$rewardOrderList =  $value;
				break;
			}
		}
		if ( empty( $rewardOrderList ) ) 
		{
			$rewardOrderList = $order_list[$order_list_num-1];
		}
		
		$rewardInfo = $rewardOrderList[BossDef::REWARD_INFO];
		$standardArr = array();
		foreach ( $rewardInfo as $rewardKey => $bossRewardInfo )
		{
			switch ( $bossRewardInfo['type'] )
			{
				case RewardConfType::EXECUTION :
					self::addValue($standardArr, RewardType::EXE, $bossRewardInfo['val']);
					//$standardArr[RewardType::EXE] = $bossRewardInfo['val'];
					break;
				case RewardConfType::SOUL :
					self::addValue($standardArr, RewardType::SOUL, $bossRewardInfo['val']);
					//$standardArr[RewardType::SOUL] = $bossRewardInfo['val'];
					break;
				case RewardConfType::SOUL_MUL_LEVEL :
					self::addValue($standardArr, RewardType::SOUL, $bossRewardInfo['val']*$userLevel);
					//$standardArr[RewardType::SOUL] = $bossRewardInfo['val'];
					break;
				case RewardConfType::SILVER :
					self::addValue($standardArr, RewardType::SILVER, $bossRewardInfo['val']);
					//$standardArr[RewardType::SILVER] = $bossRewardInfo['val'];
					break;
				case RewardConfType::SILVER_MUL_LEVEL :
					self::addValue($standardArr, RewardType::SILVER, $bossRewardInfo['val']*$userLevel);
					//$standardArr[RewardType::SILVER] = $bossRewardInfo['val'];
					break;	
				case RewardConfType::EXP_MUL_LEVEL :
					self::addValue($standardArr, RewardType::EXP_NUM, $bossRewardInfo['val']*$userLevel);
					//$standardArr[RewardType::EXP_NUM] = $bossRewardInfo['val'];
					break;
				case RewardConfType::STAMINA :
					self::addValue($standardArr, RewardType::STAMINA, $bossRewardInfo['val']);
					//$standardArr[RewardType::STAMINA] = $bossRewardInfo['val'];
					break;
				case RewardConfType::PRESTIGE :
					self::addValue($standardArr, RewardType::PRESTIGE, $bossRewardInfo['val']);
					//$standardArr[RewardType::PRESTIGE] = $bossRewardInfo['val'];
					break;
				case RewardConfType::JEWEL :
					self::addValue($standardArr, RewardType::JEWEL, $bossRewardInfo['val']);
					//$standardArr[RewardType::JEWEL] = $bossRewardInfo['val'];
					break;
				case RewardConfType::GOLD :
					self::addValue($standardArr, RewardType::GOLD, $bossRewardInfo['val']);
					//$standardArr[RewardType::GOLD] = $bossRewardInfo['val'];
					break;
				case RewardConfType::HERO :
					if ( !isset( $standardArr[ RewardType::ARR_HERO_TPL ][$bossRewardInfo['val']] ) )
					{
						$standardArr[ RewardType::ARR_HERO_TPL ][$bossRewardInfo['val']] = 1;
					}
					else
					{
						$standardArr[ RewardType::ARR_HERO_TPL ][$bossRewardInfo['val']] += 1;
					}
					break;
					//此处的val不是一个二维数组 是一个一维的数组 这与rewardUtil解析的是不一样的，
					//考虑到此处是直接往奖励中心发 就不纠结了。。。。
				case RewardConfType::HERO_MULTI :
					if ( !isset( $standardArr[ RewardType::ARR_HERO_TPL ][$bossRewardInfo['val'][0]] ) )
					{
						$standardArr[ RewardType::ARR_HERO_TPL ][$bossRewardInfo['val'][0]] = $bossRewardInfo['val'][1];
					}
					else
					{
						$standardArr[ RewardType::ARR_HERO_TPL ][$bossRewardInfo['val'][0]] += $bossRewardInfo['val'][1];
					}
					break;
				case RewardConfType::ITEM :
					if ( !isset( $standardArr[ RewardType::ARR_ITEM_TPL ][$bossRewardInfo['val']] ) )
					{
						Logger::debug('offsets are: %d, %s ', RewardType::ARR_ITEM_TPL, $bossRewardInfo['val'] );
						$standardArr[ RewardType::ARR_ITEM_TPL ][$bossRewardInfo['val']] = 1;
					}
					else
					{
						$standardArr[ RewardType::ARR_ITEM_TPL ][$bossRewardInfo['val']] += 1;
					}
					break;
					//此处的val不是一个二维数组 是一个一维的数组 这与rewardUtil解析的是不一样的，
					//考虑到此处是直接往奖励中心发 就不纠结了。。。。
				case RewardConfType::ITEM_MULTI :
					if ( !isset( $standardArr[ RewardType::ARR_ITEM_TPL ][$bossRewardInfo['val'][0]] ) )
					{
						$standardArr[ RewardType::ARR_ITEM_TPL ][$bossRewardInfo['val'][0]] = $bossRewardInfo['val'][1];
					}
					else
					{
						$standardArr[ RewardType::ARR_ITEM_TPL ][$bossRewardInfo['val'][0]] += $bossRewardInfo['val'][1];
					}
					break;
		
			}
		}
		
		$dropThings = array();
		if ( !empty( $rewardOrderList[BossDef::REWARD_DROP_TEMPLATE_ID] ) )
		{
			$dropThings = Drop::dropMixed( $rewardOrderList[BossDef::REWARD_DROP_TEMPLATE_ID] );
		}
		
		if ( !empty( $dropThings ) )
		{
			foreach ( $dropThings as $type => $info )
			{
				switch ( $type )
				{
					case DropDef::DROP_TYPE_SILVER:
						if ( !isset( $standardArr[RewardType::SILVER] ) )
						{
							$standardArr[RewardType::SILVER] = $info[0];
						}
						else
						{
							$standardArr[RewardType::SILVER] += $info[0];
						}
		
						break;
					case DropDef::DROP_TYPE_SOUL:
						if ( !isset( $standardArr[RewardType::SOUL] ) )
						{
							$standardArr[RewardType::SOUL] = $info[0];
						}
						else
						{
							$standardArr[RewardType::SOUL] += $info[0];
						}
						break;
					case DropDef::DROP_TYPE_ITEM:
						foreach ( $info as $itemId => $itemNum)
						{
							if ( !isset( $standardArr[RewardType::ARR_ITEM_TPL][$itemId] ) )
							{
								$standardArr[RewardType::ARR_ITEM_TPL][$itemId] = $itemNum;
							}
							else
							{
								$standardArr[RewardType::ARR_ITEM_TPL][$itemId] += $itemNum;
							}
						}
						break;
					case DropDef::DROP_TYPE_HERO:
						foreach ( $info as $htid => $hNum )
						{
							if ( !isset( $standardArr[RewardType::ARR_HERO_TPL][$htid] ) )
							{
								$standardArr[RewardType::ARR_HERO_TPL][$htid] = $hNum;
							}
							else
							{
								$standardArr[RewardType::ARR_HERO_TPL][$htid] += $hNum;
							}
						}
						break;
					default :
						throw new ConfigException( 'invalid type: %d', $type );
				}
			}
		}
		
		return $standardArr;
	}

	public static function addValue(&$res, $key, $val)
	{
		if ( isset( $res[$key] ) )
		{
			$res[$key] += $val;
		}
		else 
		{
			$res[$key] = $val;
		}
	}
	
	/**
	 *
	 * 得到boss每次攻击奖励的belly
	 *
	 * @param int $boss_id
	 *
	 * @throws Exception
	 *
	 * @return NULL
	 */
	public static function getSilverPerAttack($boss_id)
	{
		if ( !isset(btstore_get()->BOSS[$boss_id]) || !isset(btstore_get()->BOSS[$boss_id][BossDef::REWARD_SILVER_BASIC]) )
		{
			Logger::FATAL('invalid silver per attack id!boss id:%d', $boss_id);
			throw new Exception('config');
		}
		return btstore_get()->BOSS[$boss_id][BossDef::REWARD_SILVER_BASIC];
	}

	/**
	 *
	 * 得到boss每次攻击奖励的阅历
	 *
	 * @param int $boss_id
	 *
	 * @throws Exception
	 *
	 * @return NULL
	 */
	public static function getPrestigePerAttack($boss_id)
	{
		if ( !isset(btstore_get()->BOSS[$boss_id]) || !isset(btstore_get()->BOSS[$boss_id][BossDef::REWARD_PRESTIGE_BASIC]) )
		{
			Logger::FATAL('invalid soul per attack id!boss id:%d', $boss_id);
			throw new Exception('config');
		}
		return btstore_get()->BOSS[$boss_id][BossDef::REWARD_PRESTIGE_BASIC];
	}

	/**
	 *
	 * 得到boss每次攻击奖励的声望
	 *
	 * @param int $boss_id
	 *
	 * @throws Exception
	 *
	 * @return NULL
	 */
	public static function getBossPrestigePerAttack($boss_id)
	{
		if ( !isset(btstore_get()->BOSS[$boss_id]) || !isset(btstore_get()->BOSS[$boss_id][BossDef::REWARD_PRESTIGE_BASIC]) )
		{
			Logger::FATAL('invalid boss prestige per attack id!boss id:%d', $boss_id);
			throw new Exception('config');
		}
		return btstore_get()->BOSS[$boss_id][BossDef::REWARD_PRESTIGE_BASIC];
	}

	/**
	 *
	 * 得到攻击boss的血量所占的百分比
	 *
	 * @param int $attack_hp
	 * @param int $boss_max_hp
	 */
	public static function getBossAttackHPPercent($attack_hp, $boss_max_hp)
	{
		$attack_hp_precent = floatval($attack_hp) / $boss_max_hp * 1000;
		$attack_hp_precent = floatval(intval($attack_hp_precent)) / 10;
		return strval($attack_hp_precent) . "%";
	}

	/**
	 *
	 * 是否是boss战斗时间
	 *
	 * @param NULL
	 *
	 * @return bool TRUE表示在boss战时间内, FALSE表示没有
	 */
	public static function isInBossTime()
	{
		foreach ( btstore_get()->BOSS as $boss_id => $value )
		{
			if ( self::isBossTime($boss_id) )
			{
				return TRUE;
			}
		}
		return FALSE;
	}
	
	public static function getSuperHero( $bossId )
	{
		return array(
			'good' => array(),
			'better' => array(),
			'best' => array(),	
		);
		//--------应策划要求功能废弃掉
		
		self::checkBossIdValidate( $bossId );
		$dropIdArr = btstore_get()->BOSS[$bossId][BossDef::SUPERHERO];
		
		$dropedHero = array();
		$dropedStandardHero = array();
		
		//依赖掉落表的每次掉落次数，每次五个
		$heroNumConf = btstore_get()->BOSS[$bossId][BossDef::SUPERHERO_NUM_ARR]->toArray();
		$heroNumNeed = 0;
		foreach ( $heroNumConf as $key => $val )
		{
			$heroNumNeed += $val[0];
		}
	
		//掉三次每次一种伤害阵容
		$dropNum = 3;
			
		for ( $i = 0; $i<$dropNum; $i++ )
		{
			$dropThings = Drop::dropMixed($dropIdArr[$i], $dropedHero);
			foreach ( $dropThings as $droptype => $dropInfo )
			{
				if ( $droptype != DropDef::DROP_TYPE_HERO )
				{
					throw new ConfigException( 'dropid: %d not drop hero %s', $dropIdArr[$i], $dropThings);
				}
				foreach ( $dropInfo as $htid => $hnum )
				{
					if (in_array( $htid , $dropedHero))
					{
						throw new ConfigException( 'drop same hero' );
					}
					$dropedHero[] = $htid;
					$dropedStandardHero[$i][] = $htid;
				}
			}
		}
		
		if ( count( $dropedHero ) < $heroNumNeed )
		{
			throw new ConfigException( 'droped: %d < need: %d', count( $dropedHero ), $heroNumNeed );
		}
		
		
		$ret['good'] = $dropedStandardHero[0];
		$ret['better'] = $dropedStandardHero[1];
		$ret['best'] = $dropedStandardHero[2];
		
		return $ret;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */