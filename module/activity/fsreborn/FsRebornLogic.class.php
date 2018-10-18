<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FsRebornLogic.class.php 210086 2015-11-17 05:51:32Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/fsreborn/FsRebornLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-11-17 05:51:32 +0000 (Tue, 17 Nov 2015) $
 * @version $Revision: 210086 $
 * @brief 
 *  
 **/
class FsRebornLogic
{
	public static function getInfo($uid)
	{
		Logger::trace('FsRebornLogic::getInfo Start.');

		$fs = FsRebornObj::getInstance($uid);
		$info = array(
				FsRebornDef::FIELD_NUM => $fs->getNum(),
		);
		
		Logger::trace('FsRebornLogic::getInfo End.');
		return $info;
	}
	
	public static function reborn($uid, $itemId)
	{
		Logger::trace('FsRebornLogic::reborn Start.');
		
		$item = ItemManager::getInstance()->getItem($itemId);
		//战魂
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_FIGHTSOUL)
		{
			throw new FakeException('itemId:%d is not a fightsoul!', $itemId);
		}
		//紫色
		if ($item->getItemQuality() != ItemDef::ITEM_QUALITY_PURPLE)
		{
			throw new FakeException('itemId:%d quality is not purple', $itemId);
		}
		//等级大于1
		if ($item->getLevel() <= 1)
		{
			throw new FakeException('itemId:%d level is less than 1', $itemId);
		}
		//有精炼等级
		if ($item->getEvolve() > FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_INIT_EVOLVE) 
		{
			throw new FakeException('itemId:%d evolve is larger than 0', $itemId);
		}
		$bag = BagManager::getInstance()->getBag($uid);
		//在背包里
		if (!$bag->isItemExist($itemId)) 
		{
			throw new FakeException('itemId:%d is not in bag', $itemId);
		}
		
		$fs = FsRebornObj::getInstance($uid);
		$user = EnUser::getUserObj($uid);
		$numConf = self::getNumConf();
		$num = $fs->getNum();
		$limit = $numConf[$user->getVip()];
		//达到重生上限
		if ($num >= $limit) 
		{
			throw new FakeException('user:%d reborn num reach limit:%d', $uid, $limit);
		}
		$fs->addNum(1);
		
		//开始重生
		$exp = 0;
		$silver = 0;
		$rate = self::getRateConf();
		$level = $item->getLevel();
		$limit = $item->getLimit($user->getLevel());
		if ($level >= $limit) 
		{
			$silver = $item->getUpgradeValue($limit) * $rate;
			$exp = $item->getExp() - $item->getUpgradeValue($limit);
		}
		else 
		{
			$silver = $item->getExp() * $rate;
		}
		$item->reset();
		
		if (!empty($silver)) 
		{
			$user->addSilver($silver);
		}
		$items = array();
		if (!empty($exp)) 
		{
			$sItemTplId = HuntConf::SPECIAL_ITEM;
			$arrItemId = ItemManager::getInstance()->addItem($sItemTplId);
			$sItemId = $arrItemId[0];
			if (!$bag->addItem($sItemId)) 
			{
				throw new FakeException('bag is full');
			}
			$sItem = ItemManager::getInstance()->getItem($sItemId);
			$sLevel = $sItem->getLevel();
			$sExp = $exp - $sItem->getValue() + $sItem->getExp();
			$sUpgradeValue = $sItem->getUpgradeValue($sLevel + 1);
			while ($sExp >= $sUpgradeValue)
			{
				$sLevel++;
				if ($sLevel == $sItem->getLimit($user->getLevel()))
				{
					break;
				}
				$sUpgradeValue = $sItem->getUpgradeValue($sLevel + 1);
			}
			$sItem->setLevel($sLevel);
			$sItem->setExp($sExp);
			$items[$sItemId] = $sItemTplId;
		}
		
		$fs->update();
		$bag->update();
		$user->update();
	
		Logger::trace('FsRebornLogic::reborn End.');
		return array(
				'exp' => $exp,
				'silver' => $silver,
				'item' => $items,
		);
	}

	public static function getActTime()
	{
		$actConf = EnActivity::getConfByName(ActivityName::FSREBORN);
		return array($actConf['start_time'], $actConf['end_time']);
	}
	
	public static function isInCurRound($time)
	{
		list($startTime, $endTime) = self::getActTime();
		if ($time >= $startTime && $time <= $endTime)
		{
			return true;
		}
	
		return false;
	}
	
	public static function getConf()
	{
		$conf = EnActivity::getConfByName(ActivityName::FSREBORN);
		return $conf['data'];
	}
	
	public static function getNumConf()
	{
		$conf = self::getConf();
		return $conf[FsRebornDef::NUMS];
	}
	
	public static function getRateConf()
	{
		$conf = self::getConf();
		return $conf[FsRebornDef::RATE];
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */