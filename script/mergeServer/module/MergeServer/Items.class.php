<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Items.class.php 38797 2013-02-20 08:52:51Z HongyuLan $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/module/MergeServer/Items.class.php $
 * @author $Author: HongyuLan $(jhd@babeltime.com)
 * @date $Date: 2013-02-20 16:52:51 +0800 (星期三, 20 二月 2013) $
 * @version $Revision: 38797 $
 * @brief
 *
 **/

class Items
{

	
	/**
	 *
	 * 得到某个英雄相关的物品
	 * 装备，宝物等
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function hero2item($data)
	{
		$arrItemId = array();

		if ( isset($data['va_hero']['arming']) && is_array($data['va_hero']['arming']) )
		{
			foreach ($data['va_hero']['arming'] as $key => $value)
			{
				if ( $value!='0' )
				{
					$arrItemId[] = $value;
				}
			}
		}
		
		if ( isset($data['va_hero']['treasure']) && is_array($data['va_hero']['treasure']) )
		{
			foreach ($data['va_hero']['treasure'] as $key => $value)
			{
				if ( $value!='0' )
				{
					$arrItemId[] = $value;
				}
			}
		}
		
		if ( isset($data['va_hero']['skillBook']) && is_array($data['va_hero']['skillBook']) )
		{
			if(!empty( $data['va_hero']['skillBook'] ))
			{
				//目前skillBook还没有使用
				throw new Exception('not deal skillBook');
			}
		}
		
		if ( isset($data['va_hero']['dress']) && is_array($data['va_hero']['dress']) )
		{
			foreach ($data['va_hero']['dress'] as $key => $value)
			{
				if ( $value!='0' )
				{
					$arrItemId[] = $value;
				}
			}
		}
		
		if ( isset($data['va_hero']['fightSoul']) && is_array($data['va_hero']['fightSoul']) )
		{
			foreach ($data['va_hero']['fightSoul'] as $key => $value)
			{
				if ( $value!='0' )
				{
					$arrItemId[] = $value;
				}
			}
		}
		
		if ( isset($data['va_hero']['godWeapon']) && is_array($data['va_hero']['godWeapon']) )
		{
		    foreach ($data['va_hero']['godWeapon'] as $key => $value)
		    {
		        if ( $value!='0' )
		        {
		            $arrItemId[] = $value;
		        }
		    }
		}
		
		if ( isset($data['va_hero']['pocket']) && is_array($data['va_hero']['pocket']) )
		{
			foreach ($data['va_hero']['pocket'] as $key => $value)
			{
				if ( $value!='0' )
				{
					$arrItemId[] = $value;
				}
			}
		}
		
		if ( isset($data['va_hero']['tally']) && is_array($data['va_hero']['tally']) )
		{
			foreach ($data['va_hero']['tally'] as $key => $value)
			{
				if ( $value!='0' )
				{
					$arrItemId[] = $value;
				}
			}
		}
		
		if ( isset($data['va_hero']['chariot']) && is_array($data['va_hero']['chariot']) )
		{
			foreach ($data['va_hero']['chariot'] as $key => $value)
			{
				if ( $value!='0' )
				{
					$arrItemId[] = $value;
				}
			}
		}
		
		return $arrItemId;
	}

	/**
	 *
	 * 得到某个背包格子里所相关的物品id
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function bag2item($data)
	{
		return array($data['item_id']);
	}

	/**
	 *
	 * 得到某个物品所关联的其他物品
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function item2item($data)
	{
		$arrItemId = array();

		if ( isset($data['va_item_text']['treasureInlay']) )
		{
			foreach ($data['va_item_text']['treasureInlay'] as $key => $value)
			{
				if ( $value!='0' )
				{
					$arrItemId[] = $value;
				}
			}
		}
		
		return $arrItemId;
	}
	
	/**
	 * 获取奖励中心的物品
	 * @param array $data
	 * @param array
	 */
	public static function reward2item($data)
	{
		if ( isset($data['va_reward']['arrItemId']) && is_array($data['va_reward']['arrItemId']) )
		{
			return $data['va_reward']['arrItemId'];
		}
		return array();
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */