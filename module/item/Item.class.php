<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Item.class.php 121013 2014-07-17 06:03:59Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/Item.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2014-07-17 06:03:59 +0000 (Thu, 17 Jul 2014) $
 * @version $Revision: 121013 $
 * @brief
 *
 **/

class Item
{
	/**
	 *
	 * 物品ID, 系统唯一
	 * @var int
	 */
	protected $mItemId;

	/**
	 *
	 * 物品模板ID
	 * @var int
	 */
	protected $mItemTplId;

	/**
	 *
	 * 物品数量
	 * @var int
	 */
	protected $mItemNum;

	/**
	 *
	 * 物品产生时间
	 * @var int
	 */
	protected $mItemTime;
	
	/**
	 *
	 * 物品删除时间
	 * @var int
	 */
	protected $mItemDeltime;
	
	/**
	 *
	 * 物品属性信息, AMF encode
	 * @var string
	 */
	protected $mItemText;

	/**
	 *
	 * Item 初始化函数
	 * 
	 * @param object $item
	 * @throws Exception				如果该物品不存在,则throw exception
	 *
	 * @return NULL
	 */
	public function Item($item)
	{
		if ( empty($item) )
		{
			Logger::FATAL('Item::Item is NULL');
			throw new Exception('fake');
		}
		$this->mItemId = $item[ItemDef::ITEM_SQL_ITEM_ID];
		$this->mItemTplId = $item[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID];
		$this->mItemNum = $item[ItemDef::ITEM_SQL_ITEM_NUM];
		$this->mItemTime = $item[ItemDef::ITEM_SQL_ITEM_TIME];
		$this->mItemText = $item[ItemDef::ITEM_SQL_ITEM_TEXT];
	}

	/**
	 *
	 * 得到物品ID
	 *
	 * @return int
	 */
	public function getItemID()
	{
		return $this->mItemId;
	}

	/**
	 *
	 * 得到物品的模板ID
	 *
	 * @return int
	 */
	public function getItemTemplateID()
	{
		return $this->mItemTplId;
	}

	/**
	 *
	 * 得到物品的数量
	 *
	 * @return int
	 */
	public function getItemNum()
	{
		return $this->mItemNum;
	}

	/**
	 *
	 * 设置物品的数量
	 * 
	 * @param int $itemNum
	 *
	 * @return NULL
	 */
	public function setItemNum($itemNum)
	{
		$this->mItemNum = $itemNum;
	}

	/**
	 *
	 * 得到物品的生成时间
	 *
	 * @return int
	 */
	public function getItemTime()
	{
		return $this->mItemTime;
	}
	
	/**
	 *
	 * 得到物品的删除时间
	 *
	 * @return int
	 */
	public function getItemDelTime()
	{
		return $this->mItemDeltime;
	}

	/**
	 *
	 * 得到物品的属性
	 *
	 * @return string
	 */
	public function getItemText()
	{
		return $this->mItemText;
	}

	/**
	 *
	 * 设置物品的属性
	 * 
	 * @param string $itemText
	 *
	 * @return NULL
	 */
	public function setItemText($itemText)
	{
		$this->mItemText = $itemText;
	}
	
	public function isLock()
	{
		return isset($this->mItemText[ItemDef::ITEM_TEXT_LOCK]) ? 1 : 0;
	}
	
	public function lock()
	{
		$this->mItemText[ItemDef::ITEM_TEXT_LOCK] = 1;
	}
	
	public function unlock()
	{
		unset($this->mItemText[ItemDef::ITEM_TEXT_LOCK]);
	}

	/**
	 *
	 * 得到物品的类型
	 *
	 * @return int
	 */
	public function getItemType()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_TYPE);
	}

	/**
	 *
	 * 得到物品的品质
	 *
	 * @return int
	 */
	public function getItemQuality()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_QUALITY);
	}

	/**
	 *
	 * 判断物品是否可售
	 *
	 * @return boolean		TRUE表示可以出售，FALSE表示不可以出售
	 */
	public function canSell()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_SELL_TYPE)
			!= ItemDef::ITEM_CAN_NOT_SELL;
	}

	/**
	 *
	 * 得到物品的出售信息
	 *
	 * @return array		sell_price表示出售的价格, sell_type表示出售的类型
	 */
	public function sellInfo()
	{
		$ret[ItemDef::ITEM_ATTR_NAME_SELL_PRICE] = ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_SELL_PRICE);
		$ret[ItemDef::ITEM_ATTR_NAME_SELL_TYPE] = ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_SELL_TYPE);
		return $ret;
	}

	/**
	 *
	 * 判断物品是否可用
	 *
	 * @return boolean		TRUE 表示可以使用，FALSE表示不可以使用
	 */
	public function canUse()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::Item_ATTR_NAME_USE)
			== ItemDef::ITEM_CAN_USE;
	}

	/**
	 *
	 * 物品使用需求
	 *
	 * @return array
	 */
	public function useReqInfo()
	{
		return array();
	}

	/**
	 *
	 * 使用获得物品
	 * @see 本函数为抽象函数, 子类需要实现该函数
	 */
	public function useAcqInfo()
	{
		Logger::FATAL("invoke fall down item basic class!item_template_id=%s!", $this->mItemTplId);
		return FALSE;
	}

	/**
	 *
	 * 判断物品是否可以摧毁
	 *
	 * @return boolean		TRUE 表示可以摧毁, 否则表示不可以摧毁
	 */
	public function canDestory()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_DESTORY)
			== ItemDef::ITEM_CAN_DESTORY;
	}

	/**
	 *
	 * 判断物品是否可以叠加
	 *
	 * @return boolean		TRUE 表示可以叠加, 否则表示不可以叠加
	 */
	public function canStackable()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_STACKABLE)
			!= ItemDef::ITEM_CAN_NOT_STACKABLE;
	}

	/**
	 *
	 * 得到物品的叠加上限
	 *
	 * @return int
	 */
	public function getStackable()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_STACKABLE);
	}
	
	/**
	 *
	 * 删除物品
	 *
	 * @return boolean		TRUE 表示删除成功, FALSE表示删除失败
	 */
	public function deleteItem()
	{
		$this->mItemId = 0;
		$this->mItemTplId = 0;
		$this->mItemNum = 0;
		$this->mItemTime = 0;
		$this->mItemDeltime = Util::getTime();
		$this->mItemText = '';
		return TRUE;
	}

	/**
	 *
	 * 物品信息
	 *
	 * @param boolean					TRUE表示简单版本,供最终返回给前端的接口使用
	 *
	 * @return array
	 * <code>
	 * 	[
	 * 			item_id:int
	 * 			item_template_id:int
	 * 			item_num:int
	 * 			item_time:int
	 * 			va_item_text:int
	 * 	]
	 * </code>
	 */
	public function itemInfo()
	{
		return array(
			ItemDef::ITEM_SQL_ITEM_ID 				=> $this->mItemId,
			ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID 		=> $this->mItemTplId,
			ItemDef::ITEM_SQL_ITEM_NUM				=> $this->mItemNum,
			ItemDef::ITEM_SQL_ITEM_TIME				=> $this->mItemTime,
			ItemDef::ITEM_SQL_ITEM_TEXT				=> $this->mItemText,
		);
	}

	/**
	 *
	 * 生成物品扩展属性
	 * 
	 * @param int $itemTplId 			物品模板id
	 */
	public static function createItem($itemTplId)
	{
		return array();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
