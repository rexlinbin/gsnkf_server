<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ISale.class.php 65217 2013-09-17 10:48:37Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/sale/ISale.class.php $
 * @author $Author: ShiyuZhang $(tianming@babeltime.com)
 * @date $Date: 2013-09-17 10:48:37 +0000 (Tue, 17 Sep 2013) $
 * @version $Revision: 65217 $
 * @brief 
 *  
 **/
interface ISale
{
	/**
	 * 商品信息
	 *
	 * @return array
	 * <code>
	 * {
	 * 		$goodsId				商品id
	 * 		{
	 * 			'num'				购买次数
	 * 			'time'				购买时间
	 * 		}
	 * }
	 * </code>
	 */
	public function getGoodsInfo();
	
	/**
	 * 购买商品
	 *
	 * @param int $goodsId			商品id
	 * @param int $num				数量
	 * @return 
	 * array (
	 *    'ret' => 'ok',
	 *	  'drop' => $return, //如果有随机掉落的话，这是掉落物品
	 * )
	*/
	public function buy($goodsId, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */