<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DelPocket.php 244468 2016-05-27 08:01:49Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/DelPocket.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2016-05-27 08:01:49 +0000 (Fri, 27 May 2016) $
 * @version $Revision: 244468 $
 * @brief 
 *  
 **/

class DelPocket extends BaseScript
{
	protected function executeScript($arrOption)
	{	
		$point = 10000;
		$itemTplId = 840036;
		
		$fix = false;
		if ($arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		$uid = intval($arrOption[1]);
	
		if ($fix) 
		{
			Util::kickOffUser($uid);
		}
		
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		
		//先检查积分
		$data = MallDao::select($uid, MallDef::MALL_TYPE_SCORESHOP);
		if ($data[ScoreShopDef::SQL_USED_POINT] < $point) 
		{
			echo "uid:$uid cost point is not enough\n";
			return ;
		}
		
		//再检查物品在背包里面吗？
		$inHero = false;
		$arrEquipPos = array();
		$user = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		$itemIds = $bag->getItemIdsByTemplateID($itemTplId);
		if (empty($itemIds)) 
		{
			//再检查物品在武将身上吗？
			$allHeroObj = $user->getHeroManager()->getAllHeroObjInSquad();
			foreach ($allHeroObj as $heroObj)
			{
				foreach ($heroObj->getEquipObjByType(HeroDef::EQUIP_POCKET) as $pos => $equipObj)
				{
					if (!empty($equipObj) && $equipObj->getItemTemplateID() == $itemTplId) 
					{
						$itemIds[] = $equipObj->getItemID();
						$arrEquipPos[$equipObj->getItemID()] = array($heroObj->getHid(), $pos);
					}
				}
			}
			if (empty($itemIds))
			{
				echo "uid:$uid bag and hero don't have item:$itemTplId\n";
				return ;
			}
			$inHero = true;
		}
		
		//批量获取物品
		$items = ItemManager::getInstance()->getItems($itemIds);
		$itemInfos = array();
		foreach ($items as $item)
		{
			$itemInfos[] = $item->itemInfo();
		}
		
		//取等级最小的物品
		$cmpFunc = function($a, $b)
		{
			if ($a[ItemDef::ITEM_SQL_ITEM_TEXT][PocketDef::ITEM_ATTR_NAME_POCKET_LEVEL] >= $b[ItemDef::ITEM_SQL_ITEM_TEXT][PocketDef::ITEM_ATTR_NAME_POCKET_LEVEL])
			{
				return 1;
			}
			return -1;
		};
		usort($itemInfos, $cmpFunc);
		$itemId = $itemInfos[0][ItemDef::ITEM_SQL_ITEM_ID];
		
		//在武将身上就先卸下
		if ($inHero) 
		{
			list($hid, $pos) = $arrEquipPos[$itemId];
			$hero = $user->getHeroManager()->getHeroObj($hid);
			$setFunc = HeroUtil::getSetEquipFunc(HeroDef::EQUIP_POCKET);
			call_user_func_array(array($hero, $setFunc), array(HeroDef::EQUIP_POCKET, BagDef::ITEM_ID_NO_ITEM, $pos));
			//3.将移除的物品加入到背包中
			if (!$bag->addItem($itemId, true))
			{
				throw new InterException('add the itemId:%d to bag failed', $itemId);
			}
			if ($fix) 
			{
				HeroLogic::refershFmtOnHeroChange($hid);
			}
		}
		
		//重生锦囊
		$item = $items[$itemId];
		if ($item->getLevel() > PocketDef::ITEM_ATTR_NAME_POCKET_INIT_LEVEL) 
		{
			$items = array();
			$arrRebornItemId = array();
			$exp = $item->getExp();
			$valueCost = $item->getValueCost();
			$silver = $exp * $valueCost;
			$rebornItemValue = ItemAttr::getItemAttr(PocketDef::REBORN_ITEM, PocketDef::ITEM_ATTR_NAME_POCKET_VALUE);
			if ($exp > $rebornItemValue)
			{
				//加经验值，最多10个，每个给配置值，多出的经验值放最后一个
				for ($i = 1; $exp > 0; $i++)
				{
					if (!isset($items[PocketDef::REBORN_ITEM]))
					{
						$items[PocketDef::REBORN_ITEM] = 0;
					}
					$items[PocketDef::REBORN_ITEM]++;
					$rebornItemId = ItemManager::getInstance()->addItem(PocketDef::REBORN_ITEM);
					$rebornItemId = $rebornItemId[0];
					$arrRebornItemId[] = $rebornItemId;
					$rebornItem = ItemManager::getInstance()->getItem($rebornItemId);
					$addExp = min($exp, ItemDef::UPPER_LIMIT_EXP_FOR_POCKET);
					$exp -= $addExp;
					$rebornItem->addExp($addExp - $rebornItemValue);
					//1.剩余经验值小于经验物品的基础经验值，就把经验加在上一个物品里
					//2.物品数量超过上限，就把经验加在上一个物品里
					if ($exp < $rebornItemValue || $i >= ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM)
					{
						$rebornItem->addExp($exp);
						$exp = 0;
						break;
					}
				}
			}
			
			if (!empty($silver))
			{
				$user->addSilver($silver);
			}
			if (!empty($arrRebornItemId))
			{
				if (!$bag->addItems($arrRebornItemId, true))
				{
					throw new FakeException('full bag. add item ids:%s failed', $arrRebornItemId);
				}
			}
			Logger::info('reborn pocket. uid:%d, itemId:%d, add silver:%d, add items:%s', $uid, $itemId, $silver, $arrRebornItemId);
		}
		
		$bag->deleteItem($itemId);
		
		echo "del pocket. uid:$uid, itemId:$itemId, add point:$point, inHero:$inHero\n";
		
		if ($fix) 
		{
			//扣积分
			$data[ScoreShopDef::SQL_USED_POINT] -= $point;
			$arrField = array(
					MallDef::USER_ID => $uid,
					MallDef::MALL_TYPE => MallDef::MALL_TYPE_SCORESHOP,
					MallDef::VA_MALL => $data,
			);
			MallDao::insertOrUpdate($arrField);
			$user->update();
			$bag->update();
			
			Logger::info('del pocket sucess. uid:%d, itemId:%d, add point:%d, inHero:%d', $uid, $itemId, $point, $inHero);
		}
			
		printf("done\n");
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */