<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UnionLogic.class.php 241861 2016-05-10 07:54:48Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/union/UnionLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-10 07:54:48 +0000 (Tue, 10 May 2016) $
 * @version $Revision: 241861 $
 * @brief 
 *  
 **/
class UnionLogic
{
	public static function getInfoByLogin($uid)
	{
		Logger::trace('function[%s] param[%s] begin', __FUNCTION__, func_get_args());
		
		$addUnion = EnUnion::getAddUnion($uid);
		$addAttr = EnUnion::getAddAttr($uid);
		$addFunc = EnUnion::getAddFunc($uid);
		$ret = array(
				'union' => $addUnion,
				'attr' => $addAttr,
				'func' => $addFunc,
		);
		
		Logger::trace('function[%s] param[%s] ret[%s] end', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	public static function getInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin', __FUNCTION__, func_get_args());
		
		$union = UnionObj::getInstance($uid);
		$ret = $union->getInfo();
		
		Logger::trace('function[%s] param[%s] ret[%s] end', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	public static function fill($uid, $id, $aimId, $isHero, $type)
	{
		Logger::trace('function[%s] param[%s] begin', __FUNCTION__, func_get_args());
		
		$confname = UnionDef::$TYPE_TO_CONFNAME[$type];
		$conf = btstore_get()->$confname;
		
		//检查需要用户等级
		$user = EnUser::getUserObj($uid);
		$userLevel = $user->getLevel();
		$needLevel = $conf[$id][UnionDef::NEED_LEVEL];
		if ($userLevel < $needLevel) 
		{
			throw new FakeException('conf type:%d id:%d need level:%d user level:%d', $type, $id, $needLevel, $userLevel);
		}
		
		//检查等级
		$openLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_UNION_OPEN_LEVEL_ARR][$type];
		if ($userLevel < $openLevel)
		{
			throw new FakeException('type:%d open level:%d user level:%d', $type, $openLevel, $userLevel);
		}
		
		$bag = BagManager::getInstance()->getBag($uid);
		if ($isHero) 
		{
			//检查是否裸卡,等级为1，没有进阶，没有觉醒，没有锁定，没有在阵上, 没有在过关斩将里
			$hero = $user->getHeroManager()->getHeroObj($aimId);
			if ($hero->getLevel() > 1 || $hero->hasTalent() || $hero->isLocked() || !$hero->canBeDel()) 
			{
				throw new FakeException('hero:%d can not be used to fill', $aimId);
			}
			$tid = $hero->getHtid();
			$qualification = Creature::getHeroConf($tid, CreatureAttr::QUALIFICATION);
			$user->getHeroManager()->delHeroByHid($aimId);
			Logger::info('union type:%d id:%d del hid:%d', $type, $id, $aimId);
		}
		else 
		{
			//物品未装备
			$item = ItemManager::getInstance()->getItem($aimId);
			if ($item === NULL || !$bag->isItemExist($aimId)) 
			{
				throw new FakeException('item:%d is not valid', $aimId);
			}
			$tid = $item->getItemTemplateID();
			//宝物未强化、未精炼、未进阶、未镶嵌符印
			if (ItemDef::ITEM_TYPE_TREASURE == $item->getItemType())
			{
				if ($item->getLevel() || $item->getEvolve() || $item->getDevelop() > -1 || $item->getInlay() != array()) 
				{
					throw new FakeException('treasureItem:%d can not be used to fill', $aimId);
				}
				$qualification = ItemAttr::getItemAttr($tid, TreasureDef::ITEM_ATTR_NAME_TREASURE_SCORE_BASE);
			}
			//神兵未洗练、橙色品质及以上
			if (ItemDef::ITEM_TYPE_GODWEAPON == $item->getItemType())
			{
				if ($item->getItemQuality() < ItemDef::ITEM_QUALITY_ORANGE || $item->getConfirmedAttr() != array()) 
				{
					throw new FakeException('godweaponItem:%d can not be used to fill', $aimId);
				}
				$qualification = ItemAttr::getItemAttr($tid, GodWeaponDef::ITEM_ATTR_NAME_SCORE);
			}
			if (!$bag->deleteItem($aimId)) 
			{
				throw new FakeException('item:%d is not exist', $aimId);
			}
			Logger::info('union type:%d id:%d del itemId:%d', $type, $id, $aimId);
		}
		
		//检查消耗物品或金币
		$itemTplId = $conf[$id][UnionDef::ITEM_TPLID];
		$goldNum = $conf[$id][UnionDef::GOLD_NUM];
		$needNum = $conf[$id][UnionDef::ITEM_NUM_ARR][$qualification];
		$itemNum = $bag->getItemNumByTemplateID($itemTplId);
		$decreaseNum = min($needNum, $itemNum);
		$cost = $goldNum * ($needNum - $decreaseNum);
		if ($decreaseNum && !$bag->deleteItembyTemplateID($itemTplId, $decreaseNum)) 
		{
			throw new InterException('user dont have itemTplId:%d decreaseNum:%d', $itemTplId, $decreaseNum);
		}
		if ($cost && !$user->subGold($cost, StatisticsDef::ST_FUNCKEY_UNION_FILL_COST)) 
		{
			throw new FakeException('user dont have enough gold:%d', $cost);
		}
		
		//检查是否重复镶嵌
		$union = UnionObj::getInstance($uid);
		$arrNeed = $conf[$id][UnionDef::NEED_ARR]->toArray();
		if (!in_array($tid, $arrNeed)) 
		{
			throw new FakeException('conf type:%d id:%d arrNeed:%s tid:%d', $type, $id, $arrNeed, $tid);
		}
		if (UnionDef::FATE == $type)
		{
			$fate = $union->getFate($id);
			if (in_array($tid, $fate)) 
			{
				throw new FakeException('duplicated, fate:%s tid:%d', $fate, $tid);
			}
			$union->addFate($id, $tid);
		}
		if (UnionDef::LOYAL == $type)
		{
			$loyal = $union->getLoyal($id);
			if (in_array($tid, $loyal))
			{
				throw new FakeException('duplicated, loyal:%s tid:%d', $loyal, $tid);
			}
			$union->addloyal($id, $tid);
		}
		if (UnionDef::MARTIAL == $type)
		{
			$martial = $union->getMartial($id);
			if (in_array($tid, $martial))
			{
				throw new FakeException('duplicated, martial:%s tid:%d', $martial, $tid);
			}
			$union->addMartial($id, $tid);
		}
		
		//影响战斗
		$user->modifyBattleData();
		
		$bag->update();
		$user->update();
		$union->update();
		
		$ret = 'ok';
		
		Logger::trace('function[%s] param[%s] ret[%s] end', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */