<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildCopyUtil.class.php 234198 2016-03-22 12:43:37Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildcopy/GuildCopyUtil.class.php $
 * @author $Author: DuoLi $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-03-22 12:43:37 +0000 (Tue, 22 Mar 2016) $
 * @version $Revision: 234198 $
 * @brief 
 *  
 **/
 
class GuildCopyUtil
{
	/**
	 * 判断玩家军团副本功能是否开启
	 * 
	 * @param int $uid
	 * @return boolean
	 */
	public static function isGuildCopyOpen($uid)
	{
		$arrBuildCond = btstore_get()->GUILD_COPY_RULE['build_cond']->toArray();
		foreach ($arrBuildCond as $buildType => $needLevel)
		{
			if (EnGuild::getBuildLevel($uid, $buildType) < $needLevel) 
			{
				return FALSE;
			}
		}
		return TRUE;
	}
	
	/**
	 * 根据copyId和baseId获取真正的据点Id
	 *
	 * @param int $copyId 		副本Id
	 * @param int $baseIndex	据点的索引，从1开始
	 * @throws InterException
	 * @return int				这个据点真正的baseId
	 */
	public static function getBaseId($copyId, $baseIndex)
	{
		if (!isset(btstore_get()->GUILD_COPY_INFO[$copyId]))
		{
			throw new InterException('invalid copy id[%d], all copy id[%s]', $copyId, array_keys(btstore_get()->GUILD_COPY_INFO->toArray()));
		}
		$arrBase = btstore_get()->GUILD_COPY_INFO[$copyId]['base']->toArray();
	
		if (!isset($arrBase[$baseIndex - 1]))
		{
			throw new InterException('invalid base index[%d], all base[%s]', $baseIndex, $arrBase);
		}
		return $arrBase[$baseIndex - 1];
	}
	
	/**
	 * 通过copyId和baseIndex获取这个据点的战斗数据，战斗类型，结束条件
	 * 
	 * @param int $copyId 从1开始
	 * @param int $baseIndex 从1开始
	 * @throws ConfigException
	 * @return array
	 */
	public static function getBaseBattleFormation($copyId, $baseIndex)
	{
		/**
		 * 这里的baseId其实就是armyId!!!!!!!!!!!!!!
		 */
		// 获得战斗数据
		$baseId = GuildCopyUtil::getBaseId($copyId, $baseIndex);
		
		/*$baseLv = BaseLevel::SIMPLE;
		$lvName = CopyConf::$BASE_LEVEL_INDEX[$baseLv];
		$armyIds = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays']->toArray();
		if(count($armyIds) < 1)
		{
			throw new ConfigException('no army to defend copy[%d] base index[%d] base id[%d]', $copyId, $baseIndex, $baseId);
		}*/
		
		$armyId = $baseId;
		$formation = EnFormation::getMonsterBattleFormation($armyId);
		
		// 增加免疫的BUFF
		$arrImmunedBuff = btstore_get()->GUILD_COPY_RULE['immuned_buff']->toArray();
		if (!empty($arrImmunedBuff)) 
		{
			foreach ($formation['arrHero'] as $pos => $aHeroInfo)
			{
				if (!isset($aHeroInfo[PropertyKey::ARR_IMMUNED_BUFF]))
				{
					$formation['arrHero'][$pos][PropertyKey::ARR_IMMUNED_BUFF] = array();
				}
				$formation['arrHero'][$pos][PropertyKey::ARR_IMMUNED_BUFF] = array_merge($formation['arrHero'][$pos][PropertyKey::ARR_IMMUNED_BUFF], $arrImmunedBuff);
			}
		}
		
		// 获得战斗类型和结束条件
		$battleType = btstore_get()->ARMY[$armyId]['fight_type'];
		$endCondition = array();
		if(isset(btstore_get()->ARMY[$armyId]['end_condition']))
		{
			$endCondition = btstore_get()->ARMY[$armyId]['end_condition']->toArray();
		}
		
		return array($formation, $battleType, $endCondition);
	}
	
	/**
	 * 根据copyId和baseIndex，从配置了读取这个据点的总血量。
	 * 
	 * @param int $copyId
	 * @param int $baseIndex
	 * @return int
	 */
	public static function getTotalHpByBase($copyId, $baseIndex)
	{
		list($formation, $battleType, $endCondition) = GuildCopyUtil::getBaseBattleFormation($copyId, $baseIndex);
		$totalHp = 0;
		foreach ($formation['arrHero'] as $aHeroInfo)
		{
			$totalHp += $aHeroInfo[PropertyKey::MAX_HP];
		}
		
		return $totalHp;
	}
	
	/**
	 * 根据copyId到配置中，找出这个copyId的所有base上的怪的总血量
	 * 
	 * @param int $copyId
	 * @return int 
	 */
	public static function getTotalHpByCopy($copyId)
	{
		$totalHp = 0;
		for ($baseIndex = 1; $baseIndex <= GuildCopyCfg::BASE_COUNT; ++$baseIndex)
		{
			$totalHp += GuildCopyUtil::getTotalHpByBase($copyId, $baseIndex);
		}
		
		return $totalHp;
	}
	
	/**
	 * 随机生成两两国家组合，保证这个组合不重复，保证魏蜀吴群至少每个国家都出现一次
	 * 
	 * @param int $n	据点的个数
	 * @return array() 类似这种 array(1,3,3,4,2,4,3,2,1,4,4,1)
	 */
	public static function randCountryType($n = NULL)
	{
		if (empty($n)) 
		{
			$n = GuildCopyCfg::BASE_COUNT;
		}
		if ($n < 2) 
		{
			throw new FakeException('count[%d] too small when rand country type, at lease 2!', $n);
		}
		
		// 每个据点两个位置
		$hole = 2 * $n;
		
		// 1-4分别代表魏蜀吴群，$ret是返回值
		$ret = array_fill(0, $hole, 0);
		$all = array(1, 2, 3, 4);
		
		// 先随机各放一个国家的武将
		$a = range(0, $hole - 1);
		shuffle($a);
		$a = array_slice($a, 0, 4); 
		$x = 0;
		foreach ($a as $i) 
		{
		    $ret[$i] = ++$x;
		}
		
		// 再将剩余的位置上填充上武将，1-4分别代表魏蜀吴群
		for ($i = 0; $i < $hole; ++$i)
		{
		    if ($ret[$i] == 0)
		    {   
		        if ($i % 2 == 1)
		        {   
		            $magic = $all;
		            unset($magic[$ret[$i - 1] - 1]);
		            $tmp = array();
		            foreach ($magic as $x) $tmp[] = $x; 
		            $ret[$i] = $tmp[mt_rand(0, 2)];
		        }   
		        else
		        {   
		            if ($ret[$i + 1] > 0)
		            {   
		                $magic = $all;
		                unset($magic[$ret[$i + 1] - 1]);
		                $tmp = array();
		                foreach ($magic as $x) $tmp[] = $x; 
		                $ret[$i] = $tmp[mt_rand(0, 2)];
		            }   
		            else
		            {   
		                $ret[$i] = $all[mt_rand(0, 3)];
		            }   
		        }   
		    }   
		}
		
		return $ret;
	}
	
	/**
	 * 从奖励池中随机出一种奖励
	 * 奖励池类似array(5 => 10, 6 => 20)代表奖励Id为5的还有10份，奖励Id为6的还有20份，下表从0开始
	 * 
	 * @param int $copyId 		      副本Id
	 * @param array $arrExclude   已经被领取的奖励
	 * @param int $time			     领取奖励的时间，默认是当前时间，当今天补发昨天的宝箱奖励的时候，会传昨天的时间
	 */
	public static function randBoxReward($copyId, $arrExclude, $time = 0)
	{
		if (!isset(btstore_get()->GUILD_COPY_INFO[$copyId])) 
		{
			throw new InterException('not set copy id[%d] in GUILD_COPY_INFO', $copyId);
		}
		
		$arrRewardAvailable = array();
		$arrRewardPool = btstore_get()->GUILD_COPY_INFO[$copyId]['box_reward']->toArray();
		list($beginDate, $boxIndex) = btstore_get()->GUILD_COPY_INFO[$copyId]['box_reward_indicate']->toArray();
		$beginTime = strtotime($beginDate . '000000');
		$now = ($time == 0 ? Util::getTime() : $time);
		if (($now >= $beginTime && $boxIndex == 2)
			|| ($now < $beginTime && $boxIndex == 1))
		{
			$arrRewardPool = btstore_get()->GUILD_COPY_INFO[$copyId]['box_reward_2']->toArray();
			Logger::trace('RAND_BOX_REWARD : use box reward 2, now[%s], beginDate[%s], boxIndex[%d]', strftime('%Y%m%d-%H%M%S', $now), $beginDate, $boxIndex);
		}
		else
		{
			Logger::trace('RAND_BOX_REWARD : use box reward 1, now[%s], beginDate[%s], boxIndex[%d]', strftime('%Y%m%d-%H%M%S', $now), $beginDate, $boxIndex);
		}
		
		$totalWeight = 0;
		foreach ($arrRewardPool as $index => $rewardInfo)
		{
			$num = $rewardInfo['num'];
			if (isset($arrExclude[$index])) 
			{
				$num -= $arrExclude[$index];
			}
			
			if ($num > 0) 
			{
				$totalWeight += $num;
				$arrRewardAvailable[$index] = $num;
			}
		}
		
		if (empty($arrRewardAvailable)) 
		{
			throw new InterException('not enough box reward of copy id[%d], arr exclude[%s]', $copyId, $arrExclude);
		}
		
		Logger::trace('RAND_BOX_REWARD : arr exclude reward[%s], arr reward pool[%s], arr available reward[%s], total weight[%d]', $arrExclude, $arrRewardPool, $arrRewardAvailable, $totalWeight);
		
		$randWeight = mt_rand(1, $totalWeight);
		$curWeight = 0;
		$rewardIndex = -1;
		foreach ($arrRewardAvailable as $index => $weight)
		{
			$curWeight += $weight;
			if ($curWeight >= $randWeight) 
			{
				$rewardIndex = $index;
				break;
			}
		}
		
		if ($rewardIndex == -1) 
		{
			throw new InterException('impossible!!!');
		}
		
		$rewardContent = $arrRewardPool[$rewardIndex]['reward'];
		
		return array($rewardIndex, $rewardContent);
	}
	
	/**
	 * 根据参数中的国家，给战斗数据中这些国家的武将加成
	 * 
	 * @param array $formation		玩家战斗数据
	 * @param array $arrCountry   	需要加成的国家数组，1,2,3,4分别代表魏蜀吴群
	 */
	public static function addCountryAddition($formation, $arrCountry)
	{
		$arrAddition = btstore_get()->GUILD_COPY_RULE['country_add']->toArray();
		
		$arrHero = $formation['arrHero'];
		foreach ($arrHero as $index => $hero)
		{
			if (in_array($hero[PropertyKey::COUNTRY], $arrCountry)) 
			{
				foreach ($arrAddition as $property => $addition)
				{
					if (!isset($hero[$property]))
					{
						$arrHero[$index][$property] = 0;
					}
					$arrHero[$index][$property] += $addition;
				}
			}
		}
		$formation['arrHero'] = $arrHero;
		
		return $formation;
	}
	
	/**
	 * 根据名次返回全服排行的奖励，这个奖励是一个三元组，要直接往奖励中心发送
	 * 
	 * @param int $rank
	 * @return array
	 */
	public static function getRewardByRank($rank)
	{
		if ($rank <= 0) 
		{
			throw new FakeException('invalid rank[%d]', $rank);
		}
		
		$arrRewardConfig = btstore_get()->GUILD_COPY_REWARD->toArray();
		foreach ($arrRewardConfig as $maxRank => $reward)
		{
			if ($rank <= $maxRank) 
			{
				return $reward;
			}
		}
		
		Logger::warning('no reward for rank[%d], use end reward[%s]', $rank, end($arrRewardConfig));
		return end($arrRewardConfig);
	}
	
	/**
	 * 根据当前要购买的次数，获得购买的花费
	 * 
	 * @param int $num
	 */
	public static function getBuyCostByNum($num)
	{
		if ($num <= 0)
		{
			throw new FakeException('invalid num[%d]', $num);
		}
		
		$arrBuyCost = btstore_get()->GUILD_COPY_RULE['buy_cost']->toArray();
		foreach ($arrBuyCost as $maxNum => $cost)
		{
			if ($num <= $maxNum) 
			{
				return $cost;
			}
		}
		
		throw new ConfigException('no cost for num[%d], cost config[%s]', $num, $arrBuyCost);
	}
	
	/**
	 * 根据配置获得BOSS初始血量
	 * */
	public static function getBossInitInfo($copyId)
	{
		$formation = self::getBossFormation($copyId);
		
		$ret['cd'] = 0;
		
		$ret['arrHero'] = array();
		foreach ($formation['arrHero'] as $key => $hero)
		{
			$BossHid = $formation['arrHero'][$key][PropertyKey::HID];
			$ret['arrHero'][$BossHid] = array(
				'hp' => $hero[PropertyKey::MAX_HP],
				'max_hp' => $hero[PropertyKey::MAX_HP],
			);
		}
		
		return $ret;
	}
	
	public static function getBossFormation($copyId)
	{	
		$armyId = self::getBossBaseId($copyId);
		return EnFormation::getMonsterBattleFormation( $armyId );
	}
	
	public static function getBossBaseId($copyId)
	{
		$conf = btstore_get()->GUILD_COPY_INFO[$copyId];
		
		return $conf['boss_id'];
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */