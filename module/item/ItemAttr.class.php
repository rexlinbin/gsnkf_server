<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ItemAttr.class.php 55488 2013-07-15 10:28:56Z TiantianZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/ItemAttr.class.php $
 * @author $Author: TiantianZhang $(jhd@babeltime.com)
 * @date $Date: 2013-07-15 10:28:56 +0000 (Mon, 15 Jul 2013) $
 * @version $Revision: 55488 $
 * @brief
 *
 **/

class ItemAttr
{
	/**
	 *
	 * 得到物品的属性
	 * 
	 * @param int $itemTplId            		 物品的模板ID
	 * @param string $attrName                  物品的属性名
	 *
	 * @throws Exception 			                                如果物品不存在这个属性名，则会抛出异常
	 *
	 * @return mixed				                                物品的属性值
	 */
	static public function getItemAttr($itemTplId, $attrName)
	{
		// 判断是否存在这个模板ID
		if ( !isset(btstore_get()->ITEMS[$itemTplId]) )
		{
			throw new ConfigException('invalid item tempalte id = %d', $itemTplId);
		}
		// 从btstore里面读取指定模板ID的数据
		$item = btstore_get()->ITEMS[$itemTplId];
		// 是否有这个属性名字段
		if ( isset($item[$attrName]) )
		{
			// 有的话，直接返回属性值
			return $item[$attrName];
		}
		else
		{
			// 不存在这个属性名，就抛出异常
			throw new FakeException('Access invalid attibute! item tempalte id = %d, attr name = %s', $itemTplId, $attrName);
		}
	}

	/**
	 *
	 * 得到多个物品的属性
	 * 
	 * @param int $itemTplId             		物品的模板ID
	 * @param array(string) $attrNames			物品的属性名数组
	 *
	 * @throws Exception 			                                如果物品不存在这个属性名，则会抛出异常
	 *
	 * @return $attrInfos				                      物品的属性值数组
	 */
	static public function getItemAttrs($itemTplId, $attrNames)
	{
		// 判断是否存在这个模板ID
		if ( !isset(btstore_get()->ITEMS[$itemTplId]) )
		{
			throw new ConfigException('invalid item tempalte id = %d', $itemTplId);
		}
		// 从btstore里面读取指定模板ID的数据
		$item = btstore_get()->ITEMS[$itemTplId];
		$attrInfos = array();
		// 遍历每个属性名
		foreach ( $attrNames as $attrName )
		{
			// 是否有这个属性名字段
			if ( isset($item[$attrName]) )
			{
				// 有的话，直接返回属性值
				$attrInfos[$attrName] = $item[$attrName];
			}
			else
			{
				// 不存在这个属性名，就抛出异常
				throw new FakeException('Access invalid attibute! item tempalte id = %d, attr name = %s', $itemTplId, $attrName);
			}
		}
		return $attrInfos;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */