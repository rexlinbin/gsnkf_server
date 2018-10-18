<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IStar.class.php 164386 2015-03-31 03:31:00Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/IStar.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-03-31 03:31:00 +0000 (Tue, 31 Mar 2015) $
 * @version $Revision: 164386 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : IStar
 * Description : 名将系统对外接口类
 * Inherit     : 
 **********************************************************************************************************************/

interface IStar
{
	/**
	 * 获取用户拥有的所有名将信息
	 * 没有名将是不能开启名将系统的，调用这个接口会报错
	 *
	 * @return array 
	 * <code> 
	 * {
	 * 		'ret':string
	 *     		'ok'										成功
	 * 		'allStarInfo':array								所有名将信息
	 * 		{
	 * 			'send_num':int  							当天使用的金币赠送次数,
	 * 			'send_time':int								上次刷新时间,
	 * 			'draw_num':int								当天使用的翻牌次数,
	 * 			'va_act_info':array							行为信息
	 * 			{ 
	 * 				'act':array
	 * 				{
	 * 					$actId=>$actNum
	 * 				}
	 * 				'draw':array
	 * 				{
	 * 					$sid:array
	 * 					{
	 * 						0:int							花型，1-7
	 * 						1-5:int							牌id，1-N
	 * 					}
	 * 				}
	 * 				'skill'=>$sid							装备的技能是属于哪个武将的
	 * 			}
	 * 			'star_list':array							名将列表										
	 * 			{
	 * 				star_id:array							名将id
	 *				{
	 *					'star_id':int						名将id
	 *					'star_tid':int						名将模板id
	 *					'level':int							好感度等级
	 *          		'total_exp':int						好感度总值
	 *          		'feel_skill':int					感悟技能id
	 *          		'feel_level':int					感悟度等级
	 *          		'feel_total_exp':int				感悟度总值
	 *          		'pass_hcopy_num':int				武将列传副本通关次数
	 *      		}   
	 *      	}
	 * 		}
	 * 		'athena'
	 * 		{
	 * 			0 => $skillId
	 * 		}
	 * }
	 * </code>
	 */
	public function getAllStarInfo();
	
	/**
	 * 通过赠送礼物增加名将的好感度
	 * 
	 * @param int $sid                                  	名将id
	 * @param int $giftTid                              	礼物模板id
	 * @param int $giftNum                              	礼物数量                          
	 * @return boolean										是否产生暴击，true暴击，false没有暴击
	 */
	public function addFavorByGift($sid, $giftTid, $giftNum);
	
	/**
	 * 全部赠送
	 * 
	 * @param int $sid										名将id
	 * @return array 
	 * <code> 
	 * {
	 * 		'fatal':int										总暴击数
	 * 		'exp':int										总经验值
	 * }
	 * </code>
	 */
	public function addFavorByAllGifts($sid);
	
	/**
	 * 通过赠送金币增加名将的好感度
	 * 
	 * @param int $sid                                  	名将id
	 * @return string 'ok'									成功
	 */
	public function addFavorByGold($sid);
	
	/**
	 * 通过行为事件增进名将的感情
	 * 
	 * @param int $sid                                  	名将id
	 * @param int $actId									事件id
	 * @return array 
	 * <code> 
	 * {
	 * 		'ret':string
	 *     		'ok'										成功
	 * 		'trigerId':int									答题包id, 为0表示未触发答题事件
	 * }
	 * </code>
	 */
	public function addFavorByAct($sid, $actId);
	
	/**
	 * 答题
	 *
	 * @param int $sid                                  	名将id
	 * @param int $trigerId									答题包id	
	 * @param int $optionId									选项id
	 * @return string 'ok' 									成功
	 */
	public function answer($sid, $trigerId, $optionId);
	
	/**
	 * 名将好感度互换
	 * 
	 * @param int $sida										名将id
	 * @param int $sidb										名将id
	 * @return string 'ok'
	 */
	public function swap($sida, $sidb);
	
	/**
	 * 翻牌
	 * 
	 * @param int $sid
	 * @return array
	 * <code>
	 * {
	 * 		0 => 花型，1-7
	 * 		1-5 => 牌id，1-N
	 * }
	 * </code>
	 */
	public function draw($sid);
	
	/**
	 * 一键洗牌
	 *
	 * @param int $sid
	 * @return array
	 * <code>
	 * {
	 * 		0 => 花型，1-7
	 * 		1-5 => 牌id，1-N
	 * }
	 * </code>
	 */
	public function shuffle($sid);
	
	/**
	 * 领奖
	 * 
	 * @param int $sid
	 * @return string 'ok'
	 */
	public function getReward($sid);
	
	/**
	 * 更换技能
	 * 
	 * @param int $sid 传0就是换回主角自己的技能
	 * @return string 'ok'
	 */
	public function changeSkill($sid);
	
	/**
	 * 一键学艺
	 * 
	 * @param int $sid
	 * @return int $feel 感悟值
	 */
	public function quickDraw($sid);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */