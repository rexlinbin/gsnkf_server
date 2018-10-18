<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HuntLogic.class.php 240398 2016-04-27 06:45:18Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hunt/HuntLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-04-27 06:45:18 +0000 (Wed, 27 Apr 2016) $
 * @version $Revision: 240398 $
 * @brief 
 *  
 **/
class HuntLogic
{
	public static function getHuntInfo($uid)
	{
		Logger::trace('HuntLogic::getHuntInfo Start.');
		
		$info = HuntDao::select($uid);
		if (empty($info)) 
		{
			$info = self::init($uid);
		}
		$place = $info[HuntDef::HUNT_PLACE];
		
		//为了清空用户积分和增加变更次数字段，需要修改va的结构
		if (!empty($info[HuntDef::HUNT_VAINFO]) 
		&& !isset($info[HuntDef::HUNT_VAINFO][HuntDef::ALL])) 
		{
			$info[HuntDef::HUNT_POINT] = 0;
			$info[HuntDef::HUNT_VAINFO] = array(
					HuntDef::ALL => $info[HuntDef::HUNT_VAINFO],
					HuntDef::CHANGE => 0,
			);
			Logger::info('user:%d va is updated to:%s', $uid, $info);
			HuntDao::update($uid, $info);
		}
		
		Logger::trace('HuntLogic::getHuntInfo End.');
		
		return $place;
	}
	
	public static function skip($uid, $type)
	{
		Logger::trace('HuntLogic::skip Start.');
		
		$user = EnUser::getUserObj($uid);
		$vip = $user->getVip();
		$conf = btstore_get()->VIP[$vip]['goldOpenExplore']->toArray();
		
		if (empty($conf)) 
		{
			throw new FakeException('user:%d can not skip', $uid);
		}
		list($place, $gold, $itemTplId1, $itemTplId2) = $conf;
		
		$bag = BagManager::getInstance()->getBag($uid);
		if (HuntDef::SKIP_TYPE_ITEM == $type) 
		{
			if ($bag->deleteItembyTemplateID($itemTplId1, 1) == false) 
			{
				throw new FakeException('no enough itemTplId:%d!', $itemTplId1);
			}
		}
		else 
		{
			if ($user->subGold($gold, StatisticsDef::ST_FUNCKEY_HUNT_SKIP) == false) 
			{
				throw new FakeException('no enough gold:%d!', $gold);
			}
		}
		
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId2);
		$itemId = $arrItemId[0];
		$items = array($itemId => $itemTplId2);
		if ($bag->addItem($itemId) == false)
		{
			throw new FakeException('bag is full, itemId:%d', $itemId);
		}
		
		//在特殊场景以上的不能跳转
		$info = HuntDao::select($uid);
		if ($info[HuntDef::HUNT_PLACE] >= $place)
		{
			throw new FakeException('user:%d can not use skip hunt', $uid);
		}
		
		$arrField = array(HuntDef::HUNT_PLACE => $place);
		$ret = HuntDao::update($uid, $arrField);
		if ($ret == false) 
		{
			throw new FakeException('user is in place:%d already', $place);
		}
		
		//活动期间额外掉落
		$extra = array();
		$extraDropArr = EnActExchange::getDropForLhzl();
		if(!empty($extraDropArr[0]))
		{
			$dropInfo = Drop::dropMixed($extraDropArr[0]);
			$extra = $dropInfo[DropDef::DROP_TYPE_ITEM];
		}
		if ($bag->addItemsByTemplateID($extra, true) == false)
		{
			throw new FakeException('bag is full, items:%s', $extra);
		}
		
		$user->update();
		$bag->update();
		
		Logger::trace('HuntLogic::skip End.');
		
		return array(
				'item' => $items,
				'extra' => $extra,
				'place' => $place,
		);
	}
	
	public static function skipHunt($uid, $num)
	{
		Logger::trace('HuntLogic::skipHunt Start. num:%d', $num);
		
		//检查银币
		$user = EnUser::getUserObj($uid);
		$silver = $user->getSilver();
		if ($silver < 50000 * $num) 
		{
			throw new FakeException('no enough silver:%d!', 50000 * $num);
		}
		$vip = $user->getVip();
		$conf = btstore_get()->VIP[$vip]['goldOpenExplore']->toArray();
		if (empty($conf))
		{
			throw new FakeException('user:%d can not skip hunt', $uid);
		}
		list($splace, $gold, $itemTplId1, $itemTplId2) = $conf;
		
		//判断用户神龙令的数量,不够的时候补金币
		$bag = BagManager::getInstance()->getBag($uid);
		$itemNum = $bag->getItemNumByTemplateID($itemTplId1);
		$cost = 0;
		$items = array();
		if ($itemNum >= $num) 
		{
			$items[$itemTplId1] = $num;
		}
		else
		{
			$items[$itemTplId1] = $itemNum;
			$cost = $gold * ($num - $itemNum);
		}
		//减物品
		if ($itemNum > 0 && $bag->deleteItemsByTemplateID($items) == false)
		{
			throw new FakeException('no enough items:%s!', $items);
		}
		//减金币
		if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_HUNT_SKIP_HUNT) == false)
		{
			throw new FakeException('no enough gold:%d!', $cost);
		}
		//必加物品部分
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId2, $num);
		$items = array();
		$itemIds = array();
		$itemTplIds = array();
		foreach ($arrItemId as $key => $itemId)
		{
			$itemIds[] = $itemId;
			$itemTplIds[] = $itemTplId2;
			$items[$key][$itemId] = $itemTplId2;
		}
		//准备猎魂需要的信息，在特殊场景以上的不能跳转
		$info = HuntDao::select($uid);
		if ($info[HuntDef::HUNT_PLACE] >= $splace) 
		{
			throw new FakeException('user:%d can not use skip hunt', $uid);
		}
		$point = $info[HuntDef::HUNT_POINT];
		$vaInfo = $info[HuntDef::HUNT_VAINFO];
		$conf = btstore_get()->HUNT->toArray();
		$first = key($conf);
		
		//开始循环
		$cost = 0;
		$place = $splace;
		$arrDropId = array();
		for ($i = 0; $i < $num; $i++)
		{
			//银币花费累加
			$cost += $conf[$place][HuntDef::HUNT_PLACE_COST];
			//探索次数累加
			if (!isset($vaInfo[HuntDef::ALL][$place]))
			{
				$vaInfo[HuntDef::ALL][$place] = 0;
			}
			$vaInfo[HuntDef::ALL][$place]++;
			//获得变更次数
			if (!isset($vaInfo[HuntDef::CHANGE]))
			{
				$vaInfo[HuntDef::CHANGE] = 0;
			}	
			//掉落战魂物品,根据累积积分和变更掉落次数判断是否需要使用累积变更掉落表
			$drop = self::getLevelDrop($conf[$place][HuntDef::HUNT_PLACE_DROP], $user->getLevel());
			$serial = $conf[$place][HuntDef::HUNT_SPECIAL_SERIAL];
			if (!empty($serial) && self::inSpecialSerial($point, $serial, $vaInfo[HuntDef::CHANGE]) == true)
			{
				$drop = self::getLevelDrop($conf[$place][HuntDef::HUNT_SPECIAL_DROP], $user->getLevel());
				$vaInfo[HuntDef::CHANGE]++;
			}
			$arrDropId[$i][] = $drop;	
			//掉落后进行积分累计
			$point += $conf[$place][HuntDef::HUNT_PLACE_POINT];	
			//判断是否可以开启下个场景,否则回到第N个场景
			$rate = $conf[$place][HuntDef::HUNT_NEXT_RATE] / 100;
			$rand = rand(1, 100);
			$place = $rand <= $rate ? $conf[$place][HuntDef::HUNT_NEXT_PLACE] : $first;
			$place = $place == $first ? $splace : $place;
			if ($place != $splace) 
			{
				$i--;
			}
		}
		//减银币
		if ($user->subSilver($cost) == false)
		{
			throw new FakeException('no enough silver:%d!', $cost);
		}
		//掉落战魂
		$material = array();
		foreach ($arrDropId as $key => $dropIds)
		{
			foreach ($dropIds as $dropId)
			{
				//掉落道具+战魂
				$dropInfo = Drop::dropMixed($dropId);
				if (empty($dropInfo[DropDef::DROP_TYPE_ITEM]))
				{
					throw new ConfigException('dropId:%d drop type is not item ', $dropId);
				}
				foreach ($dropInfo[DropDef::DROP_TYPE_ITEM] as $itemTplId => $itemNum)
				{
					$itemType = ItemManager::getInstance()->getItemType($itemTplId);
					if ($itemType == ItemDef::ITEM_TYPE_FIGHTSOUL) 
					{
						$itemTplIds[] = $itemTplId;
						$arrItemId = ItemManager::getInstance()->addItem($itemTplId, $itemNum);
						foreach ($arrItemId as $itemId)
						{
							$itemIds[] = $itemId;
							$items[$key][$itemId] = $itemTplId;
						}
					}
					else 
					{
						$material = Util::arrayAdd2V(array($material, array($itemTplId => $itemNum)));
					}
				}
			}
		}
		//加背包
		if ($bag->addItems($itemIds) == false)
		{
			throw new FakeException('bag is full, itemIds:%s', $itemIds);
		}
		if (!$bag->addItemsByTemplateID($material, true))
		{
			throw new FakeException('bag is full, items:%s', $material);
		}
		
		//活动期间额外掉落
		$extra = array();
		$extraDropArr = EnActExchange::getDropForLhzl();
		if(!empty($extraDropArr[0]))
		{
			for ($i = 0; $i < $num; $i++)
			{
				$dropInfo = Drop::dropMixed($extraDropArr[0]);
				$extra = Util::arrayAdd2V(array($extra, $dropInfo[DropDef::DROP_TYPE_ITEM]));
			}
		}
		if ($bag->addItemsByTemplateID($extra, true) == false) 
		{
			throw new FakeException('bag is full, items:%s', $extra);
		}
		
		$user->update();
		$bag->update();
		
		$arrField = array(
				HuntDef::HUNT_PLACE => $first,
				HuntDef::HUNT_POINT => $point,
				HuntDef::HUNT_VAINFO => $vaInfo,
		);
		HuntDao::update($uid, $arrField);
	
		//加入每日任务
		EnActive::addTask(ActiveDef::HUNT, $num);
		//加入成就系统
		$maxQuality = 0;
		foreach ($itemTplIds as $itemTplId)
		{
			EnAchieve::updateFightSoulTypes($uid, $itemTplId);
			$maxQuality = max($maxQuality, ItemManager::getInstance()->getItemQuality($itemTplId));
		}
		EnAchieve::updateFightSoul($uid, $maxQuality);
		//加入悬赏榜
		EnMission::doMission($uid, MissionType::HUNT, $num);
		
		Logger::trace('HuntLogic::skipHunt End.');
		
		return array(
				'item' => $items,
				'material' => $material,
				'extra' => $extra,
				'place' => $first,
				'silver' => $cost,
		);
	}
	
	public static function huntSoul($uid, $num)
	{
		Logger::trace('HuntLogic::huntSoul Start.');
		
		$user = EnUser::getUserObj($uid);
		$vip = $user->getVip();
		$need = btstore_get()->VIP[$vip]['huntTenNeedLevel'];
		if ($num == 10 && $user->getLevel() < $need)
		{
			throw new FakeException('user level is not enough to use ten hunt:%d!', $need);
		}
		$need = btstore_get()->VIP[$vip]['huntFiftyNeedLevel'];
		if ($num == 50 && $user->getLevel() < $need)
		{
			throw new FakeException('user level is not enough to use fifty hunt:%d!', $need);
		}
		
		$info = HuntDao::select($uid);
		$place = $info[HuntDef::HUNT_PLACE];
		$point = $info[HuntDef::HUNT_POINT];
		$vaInfo = $info[HuntDef::HUNT_VAINFO];
		$conf = btstore_get()->HUNT->toArray();
		$first = key($conf);
		
		//循环
		$silver = 0;
		$arrDropId = array();
		for ($i = 0; $i < $num; $i++)
		{
			//银币花费累加
			$silver += $conf[$place][HuntDef::HUNT_PLACE_COST];
			//探索次数累加
			if (!isset($vaInfo[HuntDef::ALL][$place]))
			{
				$vaInfo[HuntDef::ALL][$place] = 0;
			}
			$vaInfo[HuntDef::ALL][$place]++;
			//获得变更次数
			if (!isset($vaInfo[HuntDef::CHANGE]))
			{
				$vaInfo[HuntDef::CHANGE] = 0;
			}
			
			//掉落战魂物品,根据累积积分和变更掉落次数判断是否需要使用累积变更掉落表
			$drop = self::getLevelDrop($conf[$place][HuntDef::HUNT_PLACE_DROP], $user->getLevel());
			$serial = $conf[$place][HuntDef::HUNT_SPECIAL_SERIAL];
			if (!empty($serial) && self::inSpecialSerial($point, $serial, $vaInfo[HuntDef::CHANGE]) == true)
			{
				$drop = self::getLevelDrop($conf[$place][HuntDef::HUNT_SPECIAL_DROP], $user->getLevel());
				$vaInfo[HuntDef::CHANGE]++;
			}
			$arrDropId[] = $drop;
			
			//掉落后进行积分累计
			$point += $conf[$place][HuntDef::HUNT_PLACE_POINT];
			
			//判断是否可以开启下个场景,否则回到第一个场景
			$rate = $conf[$place][HuntDef::HUNT_NEXT_RATE] / 100;
			$rand = rand(1, 100);
			$place = $rand <= $rate ? $conf[$place][HuntDef::HUNT_NEXT_PLACE] : $first;
		}
		
		if ($user->subSilver($silver) == false)
		{
			throw new FakeException('no enough silver:%d!', $silver);
		}
		
		$items = array();
		$itemIds = array();
		$material = array();
		$sumValue = 0;
		$white = 0;
		$green = 0;
		$blue = 0;
		$purple = 0;
		foreach ($arrDropId as $dropId)
		{
			$dropInfo = Drop::dropMixed($dropId);
			if (empty($dropInfo[DropDef::DROP_TYPE_ITEM]))
			{
				throw new ConfigException('dropId:%d drop type is not item', $dropId);
			}
			foreach ($dropInfo[DropDef::DROP_TYPE_ITEM] as $itemTplId => $itemNum)
			{
				$itemType = ItemManager::getInstance()->getItemType($itemTplId);
				$itemQuality = ItemManager::getInstance()->getItemQuality($itemTplId);
				if ($itemType == ItemDef::ITEM_TYPE_FIGHTSOUL)
				{
					switch ($itemQuality)
					{
						case 1:
						case ItemDef::ITEM_QUALITY_WHITE:$white+=$itemNum;break;
						case ItemDef::ITEM_QUALITY_GREEN:$green+=$itemNum;break;
						case ItemDef::ITEM_QUALITY_BLUE:$blue+=$itemNum;break;
						case ItemDef::ITEM_QUALITY_PURPLE:$purple+=$itemNum;break;
						default:throw new ConfigException('invalid item quality:%d', $itemQuality);
					}
					if ($num == HuntConf::SPECIAL_NUM && $itemQuality < ItemDef::ITEM_QUALITY_BLUE)
					{
						$sumValue += btstore_get()->ITEMS[$itemTplId][FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_VALUE];
					}
					else
					{
						$arrItemId = ItemManager::getInstance()->addItem($itemTplId, $itemNum);
						foreach ($arrItemId as $itemId)
						{
							$itemIds[] = $itemId;
							$items[$itemId] = $itemTplId;
						}
					}
				}
				else 
				{
					$material = Util::arrayAdd2V(array($material, array($itemTplId => $itemNum)));
				}
			}
		}
		
		//所有经验加到小经验魂物品上
		$exp = 0;
		if (!empty($sumValue)) 
		{
			$itemTplId = HuntConf::SPECIAL_ITEM;
			$itemValue = btstore_get()->ITEMS[$itemTplId][FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_VALUE];
			$arrItemId = ItemManager::getInstance()->addItem($itemTplId);
			$itemId = $arrItemId[0];
			$items[$itemId] = $itemTplId;
			$itemIds[] = $itemId;
			//加经验升级
			ItemManager::getInstance()->getItems($itemIds);
			$item = ItemManager::getInstance()->getItem($itemId);
			$exp = $item->getExp();
			$level = $item->getLevel();
			$userLevel = $user->getLevel();
			$limit = $item->getLimit($userLevel);
			$upgradeValue = $item->getUpgradeValue($level + 1);
			$exp += $sumValue - $itemValue;
			while ($exp >= $upgradeValue)
			{
				$level++;
				if ($level == $limit)
				{
					break;
				}
				$upgradeValue = $item->getUpgradeValue($level + 1);
			}
			$item->setLevel($level);
			$item->setExp($exp);
		}
		
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->addItems($itemIds) == false)
		{
			throw new FakeException('bag is full, itemIds:%d', $itemIds);
		}
		if (!$bag->addItemsByTemplateID($material, true))
		{
			throw new FakeException('bag is full, items:%s', $material);
		}
	
		$user->update();
		$bag->update();
		
		$arrField = array(
				HuntDef::HUNT_PLACE => $place,
				HuntDef::HUNT_POINT => $point,
				HuntDef::HUNT_VAINFO => $vaInfo
		);
		HuntDao::update($uid, $arrField);
		
		//加入每日任务
		EnActive::addTask(ActiveDef::HUNT, $num);
		//加入成就系统
		$maxQuality = 0;
		foreach ($items as $itemTplId)
		{
			EnAchieve::updateFightSoulTypes($uid, $itemTplId);
			$maxQuality = max($maxQuality, ItemManager::getInstance()->getItemQuality($itemTplId));
		}
		EnAchieve::updateFightSoul($uid, $maxQuality);
		//加入悬赏榜
		EnMission::doMission($uid, MissionType::HUNT, $num);
		
		Logger::trace('HuntLogic::huntSoul End.');
		
		return array(
				'item' => $items,
				'material' => $material,
				'place' => $place,
				'silver' => $silver,
				'white' => $white,
				'green' => $green,
				'blue' => $blue,
				'purple' => $purple,
				'exp' => $exp,
		);
	}
	
	public static function rapidHunt($uid, $type, $arrQuality)
	{
		Logger::trace('HuntLogic::rapidHunt Start. type:%d arrQuality:%s', $type, $arrQuality);
	
		$user = EnUser::getUserObj($uid);
		$needLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RAPID_HUNT_SOUL][1];
		if ($user->getLevel() < $needLevel) 
		{
			throw new FakeException('user can not use rapid hunt, user level:%d, need level:%d!', $user->getLevel(), $needLevel);
		}
		if (!isset(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RAPID_HUNT_SOUL_TYPE][$type])) 
		{
			throw new FakeException('rapid hunt type:%d is not exist!', $type);
		}
		$needSilver = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RAPID_HUNT_SOUL_TYPE][$type];
		if ($user->getSilver() < $needSilver) 
		{
			throw new FakeException('user can not use rapid hunt, user silver:%d, need silver:%d!', $user->getSilver(), $needSilver);
		}
		$needLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RAPID_HUNT_QUALITY];
		if (!in_array(ItemDef::ITEM_QUALITY_PURPLE, $arrQuality) && $user->getLevel() < $needLevel) 
		{
			throw new FakeException('user can not use rapid hunt without purple, user level:%d, need level:%d!', $user->getLevel(), $needLevel);
		}

		$info = HuntDao::select($uid);
		$place = $info[HuntDef::HUNT_PLACE];
		$point = $info[HuntDef::HUNT_POINT];
		$vaInfo = $info[HuntDef::HUNT_VAINFO];
		$conf = btstore_get()->HUNT->toArray();
		$first = key($conf);
	
		//循环
		$num = 0;
		$silver = 0;
		$arrDropId = array();
		while ($silver + $conf[$place][HuntDef::HUNT_PLACE_COST] <= $needSilver)
		{
			$num++;
			//银币花费累加
			$silver += $conf[$place][HuntDef::HUNT_PLACE_COST];
			//探索次数累加
			if (!isset($vaInfo[HuntDef::ALL][$place]))
			{
				$vaInfo[HuntDef::ALL][$place] = 0;
			}
			$vaInfo[HuntDef::ALL][$place]++;
			//获得变更次数
			if (!isset($vaInfo[HuntDef::CHANGE]))
			{
				$vaInfo[HuntDef::CHANGE] = 0;
			}
			//掉落战魂物品,根据累积积分和变更掉落次数判断是否需要使用累积变更掉落表
			$drop = self::getLevelDrop($conf[$place][HuntDef::HUNT_PLACE_DROP], $user->getLevel());
			$serial = $conf[$place][HuntDef::HUNT_SPECIAL_SERIAL];
			if (!empty($serial) && self::inSpecialSerial($point, $serial, $vaInfo[HuntDef::CHANGE]) == true)
			{
				$drop = self::getLevelDrop($conf[$place][HuntDef::HUNT_SPECIAL_DROP], $user->getLevel());
				$vaInfo[HuntDef::CHANGE]++;
			}
			$arrDropId[] = $drop;
			
			//掉落后进行积分累计
			$point += $conf[$place][HuntDef::HUNT_PLACE_POINT];
				
			//判断是否可以开启下个场景,否则回到第一个场景
			$rate = $conf[$place][HuntDef::HUNT_NEXT_RATE] / 100;
			$rand = rand(1, 100);
			$place = $rand <= $rate ? $conf[$place][HuntDef::HUNT_NEXT_PLACE] : $first;
		}
		
		if ($user->subSilver($silver) == false)
		{
			throw new FakeException('no enough silver:%d!', $silver);
		}
	
		$sumValue = 0;
		$items = array();
		$itemIds = array();
		$material = array();
		foreach ($arrDropId as $dropId)
		{
			$dropInfo = Drop::dropMixed($dropId);
			if (empty($dropInfo[DropDef::DROP_TYPE_ITEM]))
			{
				throw new ConfigException('dropId:%d drop type is not item', $dropId);
			}
			foreach ($dropInfo[DropDef::DROP_TYPE_ITEM] as $itemTplId => $itemNum)
			{
				$itemType = ItemManager::getInstance()->getItemType($itemTplId);
				$itemQuality = ItemManager::getInstance()->getItemQuality($itemTplId);
				if ($itemType == ItemDef::ITEM_TYPE_FIGHTSOUL)
				{
					if (!in_array($itemQuality, $arrQuality))
					{
						$sumValue += btstore_get()->ITEMS[$itemTplId][FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_VALUE];
					}
					else
					{
						$arrItemId = ItemManager::getInstance()->addItem($itemTplId, $itemNum);
						foreach ($arrItemId as $itemId)
						{
							$itemIds[] = $itemId;
							$items[$itemId] = $itemTplId;
						}
					}
				}
				else
				{
					$material = Util::arrayAdd2V(array($material, array($itemTplId => $itemNum)));
				}
			}
		}
	
		//加物品和经验
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->addItems($itemIds) == false)
		{
			throw new FakeException('bag is full, itemIds:%d', $itemIds);
		}
		if (!$bag->addItemsByTemplateID($material, true))
		{
			throw new FakeException('bag is full, items:%s', $material);
		}
		$user->addFsExp($sumValue);

		$user->update();
		$bag->update();

		$arrField = array(
				HuntDef::HUNT_PLACE => $place,
				HuntDef::HUNT_POINT => $point,
				HuntDef::HUNT_VAINFO => $vaInfo
		);
		HuntDao::update($uid, $arrField);
	
		//加入每日任务
		EnActive::addTask(ActiveDef::HUNT, $num);
		//加入成就系统
		$maxQuality = 0;
		foreach ($items as $itemTplId)
		{
			EnAchieve::updateFightSoulTypes($uid, $itemTplId);
			$maxQuality = max($maxQuality, ItemManager::getInstance()->getItemQuality($itemTplId));
		}
		EnAchieve::updateFightSoul($uid, $maxQuality);
		//加入悬赏榜
		EnMission::doMission($uid, MissionType::HUNT, $num);
	
		Logger::trace('HuntLogic::huntSoul End.');
	
		return array(
				'item' => $items,
				'material' => $material,
				'place' => $place,
				'silver' => $silver,
				'fs_exp' => $sumValue,
		);
	}
	
	private static function init($uid)
	{
		Logger::trace('HuntLogic::init Start.');
		
		$conf = btstore_get()->HUNT->toArray();
		$place = key($conf);
		
		$arrField = array(
				HuntDef::HUNT_UID => $uid,
				HuntDef::HUNT_PLACE => $place,
				HuntDef::HUNT_POINT => 0,
				HuntDef::HUNT_VAINFO => array()
		);
		
		HuntDao::insert($arrField);
		
		Logger::trace('HuntLogic::init End.');
		
		return $arrField;
	}
	
	/**
	 * 判断一个数是否在一个特殊序列里
	 *
	 * @param int $num
	 * @param array $serial
	 * @param int $change
	 * @return boolean
	 */
	private static function inSpecialSerial($num, $serial, $change)
	{
		Logger::trace('HuntLogic::inSpecialSerial Start.');
	
		$ret = false;
		
		if (empty($num) || empty($serial) || $change < 0)
		{
			return $ret;
		}
		
		if (isset($serial[$change])) 
		{
			$ret = $num >= $serial[$change] ? true : false;
		}
		else 
		{
			$count = count($serial);
			$sum = $serial[$count - 1];
			$final = $count < 2 ? $sum : $sum - $serial[$count - 2];
			$need = $sum + 	$final * ($change - $count + 1);
			$ret = $num >= $need ? true : false;
		}
	
		Logger::trace('HuntLogic::inSpecialSerial End.');
		
		return $ret;
	}
	
	private static function getLevelDrop($arrDrop, $level)
	{
		$drop = 0;
		foreach ($arrDrop as $key => $value)
		{
			if ($level < $key) 
			{
				break;
			}
			$drop = $value;
		}
		return $drop;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */