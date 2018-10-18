<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IOnline.class.php 59715 2013-08-15 09:50:29Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/online/IOnline.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-15 09:50:29 +0000 (Thu, 15 Aug 2013) $
 * @version $Revision: 59715 $
 * @brief 
 *  
 **/

interface IOnline
{
	/**
	 *获取在线奖励信息
	 * @return array 在线信息
	 * <code>
	 *array(
	 *		'uid', 
	 *		'step',    奖励领取的次数（一次也没领 则为0）
	 * 		'accumulate_time', 本轮累计计时时间
	 * );
	 * </code>
	 */
	public function getOnlineInfo ();
	
	/**
	 * 领取在线奖励
	 * @param $step 领取在线奖励的哪一个阶段（第一次领取奖励则为1）
	 * @return array 奖励数组
	 * <code>
	 *array( );
	 * </code>
	*/
	public function gainGift ( $step );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */