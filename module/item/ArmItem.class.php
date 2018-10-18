<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ArmItem.class.php 210201 2015-11-17 08:22:35Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/ArmItem.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2015-11-17 08:22:35 +0000 (Tue, 17 Nov 2015) $
 * @version $Revision: 210201 $
 * @brief
 *
 **/
/**
 * 目前没有使用randPotence字段
 * va_item_text:array	物品扩展信息
 * {
 * 	  	'armReinforceLevel':强化等级
 * 		'armReinforceCost':强化费用
 * 		'armDevelop':装备进阶等级
 * 		'armPotence':潜能属性数组
 * 		{
 * 			$attrId => $attrValue
 * 		}
 * 		'armFixedPotence':洗练属性数组
 * 		{
 * 			$attrId => $attrValue
 * 		}
 * }
 * @author tianming
 */
class ArmItem extends Item
{	
	/**
	 *
	 * 产生物品
	 *
	 * @param int $itemTplId		物品模板ID
	 * @return array				等级和花费银币
	 */
	public static function createItem($itemTplId)
	{
		$itemText = array();
	
		//初始化物品强化等级
		$initLevel = ItemAttr::getItemAttr($itemTplId, ArmDef::ITEM_ATTR_NAME_ARM_INIT_LEVEL);
		if ( !empty($initLevel) )
		{
			$itemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_LEVEL] = $initLevel;
		}
		else
		{
			$itemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_LEVEL] = ArmDef::ARM_REINFORCE_LEVEL_DEFAULT;
		}
		$itemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_COST] = 0;

		return $itemText;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Item::itemInfo()
	 */
	public function itemInfo()
	{
		if (!isset($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_COST]))
		{
			$this->setReinforceCost(0);
		}
		
		$itemInfo = parent::itemInfo();
	
		//得到固定潜能id，暂时没有随机潜能id
		$potenceId = $this->getFixedPotenceId();
	
		if ( !empty($itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_POTENCE]) )
		{
			foreach ( $itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_POTENCE] as $attrId => $attrValue )
			{
				$itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_POTENCE][$attrId]
				= self::getPotenceValue($potenceId, $attrId, $attrValue);
			}
		}
		if ( !empty($itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE]) )
		{
			foreach ( $itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE] as $attrId => $attrValue )
			{
				$itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE][$attrId]
				= self::getPotenceValue($potenceId, $attrId, $attrValue);
			}
		}
		if ( !empty($itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE]) )
		{
			foreach ( $itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE] as $attrId => $attrValue )
			{
				$itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE][$attrId]
				= self::getPotenceValue($potenceId, $attrId, $attrValue);
			}
		}
		return $itemInfo;
	}
	
	/**
	 *
	 * 得到物品的出售信息
	 *
	 * @return array		sell_pirce表示出售的价格, sell_type表示出售的类型
	 */
	public function sellInfo()
	{
		$sellInfo = parent::sellInfo();
		
		if ( $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_TYPE] != ItemDef::ITEM_SELL_TYPE_SILVER )
		{
			throw new ConfigException('arm template id:%d item sell type is wrong!', $this->getItemTemplateID());
		}
		
		$reinforceCost = $this->getReinforceCost();
		$sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_PRICE] += intval($reinforceCost);
		return $sellInfo;
	}
	
	/**
	 * 物品的属性信息
	 *
	 * @return array
	 */
	public function info()
	{
		$info = array();
		//计算武器本身的数值
		foreach ( ArmDef::$ARM_ATTRS_CALC as $key => $value)
		{
			$info[$key] = ItemAttr::getItemAttr($this->getItemTemplateID(), $value[0]) +
			intval($this->getLevel() * ItemAttr::getItemAttr($this->getItemTemplateID(), $value[1]) / HeroConf::INC_RATIO);
		}
		foreach ( ArmDef::$ARM_ATTRS_CALC_ADDITION as $key => $value)
		{
			$info[$key] = intval( $this->getLevel() * ItemAttr::getItemAttr($this->getItemTemplateID(), $value) / HeroConf::INC_RATIO );
		}
	
		Logger::trace('arm:%d template_id:%d basic info:%s', $this->getItemID(), $this->getItemTemplateID(), $info);
	
		//计算固定潜能, 暂时没有使用随机潜能
		$potence = $this->getPotence();
		$potenceId = $this->getFixedPotenceId();
		foreach ($potence as $attrId => $attrValue)
		{
			$attrName = PropertyKey::$MAP_CONF[$attrId];
			if ( !isset($info[$attrName]) )
			{
				$info[$attrName] = 0;
			}
			$info[$attrName] += self::getPotenceValue($potenceId, $attrId, $attrValue);
		}
		
		if ($this->canDevelop())
		{
			$level = $this->getLevel();
			$develop = $this->getDevelop();
			$developAttrs = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_ATTRS);
			$developExtra = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_EXTRA);
			foreach ($developAttrs as $needDevelop => $attr)
			{
				if ($develop < $needDevelop)
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
					$info[$attrName] += intval($level * $attrValue / HeroConf::INC_RATIO);
				}
			}
			foreach ($developExtra as $needDevelop => $attr)
			{
				if ($develop < $needDevelop)
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
					$info[$attrName] += intval($attrValue);
				}
			}
		}

		Logger::DEBUG('arm:%d template_id:%d fixed potence info:%s',
		$this->getItemID(), $this->getItemTemplateID(), $info);

		return $info;
	}
	
	/**
	 * 装备重置
	 */
	public function reset()
	{
		unset($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE]);
		unset($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE]);
		unset($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE]);
		unset($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP]);
		
		//初始化物品强化等级
		$initLevel = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_INIT_LEVEL);
		if (empty($initLevel)) 
		{
			$initLevel = ArmDef::ARM_REINFORCE_LEVEL_DEFAULT;
		}
		$this->setLevel($initLevel);
		$this->setReinforceCost(0);
	}
	
	/**
	 *
	 * 得到装备的类型
	 *
	 * @return int
	 */
	public function getType()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_TYPE);
	}
	
	/**
	 * 得到装备的评分
	 * @return number
	 */
	public function getScore()
	{
		$level = $this->getLevel();
		if ($this->getDevelop() >= 0)
		{
			$scoreBase = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_SCORE);
		}
		else 
		{
			$scoreBase = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_SCORE_BASE);
		}
		$scoreAdd = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_SCORE_ADD);
		return $scoreBase + $scoreAdd * $level;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Item::getItemQuality()
	 */
	public function getItemQuality()
	{
		if ($this->getDevelop() >= 0)
		{
			return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_QUALITY);
		}
		else
		{
			return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_QUALITY);
		}
	}
	
	/**
	 * 
	 * 得到装备所属的套装ID
	 * 
	 * @return int
	 */
	public function getSuitId()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_SUIT);
	}
	
	/**
	 *
	 * 得到装备等级（武将）
	 *
	 * @return array
	 */
	public function getReqLevel()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_LEVEL);
	}
	
	/**
	 *
	 * 得到装备的强化费用
	 *
	 * @return int
	 */
	public function getReinforceCost()
	{
		if (!isset($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_COST])) 
		{
			$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_COST] = 0;
		}
		return $this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_COST];
	}
	
	/**
	 *
	 * 设置装备的强化费用
	 *
	 * @return int
	 */
	public function setReinforceCost($cost)
	{
		$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_COST] = $cost;
	}
	
	/**
	 *
	 * 得到装备的强化等级
	 *
	 * @return int
	 */
	public function getLevel()
	{
		return $this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_LEVEL];
	}
	
	/**
	 * 设置装备的强化等级
	 * 
	 * @param int $level
	 */
	public function setLevel($level)
	{
		$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_LEVEL] = $level;
	}
	
	/**
	 * 得到装备强化等级上限系数
	 * @return mixed
	 */
	public function getReinforceRate()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE_RATE);
	}
	
	/**
	 *
	 * 得到装备强化的需求
	 *
	 * @return array
	 */
	public function getReinforceReq()
	{
		$feeId = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_REINFORCE);
		if ( !isset(btstore_get()->REINFORCE_FEE[$feeId]) )
		{
			throw new ConfigException('invalid reinforce fee table id:%d templateid:%d', $feeId, $this->getItemTemplateID());
		}
		return btstore_get()->REINFORCE_FEE[$feeId];
	}
	
	/**
	 *
	 * 得到装备的固定潜能id
	 *
	 * @return int
	 */
	public function getFixedPotenceId()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE);
	}
	
	/**
	 *
	 * 得到装备的随机潜能id
	 *
	 * @return int
	 */
	public function getRandPotenceId()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE);
	}
	
	/**
	 *
	 * 得到装备固定洗练的花费
	 *
	 * @return int
	 */
	public function getFixedRefreshCost($type)
	{
		$potenceId = $this->getFixedPotenceId();
		return Potence::getRefreshCost($potenceId, $type);
	}

	/**
	 *
	 * 得到装备的兑换ID
	 */
	public function getExchangeId()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_EXCHANGE);
	}
	
	/**
	 *
	 * 得到橙装的进化表ID
	 */
	public function getFoundryId()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_FOUNDRY);
	}
	
	/**
	 * 得到装备的重生花费（金币）
	 */
	public function getRebornCost()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_REBORN_COST);
	}
	
	/**
	 * 得到装备的潜能属性数组
	 */
	public function getPotence()
	{
		$potence = array();
		if (!empty($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE]))
		{
			$potence = $this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		}
		return $potence;
	}
	
	/**
	 * 设置装备的潜能属性数组
	 * @param unknown $potence
	 */
	public function setPotence($potence)
	{
		$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE] = $potence;
	}
	
	/**
	 * 得到装备的洗练属性数组
	 * 
	 * @return array
	 */
	public function getFixedPotence()
	{
		$fixedPotence = array();
		if (!empty($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE]))
		{
			$fixedPotence = $this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE];
		}
		return $fixedPotence;
	}
	
	/**
	 * 设置装备的洗练属性数组
	 * 
	 * @param unknown $fixedPotence
	 */
	public function setFixedPotence($fixedPotence)
	{
		$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE] = $fixedPotence;
	}
	
	/**
	 *
	 * 得到潜能的值(数据库中储存的是该潜能属性的价值)
	 *
	 * @param int $potenceId
	 * @param int $attrId
	 * @param int $attrValue
	 *
	 */
	public static function getPotenceValue($potenceId, $attrId, $attrValue)
	{
		$value = floor($attrValue / Potence::getPotenceAttrValue($potenceId , $attrId));
		return $value < 0 ? 0 : $value;
	}
	
	/**
	 * 得到装备的所有潜能属性的价值总和
	 */
	public function getPotenceSum()
	{
		$sum = 0;
		$potence = $this->getPotence();
		foreach ($potence as $attrId => $attrValue)
		{
			if ($attrValue < 0)
			{
				$attrValue = 0;
			}
			$sum += $attrValue;
		}
		
		return $sum;
	}
	
	/**
	 * 得到装备潜能分解物品
	 * 
	 * @return $items
	 */
	public function getPotenceResolve()
	{
		$items = array();
		$sum = $this->getPotenceSum();
		$armResolve = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_RESOLVE);
		if (!empty($armResolve))
		{
			foreach ($armResolve as $itemTplId => $itemValue)
			{
				break;
			}
			$itemNum = intval($sum / $itemValue);
			if (!empty($itemNum)) 
			{
				$items = array($itemTplId => $itemNum);
			}
		}
		
		return $items;
	}
	
	public function getPotenceLimit()
	{
		$level = $this->getLevel();
		$init = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_INIT);
		$ratio = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_RATIO);
		$limit = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_POTENCE_LIMIT);
		return $init + intval($level / $ratio) * $limit;
	}
	
	/**
	 * 获得进阶等级
	 * 
	 * @return int
	 */
	public function getDevelop()
	{
		if ($this->canDevelop() && isset($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP]))
		{
			return $this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP];
		}
		else
		{
			return ArmDef::ARM_DEVELOP_DEFAULT;
		}
	}
	
	/**
	 * 设置进阶等级
	 * 
	 * @param int $develop
	 */
	public function setDevelop($develop)
	{
		$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP] = $develop;
	}
	
	/**
	 * 获得进阶等级上限
	 * 
	 * @return int
	 */
	public function getDevelopLimit()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_LIMIT);
	}
	
	/**
	 * 得到进阶花费
	 *
	 * @return array
	 */
	public function getDevelopExpend($develop)
	{
		//是从0级开始的
		$expend = ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP_EXPEND);
		return $expend[$develop]->toArray();
	}
	
	/**
	 * 获得进阶的总花费
	 *
	 * @return array
	 */
	public function getDevelopExpendSum()
	{
		$arrExpend = array();
		$develop = $this->getDevelop();
		for ($i = 0; $i <= $develop; $i++)
		{
			$arrExpend = array_merge($arrExpend, $this->getDevelopExpend($i));
		}
		return $arrExpend;
	}
	
	/**
	 *
	 * 是否可以随机洗潜能
	 *
	 * @return boolean						TRUE表示可以随机洗潜能,FALSE表示不能
	 */
	public function canRandomRefresh()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_RAND_REFRESH)
		== ArmDef::ARM_CAN_RANDOM_REFRESH;
	}
	
	/**
	 *
	 * 是否可以固定洗潜能
	 *
	 * @return boolean						TRUE表示可以固定洗潜能,FALSE表示不能
	 */
	public function canFixedRefresh()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_FIXED_REFRESH)
		== ArmDef::ARM_CAN_FIXED_REFRESH;
	}
	
	/**
	 * 是否可以进阶
	 * 
	 * @return bool
	 */
	public function canDevelop()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ArmDef::ITEM_ATTR_NAME_ARM_CAN_DEVELOP);
	}
	
	/**
	 *
	 * 物品强化
	 * 
	 * @param int $limit					强化等级
	 * @return boolean						TRUE表示强化成功, FALSE表示强化失败
	 */
	public function reinforce($level)
	{
		$oldLevel = $this->getLevel();
		if ( $oldLevel + $level > ArmDef::ARM_REINFORCE_LEVEL_MAX )
		{
			return FALSE;
		}
		else if ( $oldLevel > ArmDef::ARM_REINFORCE_LEVEL_MAX )
		{
			Logger::fatal('Item level is invalid:itemId:%d, itemTplId:%d, level:%d',
					$this->getItemID(), $this->getItemTemplateID(),$oldLevel);
			return FALSE;
		}
		$this->setLevel($oldLevel + $level);
		return TRUE;
	}	
	
	/**
	 *
	 * 随机洗属性
	 *
	 * @param int $type					洗练方式
	 * @return array
	 */
	public function randRefresh($type)
	{
		return $this->refresh($type, false);
	}
	
	/**
	 *
	 * 固定洗属性
	 *
	 * @param int $type					洗练方式
	 * @param int $num					次数
	 * @return array
	 */
	public function fixedRefresh($type, $num)
	{
		return $this->refresh($type, true, $num);
	}

	/**
	 * 洗练
	 * 潜能的洗练方式跟海贼的完全不同！
	 * 
	 * @param int $type						洗练方式
	 * @param boolean $fixed				是否固定洗练
	 * @param int $num						次数
	 * @return array
	 */
	private function refresh($type, $fixed = TRUE, $num = 1)
	{
		//得到固定潜能id
		$potence = $this->getPotence();
		$potenceId = $this->getFixedPotenceId();

		//随机洗属性
		if ( $fixed == FALSE && $this->canRandomRefresh() == TRUE )
		{
			if (empty($potence))
			{
				$potence = array();
				$this->setPotence($potence);
			}
			$rand = Potence::randPotence($potenceId, $type);
			$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE] = $rand;
			$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE] = $potence;
			$ret = $this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE];
		}
		else if ( $fixed == TRUE && $this->canFixedRefresh() == TRUE )
		{
			if (empty($potence)) 
			{
				$potence = array();
				$this->setPotence($potence);
			}
			Logger::trace('arm potenceArr:%s', $potence);
			$rand = Potence::randPotence($potenceId, $type);
			Logger::trace('rand potenceArr:%s', $rand);
			foreach ($rand as $attrId => $attrValue)
			{
				if (!empty($potence[$attrId])) 
				{
					$rand[$attrId] = $potence[$attrId];
				}
			}
			$limit = $this->getPotenceLimit();
			$refresh = Potence::refreshPotence($potenceId, $rand, $type, $limit, $num);
			Logger::trace('fixed refresh potenceArr:%s', $refresh);
			foreach ($refresh as $attrId => $attrValue)
			{
				$potence[$attrId] = $attrValue;
			}
			$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE] = $potence;
			$ret = $this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE];
		}
		else
		{
			throw new FakeException('itemTplId:%d can not be refreshed!', $this->getItemTemplateID());
		}
		foreach ( $ret as $attrId => $attrValue )
		{
			$ret[$attrId] = self::getPotenceValue($potenceId, $attrId, $attrValue);
		}
		return $ret;
	}
	
	public function fixedRefreshAffirm()
	{
		return $this->refreshAffirm(ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE);
	}
	
	public function randRefreshAffirm()
	{
		return $this->refreshAffirm(ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE);
	}

	/**
	 *
	 * 属性替换
	 *
	 * @param string $attrName
	 * @return boolean
	 */
	private function refreshAffirm($attrName)
	{
		if ( !isset($this->mItemText[$attrName]) )
		{
			return FALSE;
		}

		$this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE] = $this->mItemText[$attrName];
		unset($this->mItemText[$attrName]);
		
		if ( $attrName == ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE )
		{
			unset($this->mItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE]);
		}
		return TRUE;
	}
	
	/**
	 * 获得装备套装的属性加成和全身红装进阶等级额外属性加成
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
		
		// 统计套装和装备数组信息
		$arrArm = array();
		$arrSuit = array();
		foreach ($itemInfos as $itemInfo)
		{
			$itemTplId = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID];
			$itemType = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TYPE);
			//检查装备类型
			if ($itemType != ItemDef::ITEM_TYPE_ARM)
			{
				throw new FakeException('item type:%d is not a arm!', $itemType);
			}
			//检查装备是否重复了
			$armType = ItemAttr::getItemAttr($itemTplId, ArmDef::ITEM_ATTR_NAME_ARM_TYPE);;
			if (isset($arrArm[$armType])) 
			{
				throw new FakeException('item arm type:%d is duplicate!', $armType);
			}
			else 
			{
				$arrArm[$armType] = 1;	
			}
			
			$suitId = ItemAttr::getItemAttr($itemTplId, ArmDef::ITEM_ATTR_NAME_ARM_SUIT);
			if (empty($suitId)) 
			{
				continue;
			}
			if (!isset(btstore_get()->SUIT_ITEM[$suitId]))
			{
				throw new ConfigException('suit id:%d is not exist!item template id %d.', $suitId, $itemTplId);
			}
			
			if (!in_array($itemTplId, btstore_get()->SUIT_ITEM[$suitId][ArmDef::ITEM_ATTR_NAME_ARM_SUIT_ITEMS]->toArray())) 
			{
				throw new ConfigException('suit id:%d item array do not have item template id %d.', $suitId, $itemTplId);
			}
			
			if (!isset($arrSuit[$suitId])) 
			{
				$arrSuit[$suitId] = 1;
			}
			else 
			{
				$arrSuit[$suitId]++;
			}
		}
		
		$minDevelop = -2;
		foreach ($itemInfos as $itemInfo)
		{
			if (isset($itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP]))
			{
				$itemDevelop = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][ArmDef::ITEM_ATTR_NAME_ARM_DEVELOP];
			}
			else
			{
				$itemDevelop = ArmDef::ARM_DEVELOP_DEFAULT;
			}
			$minDevelop = $minDevelop == -2 ? $itemDevelop : min($minDevelop, $itemDevelop);
		}
		
		//检查所有装备类型是否正确
		if (count($arrArm) > ArmDef::ARM_TYPE_NUM) 
		{
			throw new FakeException('arm num limit is %d!', ArmDef::ARM_TYPE_NUM);
		}
		
		//套装属性加成
		$attrInfo = array();	
		foreach ($arrSuit as $suitId => $armNum)
		{
			$suitInfo = btstore_get()->SUIT_ITEM[$suitId][ArmDef::ITEM_ATTR_NAME_ARM_SUIT_ATTR];
			foreach ($suitInfo as $key => $attr)
			{
				if ($armNum < $key) 
				{
					break;
				}
				$attrInfo[] = $attr;
			}
		}
		
		//全身4件装备达到进阶N级有额外属性加成
		if (count($arrArm) == ArmDef::ARM_TYPE_NUM && $minDevelop > ArmDef::ARM_DEVELOP_DEFAULT) 
		{
			$developExtraAll = btstore_get()->ARM_EXTRA;
			foreach ($developExtraAll as $needDevelop => $attr)
			{
				if ($minDevelop < $needDevelop)
				{
					break;
				}
				$attrInfo[] = $attr;
			}
		}
		
		$attrInfo = Util::arrayAdd2V($attrInfo);
		$attrInfo = HeroUtil::adaptAttr($attrInfo);

		return $attrInfo;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */