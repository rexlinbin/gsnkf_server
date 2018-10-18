<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ISignactivity.class.php 232025 2016-03-10 08:33:38Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/signactivity/ISignactivity.class.php $
 * @author $Author: JiexinLin $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-03-10 08:33:38 +0000 (Thu, 10 Mar 2016) $
 * @version $Revision: 232025 $
 * @brief 
 *  
 **/
interface  ISignactivity
{
	/**
	 * 獲取活動信息
	 * @return 
	 * array
	 * (
	 * 		'acti_sign_num' => int 本次活動登陸次數
	 * 		'acti_sign_time' => int 最后一次签到时间戳
	 * 		'today'	=>	int 今天是活动的第几天
	 * 		'va_acti_sign' => array(1,2,5,3...)已經領取的獎勵id
	 * )
	 * 
	 */
	public function getSignactivityInfo();
	
	/**
	 * 獲取獎勵
	 * @param int $id
	 */
	public function gainSignactivityReward($id);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */