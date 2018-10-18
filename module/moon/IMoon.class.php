<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IMoon.class.php 245659 2016-06-06 09:29:05Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/IMoon.class.php $
 * @author $Author: QingYao $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-06-06 09:29:05 +0000 (Mon, 06 Jun 2016) $
 * @version $Revision: 245659 $
 * @brief 
 *  
 **/

/*******************************接口修改记录*******************************
 * 创建接口 									20150421-14:20:00
 * 商店的接口去掉了一些不必须要的字段					20150422-18:48:00
 * 商店增加buyBox接口，开宝箱						20150505-12:04:00
 * getShopInfo增加字段buy_box_count			20150505-13:18:00
 * addAttackNum增加nightmare参数                                            20151228-18：58：00
 * attackBoss增加nightmare参数                                                  20151228-18：58：00
 * 增加兵符商店  buyBingfu接口						20160104-10:19:00
 * 增加扫荡功能   sweep                       2016-5-30 17:44:06
* @author Administrator
* 
*
*/
 
interface IMoon
{
	/**
	 * 获得基本信息
	 * 
	 * @return
	 * {
	 * 		tg_num								天工令，操蛋的名字
	 * 		atk_num								攻击BOSS的次数
	 * 		buy_num								今天购买的次数
	 *      max_pass_copy						已经通关的最大副本Id
	 *      nightmare_atk_num                   梦魇可攻打次数(购买次数+免费剩余次数),功能节点未开启时返回0
	 *      nightmare_buy_num                   梦魇购买次数	
	 * 		max_nightmare_pass_copy			            已经通关的最大梦魇bossId
	 * 		grid => array						最新的副本信息
	 * 		[
	 * 			index => status					index取值1-9,status取值1-3分别代表 锁定/解锁/已攻打或者已领取
	 * 		]
	 * }
	 */
	public function getMoonInfo();
	
	/**
	 * 攻打某个副本某个格子的怪物
	 * 
	 * @param int $copyId
	 * @param int $gridId
	 * @return array
	 * {
	 * 		ret	=> 'ok'							返回值
	 * 		fightRet => string					战斗串
	 * 		appraise => int						战斗评价
	 * 		open_grid => array(index)			开启的所有新格子
	 * 		open_boss => int					是否开启了本副本的BOSS，1开启，0未开启
	 * }
	 */
	public function attackMonster($copyId, $gridId);
	
	/**
	 * 领取某个副本某个格子的的宝箱
	 *
	 * @param int $copyId
	 * @param int $gridId
	 * @return array
	 * {
	 * 		ret	=> 'ok'							返回值
	 * 		open_grid => array(index)			开启的所有新格子
	 * 		open_boss => int					如果胜利的话，并且开启了所有的格子，这个字段是1,否则是0
	 * }
	 */
	public function openBox($copyId, $gridId);
	
	/**
	 * 攻打某个副本的BOSS
	 * 
	 * @param int $copyId
	 * @param int $nightmare 是否为梦魇模式  0或1
	 * @return array
	 * {
	 * 		ret => 'ok'							返回值
	 * 		fightRet => string					战斗串
	 * 		appraise => int						战斗评价
	 * 		drop => array						掉落
	 *      open_copy => int					开启的新副本
	 * }
	 */
	public function attackBoss($copyId, $nightmare = MoonTypeDef::BOSS_NORMAL_TYPE);
	
	/**
	 * 花金币购买攻击次数
	 * @param int $nightmare 是否为梦魇模式  0或1
	 * @return ok
	 */
	public function addAttackNum($nightmare = MoonTypeDef::BOSS_NORMAL_TYPE);
	
	/**
	 * 花金币开宝箱
	 * @return
	 * {
	 * 		ret => 'ok'							返回值
	 * 		drop => array						掉落的奖励
	 * 		{
	 * 			array(1,0,200)					银币200
	 * 			array(7,60007,20)				物品60007 20个
	 * 			......
	 * 		}
	 * }
	 */
	public function buyBox();
	
	/**
	 * 商店信息
	 *
	 * @return array
	 * <code>
	 * [
	 *     goods_list:array
	 *     [
	 *         goodsId=>canBuyNum
	 *     ]
	 *     gold_refresh_num:int   		玩家当日金币刷新次数
	 *     free_refresh_num			             当天免费刷新剩余的次数
	 *     buy_box_count:int			 玩家当日买宝箱的次数
	 * ]
	 * </code>
	 */
	public function getShopInfo();
	
	/**
	 * 购买物品
	 *
	 * @param int $goodsId
	 * @return array
	 * <code>
	 * [
	 *     ret:string            'ok'
	 *     drop					 如果没有的话是空数组
	 * ]
	 * </code>
	 */
	public function buyGoods($goodsId);
	
	/**
	 * 刷新商品
	 *
	 * @return array
	 * <code>
	 * [
	 *     goods_list:array
	 *     [
	 *         goodsId=>canBuyNum
	 *     ]
	 *     gold_refresh_num:int       玩家当日金币刷新次数
	 *     free_refresh_num			      当天免费刷新剩余的次数
	 * ]
	 * </code>
	 */
	public function refreshGoodsList();
	
	/**
	 * 购买兵符商店的商品
	 * 
	 * @param 商品id $goodsId
	 * @param 购买数量 $num
	 */
	public function buyTally($goodsId, $num = 1);
	/**
	 * 商店信息
	 *
	 * @return array
	 * <code>
	 * [
	 *     goods_list:array
	 *     [
	 *         goodsId=>canBuyNum
	 *     ]
	 *     gold_refresh_num:int   		玩家当日金币刷新次数
	 *     free_refresh_num			             当天免费刷新剩余的次数
	 * ]
	 * </code>
	 */
	public function getTallyInfo();
	/**
	 * 刷新商品
	 *
	 * @return array
	 * <code>
	 * [
	 *     goods_list:array
	 *     [
	 *         goodsId=>canBuyNum
	 *     ]
	 *     gold_refresh_num:int       玩家当日金币刷新次数
	 *     free_refresh_num			      当天免费刷新剩余的次数
	 * ]
	 * </code>
	 */
	public function refreshTallyGoodsList();
	
	/**
	 * 
	 * @param int $nightmare 是否为梦魇模式  0或1
	 *  @return array
	 *  [ 
	 *  	0=>array()   //奖励三元组数组
	 *  	1=>array()
	 *  ]
	 * 
	 */
	public function sweep($nightmare = MoonTypeDef::BOSS_NORMAL_TYPE);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */