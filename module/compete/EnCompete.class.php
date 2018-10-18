<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnCompete.class.php 218442 2015-12-29 08:06:09Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/compete/EnCompete.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-12-29 08:06:09 +0000 (Tue, 29 Dec 2015) $
 * @version $Revision: 218442 $
 * @brief 
 *  
 **/
class EnCompete
{
	public static function getRank($uid)
	{
		try 
		{
			$info = CompeteLogic::getCompeteInfo($uid);
			$rank = $info['rank'];
		}
		catch (Exception $e )
		{
			$rank = 0;
		}
		
		return $rank;
	}
	
	public static function getHonor($uid)
	{
		$info = CompeteDao::select($uid);
		return !empty($info[CompeteDef::COMPETE_HONOR]) ? $info[CompeteDef::COMPETE_HONOR] : 0;
	}
	
	/**
	 * 给比武的用户加减荣誉值
	 * 提示：直接更新数据库
	 * 非当前用户线程会抛异常
	 * 
	 * @param int $uid
	 * @param int $honor
	 * @throws InterException
	 * @throws FakeException
	 * @return string 'ok'/'failed'
	 */
	public static function addHonor($uid, $honor)
	{
		if($uid != RPCContext::getInstance()->getUid())
		{
			throw new InterException('Not in the uid:%d session', $uid);
		}
		if ($honor == 0) 
		{
			return 'ok';
		}
		return CompeteLogic::addHonor($uid, $honor);
	}
	
	public static function getRetrieveInfo($uid)
	{
		return CompeteLogic::getRetrieveInfo($uid);
	}
	
	public static function getTopActivityInfo()
	{
		return CompeteLogic::getTopActivityInfo();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */