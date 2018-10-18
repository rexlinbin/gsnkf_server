<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ILevelfund.class.php 58584 2013-08-09 09:56:19Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/levelfund/ILevelfund.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-09 09:56:19 +0000 (Fri, 09 Aug 2013) $
 * @version $Revision: 58584 $
 * @brief 
 *  
 **/
interface ILevelfund
{
	/**
	 * 获取升级嘉奖活动信息
	 * @return array(
	 * 1，3... 已经领取的奖励的id
	 * )
	 */
	function getLevelfundInfo();
	/**
	 * 
	 * @param int $id 要领取奖励的id （从1开始）
	 *  领取升级奖励
	 * @return 'ok'
	 */
	function gainLevelfundPrize( $id );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */