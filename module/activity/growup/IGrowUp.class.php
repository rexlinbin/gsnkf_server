<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IGrowUp.class.php 67669 2013-10-09 01:54:29Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/growup/IGrowUp.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-10-09 01:54:29 +0000 (Wed, 09 Oct 2013) $
 * @version $Revision: 67669 $
 * @brief 
 *  
 **/
interface IGrowUp
{

	/**
	 * 获取用户成长计划
	 *
	 * @return 
	 * array
	 * {
	 * 		'prized' => array( 0, 1  ..), 已经领取的奖励
	 * 		'active_time' => ,用户激活该功能的时间
	 * }
	 * Or
	 * 'invalid_time'活动时间不对
	 * or
	 * 'unactived' 用户未激活
	 * or
	 * 'fetch_all' 已经领取了所有了（领完活动就应该停止了）							
	 */
	function getInfo();


	/**
	 * 激活计划（购买）
	 *
	 * @return ok
	*/
	function activation();


	/**
	 * 获取奖励
	 *
	 * @param int $index						用户选择的奖励 , 从0开始
	 *
	 * @return ok								
	*/
	function fetchPrize($index);

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */