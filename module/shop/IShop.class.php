<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IShop.class.php 123410 2014-07-28 12:03:52Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/shop/IShop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-07-28 12:03:52 +0000 (Mon, 28 Jul 2014) $
 * @version $Revision: 123410 $
 * @brief 
 *  
 **/
/**********************************************************************************************************************
 * Class       : IShop
 * Description : 商店系统对外接口类
 * Inherit     :
 **********************************************************************************************************************/

interface IShop
{
	/**
	 * 获取用户酒馆信息
	 *
	 * @return array 
	 * <code> 
	 * {
	 * 		'point'							积分
	 * 		'bronze_recruit_num'			青铜累积招将次数
	 * 		'silver_recruit_num'			白银剩余免费招将次数
	 * 		'silver_recruit_time'			白银招将冷却时间,为0就是没有冷却时间,自动转为免费
	 * 		'silver_recruit_status'			白银首刷的状态，0免费和金币都未使用，1免费使用但金币未使用，2金币使用但免费未使用，3免费金币都使用
	 *		'gold_recruit_num'				黄金剩余免费招将次数
	 * 		'gold_recruit_sum'				黄金累积招将次数
	 * 		'gold_recruit_time'				黄金招将冷却时间,为0就是没有冷却时间,自动转为免费
	 * 		'gold_recruit_status'			黄金首刷的状态，0免费和金币都未使用，1免费使用但金币未使用，2金币使用但免费未使用，3免费金币都使用
	 * 		'va_shop'						
	 * 		{
	 * 			'vip_gift'					vip领奖信息,为空表示没有购买任何vip礼包
	 * 			{
	 *				0 => 1					已领取
	 * 				1 => 0					未领取
	 * 				2 => 1					已领取
	 * 				...
	 * 			}
	 * 		}
	 * 		'goods'
	 * 		{
	 * 			$goodsId					商品id
	 * 			{
	 * 				'num'					购买次数 
	 * 				'time'					购买时间
	 * 			}
	 * 		}
	 * }
	 * </code>
	 */
	public function getShopInfo();
	
	/**
	 * 酒馆青铜招将
	 * 需要消耗物品
	 * 
	 * @return array 
	 * <code>
	 * {
	 * 		'hero'
	 * 		{
	 * 			$hid => $htid				掉落的武将信息		
	 * 		}
	 * 		'star'
	 * 		{	
	 * 			$sid => $stid				掉落的名将信息
	 * 		}
	 * 		'item'
	 * 		{
	 * 			$itemTplId => $num
	 * 		}
	 * }
	 * </code>
	 */
	public function bronzeRecruit();
	
	/**
	 * 酒馆白银招将
	 *
	 * @param int $isCost					是否使用金币招将，1使用，0未用。
	 * @return array 						
	 * <code>
	 * {
	 * 		'hero'
	 * 		{
	 * 			$hid => $htid				掉落的武将信息		
	 * 		}
	 * 		'star'
	 * 		{	
	 * 			$sid => $stid				掉落的名将信息
	 * 		}
	 * 		'item'
	 * 		{
	 * 			$itemTplId => $num
	 * 		}
	 * }
	 * </code>
	 */
	public function silverRecruit($isCost);
	
	/**
	 * 酒馆黄金招将
	 *
	 * @param int $isCost					是否使用金币招将，1使用，0未用。
	 * @param int $num						招将次数, 1或10,默认1.
	 * @return array 						
	 * <code>
	 * {
	 * 		'hero'
	 * 		{
	 * 			$hid => $htid				掉落的武将信息		
	 * 		}
	 * 		'star'
	 * 		{	
	 * 			$sid => $stid				掉落的名将信息
	 * 		}
	 * 		'item'
	 * 		{
	 * 			$itemTplId => $num
	 * 		}
	 * }
	 * </code>
	 */
	public function goldRecruit($isCost, $num = 1);
	
	/**
	 * 购买VIP礼包
	 * 
	 * @param int $vip
	 * @return string 'ok'				
	 */
	public function buyVipGift($vip);
	
	/**
	 * 购买商品
	 * 
	 * @param int $goodsId					商品id	
	 * @param int $num						数量
	 * @return array									
	 * <code>
	 * 	{
	 * 		'ret':string
	 *     		'ok'									成功
	 * 		'drop':array								掉落信息
	 * 		{
	 * 			'item':array							物品
	 * 			{
	 * 				itemTemplateId => itemNum			物品模板id和数量
	 * 			}
	 * 			'hero':array							武将
	 * 			{	
	 * 				heroTid => heroNum					武将模板id和数量
	 * 			}
	 * 			'treasFrag':array						宝物碎片
	 * 			{
	 * 				itemTemplateId => itemNum			物品模板id和数量
	 *			}
	 * 			'silver':int							银币数量
	 * 			'soul':int								将魂数量	
	 * 		}
	 *  }
	 * </code>		
	 */
	public function buyGoods($goodsId, $num);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */