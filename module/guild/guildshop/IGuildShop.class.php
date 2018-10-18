<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IGuildShop.class.php 183635 2015-07-10 10:33:26Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/guildshop/IGuildShop.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-07-10 10:33:26 +0000 (Fri, 10 Jul 2015) $
 * @version $Revision: 183635 $
 * @brief 
 *  
 **/
interface IGuildShop
{
	/**
	 * 获得商店信息
	 * 
	 * @return array
	 * <code>
	 * {
	 *     'normal_goods':array		普通商品列表
	 *     {
	 *         $goodsId:array
	 *         {
	 *         		'num':int		个人购买次数
	 *         		'sum':int		军团购买次数			
	 *         }
	 *     }
	 *     'special_goods':array	珍品商品列表
	 *     {
	 *         $goodsId:array
	 *         {
	 *         		'num':int		个人购买次数
	 *         		'sum':int		军团购买次数			
	 *         }
	 *     }
	 *     'refresh_cd':int         系统刷新CD
	 * }
	 * </code>
	 */
	public function getShopInfo();
	
	/**
	 * 购买
	 *
	 * @param int $goodsId			商品id
	 * @param int $num				数量
	 * @return string $ret 			处理结果
	 * 'ok'							成功
	 * 'failed'						失败,次数不够
	 */
	function buy($goodsId, $num);
	
	/**
	 * 刷新珍品类商品列表
	 * 
	 * @return array
	 * <code>
	 * {
	 * 		'special_goods':array	珍品商品列表
	 * 		{
	 * 			$goodsId:array
	 *         {
	 *         		'num':int		个人购买次数		
	 *         		'sum':int		军团购买次数	
	 *         }
	 * 		}
	 * 		'refresh_cd':int        系统刷新CD
	 * }
	 * </code>
	 */
	function refreshList();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */