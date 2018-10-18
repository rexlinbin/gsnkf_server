<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AchieveLogic.class.php 77079 2013-11-26 13:10:13Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/AchieveLogic.class.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2013-11-26 13:10:13 +0000 (Tue, 26 Nov 2013) $
 * @version $Revision: 77079 $
 * @brief 
 *  
 **/

class AchieveLogic
{
	/**
	 * 获取一个玩家已获得的成就
	 * @param int $uid
	 * @param array 成就id数组
	 */
	public static function getArrAchieveId($uid)
	{
		$allStarFavor = EnStar::getAllStarFavor($uid);
		
		$arrId = array();
		$arrConf = btstore_get()->ACHIEVE;
		foreach($arrConf as $id => $conf)
		{
			if( $allStarFavor >= $conf['arrCond'][0] )
			{
				$arrId[] = $id;
			}
		}
		
		Logger::trace('getArrAchieveId. uid:%d, allStarFavor:%d, arrId:%s', $uid, $allStarFavor, $arrId);
		return $arrId;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */