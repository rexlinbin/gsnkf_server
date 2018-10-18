<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnChariot.class.php 251627 2016-07-14 11:23:17Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chariot/EnChariot.class.php $
 * @author $Author: BaoguoMeng $(yaoqing@babeltime.com)
 * @date $Date: 2016-07-14 11:23:17 +0000 (Thu, 14 Jul 2016) $
 * @version $Revision: 251627 $
 * @brief 
 *  
 **/
class EnChariot
{
	
	public static function getAddAttrByChariot($uid)
	{
		if (!ChariotUtil::isChariotOpen($uid))
		{
			return array();
		}
		$arrAddAttr = array();
		//图鉴
		$book=ItemInfoLogic::getChariotBook($uid);
		foreach ($book as $itemTplId)
		{
			$bookAttr=ChariotItem::getBookAttr($itemTplId);
			foreach ($bookAttr as $k=>$v)
			{
				if (isset($arrAddAttr[$k]))
				{
					$arrAddAttr[$k]+=$v;
				}
				else 
				{
					$arrAddAttr[$k]=$v;
				}
			}
		}
		Logger::debug('book attr:%s',$arrAddAttr);
		//套装属性
		$suitConf=ChariotUtil::getChariotSuitConf();
		foreach ($suitConf as $id=>$idConf)
		{
			$flag = true; //套装是否集齐
			foreach ($idConf['suit_items'] as $itemId)
			{
				if (!in_array($itemId, $book))
				{
					$flag=false;
					break;
				}
			}
			if($flag)
			{
				foreach ($idConf['suit_attr'] as $k=>$v)
				{
					if (isset($arrAddAttr[$k]))
					{
						$arrAddAttr[$k]+=$v;
					}
					else
					{
						$arrAddAttr[$k]=$v;
					}
				}
			}
		}
		//装备着的战车
		$userObj=EnUser::getUserObj($uid);
		$masterHeroObj=$userObj->getHeroManager()->getMasterHeroObj();
		$chariotInfo=$masterHeroObj->getEquipByType(HeroDef::EQUIP_CHARIOT);
		foreach ($chariotInfo as $pos =>$itemId)
		{
			$item = ItemManager::getInstance()->getItem($itemId);
			if( empty($item) )
			{
				continue;
			}
			$enForceLv=$item->getLevel();
			//先算基础属性
			$baseAttr=$item->getBaseAttr();
			foreach ($baseAttr as $k=>$v)
			{
				if (isset($arrAddAttr[$k]))
				{
					$arrAddAttr[$k]+=$v;
				}
				else
				{
					$arrAddAttr[$k]=$v;
				}
			}
			//再算成长属性，每一级加的属性都是一样的
			$growAttr=$item->getGrowAttr();
			foreach ($growAttr as $k=>$v)
			{
				if (isset($arrAddAttr[$k]))
				{
					$arrAddAttr[$k]+=$v*$enForceLv;
				}
				else
				{
					$arrAddAttr[$k]=$v*$enForceLv;
				}
			}
		}
		Logger::debug('chariot add attr:%s',$arrAddAttr);
		
		$arrRet = HeroUtil::adaptAttr($arrAddAttr);
		Logger::trace('getAddAttrByChariot. uid:%d, arr:%s', $uid, $arrRet);
		return $arrRet;
	}
	
	/**
	 * @return array:
	 * 						[
	 * 							pos=>array:
	 * 											[
	 * 												tid=>int  //战车对应的模板ID
	 * 												attackRound=>int //战车释放技能的回合
	 * 												attackSkill=>int //战车技能ID
	 * 												skillLevel=>int //战车技能等级，默认0
	 * 												fightRatio=>int //战车战斗系数
	 * 												fatal=>int //战车暴击率基础值
	 * 												fatalRatio=>int //战车暴击伤害倍数
	 * 												hit=>int //战车基础命中
	 * 											]
	 * 						]
	 */
	public static function getChariotSkill($uid)
	{
		if (!ChariotUtil::isChariotOpen($uid))
		{
			return array();
		}
		//装备着的战车
		$userObj=EnUser::getUserObj($uid);
		$masterHeroObj=$userObj->getHeroManager()->getMasterHeroObj();
		$chariotInfo=$masterHeroObj->getEquipByType(HeroDef::EQUIP_CHARIOT);
		$skillInfo=array();
		foreach ($chariotInfo as $pos=>$itemId)
		{
			$item = ItemManager::getInstance()->getItem($itemId);
			if (empty($item))
			{
				continue ;
			}
			$skillInfo[$pos]['tid']=$item->getItemTemplateID();
			$skillInfo[$pos]['attackRound']=$item->getRound();
			$skillInfo[$pos][PropertyKey::ATTACK_SKILL]=$item->getSkill();
			$skillInfo[$pos]['skillLevel']=0;
			$skillInfo[$pos]['fightRatio']=$item->getFightRatio();
			$skillInfo[$pos][PropertyKey::HIT]=$item->getBaseHit();
			$skillInfo[$pos][PropertyKey::FATAL]=$item->getBaseCritical();
			$skillInfo[$pos][PropertyKey::FATAL_RATIO]=$item->getBaseCriticalMutiple();
			$skillInfo[$pos][PropertyKey::PHYSICAL_ATTACK_RATIO]=$item->getPhysicalAttackRatio();
			$skillInfo[$pos][PropertyKey::MAGIC_ATTACK_RATIO]=$item->getMagicAttackRatio();
		}
		return $skillInfo;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */