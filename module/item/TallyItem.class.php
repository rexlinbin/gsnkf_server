<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TallyItem.class.php 250060 2016-07-05 09:53:16Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/TallyItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-07-05 09:53:16 +0000 (Tue, 05 Jul 2016) $
 * @version $Revision: 250060 $
 * @brief 
 *  
 **/

/**
 * va_item_text:array	物品扩展信息
 * {
 * 	  	'tallyLevel':等级
 * 		'tallyExp':总经验值
 * 		'tallyEvolve':精炼等级
 * 		'tallyDevelop':进阶等级
 * }
 * @author tianming
 */
class TallyItem extends Item
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
				ItemDef::ITEM_ATTR_NAME_TALLY_LEVEL => ItemDef::ITEM_ATTR_NAME_TALLY_INIT_LEVEL,
				ItemDef::ITEM_ATTR_NAME_TALLY_EXP => ItemDef::ITEM_ATTR_NAME_TALLY_INIT_EXP,
				ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE => ItemDef::ITEM_ATTR_NAME_TALLY_INIT_EVOLVE,
				ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP => ItemDef::ITEM_ATTR_NAME_TALLY_INIT_DEVELOP,
		);
		return $itemText;
	}
	
	/**
	 * 物品的属性信息
	 * 
	 * @return array
	 */
	public function info()
	{
		$info = array();
		$level = $this->getLevel();
		$attrs = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_ATTRS);
		//基础属性+成长属性
		foreach ($attrs as $attrId => $attrValue)
		{
			$attrName = PropertyKey::$MAP_CONF[$attrId];
			if (!isset($info[$attrName]))
			{
				$info[$attrName] = 0;
			}
			$info[$attrName] += $attrValue[0]; 
			if (isset($attrValue[1])) 
			{
				$info[$attrName] += $attrValue[1] * $level;
			}
		}
		
		//进阶属性,从0级开始有属性加成
		$develop = $this->getDevelop();
		$developAttrs = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_ATTRS);
		foreach ($developAttrs as $needDevelop => $attr)
		{
			if ($needDevelop > $develop)
			{
				break;
			}
			foreach ($attr as $attrId => $attrValue)
			{
				$attrName = PropertyKey::$MAP_CONF[$attrId];
				if (!isset($info[$attrName]))
				{
					$info[$attrName] = 0;
				}
				$info[$attrName] += $attrValue;
			}
		}
		
		return $info;
	}
	
	/**
	 * 得到类型
	 *
	 * @return int
	 */
	public function getType()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_TYPE);
	}
	
	/**
	 * 获得等级
	 * 
	 * @return int
	 */
	public function getLevel()
	{
		return $this->mItemText[ItemDef::ITEM_ATTR_NAME_TALLY_LEVEL];
	}
	
	/**
	 * 设置等级
	 *
	 * @param int $level
	 */
	public function setLevel($level)
	{
		$this->mItemText[ItemDef::ITEM_ATTR_NAME_TALLY_LEVEL] = $level;
	}
	
	/**
	 * 获得经验值
	 * 
	 * @return int
	 */
	public function getExp()
	{
		return $this->mItemText[ItemDef::ITEM_ATTR_NAME_TALLY_EXP];
	}
	
	/**
	 * 设置经验值
	 * 
	 * @param int $exp
	 */
	public function setExp($exp)
	{
		$this->mItemText[ItemDef::ITEM_ATTR_NAME_TALLY_EXP] = $exp;
	}
	
	/**
	 * 增加经验值
	 * 
	 * @param int $addExp
	 */
	public function addExp($addExp)
	{
		$exp = $this->getExp();
		$upgradeValue = $this->getUpgradeValue();
		while ($this->canUpgrade() && $exp + $addExp >= $upgradeValue)
		{
			$level = $this->getLevel();
			$this->setLevel($level + 1);
			$upgradeValue = $this->getUpgradeValue();
		}
		$this->setExp($exp + $addExp);
	}
	
	/**
	 * 获得精炼等级
	 *
	 * @return int
	 */
	public function getEvolve()
	{
		return $this->mItemText[ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE];
	}
	
	/**
	 * 设置精炼等级
	 *
	 * @param int $evolve
	 */
	public function setEvolve($evolve)
	{
		$this->mItemText[ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE] = $evolve;
	}
	
	/**
	 * 获得进阶等级
	 *
	 * @return int
	 */
	public function getDevelop()
	{
		return $this->mItemText[ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP];
	}
	
	/**
	 * 设置进阶等级
	 *
	 * @param int $develop
	 */
	public function setDevelop($develop)
	{
		$this->mItemText[ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP] = $develop;
	}
	
	/**
	 * 重置兵符
	 */
	public function reset()
	{
		$this->setLevel(ItemDef::ITEM_ATTR_NAME_TALLY_INIT_LEVEL);
		$this->setExp(ItemDef::ITEM_ATTR_NAME_TALLY_INIT_EXP);
		$this->setDevelop(ItemDef::ITEM_ATTR_NAME_TALLY_INIT_DEVELOP);
		$this->setEvolve(ItemDef::ITEM_ATTR_NAME_TALLY_INIT_EVOLVE);
	}
	
	/**
	 * 能否升级
	 *
	 * @return int
	 */
	public function canUpgrade()
	{
		$level = $this->getLevel();
		$limit = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_LEVEL_LIMIT);
		return $level < $limit ? true : false;
	}
	
	/**
	 * 获得等级对应升级经验
	 * 
	 * @return int
	 */
	public function getUpgradeValue()
	{
		//从1级开始的
		$level = $this->getLevel();
		$expId = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_EXPID);
		return btstore_get()->EXP_TBL[$expId][$level + 1];
	}
	
	/**
	 * 获得每经验花费银币
	 * 
	 * @return int
	 */
	public function getUpgradeCost()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_UPGRADE_COST);
	}
	
	/**
	 * 获得升级总花费
	 *
	 * @return int
	 */
	public function getUpgradeCostSum()
	{
		return $this->getExp() * $this->getUpgradeCost();
	}
	
	/**
	 * 能否进阶
	 *
	 * @return int
	 */
	public function canDevelop()
	{
		//从0级开始
		$level = $this->getLevel();
		$develop = $this->getDevelop();
		$arrNeed = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_NEED)->toArray();
		return $develop < count($arrNeed) && $level >= $arrNeed[$develop] ? true : false;
	}
	
	/**
	 * 获得进阶等级对应花费
	 * 
	 * @return array
	 */
	public function getDevelopCost()
	{
		//从0级开始
		$develop = $this->getDevelop();
		$arrCost = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_COST)->toArray();
		return $arrCost[$develop];
	}
	
	/**
	 * 获得进阶全部花费
	 *
	 * @return array
	 */
	public function getDevelopCostSum()
	{
		$ret = array();
        $develop = $this->getDevelop();
        $arrCost = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP_COST)->toArray();
        foreach ($arrCost as $key => $value)
        {
            if ($develop > $key)
            {
                $ret = array_merge($ret, $value);
            }
        }
        return $ret;
	}
	
	/**
	 * 能否精炼
	 *
	 * @return int
	 */
	public function canEvolve()
	{
		//从0级开始
		$level = $this->getLevel();
		$evolve = $this->getEvolve();
		$arrNeed = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_NEED)->toArray();
		return $evolve < count($arrNeed) && $level >= $arrNeed[$evolve] ? true : false;
	}
	
	/**
	 * 获得精炼等级对应花费
	 *
	 * @return array
	 */
	public function getEvolveCost()
	{
		//从0级开始
		$evolve = $this->getEvolve();
		$arrCost = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_COST)->toArray();
		return $arrCost[$evolve];
	}
	
	/**
	 * 获得精炼全部花费
	 * 
	 * @return array
	 */
	public function getEvolveCostSum()
	{
		$ret = array();
		$evolve = $this->getEvolve();
		$arrCost = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_COST)->toArray();
		foreach ($arrCost as $key => $value)
		{
			if ($evolve > $key)
			{
				$ret = array_merge($ret, $value);
			}
		}
		return $ret;
	}
	
	/**
	 * 获得技能id
	 * 
	 * @return int
	 */
	public function getEvolveAwakeAbility()
	{
		//从0级开始
		$evolve = $this->getEvolve();
		$effect = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE_EFFECT);
		return $effect[$evolve];
	}
	
	/**
	 * 是否可以炼化
	 * 
	 * @return boolean
	 */
	public function canResolve()
	{
		$exp = $this->getExp();
		$level = $this->getLevel();
		$develop = $this->getDevelop();
		$evolve = $this->getEvolve();
		
		return empty($exp) && empty($level) && empty($develop) && empty($evolve) ? true : false;
	}
	
	/**
	 * 获得炼化的积分
	 * 
	 * @return int
	 */
	public function getResolvePoint()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_POINT);
	}
	
	/**
	 * 获得重生花费
	 * 
	 * @return int
	 */
	public function getRebornCost()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_TALLY_REBORN_COST);
	}
	
	/**
	 * 获得兵符录的属性加成
	 * 
	 * @param int $uid
	 */
	public static function getAddAttrByTallyBook($uid)
	{
		$tallyBook = ItemInfoLogic::getTallyBook($uid);
		
		$arrAddAttr = array();
		if(empty($tallyBook)) 
		{
			return $arrAddAttr;
		}
		foreach($tallyBook as $itemTplId)
		{
			$attrs= ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TALLY_BOOK_ATTRS);
			foreach($attrs as $attrId => $attrValue)
			{
				if(!isset($arrAddAttr[$attrId]))
				{
					$arrAddAttr[$attrId] = 0;
				}
				$arrAddAttr[$attrId] += $attrValue;
			}
		}
		
		$arrAddAttr = HeroUtil::adaptAttr($arrAddAttr);
		Logger::trace('getAddAttrByTallyBook. uid:%d, addAttr:%s', $uid, $arrAddAttr);
		return $arrAddAttr;
	}
	

	/**
	 * 获得橙色兵符的连锁属性加成
	 *
	 * @param int $itemIds				装备id数组
	 * @param array $itemInfos			处理删掉的物品组
	 * @throws FakeException
	 * @return array $attrInfo			属性加成信息
	 * {
	 * 		$attrName => $attrNum
	 * }
	 */
	public static function getExtraAttr($itemIds, $itemInfos = array())
	{
		if (empty($itemIds) && empty($itemInfos))
		{
			return array();
		}
	
		$arrItem = ItemManager::getInstance()->getItems($itemIds);
		foreach ($arrItem as $itemId => $item)
		{
			if (empty($item))
			{
				Logger::fatal('not found itemId:%d', $itemId);
				continue;
			}
			$itemInfos[] = $item->itemInfo();
		}
	
		$arrNum = array();
		$arrDevelop = array();
		$arrEvolve = array();
		$arrQuality = array();
		foreach ($itemInfos as $itemInfo)
		{
			$itemTplId = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID];
			$itemType = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TYPE);
			if ($itemType != ItemDef::ITEM_TYPE_TALLY)
			{
				throw new FakeException('item type:%d is not a tally!', $itemType);
			}
			$tallyType = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TALLY_TYPE);;
			if (in_array($tallyType, $arrNum))
			{
				throw new FakeException('item tally type:%d is duplicate!', $tallyType);
			}
			else
			{
				$arrNum[] = $tallyType;
			}
			$arrDevelop[] = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ItemDef::ITEM_ATTR_NAME_TALLY_DEVELOP];
			$arrEvolve[] = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ItemDef::ITEM_ATTR_NAME_TALLY_EVOLVE];
			$arrQuality[] = ItemManager::getInstance()->getItemQuality($itemTplId);
		}
		rsort($arrNum);
		rsort($arrDevelop);
		rsort($arrEvolve);
		rsort($arrQuality);
		Logger::trace('arrNum:%s arrDevelop:%s arrEvolve:%s arrQuality:%s', $arrNum, $arrDevelop, $arrEvolve, $arrQuality);
		
		$attrInfo = array();
		foreach (btstore_get()->TALLY_EXTRA as $conf)
		{
			list($quality, $num) = $conf['quality_num']->toArray();
			$develop = isset($conf['develop_evolve'][1]) ? $conf['develop_evolve'][1] : 0;
			$evolve = isset($conf['develop_evolve'][2]) ? $conf['develop_evolve'][2] : 0;
			if (count($arrNum) < $num) 
			{
				continue;
			}
			if (min(array_slice($arrDevelop, 0, $num)) < $develop) 
			{
				continue;
			}
			if (min(array_slice($arrEvolve, 0, $num)) < $evolve)
			{
				continue;
			}
			if (min(array_slice($arrQuality, 0, $num)) < $quality)
			{
				continue;
			}
			$attrInfo[] = $conf['attr'];
		}
	
		$attrInfo = Util::arrayAdd2V($attrInfo);
		$attrInfo = HeroUtil::adaptAttr($attrInfo);
	
		return $attrInfo;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */