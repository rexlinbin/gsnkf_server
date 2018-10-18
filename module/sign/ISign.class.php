<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ISign.class.php 136427 2014-10-16 05:53:41Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sign/ISign.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-10-16 05:53:41 +0000 (Thu, 16 Oct 2014) $
 * @version $Revision: 136427 $
 * @brief 
 *  
 **/
interface ISign
{
	/**
	 * 获取累积签到信息
	 * @param 
	 * @return array
	 * <code>
	 *array(	
	 *		'uid' 			=> int,
	 *		'sign_num' 	=> int, 				总的登陆次数
	 *		'acc_got'		=> array( 1,3,4... ),累计签到已经领取了的奖励
	 *		),				
	 *);
	 * </code>
	 */
	public function getAccInfo();
	
	/**
	 * 获取连续签到信息
	 * @param
	 * @return array
	 * <code>
	 *array(
	 *		'uid' 			=> int,
	 *		'sign_num' 	=> int, 		签到次数也就是奖励已经领取的次数
	 *		'normal_list'	=> array(),		连续签到列表
	 *		),
	 *);
	 * </code>
	 */
	public function getNormalInfo();

	/**
	 * 领取连续签到奖励
	 * @param  $step: 要领取哪一天的奖励（如连续签到3天了，领奖时则填3） 
	 * 
	*/
	public function gainNormalSignReward( $step );

	
	/**
	 * 领取累积签到奖励
	 * @param  $index: 要领奖励的id（配置表中的id）
	 * 
	*/
	public function gainAccSignReward( $index );

	/**
	 * 获取月签到的信息
	 * @return
	 * [
	 *  'uid'  			=> $uid ,
	 *	'sign_time'		=> 0 , 最后一次签到时间
	 *	'sign_num' 	 	=> 0 , 到现在为止的签到次数
	 *	'reward_vip'	=> 8887, 今天最后一次领奖时的vip（ 当为-1的时候表示今天还没领过奖）
	 *	'va_monthsign' 	=> array('rewarded' => array(1,2,4,7)),已经领取过的奖励，不管vip
	 * ]
	 */
	public function getMonthSignInfo ();
	
	/**
	 * 获取月签到的奖励
	 * @param int $day 要获取第几天的奖励
	 * @return 'ok'
	 * 
	 */
	public function gainMonthSignReward ();
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */