<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ISupply.class.php 73865 2013-11-09 09:16:39Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/supply/ISupply.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-11-09 09:16:39 +0000 (Sat, 09 Nov 2013) $
 * @version $Revision: 73865 $
 * @brief 
 *  
 **/
interface ISupply
{
	/**
	 * 获取补给信息；
	 * 暂定体力领取时间为每日的12点至14点，18点至20点；
	 * 根据用户领取时间来判定是否领取过
	 * 
	 * @return string $time			上次领取时间
	 */
	public function getSupplyInfo();
	
	/**
	 * 补给：加体力50点
	 * 
	 * @return int $num				增加的体力数
	 */
	public function supplyExecution();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */