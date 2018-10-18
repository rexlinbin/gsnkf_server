<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ITravelShop.php 197710 2015-09-10 02:22:54Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/travelshop/ITravelShop.php $
 * @author $Author: MingTian $(zhengguohao@babeltime.com)
 * @date $Date: 2015-09-10 02:22:54 +0000 (Thu, 10 Sep 2015) $
 * @version $Revision: 197710 $
 * @brief 
 *  
 **/
interface ITravelShop
{
	/**
	 * 获取信息
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		'sum':int 购买总人次数
	 * 		'score':int 积分
	 * 		'topup':int 用户充值数
	 * 		'finish_time':int 完成进度时间
	 * 		'buy':array 购买信息
	 * 		{
	 * 			$goodsId => $num 商品id => 购买次数
	 * 		}
	 * 		'payback':array 返利信息
	 * 		{
	 * 			$id => $status 返利id => 领取状态 0未领1已领
	 * 		}
	 * 		'reward':array 奖励信息
	 * 		{
	 * 			$id 已领取的奖励id
	 * 		}
	 * }
	 * </code>
	 */
	public function getInfo();
	
	/**
	 * 购买
	 * 
	 * @param int $goodsId 商品id
	 * @param int $num 数量
	 * @return int $finishTime 进度完成时间
	 */
	public function buy($goodsId, $num);
	
	/**
	 * 领取充值返利
	 * 
	 * @param int $id 返利id
	 * @return string 'ok'
	 */
	public function getPayback($id);
	
	/**
	 * 领取普天奖励
	 * 
	 * @param int $id 奖励id,对应配置表的人次
	 * @return string 'ok'
	 */
	public function getReward($id);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */