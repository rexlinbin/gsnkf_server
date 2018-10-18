<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ItemDAO.class.php 69135 2013-10-16 06:14:46Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/ItemDAO.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2013-10-16 06:14:46 +0000 (Wed, 16 Oct 2013) $
 * @version $Revision: 69135 $
 * @brief
 *
 **/



class ItemDAO
{
	/**
	 *
	 * select物品
	 *
	 * @param array $select
	 * @param array $wheres
	 *
	 * @return array Item数据
	 */
	public static function selectItem($select, $wheres)
	{
		$data = new CData();
		$data->select($select)->from(ItemDef::ITEM_TABLE_NAME);
		foreach ( $wheres as $where )
		{
			$data->where($where);
		}
		$ret = $data->query();
		return $ret;
	}

	/**
	 *
	 * 插入物品
	 * @param array $values
	 * @throws Exception
	 */
	public static function insertItem($values)
	{
		$data = new CData();
		$ret = $data->insertInto(ItemDef::ITEM_TABLE_NAME)->values($values)->query();
		if ( $ret[DataDef::AFFECTED_ROWS] != 1 )
		{
			throw new Exception('item insert fake');
		}
		return TRUE;
	}

	/**
	 *
	 * 更新物品数据
	 * @param array $where
	 * @param array $values
	 * @throws Exception
	 */
	public static function updateItem($where, $values)
	{
		$data = new CData();
		$ret = $data->update(ItemDef::ITEM_TABLE_NAME)->set($values)->where($where)->query();
		if ( $ret[DataDef::AFFECTED_ROWS] != 1 )
		{
			throw new Exception('item update fake');
		}
		return TRUE;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
