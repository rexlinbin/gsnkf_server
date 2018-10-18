<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: VACallback.class.php 65716 2013-09-22 13:10:53Z yangwenhai $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/module/SQLModify/VACallback.class.php $
 * @author $Author: yangwenhai $(jhd@babeltime.com)
 * @date $Date: 2013-09-22 21:10:53 +0800 (星期日, 22 九月 2013) $
 * @version $Revision: 65716 $
 * @brief
 *
 **/

class VACallback
{
	/**
	 *
	 * 处理Hero的VA字段
	 * 物品需要修改
	 *
	 * @param array $data
	 * @param string $gameId
	 *
	 * @return array
	 */
	public static function hero($data, $gameId)
	{
		$arrRet = $data;

		if (isset($arrRet['arming']))
		{
			$armInfo = $arrRet['arming'];
			foreach ($armInfo as $pos => &$itemId)
			{
				if ($itemId != 0)
				{
					$itemId = SQLModify::getNewId($gameId, 'item_id', $itemId);
				}
			}
			unset($itemId);
			$arrRet['arming'] = $armInfo;
		}
		
		if (isset($arrRet['treasure']))
		{
			$treasureInfo = $arrRet['treasure'];
			foreach ($treasureInfo as $pos => &$itemId)
			{
				if ($itemId != 0)
				{
					$itemId = SQLModify::getNewId($gameId, 'item_id', $itemId);
				}
			}
			unset($itemId);
			$arrRet['treasure'] = $treasureInfo;
		}
		
		if (isset($arrRet['skillBook']) )
		{
			if( !empty($arrRet['skillBook']) )
			{
				throw new Exception('not deal skillBook');
			}
		}

		if (isset($arrRet['dress']))
		{
			$dressInfo = $arrRet['dress'];
			foreach ($dressInfo as $pos => &$itemId)
			{
				if ($itemId != 0)
				{
					$itemId = SQLModify::getNewId($gameId, 'item_id', $itemId);
				}
			}
			unset($itemId);
			$arrRet['dress'] = $dressInfo;
		}
		
		if (isset($arrRet['fightSoul']))
		{
			$fightSoulInfo = $arrRet['fightSoul'];
			foreach ($fightSoulInfo as $pos => &$itemId)
			{
				if ($itemId != 0)
				{
					$itemId = SQLModify::getNewId($gameId, 'item_id', $itemId);
				}
			}
			unset($itemId);
			$arrRet['fightSoul'] = $fightSoulInfo;
		}
		
		if (isset($arrRet['godWeapon']))
		{
		    $godWeaponInfo = $arrRet['godWeapon'];
		    foreach ($godWeaponInfo as $pos => &$itemId)
		    {
		        if ($itemId != 0)
		        {
		            $itemId = SQLModify::getNewId($gameId, 'item_id', $itemId);
		        }
		    }
		    unset($itemId);
		    $arrRet['godWeapon'] = $godWeaponInfo;
		}
		
		if (isset($arrRet['pocket']))
		{
		    $pocketInfo = $arrRet['pocket'];
		    foreach ($pocketInfo as $pos => &$itemId)
		    {
		        if ($itemId != 0)
		        {
		            $itemId = SQLModify::getNewId($gameId, 'item_id', $itemId);
		        }
		    }
		    unset($itemId);
		    $arrRet['pocket'] = $pocketInfo;
		}
		
		if (isset($arrRet['tally']))
		{
			$tallyInfo = $arrRet['tally'];
			foreach ($tallyInfo as $pos => &$itemId)
			{
				if ($itemId != 0)
				{
					$itemId = SQLModify::getNewId($gameId, 'item_id', $itemId);
				}
			}
			unset($itemId);
			$arrRet['tally'] = $tallyInfo;
		}
		
		if (isset($arrRet['chariot']))
		{
			$chariotInfo = $arrRet['chariot'];
			foreach ($chariotInfo as $pos => &$itemId)
			{
				if ($itemId != 0)
				{
					$itemId = SQLModify::getNewId($gameId, 'item_id', $itemId);
				}
			}
			unset($itemId);
			$arrRet['chariot'] = $chariotInfo;
		}
		
		return $arrRet;
	}

	/**
	 *
	 * 处理物品的VA字段
	 *
	 * @param array $data
	 * @param string $gameId
	 */
	public static function item($data, $gameId)
	{
		$arrRet = $data;
		
		if (isset($arrRet['treasureInlay']))
		{
			$inlay = $arrRet['treasureInlay'];
			foreach ($inlay as $pos => &$itemId)
			{
				if ($itemId != 0)
				{
					$itemId = SQLModify::getNewId($gameId, 'item_id', $itemId);
				}
			}
			unset($itemId);
			$arrRet['treasureInlay'] = $inlay;
		}
		
		return $arrRet;
	}

	/**
	 *
	 * 处理user的VA字段
	 *
	 * @param array $data
	 * @param string $gameId
	 *
	 * @return array
	 */
	public static function userHero($data, $gameId)
	{
		$arrRet = $data;
		//unused=>array(hid=>array(htid=>int,level=>int))
		
		if( count($data) != 1 || !isset( $data['unused'] ) )
		{
			$msg = sprintf('invalid va_hero in user. %s', var_export($data,true));
			throw new Exception($msg);
		}
		
		$arrRet['unused'] = array();
		
		foreach( $data['unused'] as $hid => $info)
		{
			$newHid = SQLModify::getNewId($gameId, 'hid', $hid);
			$arrRet['unused'][$newHid] = $info;
		}
	
		return $arrRet;
	}

	
	/**
	 * 处理比武的va
	 * @param array $data
	 * @param string $gameId
	 * 
	 * @return array
	 */
	public static function compete($data, $gameId)
	{
		$arrRet = $data;
		
		$arrRet['rival'] = array();
		$arrRet['foe'] = array();
		
		return $arrRet;
	}
	
	/**
	 * 处理阵型的va
	 * @param array $data
	 * @param string $gameId
	 *
	 * @return array
	 */
	public static function formation($data, $gameId)
	{
		$arrRet = $data;
		
		if( count($data) < 2 
			|| !isset( $data['formation'] ) 
			|| !isset( $data['extra'] ) )
		{
			$msg = sprintf('invalid va_formation in formation. %s', var_export($data,true));
			throw new Exception($msg);
		}
		
		$arrRet['formation'] = array();
		foreach($data['formation'] as $hid => $info)
		{
			$newHid = SQLModify::getNewId($gameId, 'hid', $hid);
			$arrRet['formation'][$newHid] = $info;
		}
		
		foreach($data['extra'] as $key => $hid)
		{
			$newHid = SQLModify::getNewId($gameId, 'hid', $hid);
			$arrRet['extra'][$key] = $newHid;
		}
		
		if ( isset($data['attr_extra']) )
		{
			foreach($data['attr_extra'] as $key => $hid)
			{
				$newHid = SQLModify::getNewId($gameId, 'hid', $hid);
				$arrRet['attr_extra'][$key] = $newHid;
			}
		}
		
		return $arrRet;
	}
	

	/**
	 * 处理好友的va
	 * @param array $data
	 * @param string $gameId
	 *
	 * @return array
	 */
	public static function friendlove($data, $gameId)
	{
		$arrRet = array();
	
		/* foreach ( $arrRet as $index => $dataInfo )
		{
			if( !isset( $arrRet[$index]['time']) || !isset( $arrRet[$index]['uid'] ) )
			{
				throw new InterException( 'friendLove va !isset,data before are: %s', $data);
			}
			$arrRet[$index]['uid'] = SQLModify::getNewId($gameId, 'uid', $dataInfo['uid']);
		} */
	
		return $arrRet;
	}
	
	

	/**
	 * 处理奖励中心的va
	 * @param array $data
	 * @param string $gameId
	 *
	 * @return array
	 */
	public static function reward($data, $gameId)
	{
		$arrRet = $data;
	
		if ( isset( $arrRet['arrItemId'] ) )
		{
			foreach ( $arrRet['arrItemId'] as $index => $itemId )
			{
				$arrRet['arrItemId'][$index] = SQLModify::getNewId($gameId, 'item_id', $itemId);
			}
		}
	
		return $arrRet;
	}
	
	public static function lordwar($data, $gameId)
	{
		$arrRet = array();
		
		return $arrRet;
	}
	
	public static function lordwarExtra($data, $gameId)
	{
		$arrRet = array();
	
		return $arrRet;
	}
	
	public static function petKeeper($data, $gameId)
	{
		$arrRet = $data;
		
		foreach ( $arrRet['setpet'] as $pos => $squandInfo )
		{
			if(  $squandInfo['petid'] > 0  )
			{
				$arrRet['setpet'][$pos]['petid']= SQLModify::getNewId($gameId, 'petid', $squandInfo['petid']);
			}
			
		}
		
		return $arrRet;
	}
	
	public static function allStar($data, $gameId)
	{
		$arrRet = $data;
		
		if (isset($arrRet['skill'])) 
		{
			$arrRet['skill'] = SQLModify::getNewId($gameId, 'star_id', $arrRet['skill']);
		}
		if (isset($arrRet['draw'])) 
		{
			foreach ($arrRet['draw'] as $sid => $value)
			{
				$newSid = SQLModify::getNewId($gameId, 'star_id', $sid);
				$arrRet['draw'][$newSid] = $value;
			}
		}
	
		return $arrRet;
	}
	
	public static function dragonBattleFormation($data, $gameId)
	{
		$arrRet = array();
		
		return $arrRet;
	}
	
	public static function resetGoldTreeBattleFmt($data, $gameId)
	{
	    if(isset($data['fmt_valid']))
	    {
	        unset($data['fmt_valid']);
	    }
	    if(isset($data['battle_info']))
	    {
	        unset($data['battle_info']);
	    }
	    return $data;
	}
	
	public static function guildCopyExtra($data, $gameId)
	{
		$arrVaExtra = $data;
		
		foreach ($arrVaExtra as $key => $value)
		{
			if ($key === 'copy') 
			{
				foreach ($value as $baseId => $baseInfo)
				{
					if (isset($baseInfo['max_damager'])) 
					{
						$arrVaExtra[$key][$baseId]['max_damager']['uid'] = SQLModify::getNewId($gameId, 'uid', $arrVaExtra[$key][$baseId]['max_damager']['uid']);
						$arrVaExtra[$key][$baseId]['max_damager']['uname'] = $arrVaExtra[$key][$baseId]['max_damager']['uname'] . Util::getSuffixName($gameId);
					}
				}
			}
			if ($key === 'box') 
			{
				foreach ($value as $boxId => $receiver)
				{
					$arrVaExtra[$key][$boxId]['uid'] = SQLModify::getNewId($gameId, 'uid', $arrVaExtra[$key][$boxId]['uid']);
					$arrVaExtra[$key][$boxId]['uname'] = $arrVaExtra[$key][$boxId]['uname'] . Util::getSuffixName($gameId);
				}
			}
			if ($key === 'refresher') 
			{
				foreach ($value as $index => $name)
				{
					$arrVaExtra[$key][$index] = $arrVaExtra[$key][$index] . Util::getSuffixName($gameId);
				}
			}
		}
		
		return $arrVaExtra;
	}
	
	public static function guildCopyLastBox($data, $gameId)
	{
		$arrVaLastBox = $data;
	
		foreach ($arrVaLastBox as $lastDay => $info)
		{
			foreach ($info as $key => $value)
			{
				if ($key === 'box')
				{
					foreach ($value as $boxId => $receiver)
					{
						$arrVaLastBox[$lastDay]['box'][$boxId]['uid'] = SQLModify::getNewId($gameId, 'uid', $arrVaLastBox[$lastDay]['box'][$boxId]['uid']);
						$arrVaLastBox[$lastDay]['box'][$boxId]['uname'] = $arrVaLastBox[$lastDay]['box'][$boxId]['uname'] . Util::getSuffixName($gameId);
					}
				}
			}
		}
	
		return $arrVaLastBox;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */