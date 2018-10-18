<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ItemStore.class.php 69139 2013-10-16 06:17:05Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/ItemStore.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2013-10-16 06:17:05 +0000 (Wed, 16 Oct 2013) $
 * @version $Revision: 69139 $
 * @brief
 *
 **/

class ItemStore
{
	/**
	 *
	 * 增加新的物品到系统中
	 * @param int $item_id					物品ID
	 * @param int $item_template_id			物品模板ID
	 * @param int $item_time				物品产生时间
	 * @param int $item_num					物品数量
	 * @param array $item_text				物品额外信息
	 *
	 * @throws Exception
	 */
	public static function addItem($item_id, $item_template_id, $item_time, $item_num, $item_text = array())
	{
		if ( !is_array($item_text) )
		{
			throw new Exception('ItemStore::addItem item_text is not array!');
		}

		$values = array();
		$values[ItemDef::ITEM_SQL_ITEM_ID] = $item_id;
		$values[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID] = $item_template_id;
		$values[ItemDef::ITEM_SQL_ITEM_NUM] = $item_num;
		$values[ItemDef::ITEM_SQL_ITEM_TIME] = Util::getTime();
		$values[ItemDef::ITEM_SQL_ITEM_DELTIME] = 0;
		$values[ItemDef::ITEM_SQL_ITEM_TEXT] = $item_text;
		return ItemDAO::insertItem($values);
	}

	/**
	 *
	 * 更新物品
	 * @param int $item_id					物品ID
	 * @param array $values					需要更新的物品数据
	 *
	 * @throws Exception					如果需要更新的列不在允许的列表内，则会throw execption
	 *
	 * @return boolean						TRUE表示更新成功, FALSE表示失败
	 */
	public static function updateItem($item_id, $values)
	{
		foreach ( array_keys($values) as $key )
		{
			if ( !in_array($key, ItemDef::$ITEM_ALLOW_UPDATE_COL) )
			{
				throw new Exception('ItemStore::updateItem: forbidden key:%s', $key);
				return FALSE;
			}
		}
		$where = array(ItemDef::ITEM_SQL_ITEM_ID, '=', $item_id);
		return ItemDAO::updateItem($where, $values);
	}

	/**
	 *
	 * 删除物品
	 * @param int $item_id				物品ID
	 *
	 * @return boolean					TRUE表示删除成功, FALSE表示删除失败
	 */
	public static function deleteItem($item_id)
	{
		$values = array (ItemDef::ITEM_SQL_ITEM_DELTIME => Util::getTime());
		$where = array(ItemDef::ITEM_SQL_ITEM_ID, '=', $item_id);
		return ItemDAO::updateItem($where, $values);
	}

	/**
	 *
	 * 得到物品(多个版本)
	 * @param array(int) $item_ids		物品IDs
	 *
	 * @return array(array)
	 */
	public static function getItems($item_ids)
	{
		$count = count($item_ids);

		$select = array(
			ItemDef::ITEM_SQL_ITEM_ID,
			ItemDef::ITEM_SQL_ITEM_NUM,
			ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID,
			ItemDef::ITEM_SQL_ITEM_TIME,
			ItemDef::ITEM_SQL_ITEM_TEXT,
		);
		
		$loop_time = ceil($count / CData::MAX_FETCH_SIZE);

		$array_items = array();
		for ( $i = 0; $i < $loop_time; $i++ )
		{
			$array = array_slice($item_ids, $i*CData::MAX_FETCH_SIZE, CData::MAX_FETCH_SIZE);
			$wheres = array(
				array(ItemDef::ITEM_SQL_ITEM_DELTIME, '=', 0),
				array(ItemDef::ITEM_SQL_ITEM_ID, 'IN', $array),
			);
			$items = ItemDAO::selectItem($select, $wheres);
			foreach ($items as $item)
			{
				$array_items[$item[ItemDef::ITEM_SQL_ITEM_ID]] = $item;
			}
		}

		return $array_items;
	}

	/**
	 *
	 * 得到物品
	 * @param int $item_id				物品ID
	 *
	 * @return array
	 */
	public static function getItem($item_id)
	{
		$select = array(
			ItemDef::ITEM_SQL_ITEM_ID,
			ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID,
			ItemDef::ITEM_SQL_ITEM_NUM,
			ItemDef::ITEM_SQL_ITEM_TIME,
			ItemDef::ITEM_SQL_ITEM_TEXT,
		);
		$wheres = array(
			array(ItemDef::ITEM_SQL_ITEM_DELTIME, '=', 0),
			array(ItemDef::ITEM_SQL_ITEM_ID, '=', $item_id)
		);
		$item = ItemDAO::selectItem($select, $wheres);
		if ( !empty($item) )
		{
			return $item[0];
		}
		else
		{
			return array();
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */