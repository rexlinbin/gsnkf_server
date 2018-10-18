<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ISpend.class.php 86120 2014-01-11 09:00:21Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/spend/ISpend.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-01-11 09:00:21 +0000 (Sat, 11 Jan 2014) $
 * @version $Revision: 86120 $
 * @brief 
 *  
 **/

interface ISpend
{
	/**
	 * 得到消费累计信息
	 * @return array
	 * <code>
	 * array(
	 * 			gold_accum: num 累计消费的金币
	 * 			reward: array(id1, id2) 已经领取奖励的id
	 * )
	 * </code>
	 */
	public function getInfo();

	/**
	 * 得到奖励
	 * @param unknown_type $id 奖励id( 由1开始 )
	 * return 'ok';
	*/
	public function gainReward($id);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */