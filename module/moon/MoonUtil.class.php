<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonUtil.class.php 223550 2016-01-19 08:03:00Z NanaPeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/MoonUtil.class.php $
 * @author $Author: NanaPeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-01-19 08:03:00 +0000 (Tue, 19 Jan 2016) $
 * @version $Revision: 223550 $
 * @brief 
 *  
 **/
 
class MoonUtil
{
	/**
	 * 根据购买次数返回购买需要花费的金币
	 * 
	 * @param int $num
	 */
	public static function getBuyCost($num)
	{
		if (!isset(btstore_get()->MOON_RULE['buy_cost'][$num])) 
		{
			throw new InterException('no cost config of buy num[%d]', $num);
		}
		
		return intval(btstore_get()->MOON_RULE['buy_cost'][$num]);
	}
	/**
	 * 根据购买次数返回购买梦魇模式次数需要花费的金币
	 *
	 * @param int $num
	 */
	public static function getNightmareBuyCost($num)
	{
		if (!isset(btstore_get()->MOON_RULE['nightmare_price'][$num]))
		{
			throw new InterException('no cost config of buy num[%d]', $num);
		}
	
		return intval(btstore_get()->MOON_RULE['nightmare_price'][$num]);
	}
	
	/**
	 * 根据配置获取某副本梦魇属性
	 *
	 * @param int $num
	 */
	public static function getNightmareAttr($copyId)
	{
		return btstore_get()->MOON_COPY[$copyId]['strengthen'];
	}
	
	/**
	 * 根据购买宝箱次数返回购买需要花费的金币
	 *
	 * @param int $num
	 */
	public static function getBuyBoxCost($num)
	{
		if (!isset(btstore_get()->MOON_RULE['box_cost'][$num]))
		{
			throw new InterException('no cost config of buy num[%d]', $num);
		}
		
		return intval(btstore_get()->MOON_RULE['box_cost'][$num]);
	}
	
	/**
	 * 获得天工阁里宝箱掉落的物品
	 * 
	 * @throws ConfigException
	 * @return array
	 */
	public static function getBoxDropReward()
	{
		$ret = array();
		
		$arrDropInfo = Drop::dropMixed(intval(btstore_get()->MOON_RULE['box_drop']));
		if (!empty($arrDropInfo))
		{
			foreach ($arrDropInfo as $type => $info)
			{
				switch ($type)
				{
					case DropDef::DROP_TYPE_SILVER:
						$ret[] = array(RewardConfType::SILVER, 0, $info[0]);
						break;
					case DropDef::DROP_TYPE_SOUL:
						$ret[] = array(RewardConfType::SOUL, 0, $info[0]);
						break;
					case DropDef::DROP_TYPE_ITEM:
						foreach ($info as $itemId => $itemNum)
						{
							$ret[] = array(RewardConfType::ITEM_MULTI, $itemId, $itemNum);
						}
						break;
					case DropDef::DROP_TYPE_HERO:
						foreach ($info as $htid => $hNum)
						{
							$ret[] = array(RewardConfType::HERO_MULTI, $htid, $hNum);
						}
						break;
					case DropDef::DROP_TYPE_TREASFRAG:
						foreach ($info as $ttid => $tNum)
						{
							$ret[] = array(RewardConfType::TREASURE_FRAG_MULTI, $ttid, $tNum);
						}
						break;
					default :
						throw new ConfigException('invalid type: %d', $type);
				}
			}
		}
		
		return $ret;
	}
	
	/**
	 * 获得每天购买攻击次数的上限
	 * 
	 * @return int
	 */
	public static function getBuyLimit()
	{
		$buyLimit = 0;
		
		$buyConf = btstore_get()->MOON_RULE['buy_cost']->toArray();
		foreach ($buyConf as $buyNum => $buyCost)
		{
			if ($buyNum > $buyLimit) 
			{
				$buyLimit = $buyNum;
			}
		}
		
		return $buyLimit;
	}
	
	/**
	 * 获得每天购买梦魇攻击次数的上限
	 *
	 * @return int
	 */
	public static function getBuyNightmareLimit()
	{
		$buyLimit = 0;
	
		$buyConf = btstore_get()->MOON_RULE['nightmare_price']->toArray();
		foreach ($buyConf as $buyNum => $buyCost)
		{
			if ($buyNum > $buyLimit)
			{
				$buyLimit = $buyNum;
			}
		}
	
		return $buyLimit;
	}
	
	/**
	 * 获得每天购买宝箱次数的上限
	 *
	 * @return int
	 */
	public static function getBuyBoxLimit()
	{
		$buyLimit = 0;
		
		$buyConf = btstore_get()->MOON_RULE['box_cost']->toArray();
		foreach ($buyConf as $buyNum => $buyCost)
		{
			if ($buyNum > $buyLimit)
			{
				$buyLimit = $buyNum;
			}
		}
		
		return $buyLimit;
	}
	
	/**
	 * 获得某个副本某个格子内的怪的战斗数据
	 * 
	 * @param int $copyId
	 * @param int $gridId
	 * @throws InterException
	 * @return array
	 */
	public static function getMonsterBattleFormation($copyId, $gridId)
	{
		if (!isset(btstore_get()->MOON_COPY[$copyId])) 
		{
			throw new InterException('no config of copy id[%d]', $copyId);
		}
		
		if ($gridId <= 0 || $gridId > MoonConf::MAX_GRID_NUM) 
		{
			throw new InterException('invalid grid id[%d], max grid num[%d]', $gridId, MoonConf::MAX_GRID_NUM);
		}
		
		if (MoonGridType::MONSTER != btstore_get()->MOON_COPY[$copyId]['grid'][$gridId]['type']) 
		{
			throw new InterException('copy id[%d] grid id[%d] not monster', $copyId, $gridId);
		}		
		
		$baseId = intval(btstore_get()->MOON_COPY[$copyId]['grid'][$gridId]['baseId']);
		$lvName = CopyConf::$BASE_LEVEL_INDEX[BaseLevel::SIMPLE];
		$armyIds = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays']->toArray();
		if(count($armyIds) < 1)
		{
			throw new ConfigException('no army');
		}
		$armyId = $armyIds[0];
		
		$formation = EnFormation::getMonsterBattleFormation($armyId);
		$battleType = btstore_get()->ARMY[$armyId]['fight_type'];
		$endCondition = array();
		if(isset(btstore_get()->ARMY[$armyId]['end_condition']))
		{
			$endCondition = btstore_get()->ARMY[$armyId]['end_condition']->toArray();
		}
		
		return array($formation, $battleType, $endCondition);
	}
	
	/**
	 * 获得某个副本BOSS的战斗数据
	 *
	 * @param int $copyId
	 * @throws InterException
	 * @return array
	 */
	public static function getBossBattleFormation($copyId, $nightmare = MoonTypeDef::BOSS_NORMAL_TYPE)
	{
		if (!isset(btstore_get()->MOON_COPY[$copyId]))
		{
			throw new InterException('no config of copy id[%d]', $copyId);
		}
				
		$baseId = intval(btstore_get()->MOON_COPY[$copyId]['boss']);
		$lvName = CopyConf::$BASE_LEVEL_INDEX[BaseLevel::SIMPLE];
		$armyIds = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays']->toArray();
		if(count($armyIds) < 1)
		{
			throw new ConfigException('no army');
		}
		$armyId = $armyIds[0];
		
		$formation = EnFormation::getMonsterBattleFormation($armyId);
		$battleType = btstore_get()->ARMY[$armyId]['fight_type'];
		$endCondition = array();
		if(isset(btstore_get()->ARMY[$armyId]['end_condition']))
		{
			$endCondition = btstore_get()->ARMY[$armyId]['end_condition']->toArray();
		}
		//梦魇模式属性加成
		if($nightmare == MoonTypeDef::BOSS_NIGHTMARE_TYPE)
		{
			$arrAttr = HeroUtil::adaptAttr(self::getNightmareAttr($copyId));
			foreach ($formation['arrHero'] as $pos => $val)
			{
				foreach ($arrAttr as $propertyKey => $propertyVal)
				{
					if (!isset($formation['arrHero'][$pos][$propertyKey]))
					{
						$formation['arrHero'][$pos][$propertyKey] = 0;
						Logger::fatal('propertyKey:%s is not exist in battle info nightmare.', $propertyKey);
					}
					
					Logger::debug('nightmare boss propertyKey：%s old num:%s,add num:%s.',$propertyKey , 
					$formation['arrHero'][$pos][$propertyKey], $propertyVal);
					
					//2016/1/19百分比加成变为数值直接相加
					$formation['arrHero'][$pos][$propertyKey] = $formation['arrHero'][$pos][$propertyKey] + $propertyVal;
					//重新计算血量！！涉及血量的都要重新计算
					$formation['arrHero'][$pos][PropertyKey::MAX_HP] = self::getMaxHp($formation['arrHero'][$pos]);
					Logger::debug('nightmare boss propertyKey:maxHp new num:%s.', $formation['arrHero'][$pos][PropertyKey::MAX_HP]);
				}
			}
		}
			
		return array($formation, $battleType, $endCondition);
	}
	
	public static function getMaxHp($heroInfo)
	{
		$hpBase = $heroInfo[PropertyKey::HP_BASE];
		$hpRatio = $heroInfo[PropertyKey::HP_RATIO];
		$hpFinal = $heroInfo[PropertyKey::HP_FINAL];
		if(empty($hpBase))
		{
			$hpBase = 0;
		}
		if(empty($hpRatio))
		{
			$hpBase = 0;
		}
		if(empty($hpFinal))
		{
			$hpBase = 0;
		}
		$hp = intval(($hpBase*(1+$hpRatio/UNIT_BASE)+$hpFinal)*(1+($heroInfo[PropertyKey::REIGN]-5000)/UNIT_BASE));
		return $hp;
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */