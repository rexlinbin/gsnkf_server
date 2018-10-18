<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ISevensLottery.class.php 255050 2016-08-08 08:39:26Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sevenslottery/ISevensLottery.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-08 08:39:26 +0000 (Mon, 08 Aug 2016) $
 * @version $Revision: 255050 $
 * @brief 
 *  
 **/
interface ISevensLottery
{
	/**
	 * 获取信息
	 *
	 * @return array 				
	 * <code>
	 * {
	 * 		'period_start':int	本周期开始时间
	 * 		'period_end':int	本周期结束时间
	 * 		'curr_id':int		本周期id
	 * 		'next_id':int		下周期id
	 * 		'free':int			本周期免费次数
	 * 		'num':int			每日使用金币抽奖次数
	 * 		'point':int			积分
	 * 		'lucky':int			幸运值
	 * }
	 * </code>
	 */
	public function getSevensLotteryInfo();
	
	/**
	 * 抽奖
	 * 
	 * @param int $type 类型 0免费1道具2金币
	 * @return array 						
	 * <code>
	 * {
	 * 		'item'						物品	
	 * 		{
	 * 			$itemTplId => $num			
	 * 		}
	 * 		'lucky':int					幸运值
	 * }
	 * </code>
	 */
	public function lottery($type);
	
	/**
	 * 获取商店信息
	 *
	 * @return array
	 * <code>
	 * {
	 * 		'goods':
	 * 		{
	 * 			$goodsId					商品id
	 * 			{
	 * 				'num'					购买次数
	 * 				'time'					购买时间
	 * 			}
	 * 		}
	 * 		'point' => $point				积分
	 * }
	 * </code>
	 */
	public function getShopInfo();
	
	/**
	 * 商店兑换商品
	 *
	 * @param int $goodsId				商品id
	 * @param int $num					数量
	 * @param string 'ok'
	*/
	public function buy($goodsId, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */