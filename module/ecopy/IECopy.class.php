<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IECopy.class.php 181611 2015-06-30 07:58:59Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ecopy/IECopy.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-06-30 07:58:59 +0000 (Tue, 30 Jun 2015) $
 * @version $Revision: 181611 $
 * @brief 
 *  
 **/
interface IECopy
{
	/**
	 * 返回精英副本模块的信息  包括可以挑战次数、副本的攻击进度
	 * @return array
	 * <code>
	 * [
	 * 		uid:int													玩家uid
	 * 		last_defeat_time:int									上次攻击时间
	 * 		can_defeat_num:int	                                                                                                    可以挑战次数
	 *      buy_atk_num									                         购买攻击次数的次数
	 * 		va_copy_info:array										精英副本模块中副本攻击进度
	 * 						[
	 * 							progress:array
	 * 										[
	 * 											copyid:int			副本的攻击状态（0可显示，1可攻击，2已通关）
	 * 										]
	 * 						]
	 * ]
	 * </code>
	 */
	function getEliteCopyInfo();

	/**
	 * 判断是否能够进入某副本进行攻击
	 * @param int $copyId				精英副本id
	 * @return string ok						如果能够进入副本进行攻击，返回‘ok’
	 */
	function enterCopy($copyId);
	/**
	 * 精英副本的战斗接口
	 * @param int $copyId
	 * @param int $army_id
	 * @param array $fmt			玩家的当前阵型
	 * @return array
	 * <code>
	 * [
	 * 		err:int					ok表示操作成功，execution表示行动力不足
	 * 		fightRet:array 			战斗过程以及结果
	 * 		curHP:array				战斗英雄的当前血量
	 * 				[
	 * 					heroid:int
	 * 				]
	 * 		reward:array			奖励信息
	 * 				[
	 * 					soul:int
	 * 					silver:int
	 * 					gold:int
	 * 					exp:int
	 * 					item:array
	 *                     [
	 *                         iteminfo:array
	 *                             [
	 *                                 item_id:int
     *                                 item_template_id:int
     *                                 item_num:int
	 *                             ]
	 *                     ]
	 * 					hero:array
	 *                     [
	 *                         dropHeroInfo:array
	 *                         [
	 *                             mstId:int    掉落武将的monsterId
     *                             htid:int     掉落的武将htid
	 *                         ]
	 *                        
	 *                     ]
	 * 				]
	 *      extra_reward:array      
	 *          [
	 *             item=>array
	 *             [
	 *                 ItemTmplId=>num
	 *             ]
	 *             hero=>array
	 *             [
	 *                 Htid=>num
	 *             ]
	 *             silver=>int
	 *             soul=>int
	 *             treasFrag=>array
	 *             [
	 *                 TreasFragTmplId=>num
	 *             ]
	 *          ]
	 * 		appraisal:int			战斗结果
	 * 		score:int				副本当前得分
	 * 		newcopyorbase:array		开启的新副本或者据点
	 * ]
	 * </code>
	 */
	function doBattle($copyId,$army_id,$fmt=array());
	/**
	 * 离开副本  应用场景：战斗成功或者失败之后点击返回按钮
	 * @param int $copyId
	 * @return string 'ok'
	 */
	function leaveCopy($copyId);
	/**
	 * 返回副本攻击的攻略以及排名信息
	 * @param int $copyId
	 * @return array
	 * [
	 * 		replay:array
	 * 		rank:array
	 * ]
	 */
	function getCopyDefeatInfo($copyId);

	/**
	 * 更新在某个战斗中死亡的卡牌的血量为满血
	 * @param int $copyId				副本id
	 * @param int $cardId				卡牌id
	 * @return string 'ok'
	 */
	public function reviveCard($copyId,$cardId);
	
	/**
	 * 重新攻击某据点难度级别  应用场景：攻击失败之后点击重新攻击按钮
	 * @param int $copyId
	 * @return string ok
	 */
	public function reFight($copyId);
	
	/**
	 * 购买精英副本攻击次数
	 * @param int $num
	 * @return string 'ok'
	 */
	public function buyAtkNum($num);
	
	/**
	 * 精英副本扫荡
	 * @param int $copyId
	 * @param int $num
	 * @return array
	 * <code>
	 * [
	 *     reward:array  num此扫荡的奖励
	 *     [
	 *         index=>array
	 *         [
	 * 					soul:int
	 * 					silver:int
	 * 					gold:int
	 * 					exp:int
	 * 					item:array
	 *                     [
	 *                         iteminfo:array
	 *                             [
	 *                                 item_id:int
     *                                 item_template_id:int
     *                                 item_num:int
	 *                             ]
	 *                     ]
	 * 					hero:array
	 *                     [
	 *                         dropHeroInfo:array
	 *                         [
	 *                             mstId:int    掉落武将的monsterId
     *                             htid:int     掉落的武将htid
	 *                         ]
	 *                        
	 *                     ]
	 *         ]
	 *     ]
	 *     extra_reward:array
	 *     [
	 *             item=>array
	 *             [
	 *                 ItemTmplId=>num
	 *             ]
	 *             hero=>array
	 *             [
	 *                 Htid=>num
	 *             ]
	 *             silver=>int
	 *             soul=>int
	 *             treasFrag=>array
	 *             [
	 *                 TreasFragTmplId=>num
	 *             ]
	 *     ]
	 * ]
	 * </code>
	 */
	public function sweep($copyId, $num=1);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */