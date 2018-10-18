<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ITower.class.php 255251 2016-08-09 07:30:26Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/tower/ITower.class.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-09 07:30:26 +0000 (Tue, 09 Aug 2016) $
 * @version $Revision: 255251 $
 * @brief 
 *  
 **/
interface ITower
{
	/**
	 * 获取某个用户的爬塔系统的信息
	 * 1.更新挑战次数等信息 2.check是否有新的副本开启 3.获取爬塔系统信息
	 * @return array
	 * <code>
	 * [
	 * 	uid:int								玩家id
	 * 	max_level:int						玩家达到塔层的最高级别
	 * 	max_level_time:int					玩家达到最高塔层的时间
	 * 	cur_level:int						当前所在的塔层
	 * 	last_refresh_time:int				上一次刷新攻击次数、重置次数的时间
	 *  reset_num:int                       可以重置的次数
	 *  can_fail_num:int                    可以失败的次数
	 *  gold_buy_num:int					使用金币购买挑战失败的次数
	 *  buy_atk_num:int						购买攻击次数
	 *  buy_special_num:int					购买神秘塔层次数	
	 *  max_hell:int                        玩家达到试炼噩梦的最高级别
	 *  cur_hell:int                        玩家当前所在试炼噩梦的级别
	 *  reset_hell:int                      试炼噩梦的可重置次数
	 *  can_fail_hell:int                   试炼噩梦的可失败次数
	 *  gold_buy_hell:int                   试炼噩梦的金币购买挑战失败的次数
	 *  buy_hell_num: int                   试炼噩梦的购买攻打次数
	 *  
	 * 	va_tower_info:array							
	 * 				[
	 *                  sweep_info:array
	 *                  [
	 *                     start_level:int
	 *                     level_num:int
	 *                     start_time:int
	 *                  ]
	 *                  cur_status:int        1表示通关了所有塔层，0表示没有通关所有塔层
	 *                  special_tower:array
	 *                  [
	 *                      specail_tower_list:array
	 *                      [
	 *                          tower_level_id=>array
	 *                          [
	 *                              0=>int//据点id
	 *                              1=>int//关卡开始时间
	 *                              2=>int//攻击次关卡的次数
	 *                          ]
	 *                      ]
	 *                  ]
	 *                  sweep_hell_info:array
	 *                  [
	 *                     start_level:int
	 *                     level_num:int
	 *                     start_time:int
	 *                  ]
	 *                  cur_hell_status:int 1表示通关了所有塔层，0表示没有通关所有塔层
	 * 				]
	 * ]
	 * </code>
	 */
	function getTowerInfo();
	
	/**
	 * 击败塔层中的怪物
	 * @param int $level
	 * @param int $armyId
	 * @param int $type
	 * @return array
	 * <code>
	 * [
	 * 	fightRet:
	 * 	appraisal:int
	 * 	reward:array
	 * 			[
	 * 				silver:int
	 * 				soul:int
	 * 				item:array
	 * 				stamina:int
	 * 				execution:int
	 *              tower_num:int 
	 * 			]
	 * cd:int
	 * newcopyorbase:array     
	 *     [
	 *         pass:bool
	 *         tower_info:array
	 *     ]   
	 * ]
	 * </code>
	 */
	function defeatMonster($level,$armyId, $type=1);	
	
	/**
	 * 进入某个塔层进行攻击
	 * @param int $level	塔层id
	 * @param int $type     类型( 1：普通试练塔 2：试炼噩梦 )
	 * @return	string	'ok'
	 */
	function enterLevel($level, $type=1);
	/**
	 * 离开爬塔系统
	 * @return string 'ok'
	 */
	function leaveTower();
	/**
	 * 重置爬塔
	 * @param $type
	 */
	function resetTower($type=1);
	/**
	 * 爬塔扫荡
	 * @param int $startLv
	 * @param int $levelNum
	 * @param int $type
	 * @return array        tower_info
	 */
	function sweep($startLv,$levelNum, $type=1);
	/**
	 * 终止扫荡
	 * @param $type
	 * @return string 'ok'
	 */
	function endSweep($type=1);
	/**
	 * 获取爬塔系统排行榜
	 * @param int $rankNum  获取排名个数
	 * @return array
	 * <code>
	 * [
	 *     rank_list:array
	 *     [
	 *         array
	 *         [
	 *             uid:int
	 *             uname:string
	 *             level:int
	 *             htid:int
	 *             rank:int
	 *             max_level:int            此玩家的最高塔层
	 *             dress:array
	 *         ]
	 *     ]
	 *     user_rank:array
	 *     [
	 *         rank:int 0-10            0表示未上榜
	 *         max_level:int        
	 *     ]
	 *     
	 * ]
	 * </code>
	 */
	function getTowerRank($rankNum);
	
	function reviveCard($towerLv,$cardId);
	
	function leaveTowerLv($towerLv);
	/**
	 * 
	 * @param int $towerLvId
	 * @return string 'ok'
	 */
	function enterSpecailLevel($towerLvId);
	/**
	 * 击败塔层中的怪物
	 * @param int $level
	 * @param int $armyId
	 * @return array
	 * <code>
	 * [
	 * 	fightRet:
	 * 	appraisal:int
	 * 	reward:array
	 * 			[
	 * 				silver:int
	 * 				soul:int
	 * 				item:array
	 * 				stamina:int
	 * 				execution:int
	 * 			]
	 * cd:int
	 * newcopyorbase:array     
	 *     [
	 *         pass:bool
	 *         tower_info:array
	 *     ]   
	 * ]
	 * </code>
	 */
	function defeatSpecialTower($towerLvId,$armyId,$fmt=array());
	/**
	 * 购买爬塔失败次数
	 * @param int $num
	 * @param int $type
	 * @return string 'ok'
	 */
	function buyAtkNum($num, $type=1);
	
	/**
	 * 购买挑战次数
	 * @return int				花费金币的数目
	 * @return $type
	 */
	function buyDefeatNum($type=1);
	/**
	 * 购买神秘塔城
	 * @param int $num
	 * @return array  同getTowerInfo的返回值
	 * [
	 *     
	 * ]
	 */
	function buySpecialTower($num=1);
	/**
	 * @param $endLv int
	 * @param $type
	 * @return array 同getTowerInfo
	 * [
	 *     
	 * ]
	 */
	function sweepByGold($endLv=0, $type=1);
	
	/**
	 * 获取商店信息
	 * @return array
	 *         [
	 *             'point':int     剩余积分
	 *             'info' : array  购买信息
	 *                         [
	 *                             id => num 商品id对应的已经购买的次数
	 *                         ]
	 *         ]
	 */
	function getShopInfo();
	
	/**
	 * 购买商品
	 * @param int $id  商品id
	 * @param int $num 数量
	 * @return 'ok'
	 */
	function buy($id, $num=1);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */