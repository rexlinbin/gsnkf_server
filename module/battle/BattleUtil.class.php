<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BattleUtil.class.php 250333 2016-07-07 03:45:10Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/battle/BattleUtil.class.php $
 * @author $Author: BaoguoMeng $(hoping@babeltime.com)
 * @date $Date: 2016-07-07 03:45:10 +0000 (Thu, 07 Jul 2016) $
 * @version $Revision: 250333 $
 * @brief
 *
 **/

class BattleUtil
{

	public static function unsetEmpty($arrFormation)
	{

		foreach ( $arrFormation as $index => $value )
		{
			if (empty ( $value ))
			{
				unset ( $arrFormation [$index] );
			}
		}
		return $arrFormation;
	}
	
	/**
	 * 将战斗数据中为0或者为空数组的字段unset掉
	 * 将装备中item_num字段unset掉，因为只有1个
	 * 
	 * @param array $arrFormation
	 */
	public static function unsetEmptyField($arrFormation)
	{
		foreach ($arrFormation as $index => $arrRow)
		{
			// 如果为0，unset
			foreach (BattleDef::$ARR_BATTLE_KEY_UNSET_IF_0 as $aKey)
			{
				if (isset($arrRow[$aKey]) && is_integer($arrRow[$aKey]) && $arrRow[$aKey] === 0) 
				{
					unset($arrFormation[$index][$aKey]);
				}
			}
			
			// 如果为空array，unset
			foreach (BattleDef::$ARR_BATTLE_KEY_UNSET_IF_EMPTY_ARRAY as $aKey)
			{
				if (isset($arrRow[$aKey]) && is_array($arrRow[$aKey]) && empty($arrRow[$aKey])) 
				{
					unset($arrFormation[$index][$aKey]);
				}
			}
			
			// 将equipInfo中item_num去掉，都是1，没必要写
			foreach ($arrRow['equipInfo'] as $type => $arrInfo)
			{
				foreach ($arrInfo as $key => $value)
				{
					if (is_array($value) && isset($value['item_num'])) 
					{
						unset($arrFormation[$index]['equipInfo'][$type][$key]['item_num']);
					}
				}
			}
		}
		
		return $arrFormation;
	}
	
	/**
	 * 将战斗数据中为0或者为空数组的字段加上
	 * 将装备中item_num字段加上，值为1
	 *
	 * @param array $arrFormation
	 */
	public static function fillEmptyField($arrFormation)
	{
		foreach ($arrFormation as $index => $arrRow)
		{
			// 如果没有配置的字段，则置为0
			foreach (BattleDef::$ARR_BATTLE_KEY_UNSET_IF_0 as $aKey)
			{
				if (!isset($arrRow[$aKey]))
				{
					$arrFormation[$index][$aKey] = 0;
				}
			}
				
			// 如果没有配置的字段，则置为空数组
			foreach (BattleDef::$ARR_BATTLE_KEY_UNSET_IF_EMPTY_ARRAY as $aKey)
			{
				if (!isset($arrRow[$aKey]))
				{
					$arrFormation[$index][$aKey] = array();
				}
			}
				
			// 将equipInfo中所有item加上item_num=1
			foreach ($arrRow['equipInfo'] as $type => $arrInfo)
			{
				foreach ($arrInfo as $key => $value)
				{
					if (is_array($value) && isset($value['item_id']) && !isset($value['item_num']))
					{
						$arrFormation[$index]['equipInfo'][$type][$key]['item_num'] = 1;
					}
				}
			}
		}
		
		return $arrFormation;
	}

	private static function prepareFormation($arrFormation, $arrKey)
	{

		$arrRet = array ();
		foreach ( $arrFormation as $arrRow )
		{
			$arrTmp = array ();
			foreach ( $arrKey as $key => $type )
			{
				if (isset ( $arrRow [$key] ))
				{
					$value = $arrRow [$key];
				}
				else
				{
					$value = null;
				}

				switch ($type)
				{
					case 'int' :
						if ($value === null)
						{
							Logger::fatal ( "argument %s can't be empty", $key );
							throw new Exception ( "inter" );
						}
						$value = intval ( $value );
						break;
					case 'int_empty' :
						if ($value === null)
						{
							continue 2;
						}
						$value = intval ( $value );
						break;
					case 'array_int' :
						if (empty ( $value ) || ! is_array ( $value ))
						{
							Logger::fatal ( "invalid argument:%s, array expected", $key );
							throw new Exception ( "inter" );
						}
						foreach ( $value as $index => $v )
						{
							$value [$index] = intval ( $v );
						}
						break;
						
					case 'array_int_empty' :
						if ($value === null)
						{
							continue 2;
						}
						if (! is_array ( $value ))
						{
							Logger::fatal ( "invalid argument:%s, array expected", $key );
							throw new Exception ( "inter" );
						}
						foreach ( $value as $index => $v )
						{
							$value [$index] = intval ( $v );
						}
						break;
					case 'raw' :
						break;
					default :
						Logger::fatal ( 'undefined type:%s', $type );
						throw new Exception ( "inter" );
				}
				$arrTmp [$key] = $value;
			}
			$arrRet [] = $arrTmp;
		}

		return $arrRet;
	}

	public static function prepareBattleFormation($arrFormation)
	{
		$arrHero = $arrFormation['arrHero'];;
		
		//将其中的死人去掉
		foreach($arrHero as $key => $hero)
		{
			if( isset($hero[PropertyKey::CURR_HP]) &&  $hero[PropertyKey::CURR_HP] == 0 )
			{
				unset($arrHero[$key]);
				Logger::trace('remove dead hero:%d', $hero[PropertyKey::HID] );
			}
		}
		$arrHero = self::prepareFormation ( $arrHero, BattleDef::$ARR_BATTLE_KEY );
		
		// 处理战车
		if (!empty($arrFormation['arrCar']))
		{
			$arrCar = self::prepareFormation($arrFormation['arrCar'], BattleDef::$ARR_CAR_BATTLE_KEY);
			$arrHero = array_merge($arrHero, $arrCar);
		}
		
		return $arrHero;
	}

	public static function prepareClientFormation($arrFormation, $arrServerHero)
	{

		$totalHpCost = 0;
		$totalDamage = 0;
		foreach ( $arrServerHero as $arrHero )
		{
			$totalHpCost += $arrHero ['costHp'];
			$totalDamage += $arrHero ['damage'];
		}

		//对于死人只需要传个hid，curHp
		$arrDeadHero = array();
		foreach($arrFormation ['arrHero'] as $key => $hero)
		{
			if( isset($hero[PropertyKey::CURR_HP]) &&  $hero[PropertyKey::CURR_HP] == 0 )
			{
				$arrDeadHero[] = array(
						PropertyKey::HID => $hero[PropertyKey::HID],
						PropertyKey::HTID => $hero[PropertyKey::HTID],
						PropertyKey::CURR_HP => $hero[PropertyKey::CURR_HP],
						PropertyKey::POSITION => $hero[PropertyKey::POSITION],
						);
				unset($arrFormation ['arrHero'][$key]);
			}
		}
		
		$arrHero = self::prepareFormation ( $arrFormation ['arrHero'], BattleDef::$ARR_CLIENT_KEY );
		$arrHero = array_merge($arrHero, $arrDeadHero);

		$arrRet = array (
				'name' => $arrFormation ['name'],
				'uid' => $arrFormation ['uid'], 
				'level' => $arrFormation ['level'],
				'totalHpCost' => $totalHpCost, 
				'totalDamage' => $totalDamage,
				'isPlayer' => $arrFormation ['isPlayer'],
				'arrHero' => $arrHero,
		);
		
		if (!empty($arrFormation['arrPet']))
		{
			$arrRet['arrPet'] = self::prepareFormation($arrFormation['arrPet'], BattleDef::$ARR_PET_CLIENT_KEY);
		}
		
		if (!empty($arrFormation['arrCar']))
		{
			$arrRet['arrCar'] = self::prepareFormation($arrFormation['arrCar'], BattleDef::$ARR_CAR_CLIENT_KEY);
		}
		
		if(isset($arrFormation['fightForce']))
		{
			$arrRet['fightForce'] = $arrFormation['fightForce'];
		}
		
		if(isset($arrFormation['littleFriend']))
		{
			$arrRet['littleFriend'] = $arrFormation['littleFriend'];
		}
		
		if(isset($arrFormation['attrFriend']))
		{
			$arrRet['attrFriend'] = $arrFormation['attrFriend'];
		}
		
		if ( isset($arrFormation['craft']) )
		{
			$arrRet['craft'] = $arrFormation['craft'];
		}
		
		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
