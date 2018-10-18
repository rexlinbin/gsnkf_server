<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IAchieve.class.php 109011 2014-05-17 07:59:43Z QiangHuang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/achieve/IAchieve.class.php $
 * @author $Author: QiangHuang $(wuqilin@babeltime.com)
 * @date $Date: 2014-05-17 07:59:43 +0000 (Sat, 17 May 2014) $
 * @version $Revision: 109011 $
 * @brief 
 *  
 **/


interface IAchieve
{

	/**
	 * 获取名将成就。
	 * 目前成就系统中只有名将成就，以后如果有其他类型的成就，此接口是否有用，再作考虑
	 * 
	 * @return
	 * 	array
	 * 	[
	 * 		10000,
	 * 		10001
	 * 	]
	 */
	public function getStarAchieve();
	
	/**
	 * @param none
	 * @return array
	 {
		 id => 成就id, 注意这个不是成就类型id, 想知道成就类型去查配置
			 {
			 finish_num =>  ,  进度
			 status => 0 未完成 , 1 完成 , 2 已领奖
			 },
	 }
	 */
	function getInfo();
	
	/**
	 * 
	 * @param int $achieveId  成就id
	 * @return 
	 *  'obtained'
	 *  或
	 *  'unfinished'
	 *  或
	 	'ok'
	 * 
	 */
	function obtainReward($achieveId);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */