<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Potence.class.php 91440 2014-02-25 13:00:53Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/potence/Potence.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2014-02-25 13:00:53 +0000 (Tue, 25 Feb 2014) $
 * @version $Revision: 91440 $
 * @brief
 *
 **/

class Potence
{
	/**
	 *
	 * 得到潜能
	 *
	 * @param int $potenceId
	 * @throws Exception			如果$potenceId不存在,则throw exception
	 * return array
	 */
	public static function getPotence($potenceId)
	{
		if ( !isset(btstore_get()->POTENCE_ITEM[$potenceId]) )
		{
			throw new FakeException('invalid potence id:%d!', $potenceId);
		}
		$potence = btstore_get()->POTENCE_ITEM[$potenceId];
	
		if ( empty($potence) )
		{
			throw new ConfigException("invalid potence id=%d, config is empty!", $potenceId);
		}
		return $potence;
	}
	
	/**
	 * 获取潜能的洗练档次花费
	 * 
	 * @param int $potenceId
	 * @param int $type
	 * @throws FakeException
	 */
	public static function getRefreshCost($potenceId, $type)
	{
		$potence = self::getPotence($potenceId);
		if ( !isset($potence[PotenceDef::POTENCE_REFRESH_TYPE][$type]) )
		{
			throw new FakeException('invalid type:%d', $type);
		}
		
		return $potence[PotenceDef::POTENCE_REFRESH_TYPE][$type][PotenceDef::POTENCE_VALUE_COST]->toArray();
	}
	
	/**
	 *
	 * 潜能是否具有属性
	 *
	 * @param int $potenceId						潜能ID
	 * @param int $attrId							属性ID
	 * @return boolean
	 */
	public static function hasAttrId($potenceId, $attrId)
	{
		$potence = self::getPotence($potenceId);
		if ( !isset($potence[PotenceDef::POTENCE_LIST][$attrId]) )
		{
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	/**
	 *
	 * 得到潜能属性价值
	 *
	 * @param int $potenceId						潜能ID
	 * @param int $attrId							属性ID
	 * @return int									潜能属性价值
	 */
	public static function getPotenceAttrValue($potenceId, $attrId)
	{
		$potence = self::getPotence($potenceId);
		if ( !isset($potence[PotenceDef::POTENCE_LIST][$attrId]) )
		{
			throw new FakeException('invalid potence_id:%d, attr_id:%d', $potenceId, $attrId);
		}
		return $potence[PotenceDef::POTENCE_LIST][$attrId][PotenceDef::POTENCE_ATTR_VALUE];
	}
	
	/**
	 *
	 * 得到潜能属性的最大值
	 *
	 * @param int $potenceId						潜能ID
	 * @param int $attrId							属性ID
	 * @param array $types							可用的洗练类型
	 * @throws Exception
	 * @return int									潜能属性最大值
	 */
	public static function getMaxPotenceAttrValue($potenceId, $attrId, $types)
	{
		$potence = self::getPotence($potenceId);
		if ( !isset($potence[PotenceDef::POTENCE_REFRESH_TYPE]) )
		{
			throw new FakeException('invalid potentiality_id:%d, no refresh type!', $potenceId);
		}
		$maxValue = 0;
		foreach ( $potence[PotenceDef::POTENCE_REFRESH_TYPE] as $type => $value )
		{
			if ( in_array($type, $types) )
			{
				if ( $maxValue < $value[PotenceDef::POTENCE_VALUE_UPPER] )
				{
					$maxValue = $value[PotenceDef::POTENCE_VALUE_UPPER];
				}
			}
		}
	
		return $maxValue;
	}
	
	/**
	 *
	 * 随机潜能
	 *
	 * @param int $potenceId
	 * @throws Exception			如果$potenceId不存在,则throw exception
	 * @return array
	 */
	public static function randPotence($potenceId, $type)
	{
		$potenceArr = array();
		$potence = self::getPotence($potenceId);

		//计算潜能的数量
		$keys = Util::backSample($potence[PotenceDef::POTENCE_TYPE_NUM_LIST][$type]->toArray(), 1, PotenceDef::POTENCE_TYPE_WEIGHT);
		$potenceNum = $potence[PotenceDef::POTENCE_TYPE_NUM_LIST][$type][$keys[0]][PotenceDef::POTENCE_TYPE_NUM];

		if ( $potenceNum == 0 )
		{
			return $potenceArr;
		}

		//计算产生的潜能列表
		$keys = Util::noBackSample($potence[PotenceDef::POTENCE_LIST]->toArray(), $potenceNum);
		foreach ( $keys as $key )
		{
			$value = $potence[PotenceDef::POTENCE_LIST][$key];
			$potenceArr[$value[PotenceDef::POTENCE_ATTR_ID]] = 0;
		}
		return $potenceArr;
	}

	/**
	 *
	 * 固定洗潜能
	 *
	 * @param int $potenceId
	 * @param array $original
	 * @param boolean $type
	 * @param int $limit
	 * @param int $num
	 * @throws Exception			如果$potenceId不存在,则throw exception
	 * @return array
	 */
	public static function refreshPotence($potenceId, $original, $type, $limit, $num)
	{
		$ret = array();
		$potence = self::getPotence($potenceId);

		if ( !isset($potence[PotenceDef::POTENCE_REFRESH_TYPE][$type]) )
		{
			throw new FakeException('invalid type:%d', $type);
		}

		$adjust = $potence[PotenceDef::POTENCE_VALUE_ADJUST];
		$add = $potence[PotenceDef::POTENCE_REFRESH_TYPE][$type][PotenceDef::POTENCE_VALUE_ADD];
		$modify = $potence[PotenceDef::POTENCE_REFRESH_TYPE][$type][PotenceDef::POTENCE_VALUE_MODIFY];
		foreach ( $original as $attrId => $attrValue )
		{
			$value = 0;
			if ( isset($potence[PotenceDef::POTENCE_LIST][$attrId]) )
			{
				$randLower = $attrValue + $add * $num - $modify * $num;
				$randUpper = $attrValue + $add * $num + $modify * $num - 2 * $adjust * $num;
				$value = rand($randLower, $randUpper);
				$value = min($value, $limit);
				$value = max($value, 0);
				$base = self::getPotenceAttrValue($potenceId, $attrId);
				$value = intval($value / $base) * $base;
				Logger::trace("potenceId:%d, attrId:%d, attrValue:%d, add:%d, modify:%d, limit:%d, lower:%d, upper:%d, value:%d"
						, $potenceId, $attrId, $attrValue, $add, $modify, $limit, $randLower, $randUpper, $value);
			}
			else
			{
				throw new FakeException('invalid potence attr_id:%d', $attrId);	
			}
			$ret[$attrId] = $value;
		}
		Logger::trace('refresh ret:%s', $ret);
		return $ret;
	}

	/**
	 *
	 * 潜能转移
	 *
	 * @param int $srcPotenceId				源装备随机潜能ID
	 * @param int $desPotenceId				目标装备随机潜能ID
	 * @param array $srcPotence				源装备随机潜能
	 * @param array $refreshType			随机潜能可刷新类型
	 * @throws Exception
	 * @return array						潜能转移后的潜能
	 */
	public static function transferPotence($srcPotenceId, $desPotenceId, $srcPotence, $refreshType)
	{
		$desPotence = array();
		foreach ( $srcPotence as $attrId => $value )
		{
			//如果相应的潜能属性存在,则进行处理,否则直接跳过
			if ( Potence::hasAttrId($desPotenceId, $attrId) == TRUE )
			{
				//如果相应的潜能属性在不同潜能上的价值不同,则抛出异常
				if ( Potence::getPotenceAttrValue($srcPotenceId, $attrId)
					!= Potence::getPotenceAttrValue($desPotenceId, $attrId) )
				{
					throw new ConfigException('invalid attr value, attr_id:%d, src potence_id:%d, des potence_id:%d',
						$attrId, $srcPotenceId, $desPotenceId);
				}

				//如果源潜能属性最大值大于目标潜能属性最大值,则抛出异常
				if ( Potence::getMaxPotenceAttrValue($srcPotenceId, $attrId, $refreshType)
					> Potence::getMaxPotenceAttrValue($desPotenceId, $attrId, $refreshType) )
				{
					throw new ConfigException('invalid attr max value, attr_id:%d, src potence_id:%d, des potence_id:%d',
						$attrId, $srcPotenceId, $desPotenceId);
				}
				$desPotence[$attrId] = $value;
			}
		}
		return $desPotence;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */