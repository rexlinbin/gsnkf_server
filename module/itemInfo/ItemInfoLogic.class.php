<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ItemInfoLogic.class.php 251365 2016-07-13 05:36:38Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/itemInfo/ItemInfoLogic.class.php $
 * @author $Author: QingYao $(tianming@babeltime.com)
 * @date $Date: 2016-07-13 05:36:38 +0000 (Wed, 13 Jul 2016) $
 * @version $Revision: 251365 $
 * @brief 
 *  
 **/
class ItemInfoLogic
{
	public static function getArmBook($uid)
	{
		Logger::trace('ItemInfoLogic::getArmBook Start.');
		
		$armBook = array();
		
		//如果是当前用户线程，就先从session中取数据
		if($uid == RPCContext::getInstance()->getUid())
		{
			$armBook = RPCContext::getInstance()->getSession(ShowDef::ARM_SESSION);
			if (empty($armBook)) 
			{
				$book = ItemBookDao::getArmBook($uid);
				//如果数据库没有数据，就初始化用户数据
				if (empty($book)) 
				{
					//读背包里面的装备数据
					$arrItemId = BagManager::getInstance()->getBag($uid)->getItemIdsByItemType(ItemDef::ITEM_TYPE_ARM);
					//读用户武将的所有装备数据
					$arrHero = EnUser::getUserObj($uid)->getHeroManager()->getAllHeroObjInSquad();
					foreach ($arrHero as $hero)
					{
						$ret = $hero->getEquipByType(HeroDef::EQUIP_ARMING);
						$arrItemId = array_merge($arrItemId, $ret);
					}
					$arrItemTplInfo = ItemManager::getInstance()->getTemplateInfoByItemIds($arrItemId);
					$armBook = array_keys($arrItemTplInfo);
					//只保留在图鉴中的装备
					$armShow = btstore_get()->SHOWS[ShowDef::ARM_SHOW]->toArray();
					$armBook = array_values(array_intersect($armBook, $armShow));
					//插入用户数据
					ItemBookDao::updateArmBook($uid, array('arm' => $armBook));
				}
				else 
				{
					$armBook = $book[ItemBookDao::BOOK]['arm'];
				}
				RPCContext::getInstance()->setSession(ShowDef::ARM_SESSION, $armBook);
			}
		}
		else 
		{
			$book = ItemBookDao::getArmBook($uid);
			if (!empty($book[ItemBookDao::BOOK]['arm'])) 
			{
				$armBook = $book[ItemBookDao::BOOK]['arm'];
			}
		}
	
		Logger::trace('ItemInfoLogic::getArmBook End.');
		
		return $armBook;
	}
	
	public static function updateArmBook($armModify)
	{
		Logger::trace('ItemInfoLogic::updateArmBook Start.');
		
		if (empty($armModify)) 
		{
			return ;
		}
	
		$uid = RPCContext::getInstance()->getUid();
		$armBook = self::getArmBook($uid);
		$armModify = array_unique($armModify);
		//取两个数组的差集
		$armDiff = array_diff($armModify, $armBook);
		//只保留在图鉴中的装备
		$armShow = btstore_get()->SHOWS[ShowDef::ARM_SHOW]->toArray();
		$armDiff = array_intersect($armDiff, $armShow);
		
		//差集非空
		if (!empty($armDiff)) 
		{
			$armBook = array_merge($armBook, $armDiff);
			//更新用户数据
			ItemBookDao::updateArmBook($uid, array('arm' => $armBook));
			RPCContext::getInstance()->setSession(ShowDef::ARM_SESSION, $armBook);
			EnAchieve::updateEquipTypes($uid, count($armBook));
		}
	
		Logger::trace('ItemInfoLogic::updateArmBook End.');
	}
	
	public static function getTreasBook($uid)
	{
		Logger::trace('ItemInfoLogic::getTreasBook Start.');
		
		$treasBook = array();
		
		//如果是当前用户线程，就先从session中取数据
		if($uid == RPCContext::getInstance()->getUid())
		{
			$treasBook = RPCContext::getInstance()->getSession(ShowDef::TREASURE_SESSION);
			if (empty($treasBook))
			{
				$book = ItemBookDao::getTreasBook($uid);
				//如果数据库没有数据，就初始化用户数据
				if (empty($book))
				{
					//读背包里面的宝物数据
					$arrItemId = BagManager::getInstance()->getBag($uid)->getItemIdsByItemType(ItemDef::ITEM_TYPE_TREASURE);
					//读用户武将的所有宝物数据
					$arrHero = EnUser::getUserObj($uid)->getHeroManager()->getAllHeroObjInSquad();
					foreach ($arrHero as $hero)
					{
						$ret = $hero->getEquipByType(HeroDef::EQUIP_TREASURE);
						$arrItemId = array_merge($arrItemId, $ret);
					}
					$arrItemTplInfo = ItemManager::getInstance()->getTemplateInfoByItemIds($arrItemId);
					$treasBook = array_keys($arrItemTplInfo);
					//只保留在图鉴中的宝物
					$treasShow = btstore_get()->SHOWS[ShowDef::TREASURE_SHOW]->toArray();
					$treasBook = array_values(array_intersect($treasBook, $treasShow));
					//插入用户数据
					ItemBookDao::updateTreasBook($uid, array('treas' => $treasBook));
				}
				else
				{
					$treasBook = $book[ItemBookDao::BOOK]['treas'];
				}
				RPCContext::getInstance()->setSession(ShowDef::TREASURE_SESSION, $treasBook);
			}
		}
		else
		{
			$book = ItemBookDao::getTreasBook($uid);
			if (!empty($book[ItemBookDao::BOOK]['treas']))
			{
				$treasBook = $book[ItemBookDao::BOOK]['treas'];
			}
		}
	
		Logger::trace('ItemInfoLogic::getTreasBook End.');
	
		return $treasBook;
	}
	
	public static function updateTreasBook($treasModify)
	{
		Logger::trace('ItemInfoLogic::updateTreasBook Start.');
		
		if (empty($treasModify)) 
		{
			return ;
		}
		
		$uid = RPCContext::getInstance()->getUid();
		$treasBook = self::getTreasBook($uid);
		$treasModify = array_unique($treasModify);
		//取两个数组的差集
		$treasDiff = array_diff($treasModify, $treasBook);
		//只保留在图鉴中的宝物
		$treasShow = btstore_get()->SHOWS[ShowDef::TREASURE_SHOW]->toArray();
		$treasDiff = array_intersect($treasDiff, $treasShow);
		
		//差集非空
		if (!empty($treasDiff))
		{
			$treasBook = array_merge($treasBook, $treasDiff);
			//更新用户数据
			ItemBookDao::updateTreasBook($uid, array('treas' => $treasBook));
			RPCContext::getInstance()->setSession(ShowDef::TREASURE_SESSION, $treasBook);
			EnAchieve::updateEquipSuitTypes($uid, count($treasBook));
		}
	
		Logger::trace('ItemInfoLogic::updateTreasBook End.');
	}
	
	public static function getGodWeaponBook($uid)
	{
		Logger::trace('ItemInfoLogic::getGodWeaponBook Start.');
	
		$godWeaponBook = array();
	
		//如果是当前用户线程，就先从session中取数据
		if($uid == RPCContext::getInstance()->getUid())
		{
			$godWeaponBook = RPCContext::getInstance()->getSession(ShowDef::GODWEAPON_SESSION);
			if (empty($godWeaponBook))
			{
				$book = ItemBookDao::getGodWeaponBook($uid);
				//如果数据库没有数据，就初始化用户数据
				if (empty($book))
				{
					//读背包里面的神兵数据
					$arrItemId = BagManager::getInstance()->getBag($uid)->getItemIdsByItemType(ItemDef::ITEM_TYPE_GODWEAPON);
					//读用户武将的所有神兵数据
					$arrHero = EnUser::getUserObj($uid)->getHeroManager()->getAllHeroObjInSquad();
					foreach ($arrHero as $hero)
					{
						$ret = $hero->getEquipByType(HeroDef::EQUIP_GODWEAPON);
						$arrItemId = array_merge($arrItemId, $ret);
					}
					$arrItemTplInfo = ItemManager::getInstance()->getTemplateInfoByItemIds($arrItemId);
					$godWeaponBook = array_keys($arrItemTplInfo);
					//只保留在图鉴中的神兵
					$godWeaponShow = btstore_get()->SHOWS[ShowDef::GODWEAPON_SHOW]->toArray();
					$godWeaponBook = array_values(array_intersect($godWeaponBook, $godWeaponShow));
					//插入用户数据
					ItemBookDao::updateGodWeaponBook($uid, array('godweapon' => $godWeaponBook));
				}
				else
				{
					$godWeaponBook = $book[ItemBookDao::BOOK]['godweapon'];
				}
				RPCContext::getInstance()->setSession(ShowDef::GODWEAPON_SESSION, $godWeaponBook);
			}
		}
		else
		{
			$book = ItemBookDao::getGodWeaponBook($uid);
			if (!empty($book[ItemBookDao::BOOK]['godweapon']))
			{
				$godWeaponBook = $book[ItemBookDao::BOOK]['godweapon'];
			}
		}
	
		Logger::trace('ItemInfoLogic::getGodWeaponBook End.');
	
		return $godWeaponBook;
	}
	
	public static function updateGodWeaponBook($godWeaponModify)
	{
		Logger::trace('ItemInfoLogic::updateGodWeaponBook Start.');
	
		if (empty($godWeaponModify))
		{
			return ;
		}
	
		$uid = RPCContext::getInstance()->getUid();
		$godWeaponBook = self::getGodWeaponBook($uid);
		$godWeaponModify = array_unique($godWeaponModify);
		//取两个数组的差集
		$godWeaponDiff = array_diff($godWeaponModify, $godWeaponBook);
		//只保留在图鉴中的神兵
		$godWeaponShow = btstore_get()->SHOWS[ShowDef::GODWEAPON_SHOW]->toArray();
		$godWeaponDiff = array_intersect($godWeaponDiff, $godWeaponShow);
	
		//差集非空
		if (!empty($godWeaponDiff))
		{
            RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::GODWEAPON_NEW_DICT, $godWeaponDiff);
            //清一下战斗缓存
            Enuser::getUserObj()->modifyBattleData();
			$godWeaponBook = array_merge($godWeaponBook, $godWeaponDiff);
			//更新用户数据
			ItemBookDao::updateGodWeaponBook($uid, array('godweapon' => $godWeaponBook));
			RPCContext::getInstance()->setSession(ShowDef::GODWEAPON_SESSION, $godWeaponBook);
		}
	
		Logger::trace('ItemInfoLogic::updateGodWeaponBook End.');
	}
	
	public static function getTallyBook($uid)
	{
		Logger::trace('ItemInfoLogic::getTallyBook Start.');
	
		$tallyBook = array();
	
		//如果是当前用户线程，就先从session中取数据
		if($uid == RPCContext::getInstance()->getUid())
		{
			$tallyBook = RPCContext::getInstance()->getSession(ShowDef::TALLY_SESSION);
			if (empty($tallyBook))
			{
				$book = ItemBookDao::getTallyBook($uid);
				//如果数据库没有数据，就初始化用户数据
				if (empty($book))
				{
					//读背包里面的兵符数据
					$arrItemId = BagManager::getInstance()->getBag($uid)->getItemIdsByItemType(ItemDef::ITEM_TYPE_TALLY);
					//读用户武将的所有兵符数据
					$arrHero = EnUser::getUserObj($uid)->getHeroManager()->getAllHeroObjInSquad();
					foreach ($arrHero as $hero)
					{
						$ret = $hero->getEquipByType(HeroDef::EQUIP_TALLY);
						$arrItemId = array_merge($arrItemId, $ret);
					}
					$arrItemTplInfo = ItemManager::getInstance()->getTemplateInfoByItemIds($arrItemId);
					$tallyBook = array_keys($arrItemTplInfo);
					//只保留在图鉴中的兵符
					$tallyShow = btstore_get()->SHOWS[ShowDef::TALLY_SHOW]->toArray();
					$tallyBook = array_values(array_intersect($tallyBook, $tallyShow));
					//插入用户数据
					ItemBookDao::updateTallyBook($uid, array('tally' => $tallyBook));
				}
				else
				{
					$tallyBook = $book[ItemBookDao::BOOK]['tally'];
				}
				RPCContext::getInstance()->setSession(ShowDef::TALLY_SESSION, $tallyBook);
			}
		}
		else
		{
			$book = ItemBookDao::getTallyBook($uid);
			if (!empty($book[ItemBookDao::BOOK]['tally']))
			{
				$tallyBook = $book[ItemBookDao::BOOK]['tally'];
			}
		}
	
		Logger::trace('ItemInfoLogic::getTallyBook End.');
	
		return $tallyBook;
	}
	
	public static function updateTallyBook($tallyModify)
	{
		Logger::trace('ItemInfoLogic::updateTallyBook Start.');
	
		if (empty($tallyModify))
		{
			return ;
		}
	
		$uid = RPCContext::getInstance()->getUid();
		$tallyBook = self::getTallyBook($uid);
		$tallyModify = array_unique($tallyModify);
		//取两个数组的差集
		$tallyDiff = array_diff($tallyModify, $tallyBook);
		//只保留在图鉴中的兵符
		$tallyShow = btstore_get()->SHOWS[ShowDef::TALLY_SHOW]->toArray();
		$tallyDiff = array_intersect($tallyDiff, $tallyShow);
	
		//差集非空
		if (!empty($tallyDiff))
		{
			$tallyBook = array_merge($tallyBook, $tallyDiff);
			//清一下战斗缓存
			Enuser::getUserObj()->modifyBattleData();
			//更新用户数据
			ItemBookDao::updateTallyBook($uid, array('tally' => $tallyBook));
			RPCContext::getInstance()->setSession(ShowDef::TALLY_SESSION, $tallyBook);
		}
	
		Logger::trace('ItemInfoLogic::updateTallyBook End.');
	}
	
	public static function getChariotBook($uid)
	{
		Logger::trace('ItemInfoLogic::getChariotBook Start.');
		
		$chariotBook = array();
		
		//如果是当前用户线程，就先从session中取数据
		if($uid == RPCContext::getInstance()->getUid())
		{
			$chariotBook = RPCContext::getInstance()->getSession(ShowDef::CHARIOT_SESSION);
			if (empty($chariotBook))
			{
				$book = ItemBookDao::getChariotBook($uid);
				//如果数据库没有数据，就初始化用户数据
				if (empty($book))
				{
					//读背包里面的战车数据
					$arrItemId = BagManager::getInstance()->getBag($uid)->getItemIdsByItemType(ItemDef::ITEM_TYPE_CHARIOT);
					//读用户武将的所有战车数据
					$masterHeroObj = EnUser::getUserObj($uid)->getHeroManager()->getMasterHeroObj();
					$arrItemIdInUser=$masterHeroObj->getEquipByType(HeroDef::EQUIP_CHARIOT);
					$arrItemId=array_merge($arrItemId,$arrItemIdInUser);
					$arrItemTplInfo = ItemManager::getInstance()->getTemplateInfoByItemIds($arrItemId);
					$chariotBook = array_keys($arrItemTplInfo);
					$chariotBook=array_unique($chariotBook);
					//插入用户数据
					ItemBookDao::updateChariotBook($uid, array('chariot' => $chariotBook));
				}
				else
				{
					$chariotBook = $book[ItemBookDao::BOOK]['chariot'];
				}
				RPCContext::getInstance()->setSession(ShowDef::CHARIOT_SESSION, $chariotBook);
			}
		}
		else
		{
			$book = ItemBookDao::getChariotBook($uid);
			if (!empty($book[ItemBookDao::BOOK]['chariot']))
			{
				$chariotBook = $book[ItemBookDao::BOOK]['chariot'];
			}
		}
		
		Logger::trace('ItemInfoLogic::getChariotBook End.');
		
		return $chariotBook;
	}
	public static function updateChariotBook($chariotModify)
	{
		Logger::trace('ItemInfoLogic::updateChariotBook Start.');
		
		if (empty($chariotModify))
		{
			return ;
		}
		$uid = RPCContext::getInstance()->getUid();
		$chariotBook = self::getChariotBook($uid);
		$chariotModify = array_unique($chariotModify);
		//取两个数组的差集
		$chariotDiff = array_diff($chariotModify, $chariotBook);
		
		//差集非空
		if (!empty($chariotDiff))
		{
			$chariotBook = array_merge($chariotBook, $chariotDiff);
			//清一下战斗缓存
			Enuser::getUserObj()->modifyBattleData();
			//更新用户数据
			ItemBookDao::updateChariotBook($uid, array('chariot' => $chariotBook));
			RPCContext::getInstance()->setSession(ShowDef::CHARIOT_SESSION, $chariotBook);
		}
		
		Logger::trace('ItemInfoLogic::updateChariotBook End.');
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */