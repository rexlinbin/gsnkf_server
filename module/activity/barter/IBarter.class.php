<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IBarter.class.php 65215 2013-09-17 10:47:18Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/barter/IBarter.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-17 10:47:18 +0000 (Tue, 17 Sep 2013) $
 * @version $Revision: 65215 $
 * @brief 
 *  
 **/
interface IBarter
{
	/**
	 * 获取兑换信息
	 */
	public function getBarterInfo();
	
	/**
	 * 兑换
	 * @param unknown $exchangeId 换物品的id
	 * @param unknown $arrHid 兑换物品需要消耗的英雄
	 * 
	 * @return 
	 * array(
	 * 		'ret' => 'ok',
	 *		'drop' => $return, //如果有随机掉落的话这是实际获得的东西
	 * )
	 */
	public function barterExchange( $exchangeId, $arrHid );
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */