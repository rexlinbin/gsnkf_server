<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BookItem.class.php 53822 2013-07-04 02:53:16Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/BookItem.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2013-07-04 02:53:16 +0000 (Thu, 04 Jul 2013) $
 * @version $Revision: 53822 $
 * @brief
 *
 **/

class BookItem extends Item
{
	/**
	 * (non-PHPdoc)
	 * @see Item::itemInfo()
	 */
	public function itemInfo()
	{
		return parent::itemInfo();
	}

	/**
	 * 给战斗系统提供数值
	 * 
	 * @return array mixed 
	 */
	public function info()
	{
		$attrs = $this->getAttrs();
		$attrExtra = $this->getAttrExtra();
		if (empty($attrs) && empty($attrExtra))
		{
			return array();
		}
		if (empty($attrs) || empty($attrExtra))
		{
			throw new ConfigException('config err with attrs and attrExtra! itemid:%d templateid:%d',
					$this->getItemID(), $this->getItemTemplateID());
		}
		$info = array();
		$level = $this->getLevel();
		foreach ( $attrs as $attrId => $attrValue )
		{
			if (!isset(PropertyKey::$MAP_CONF[$attrId]))
			{
				throw new ConfigException('no attrId! itemid:%d templateid:%d attrid:%d', 
						$this->getItemID(), $this->getItemTemplateID(), $attrId);
			}
			$attrName = PropertyKey::$MAP_CONF[$attrId];
			
			$extraValue = 0;
			if (isset($attrExtra[$attrId]))
			{
				$extraValue = $attrExtra[$attrId] * $level;
			}
		
			if (isset($info[$attrName]))
			{
				$info[$attrName] += $attrValue + $extraValue;
			}
			else
			{
				$info[$attrName] = $attrValue + $extraValue;
			}
		}
		Logger::trace('Book:%d template_id:%d basic info:%s', 
		$this->getItemID(), $this->getItemTemplateID(), $info);
		return $info;
	}
	
	private function getType()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_TYPE);
	}
	
	private function getAttrs()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_ATTRS);
	}
	
	/**
	 * 获得技能书属性的成长数组
	 * 
	 * @throws ConfigException
	 * @return array mixed
	 */
	private function getAttrExtra()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ITEM_ATTR_NAME_BOOK_LEVEL_EXTRA);
	}
	
	/**
	 *
	 * 得到当前等级
	 *
	 * @return int $level
	 */
	public function getLevel()
	{
		$curExp = $this->getCurExp();
	
		if ( empty($curExp) )
		{
			return ItemDef::ITEM_ATTR_NAME_BOOK_MIN_LEVEL;
		}
		else
		{
			$expTable = $this->getLevelTable();
	
			$level = ItemDef::ITEM_ATTR_NAME_BOOK_MIN_LEVEL;
			for ( $i = $level + 1; $i <= $this->getMaxLevel(); $i++ )
			{
				if (!isset($expTable[$i]))
				{
					break;
				}
				if ($curExp < $expTable[$i] )
				{
					break;
				}
				else
				{
					$curExp -= $expTable[$i];
					$level++;
				}
			}
			return $level;
		}
	}
	
	/**
	 *
	 * 得到技能书最大等级
	 *
	 * @return int
	 *
	 */
	public function getMaxLevel()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_MAX_LEVEL);
	}
	
	/**
	 *
	 * 得到基础的经验值
	 *
	 * @return int
	 */
	public function getBaseExp()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_EXP);
	}
	
	/**
	 *
	 * 得到当前的经验值
	 *
	 * @return int
	 */
	public function getCurExp()
	{
		if ( !isset($this->mItemText[ItemDef::ITEM_ATTR_NAME_EXP]) )
		{
			$this->mItemText[ItemDef::ITEM_ATTR_NAME_EXP] = 0;
		}
		$curExp = $this->mItemText[ItemDef::ITEM_ATTR_NAME_EXP];
	}
	
	/**
	 *
	 * 得到总的经验值
	 *
	 * @return int
	 */
	public function getAllExp()
	{
		return $this->getCurExp() + $this->getBaseExp();
	}

	/**
	 *
	 * 增加技能书经验
	 *
	 * @param int $exp
	 * @return boolean
	 *
	 */
	public function addExp($exp)
	{
		if ( $exp < 0 )
		{
			throw new InterException("Book add exp = 0!");
		}
	
		if ( $this->getLevel() >= $this->getMaxLevel() )
		{
			return FALSE;
		}
	
		$this->mItemText[ItemDef::ITEM_ATTR_NAME_EXP] = $this->getCurExp() + $exp;
	
		return TRUE;
	}
	
	/**
	 *
	 * 技能列表
	 *
	 * @return array
	 * <code>
	 * 	{
	 * 		skills:array
	 * 		[
	 * 			skill_id:int
	 * 		]
	 *  }
	 * </code>
	 */
	public function getSkills()
	{
		$skills = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_SKILLS);
		if (empty($skills)) 
		{
			return array();
		}
		
		$skillIds = array();
		$level = $this->getLevel();
		foreach ($skills as $skill)
		{
			if (!isset($skill[$level]))
			{
				throw new ConfigException('getSkills level err! itemid:%d templateid:%d level:%d', 
						$this->getItemID(), $this->getItemTemplateID(), $level);
			}
			$skillIds[] = $skill[$level];
		}

		return $skillIds;
	}
	
	private function getSkillBuffGroup()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_SKILL_BUFF_GROUP);
	}
	
	/**
	 *
	 * 得到skill和buff列表
	 *
	 * @return array
	 * <code>
	 * {
	 * 		'arrImmunedEffect':array
	 * 		'parrySkill':int
	 * 		'dodgeSkill':int
	 * 		'deathSkill':int
	 * 		'arrAttackSkill':array
	 * 		'arrRageSkill':array
	 * 		'arrParrySkill':array
	 * 		'arrDodgeSkill':array
	 * 		'arrDeathSkill':array
	 * 		'arrAttackBuff':array
	 * 		'arrRageBuff':array
	 * 		'arrDeathBuff':array
	 * 		'arrParryBuff':array
	 * 		'arrDodgeBuff':array
	 * }
	 * </code>
	 */
	public function getSkillBuff()
	{
		$skillBuffGroup = $this->getSkillBuffGroup();
		if (empty($skillBuffGroup)) 
		{
			return array();
		}
		$level = $this->getLevel();
		if (!isset($skillBuffGroup[$level]))
		{
			throw new ConfigException('getSkillBuff err! level is not set! templateid:%d level:%d', $this->getItemTemplateID(), $level);
		}
		$skillBuffGroupId = $skillBuffGroup[$level];
		
		if ( !isset(btstore_get()->BOOKSKILLBUFF[$skillBuffGroupId]) )
		{
			throw new ConfigException('invalied skill group id:%d! templateid:%d', $skillBuffGroupId, $this->getItemTemplateID());
		}
		
		return btstore_get()->BOOKSKILLBUFF[$skillBuffGroupId];
	}
	
	/**
	 *
	 * 得到技能书可以装备的位置信息
	 *
	 * @return array
	 */
	public function getEquipSlot()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_EQUIP_SLOT);
	}
	
	/**
	 * 该恶魔果实是否可升级
	 * @return mixed
	 */
	public function canLevelUp()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_CAN_LEVEL_UP);
	}
	
	private function getLevelTable()
	{
		if ($this->canLevelUp()) 
		{
			$expTableId = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_LEVEL_TABLE);
			if ( !isset(btstore_get()->EXP_TBL[$expTableId]) )
			{
				throw new ConfigException('invalid exp table id:%d templateid:%d', $expTableId, $this->getItemTemplateID());
			}
			return btstore_get()->EXP_TBL[$expTableId];
		}
		else 
		{
			return 0;
		}
		
	}
	
	public function canErasure()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE);
	}	

	public function getErasureReq()
	{
		if ($this->canErasure()) 
		{
			return array (
			ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_SILVER => ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_SILVER),
			ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_GOLD => ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_GOLD),
			ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_ITEMS => ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_BOOK_ERASURE_ITEMS)->toArray(),
			);
		}
		else 
		{
			return array();
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
