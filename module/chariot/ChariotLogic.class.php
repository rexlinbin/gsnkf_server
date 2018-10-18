<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChariotLogic.class.php 252152 2016-07-18 06:00:20Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chariot/ChariotLogic.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-07-18 06:00:20 +0000 (Mon, 18 Jul 2016) $
 * @version $Revision: 252152 $
 * @brief 
 *  
 **/
class ChariotLogic
{
	/**
	 * 装备战车，如果已有同种类型的则替换掉再装备
	 * @param unknown $itemId
	 */
	public static function equip($pos,$itemId,$uid)
	{
		$pos=intval($pos);
		$itemId=intval($itemId);
		$item = ItemManager::getInstance()->getItem($itemId);
		//物品和类别参数是否合法
		if( empty($item) )
		{
			throw new FakeException('itemId:%d not exist', $itemId);
		}
		$bag=BagManager::getInstance()->getBag($uid);
		if ($bag->getGidByItemId($itemId) == BagDef::INVALID_GRID_ID)
		{
			throw new FakeException('not found itemId:%d in bag', $itemId);
		}
		$itemType	= $item->getItemType();
		if ( $itemType != ItemDef::ITEM_TYPE_CHARIOT)
		{
			throw new FakeException('invalid itemType:%d',$itemType);
		}
		$chariotType	= $item->getType();
		if ($chariotType!=ChariotUtil::getChariotTypeBypos($pos))
		{
			throw new FakeException('pos err,des type:%d,arg pos:%d',ChariotUtil::getChariotTypeBypos($pos),$pos);
		}
		$needLevel=ChariotUtil::getChariotPosNeedLevel($pos);
		$userObj=EnUser::getUserObj($uid);
		if ($userObj->getLevel()<$needLevel)
		{
			throw new FakeException('chariot pos:%d need lv:%d,your lv:%d',$pos,$needLevel,$userObj->getLevel());
		}
		$masterHeroObj=$userObj->getHeroManager()->getMasterHeroObj();
		$oldItemId=$masterHeroObj->getEquipByPos(HeroDef::EQUIP_CHARIOT, $pos);
		if (!empty($oldItemId))
		{
			if ($oldItemId==$itemId)
			{
				throw new FakeException('can not equip same chariot twice!');
			}
			if ($oldItemId != BagDef::ITEM_ID_NO_ITEM && !$bag->addItem($oldItemId,true) )
			{
				throw new InterException('add the oldItem:%d to bag failed', $oldItemId);
			}
		}
		if($bag->removeItem($itemId) == FALSE)
		{
			throw new FakeException('no such item %d in bag.remove failed.',$itemId);
		}
		$masterHeroObj->setEquipByPos(HeroDef::EQUIP_CHARIOT, $itemId, $pos);
		//清除战斗信息
		$userObj->modifyBattleData();
		$userObj->update();
		$bag->update();
		
		return 'ok';
	}
	
	public static function unequip($pos,$itemId,$uid)
	{
		$pos=intval($pos);
		$itemId=intval($itemId);
		$item = ItemManager::getInstance()->getItem($itemId);
		//物品和类别参数是否合法
		if( empty($item) )
		{
			throw new FakeException('itemId:%d not exist', $itemId);
		}
		$userObj=EnUser::getUserObj($uid);
		$masterHeroObj=$userObj->getHeroManager()->getMasterHeroObj();
		$oldItemId=$masterHeroObj->getEquipByPos(HeroDef::EQUIP_CHARIOT, $pos);
		if (empty($oldItemId)||$oldItemId!=$itemId)
		{
			throw new FakeException('arg err');
		}
		//把这个位置的车设置成0
		$masterHeroObj->setEquipByPos(HeroDef::EQUIP_CHARIOT, ItemDef::ITEM_ID_NO_ITEM, $pos);
		//往背包加这个物品
		$bag=BagManager::getInstance()->getBag();
		if ($bag->addItem($itemId,false)==false)
		{
			throw new FakeException('bag full');
		}
		$userObj->modifyBattleData();
		$userObj->update();
		$bag->update();
		
		return 'ok';
		
	}
	/**
	 * 强化战车
	 */
	public static function enforce($itemId,$addLv,$uid)
	{
		$itemId=intval($itemId);
		$addLv=intval($addLv);
		$item = ItemManager::getInstance()->getItem($itemId);
		//物品和类别参数是否合法
		if (empty($item))
		{
			throw new FakeException('itemId:%d not exist', $itemId);
		}
		$bag=BagManager::getInstance()->getBag();
		$userObj=EnUser::getUserObj();		
		$masterHeroObj=$userObj->getHeroManager()->getMasterHeroObj();
		$chariotInfo=$masterHeroObj->getEquipByType(HeroDef::EQUIP_CHARIOT);
		if (!in_array($itemId, $chariotInfo)&&$bag->getGidByItemId($itemId) == BagDef::INVALID_GRID_ID)
		{
			throw new FakeException('no such item id:%d',$itemId);//既不在背包也没有装备
		}
		$itemType	= $item->getItemType();
		if ( $itemType != ItemDef::ITEM_TYPE_CHARIOT)
		{
			throw new FakeException('invalid itemType:%d',$itemType);
		}
		//强化等级上限
		$enforceLv=$item->getLevel();
		if ($enforceLv+$addLv>$item->getMaxLevel())
		{
			throw new FakeException('enforce level max');
		}
		//检查OK，计算要扣的银币和物品   // $enforceCost的 0对应银币  1对应物品模板ID    2是物品数量
		$enforceCost=$item->getEnforceCost();
		$costSilver=0;
		$costItem=array();
		for ($i=$enforceLv;$i<$enforceLv+$addLv;$i++)
		{
			$costSilver+=$enforceCost[$i][0];
			if (!isset($costItem[$enforceCost[$i][1]]))
			{
				$costItem[$enforceCost[$i][1]]=$enforceCost[$i][2];
			}else 
			{
				$costItem[$enforceCost[$i][1]]+=$enforceCost[$i][2];
			}
		}
		
		if ($userObj->subSilver($costSilver)==FALSE)
		{
			throw new FakeException('lack silver!need:%d,your:%d',$enforceCost[0],$userObj->getSilver());
		}
		
		if ($bag->deleteItemsByTemplateID($costItem)==FALSE)
		{
			throw new FakeException('not enouph item');
		}
		Logger::info('chariot.enforce cost silver:%d cost item:%s',$costSilver,$costItem);
		//加上强化等级
		$item->setLevel($enforceLv+$addLv);
		//清除战斗信息
		if ($bag->isItemExist($itemId) == false)
		{
			$userObj->modifyBattleData();
		}
		
		$userObj->update();
		$bag->update();
		
		return $item->itemInfo();
	}
	
	/**
	 * 分解战车，参数$id是对应的物品id
	 */
	public static function resolve($itemArr,$uid,$preview)
	{
		$returnArr=array();
		$bag=BagManager::getInstance()->getBag();
		foreach ($itemArr as $itemId)
		{
			$itemId=intval($itemId);
			$item = ItemManager::getInstance()->getItem($itemId);
			//物品和类别参数是否合法
			if( empty($item) )
			{
				throw new FakeException('itemId:%d not exist', $itemId);
			}
			if ($bag->getGidByItemId($itemId) == BagDef::INVALID_GRID_ID)
			{
				throw new FakeException('no such item id:%d',$itemId);
			}
			if ( $item->getItemType()!= ItemDef::ITEM_TYPE_CHARIOT)
			{
				throw new FakeException('invalid itemType:%d',$item->getItemType());
			}
			if ($item->getLevel()>ItemDef::ITEM_ATTR_NAME_CHARIOT_INIT_ENFORCE_LV)
			{
				throw new FakeException('lv is not 0');//0级才能分解
			}
			$resolveGot=$item->getResolveGot();
			foreach ($resolveGot as $itemInfo)
			{
				foreach ($itemInfo as $id=>$num)
				{
					if (!isset($returnArr[$id]))
					{
						$returnArr[$id]=$num;
					}
					else
					{
						$returnArr[$id]+=$num;
					}
				}
			}
			if ($bag->deleteItem($itemId)==false)
			{
				throw new FakeException('no such item:%d',$itemId);
			}
		}
		
		if ($bag->addItemsByTemplateID($returnArr)==false)
		{
			throw new FakeException('bag full');
		}
		if ($preview==false)
		{
			Logger::info('chariot.resolve:%s,resolve got:%s',$itemArr,$returnArr);
			$bag->update();
		}
		
		return array('item'=>$returnArr,);
	}	
	/**
	 * 重生战车，参数$id是对应的模板id
	 */
	public static function reborn($itemId,$uid,$preview)
	{
		$itemId=intval($itemId);
		$item = ItemManager::getInstance()->getItem($itemId);
		//物品和类别参数是否合法
		if (empty($item))
		{
			throw new FakeException('itemId:%d not exist', $itemId);
		}
		$bag=BagManager::getInstance()->getBag();
		$userObj=EnUser::getUserObj();
		//只有在背包里才能重生
		if ($bag->getGidByItemId($itemId) == BagDef::INVALID_GRID_ID)
		{
			throw new FakeException('no such item id:%d',$itemId);
		}
		//类型判断
		if ( $item->getItemType() != ItemDef::ITEM_TYPE_CHARIOT)
		{
			throw new FakeException('invalid itemType:%d',$item->getItemType());
		}
		$enforceLv=$item->getLevel();
		if ($enforceLv<=ItemDef::ITEM_ATTR_NAME_CHARIOT_INIT_ENFORCE_LV)
		{
			throw new FakeException('level too low');//0级以上才能重生
		}
		//要花钱的哦
		$rebornCostGold=$item->getRebornCost();
		if ($userObj->subGold($rebornCostGold, StatisticsDef::ST_FUNCKEY_CHARIOT_REBORN_COST)==FALSE)
		{
			throw new FakeException('not enouph gold');
		}
		//计算返还的银币和物品
		$enforceCost=$item->getEnforceCost();
		$getSilver=0;
		$getItem=array();
		for ($i=$enforceLv-1;$i>=0;$i--)
		{
			$getSilver+=$enforceCost[$i][0];
			if (!isset($getItem[$enforceCost[$i][1]]))
			{
				$getItem[$enforceCost[$i][1]]=$enforceCost[$i][2];
			}else
			{
				$getItem[$enforceCost[$i][1]]+=$enforceCost[$i][2];
			}
		}
		if ($bag->addItemsByTemplateID($getItem)==false)
		{
			throw new FakeException('bag full');
		}
		$item->resetItem();
		$userObj->addSilver($getSilver);
		if ($preview==false)
		{
			Logger::info('chariot.reborn:%d got silver:%d got item:%s',$itemId,$getSilver,$getItem);
			$userObj->update();
			$bag->update();
		}
		
		return array(
			'silver'=>$getSilver,	
			'item'=>$getItem,
		);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */