<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChariotItem.class.php 251627 2016-07-14 11:23:17Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/ChariotItem.class.php $
 * @author $Author: BaoguoMeng $(yaoqing@babeltime.com)
 * @date $Date: 2016-07-14 11:23:17 +0000 (Thu, 14 Jul 2016) $
 * @version $Revision: 251627 $
 * @brief 
 *  
 **/
class ChariotItem extends Item
{
	/**
	 * 产生物品
	 *
	 * @param int $itemTplId		物品模板ID
	 * @return array 				物品的等级信息
	 */
	public static function createItem($itemTplId)
	{
		$itemText = array(
				ItemDef::ITEM_ATTR_NAME_CHARIOT_ENFORCE=> ItemDef::ITEM_ATTR_NAME_CHARIOT_INIT_ENFORCE_LV,
				ItemDef::ITEM_ATTR_NAME_CHARIOT_DEVELOP => ItemDef::ITEM_ATTR_NAME_CHARIOT_INIT_DEVELOP_LV,
		);
		return $itemText;
	}
	
	public function info()
	{
		return array();
	}
	
	public function resetItem()
	{
		$this->mItemText[ItemDef::ITEM_ATTR_NAME_CHARIOT_ENFORCE]=ItemDef::ITEM_ATTR_NAME_CHARIOT_INIT_ENFORCE_LV;
		$this->mItemText[ItemDef::ITEM_ATTR_NAME_CHARIOT_DEVELOP]= ItemDef::ITEM_ATTR_NAME_CHARIOT_INIT_DEVELOP_LV;
	}
	/**
	 *
	 * 得到战车的类型
	 *
	 * @return int
	 */
	public function getType()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_TYPE);
	}
	
	/**
	* 得到战车的强化等级
	*
	* @return int
	*/
	public function getLevel()
	{
	return $this->mItemText[ItemDef::ITEM_ATTR_NAME_CHARIOT_ENFORCE];
	}
	
	/**
	* 设置装备的强化等级
	*
	* @param int $level
	*/
	public function setLevel($level)
	{
	$this->mItemText[ItemDef::ITEM_ATTR_NAME_CHARIOT_ENFORCE] = $level;
	}
	
	/**
	 * 获取强化的最大等级
	 * @param unknown $itemTplId
	 * @return mixed
	 */
	public function getMaxLevel()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_CHARIOT_MAX_LEVEL);
	}
	
	/**
	 * 获得进阶等级
	 *
	 * @return int
	 */
	public function getDevelop()
	{
		return $this->mItemText[ItemDef::ITEM_ATTR_NAME_CHARIOT_DEVELOP];
	}
	
	/**
	 * 设置进阶等级
	 *
	 * @param int $develop
	 */
	public function setDevelop($develop)
	{
		$this->mItemText[ItemDef::ITEM_ATTR_NAME_CHARIOT_DEVELOP] = $develop;
	}
	
	/**
	 * 获得强化等级对应花费
	 *
	 * @return array
	 */
	public function getEnforceCost()
	{
		$arrCost = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_ENFORCE_COST)->toArray();
		return $arrCost;
	}
	/**
	 * 重生消耗的金币
	 * @return unknown
	 */
	public function getRebornCost()
	{
		$arrCost = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_REBORN_COST);
		return $arrCost;
	}
	public function getResolveGot()
	{
		$arrGot= ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_RESOLVE_GOT)->toArray();
		return $arrGot;
	}
	/**
	 * (non-PHPdoc)
	 * @see Item::getItemQuality()
	 */
	public function getItemQuality()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_QUALITY);
	}
	
	/**
	 * 得到基础属性的配置
	 * @return mixed
	 */
	public function getBaseAttr()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_BASE_ATTR)->toArray();
	}
	/**
	 * 得到成长属性的配置
	 * @return mixed
	 */
	public function getGrowAttr()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_GROW_ATTR)->toArray();
	}
	/**
	 * 得到技能
	 * @return mixed
	 */
	public function getSkill()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_SKILL);
	}
	/**
	 * 第几回合放技能
	 * @return mixed
	 */
	public function getRound()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_ROUND);
	}
	/**
	 * 战斗系数
	 */
	public function getFightRatio()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_FIGHT_RATIO);
	}
	/**
	 *
	 */
	public function getBaseCritical()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_BASE_CRITICAL);
	}
	
	public function getBaseCriticalMutiple()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_BASE_CRITICAL_MUTIPLE);
	}
	public function getBaseHit()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_BASE_HIT);
	}
	public function getPhysicalAttackRatio()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_PHYSICAL_ATTACK_RATIO);
	}
	public function getMagicAttackRatio()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_CHARIOT_MAGIC_ATTACK_RATIO);
	}
	/**
	 * 获取图鉴属性
	 * @param unknown $itemTplId
	 * @return mixed
	 */
	public static function getBookAttr($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_CHARIOT_BOOK_ATTR)->toArray();
	}
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */