<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IPass.class.php 259854 2016-09-01 04:48:40Z MingmingZhu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/pass/IPass.class.php $
 * @author $Author: MingmingZhu $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-09-01 04:48:40 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259854 $
 * @brief 
 *  
 **/
interface IPass
{

	/**
	 * 进入场景
	 * @return
	 * 
	 * array
	 * <code>
	 * 		uid,
	 * 		refresh_time 刷新时间,
	 * 		luxurybox_num 开宝藏宝箱的次数,
	 * 		cur_base 当前位置,
	 * 		pass_num 今天通关的个数,
	 * 		point 当前积分,
	 * 		star_star 当前星星数量,
	 * 		coin 神兵币,
	 * 		lose_num 今天输的次数,
	 * 		buy_num 今天购买的次数
	 * 
   	 *		va_pass  => array(	
   	 *
	 *	  	 				heroInfo =>( hid => array( currHp,currRage, )),							
	 *						chestShow => array( freeBox => int, goldBox => int ), 0是没处理 1是处理过了
	 *						buffShow => array( array(status => int, buff => int )), 0是没处理 1是处理过了
	 *						formation => array( int => hid,  ),	
	 *						bench => array ( int => hid ),					
	 *						buffInfo => array(),
	 *						unionInfo => array(
	 *										int => val,属性及对应的值
	 *											),
	 *						
	 *                      sweepInfo=> array(
	 *                      			count => int 上次通关次数
	 *                      			isSweeped => int 是否扫荡过
	 *                      			buyChest => int 购买宝箱
	 *                      			buyBuff => int 购买buff
	 *                      )
	 *
	 *						)
	 * </code>
	 */
	public function enter();
	
	
	/**
	 * 拉取排名信息50人
	 * @return 
	 * array
	 * <code>
	 * 'top' => array
	 * 		(
	 *  		rank => 
	 * 				uid,
	 * 				utid,
	 * 				name,
	 * 				level,
	 * 				guild_name,
	 * 				point,
	 * 		)
	 * 
	 * 'myRank' => int
	 * </code>
	 */
	public function getRankList();

	/**
	 * 获取对手信息
	 * @param int $id
	 * array
	 * <code>
	 * 		0 => array
	 * 		{
	 * 			uid,
	 * 			utid,
	 * 			name,
	 * 			level,
	 * 			fightForce,	
	 * 			attackBefore,
	 * 			arrHero = array
	 * 			(
	 * 				pos => array(
	 * 								hid,
	 * 								htid,
	 * 								level,
	 * 								evolve_level,
	 * 								currRage,
	 * 							)
	 * 			);
	 * 		},
	 * 		1 => array{},
	 * 		2 => array{},
	 * </code>
	 * 
	 */
	public function getOpponentList( $id );
	
	/**
	 * 处理宝箱
	 * @param int $id 要开启宝箱的标识
	 * @param int $isLuxury 是否宝藏宝箱
	 * 
	 */
	public function dealChest( $id, $isLuxury, $num = 1 );
	
	/**
	 * 放弃金宝箱
	 * @param int $id
	 */
	public function leaveLuxuryChest( $id );
	
	/**
	 * 
	 * 攻击一个据点的某一个难度
	 * @param int $id
	 * @param int $degree
	 * 
	 * @return
	 * array
	 * <code>
	 * appraisal =>'' 
	 * fightStr =>'' 
	 * </code>
	 * 
	 */
	public function attack( $id, $degree, $viceArr );

	/**
	 * 
	 * 购买某个buff
	 * @param int $id
	 * @param int $pos 购买哪一个，999 就是离开
	 * @param array $hidArr 作用对象
	 * 
	 */
	public function dealBuff( $id, $pos, $hidArr );
	
	
	/**
	 * 设置过关斩将阵法
	 * @param array $passFormation @see IFormation::setFormation()
	 * 
	 * @return string @see IFormation::setFormation()
	 */
	public function setPassFormation( $passFormation, $bench = null );
	
	/**
	 * 购买挑战次数
	 * 
	 */
	public function buyAttackNum( $num );
	
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
	 *     coin:int 					神兵币
	 *     free_refresh:int				当天剩余的免费刷新次数
	 *     gold_refresh_num:int   		玩家当日用金币刷新次数
	 *     stone_refresh_num:int 		玩家当日用神兵刷新石刷新次数
	 *     user
	 *     exclude						商品列表中属于特殊格子里的物品
	 *     [
	 *     		goodsId
	 *     ]
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
	 *     drop:array
	 * ]
	 * </code>
	 */
	public function buyGoods($goodsId);
	
	/**
	 * 刷新商品
	 * 
	 * @param bool $isSysRfr 是否是系统免费刷新，默认不是系统免费刷新
	 * @return array
	 * <code>
	 * [
	 *     goods_list:array
	 *     [
	 *         goodsId=>canBuyNum
	 *     ]
	 *     free_refresh:int				当天剩余的免费刷新次数	
	 *     gold_refresh_num:int			玩家当日用金币刷新次数
	 *     stone_refresh_num:int 		玩家当日用神兵刷新石刷新次数
	 *     exclude						商品列表中属于特殊格子里的物品
	 *     [
	 *     		goodsId
	 *     ]
	 * ]
	 * </code>
	 */
	public function refreshGoodsList($isSysRfr = FALSE);
	
	/**
	 * 扫荡
	 * @param $buyChest int 购买宝箱数量
	 * @param $buyBuff int 是否购买buff
	 * 
	 * @return array 
	 * <code> 
	 * [  
	 * 		Level: array
	 * 		[ 
	 * 			0  ：[ chestId ] 获得宝箱
	 * 			1 : [ [ buffId , int 花费的星] ] 获得buff
	 * 			2 : int 剩余星星数
	 *          3 ：  int 购买金币宝箱所花费金币
	 * 		]
	 * ]
	 * </code>
	 * */
	public function sweep($buyChest, $buyBuff);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */