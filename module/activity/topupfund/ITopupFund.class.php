<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ITopupFund.class.php 87828 2014-01-20 07:18:55Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/topupfund/ITopupFund.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-01-20 07:18:55 +0000 (Mon, 20 Jan 2014) $
 * @version $Revision: 87828 $
 * @brief 
 *  
 **/
interface ITopupFund
{

	/**
	 * 得到充值回馈信息
	 * @return array
	 * <code>
	 * array(
	 * gold_accum: num 累计充值的金币
	 * reward: array(id1, id2) 已经领取奖励的id
	 * )
	 * </code>
	 */
	public function getTopupFundInfo();
	
	/**
	 * 领取奖励
	 * @param int $id 奖励id( 由1开始 )
	 * @return 'ok'
	 * 
	*/
	public function gainReward($id);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */