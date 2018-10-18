<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Drop.class.php 90983 2014-02-21 03:59:10Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/drop/Drop.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2014-02-21 03:59:10 +0000 (Fri, 21 Feb 2014) $
 * @version $Revision: 90983 $
 * @brief
 *
 **/

class Drop
{
	/**
	 * 获得可能掉落的所有类型id
	 * 
	 * @param int $dropId
	 * @return  包括以下五种
	 * array(
	 * 0=>array($itemTemplateId,,,)
	 * 1=>array($heroTid,,,)
	 * 2=>array($addSilver,,,)
	 * 4=>array($addSoul,,,)
	 * 5=>array($treasFragItemTplId,,,)
	 * )
	 * @see 得到的是模板ID, 并且没有执行任何的Add操作！
	 */
	public static function getDropInfo($dropId)
	{
		Logger::trace('Drop::getDropInfo is Start, dropId:%d', $dropId);
		
		//无效掉落表id
		if (empty(btstore_get()->DROP_ITEM[$dropId]))
		{
			throw new FakeException('drop id:%d is not exist!', $dropId);
		}
		$drop = btstore_get()->DROP_ITEM[$dropId];
		
		$arrRet = array();
		$dropType = $drop[DropDef::DROP_TYPE];
		if (DropDef::DROP_TYPE_MIXED == $dropType)
		{
			foreach ($drop[DropDef::DROP_LIST] as $key => $value)
			{
				$ret = self::getDropInfo($value[DropDef::DROP_ITEM_TEMPLATE]);
				foreach ($ret as $type => $info)
				{
					if (!isset($arrRet[$type]))
					{
						$arrRet[$type] = array();
					}
					$arrRet[$type] = array_merge($arrRet[$type], $info);
					$arrRet[$type] = array_unique($arrRet[$type]);
				}
			}
		}
		else 
		{
			foreach ($drop[DropDef::DROP_LIST] as $key => $value)
			{
				$ret[] = $value[DropDef::DROP_ITEM_TEMPLATE];
			}
			$arrRet[$dropType] = $ret;
		}
		
		Logger::trace('Drop::getDropInfo is End.');
		
		return $arrRet;
	}
	
	/**
	 * 判断是否可能会掉落指定类型
	 * 
	 * @param int $dropId			掉落表id
	 * @param int $dropType			掉落类型
	 * @param int $n				递归层次，默认1
	 * @return boolean true or false
	 */
	public static function isDropSpecial($dropId, $dropType, $n = 1)
	{
		Logger::trace('Drop::isDropSpecial is Start.');
		$dropId = intval($dropId);
		Logger::trace('drop id is :%d', $dropId);
		
		if (empty($dropId)) 
		{
			return false;
		}
		//非有效掉落类型
		if (!in_array($dropType, DropDef::$DROP_VALID_TYPES)) 
		{
			throw new FakeException('drop type:%d is invalid!', $dropType);
		}
		//无效掉落表id
		if ( empty(btstore_get()->DROP_ITEM[$dropId]) )
		{
			throw new ConfigException('drop id:%d is not exist!', $dropId);
		}
		$drop = btstore_get()->DROP_ITEM[$dropId];
		//是判定的类型
		if ($dropType == $drop[DropDef::DROP_TYPE]) 
		{
			return true;
		}
		//非判定类型，但是混合掉落类型
		if (DropDef::DROP_TYPE_MIXED == $drop[DropDef::DROP_TYPE]) 
		{
			//当达到第二层时还是混合掉落就报错
			if (empty($n)) 
			{
				throw new ConfigException('more than two recursion call!');
			}
			foreach ($drop[DropDef::DROP_LIST] as $key => $value)
			{
				$ret = self::isDropSpecial($value[DropDef::DROP_ITEM_TEMPLATE], $dropType, $n - 1);
				if ($ret == true) 
				{
					return $ret;
				}
			}
			return false;
		}
		
		Logger::trace('Drop::isDropSpecial is End.');	
		return false;
	}
	/**
	 * 掉落物品，不支持混合掉落
	 * 执行掉落表逻辑,得到随机后掉落的物品列表
	 *
	 * @param int $dropId			物品掉落表ID
	 * @param int $dropType			掉落类型，默认物品类型
	 * @param int $dropExclude		排除的掉落数据 array(tplId),暂时支持物品和武将2种类型，默认空
	 * @return  以下五种之一
	 * array(itemTemplateId => itemNum)
	 * array(heroTid => heroNum)
	 * array(0 => $dropSilver)
	 * array(0 => $dropSoul)
	 * array(treasFragItemTplId => num)
	 * 
	 * @see 得到的是模板ID, 并且没有执行任何的Add操作！
	 */
	public static function dropItem($dropId, $dropType = dropdef::DROP_TYPE_ITEM, $dropExclude = null)
	{
		Logger::trace('Drop::dropItem is Start.');
		
		if (DropDef::DROP_TYPE_MIXED == $dropType) 
		{
			throw new FakeException('wrong call! The function can not drop mixed!');
		}
		
		if (empty($dropId)) 
		{
			throw new FakeException('wrong param, empty drop id!');
		}
		
		$ret = self::dropOnce($dropId, $dropExclude);
		
		if ($dropType !=  key($ret)) 
		{
			throw new FakeException('drop type is wrong! correct type is:%d', key($ret));
		}
		
		Logger::trace("drop items:%s", $ret);
		Logger::trace('Drop::dropItem is End.');
		return current($ret);
	}
	
	/**
	 * 掉落物品,支持混合掉落
	 * 执行掉落表逻辑,得到随机后掉落的物品列表
	 * 兼容dropItem
	 *
	 * @param int $dropId			物品掉落表ID
	 * @param int $dropExclude		排除的掉落数据 array(tplId),暂时支持物品和武将2种类型，默认空
	 * @param int $n				递归层次，默认2
	 * @return  包括以下三种
	 * array(
	 * 0=>array(itemTemplateId => itemNum)
	 * 1=>array(heroTid => heroNum)
	 * 2=>array(0 => $dropSilver)
	 * 4=>array(0 => $dropSoul)
	 * 5=>array(treasFragItemTplId => num)
	 * )
	 * @see 得到的是模板ID, 并且没有执行任何的Add操作！
	 */
	public static function dropMixed($dropId, $dropExclude = null, $n = 2)
	{
		Logger::trace('Drop::dropMixed is Start. recursion:%d', $n);
		
		if (empty($dropId))
		{
			throw new FakeException('wrong param, empty drop id!');
		}
		
		$ret = self::dropOnce($dropId, $dropExclude);
		
		if (!isset($ret[DropDef::DROP_TYPE_MIXED]))
		{
			return $ret;
		}
		
		//当达到第三层时还是混合掉落就报错
		if (empty($n))
		{
			throw new ConfigException('more than three recursion call!');
		}
		
		$dropMixed = array();
		$drop = current($ret);
		foreach ($drop as $dpId => $dpNum)
		{		
			for ($i = 0; $i < $dpNum; $i++)
			{
				$arrRet = self::dropMixed($dpId, $dropExclude, $n - 1);
				$dropMixed = Util::arrayAdd3V(array($dropMixed, $arrRet));
			}
		}
		
		Logger::trace("drop mixed:%s", $dropMixed);
		Logger::trace('Drop::dropMixed is End.');
		return $dropMixed;
	}
	
	/**
	 * 一次掉落
	 * 
	 * @param int $dropId			物品掉落表ID
	 * @param int $dropExclude		排除的掉落数据 array(tplId),暂时支持物品和武将2种类型
	 * @return  返回值分别是以下5种之一
	 * 0=>array(itemTemplateId => itemNum)
	 * 1=>array(heroTid => heroNum)
	 * 2=>array(0 => $dropSilver)
	 * 3=>array(dropId => dropNum)
	 * 4=>array(0 => $dropSoul)
	 * 5=>array(treasFragItemTplId => num)
	 */
	private static function dropOnce($dropId, $dropExclude)
	{
		Logger::trace('Drop::dropOnce is Start.');
		
		$dropId = intval($dropId);
		Logger::trace('drop id is :%d', $dropId);
		
		if (empty($dropId)) 
		{
			throw new FakeException('wrong param, empty drop id!');
		}
		
		//根据掉落表,得到掉落表ID
		if ( empty(btstore_get()->DROP_ITEM[$dropId]) )
		{
			throw new FakeException('drop id:%d is not exist', $dropId);
		}
		$drop = btstore_get()->DROP_ITEM[$dropId]->toArray(); //这里需要toArray，在采样函数中需要对其unset
		
		$dropInfo = array();
		
		//计算掉落的数量
		$keys = Util::backSample($drop[DropDef::DROP_NUM_LIST], 1);
		$dropNum = $drop[DropDef::DROP_NUM_LIST][$keys[0]][DropDef::DROP_NUM];
		
		if ( $dropNum == 0 )
		{
			Logger::debug('drop nothing! check config!');
			return array($drop[DropDef::DROP_TYPE] => $dropInfo);
		}
		
		//排除不需要的掉落数据
		if (!empty($dropExclude)) 
		{
			foreach ($drop[DropDef::DROP_LIST] as $key => $value)
			{
				if (in_array($value[DropDef::DROP_ITEM_TEMPLATE], $dropExclude) == true) 
				{
					unset($drop[DropDef::DROP_LIST][$key]);
				}
			}
		}
		
		//计算掉落的物品列表, 1是放回抽样，0是不放回抽样
		if ($drop[DropDef::DROP_RULE] == 1)
		{
			$keys = Util::backSample($drop[DropDef::DROP_LIST], $dropNum);
		}
		else
		{
			$keys = Util::noBackSample($drop[DropDef::DROP_LIST], $dropNum);
		}
		
		if (empty($keys)) 
		{
			Logger::warning('drop nothing! check config!');
			return array($drop[DropDef::DROP_TYPE] => $dropInfo);
		}
		
		// 如果是掉落银两，规则如下
		if (DropDef::DROP_TYPE_SILVER == $drop[DropDef::DROP_TYPE]
		|| DropDef::DROP_TYPE_SOUL == $drop[DropDef::DROP_TYPE]) 
		{
			$dropSum = 0;
			foreach ( $keys as $key )
			{
				$base = $drop[DropDef::DROP_LIST][$key][DropDef::DROP_ITEM_NUM];
				$add = $drop[DropDef::DROP_LIST][$key][DropDef::DROP_ITEM_TEMPLATE];
				$each = rand(intval((1 - $add / UNIT_BASE) * $base), intval((1 + $add / UNIT_BASE) * $base));
				$dropSum += $each;
			}
			$dropInfo[0] = $dropSum;//返回一个数组，保持和物品的结果一致
		}
		else 
		{
			// 如果是掉落物品和武将,掉落表，规则如下
			foreach ( $keys as $key )
			{
				if( isset( $dropInfo[$drop[DropDef::DROP_LIST][$key][DropDef::DROP_ITEM_TEMPLATE]] ) )
				{
					$dropInfo[$drop[DropDef::DROP_LIST][$key][DropDef::DROP_ITEM_TEMPLATE]] += $drop[DropDef::DROP_LIST][$key][DropDef::DROP_ITEM_NUM];
				}
				else 
				{
					$dropInfo[$drop[DropDef::DROP_LIST][$key][DropDef::DROP_ITEM_TEMPLATE]] = $drop[DropDef::DROP_LIST][$key][DropDef::DROP_ITEM_NUM];
				}
				
			}
		}
		
		Logger::trace("dropType:%d, dropOnce:%s", $drop[DropDef::DROP_TYPE], $dropInfo);
		Logger::trace('Drop::dropOnce is End.');
		return array($drop[DropDef::DROP_TYPE] => $dropInfo);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */