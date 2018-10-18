<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Activity.class.php 201375 2015-10-10 07:45:19Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/Activity.class.php $
 * @author $Author: ShiyuZhang $(wuqilin@babeltime.com)
 * @date $Date: 2015-10-10 07:45:19 +0000 (Sat, 10 Oct 2015) $
 * @version $Revision: 201375 $
 * @brief 
 *  
 **/



class Activity implements IActivity
{
	
	
	/**
	 * 前端获取所有的活动配置
	 * @param number $version  前端目前的版本好，如果传了这个参数，然后只返回比这个版本号新的配置
	 * @return array
	 */
	public function getActivityConf($version = 0)
	{		
		Logger::trace('get activity conf. clientVersion:%d', $version);
		
		$serverVersion = 0;
		$ret = ActivityConfLogic::getConf4Front();
		
		//validity====这是为要剔除某些活动加的
		$guid =  RPCContext::getInstance()->getUid();
		if( !empty( $guid ) )
		{
			ValidityCheck::refreshSession();
		}
		
		$maybeInvalidArr = array();
		if (ActivityNSLogic::inNS())
		{
			$returnData = ActivityNSLogic::getAllNSForFront();
			foreach($ret['arrData'] as $name => $value)
			{
				if( ActivityConfLogic::isWholeServerActivity($name,$value) )
				{
					$value['version'] = 0;
					$returnData['arrData'][$name] = $value;
					
					Logger::debug('activity %s is whole server, conf: %s', $name,$returnData['arrData'][$name]);
					//$maybeInvalidArr[] = $name;
					if( !in_array($name , ActivityConf::$MUST_VALID_FOR_VALIDITY) && $name != ActivityName::VALIDITY )
					{
						$maybeInvalidArr[] = $name;
					}
				}
			}
		}
		else 
		{
			$serverVersion = $ret['version'];
			RPCContext::getInstance()->setSession(ActivityDef::SESSION_KEY_VERSION, $serverVersion);
			$returnData = $ret;
			foreach($returnData['arrData'] as $name => $value)
			{
				if( !ActivityConfLogic::isWholeServerActivity($name,$value) && $name != ActivityName::VALIDITY )
				{
					$value =ActivityConfLogic::getRealConfForFront($name, $value);
					Logger::debug('real conf is: %s', $value);
					if($version >= $value['version'] && $version > 0 && $value['version'] > 0)
					{
						Logger::debug('unset one :%s, version: %s, %s ',$name, $version,$value['version']);
						unset($returnData['arrData'][$name]);
					}
					else
					{
						$returnData['arrData'][$name] = $value;
						Logger::trace('very normal');
					}
				}
				if( !in_array($name , ActivityConf::$MUST_VALID_FOR_VALIDITY) && $name != ActivityName::VALIDITY )
				{
					$maybeInvalidArr[] = $name;
				}
			}
		}
		
		if( isset( $returnData['arrData'][ActivityName::VALIDITY] ) )
		{
			if(ValidityCheck::isActivityValidByOutPlatId())
			{
				$frontStrArr = '';
			}
			else 
			{
				$index = 1;
				$maybeInvalidNameStr = implode('|', $maybeInvalidArr);
				$frontStrArr = '1,'.'"'.$maybeInvalidNameStr.'"';
			}
			$returnData['arrData'][ActivityName::VALIDITY]['data'] = $frontStrArr;
		}
		
		EnWeal::refreshWeal();
		
		foreach (ActivityConf::$FRONT_NEVER_NEED_DATA as $neverNeedDataActivityName )
		{
			if( isset( $returnData['arrData'][$neverNeedDataActivityName]['data'] ) )
			{
				$returnData['arrData'][$neverNeedDataActivityName]['data'] = '';
			}
		}
		
		//前期还是打个日志，方便查问题
		Logger::info('get conf. clientVersion:%d, serverVersion:%d, arrName:%s',
					$version, $serverVersion, array_keys($returnData['arrData']));
		return $returnData;
	}
	
	/**
	 * 给平台使用
	 * (non-PHPdoc)
	 * @see IActivity::getAllConf()
	 */
	public function getAllConf()
	{
		$returnData = ActivityConfLogic::getConf4Front();
	
		Logger::info('get conf. arrName:%s', array_keys($returnData['arrData']));
		return $returnData;
	}
	
	/**
	 * 给后台使用，会向：uid：0中插入一个任务
	 * @param int $newVersion
	 */
	public function refreshConf($newVersion)
	{
		$curVersion = ActivityConfLogic::getTrunkVersion();

		
		if($newVersion > $curVersion)
		{
			Logger::info('refresh now. curVersin:%d, newVersion:%d', $curVersion, $newVersion);
			ActivityConfLogic::refreshConf($curVersion, true);
		}
		else
		{
			Logger::info('no need to refresh. curVersin:%d, newVersion:%d', $curVersion, $newVersion);
		}
		
		return 'ok';
	}

	/**
	 * 给后台使用，上传配置文件时检查配置是否有效
	 * 
	 * @param array $arrConf
	 * 		array
	 * 		[
	 * 			{
	 * 				name => 
	 * 				version => 
	 * 				start_time => 
	 * 				end_time => 
	 * 				need_open_time =>
	 * 				data => 
	 * 				
	 * 			}
	 * 		]
	 * @return string 
	 * 		ok			配置合法
	 * 		empty		配置为空
	 * 		invalid     配置不合法
	 */
	public function checkConf($arrConf)
	{
		if(empty($arrConf))
		{
			Logger::fatal('empty conf');
			return 'empty';
		}
		$arrRet = ActivityConfLogic::decodeConf($arrConf);
		
		if(empty($arrRet) || count($arrRet) != count($arrConf))
		{
			Logger::fatal('invalid conf. arrConf:%s, decoded:%s', $arrConf, $arrRet);
			return 'invalid';
		}
		return 'ok';
	}
	
	/**
	 * 在uid:0的连接中执行，实际执行刷新任务
	 * @param int $version
	 */
	public function doRefreshConf($version, $force)
	{
		Logger::trace('refresh conf. version:%d', $version);
		
		ActivityConfLogic::doRefreshConf($version, $force);
		
	}
	
	
	
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */