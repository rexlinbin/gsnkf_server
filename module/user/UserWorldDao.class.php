<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserWorldDao.class.php 190794 2015-08-13 04:07:09Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/UserWorldDao.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-08-13 04:07:09 +0000 (Thu, 13 Aug 2015) $
 * @version $Revision: 190794 $
 * @brief 
 *  
 **/

class UserWorldDao
{
	//worldvip==
	const USER_WORLD = 't_user_world';
	
	public static function getCreateVip( $baseGold )
	{
		$vip = 0;
		foreach (btstore_get()->VIP as $vipInfo)
		{
			if ($vipInfo['totalRecharge'] > $baseGold)
			{
				break;
			}
			else
			{
				$vip = $vipInfo['vipLevel'];
			}
		}
		return $vip;
	}
	
	public static function getCreateBaseGoldByPid( $pid )
	{
		$data = new CData();
		$data->useDb(WorldDef::WORLD_GENERAL_PRE.PlatformConfig::PLAT_NAME);
		$ret = $data->select(array('max(base_goldnum) as base_goldnum'))
		->from(self::USER_WORLD)
		->where(array('pid','=', $pid))
		->query();
		
		$max = 0;
		if( empty( $ret ) )
		{
			$max = 0;
		}
		else
		{
			foreach ( $ret as $oneRet )
			{
				if ( $oneRet['base_goldnum'] > $max )
				{
					$max = $oneRet['base_goldnum'];
				}	
			}
		}
		
		return $max;
	}
	
	public static function updateUserWorldGold($pid, $goldnum)
	{
		try
		{
			$serverId=Util::getServerIdOfConnection();
			$values = array(
					'base_goldnum' => $goldnum,
					'pid' => $pid,
					'server_id' => $serverId,
			);
			$wheres = array(
					array('pid','=', $pid), 
					array('server_id','=',$serverId),
					array('base_goldnum','<', $goldnum),
			);
			self::updateUserWorld($values, $wheres);			
		} catch (Exception $e)
		{
			Logger::fatal('setUserWorldGold err!gold:%d error:%s',$goldnum,$e->getMessage());
		}
	}
	
	public static function updateUserWorld(  $values, $wheres )
	{
		$data = new CData();
		$data->useDb( WorldDef::WORLD_GENERAL_PRE.PlatformConfig::PLAT_NAME );
		$data->insertOrUpdate(self::USER_WORLD)->values($values);
		foreach ( $wheres as $where )
		{
			$data->where($where);
		}
		
		$ret = $data->query();
		
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */