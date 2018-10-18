<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IMineralElves.class.php 245724 2016-06-07 02:40:47Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/mineralelves/IMineralElves.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-06-07 02:40:47 +0000 (Tue, 07 Jun 2016) $
 * @version $Revision: 245724 $
 * @brief 
 *  
 **/
interface IMineralElves
{
	/**
	 * 获取玩家自己的精灵信息，没有就返回空array
	 * return array(
	 * 												["domain_id"]=>int(60003)
	 *                                 				["uid"]=>int(20585)
	 *                                 				["start_time"]=>float(1462863620)
	 *                                 				["end_time"]=>float(1462863920)
	 * )
	 */
	public function getSelfMineralElves();
	
	/**
	 * 获取当前时间所有的矿精灵信息
	 * return  array(2) {
	 *                                array(
	 *                                				["domain_id"]=>int(60003)
	 *                                 				["uid"]=>int(20585)
	 *                                 				["start_time"]=>float(1462863620)
	 *                                 				["end_time"]=>float(1462863920)
	 *                                 			),
	 *                                 array(
	 *                                				["domain_id"]=>int(60003)
	 *                                 				["uid"]=>int(20585)
	 *                                 				["start_time"]=>float(1462863620)
	 *                                 				["end_time"]=>float(1462863920)
	 *                                 			),
	 *                                 }
	 */
	public function getMineralElves();
	
	/**
	 * 根据矿页domain_id获取当前页的精灵信息
	 * @param int $domain_id
	 * return  array(
	 * 					array(4) {
	 *                                ["domain_id"]=>int(60003)
	 *                                 ["uid"]=>int(20585)
	 *                                 ["start_time"]=>float(1462863620)
	 *                                 ["end_time"]=>float(1462863920)
	 *                                 ["guild_name"]=>babeltime,
	 *                                 ["uname"]=>babeltime,
	 *                                 ["level"]=>20,
	 *                                 }
	 *                         )
	 */
	public function getMineralElvesByDomainId($domain_id);
	
	
	public function leave();
	
	/**
	 * 占领这个矿精灵
	 * @param int $domain_id
	 * return array(
	 *                           ["fight_ret"]=>
  	 *			                             string(812) "战报"
  	 *	                          ["appraisal"]=>
      *                                      string(3) "SSS"
      *                           ["elves_info"]=>     
      *                           				 array(4) {
      *                                                           ["domain_id"]=>int(60005)
      *                                                           ["uid"]=>int(20589)
      *                                                           ["start_time"]=>float(1462862851)
      *                                                            ["end_time"]=> float(1462863151)
      *                                                            }
      *                          )
	 */
	public function occupyMineralElves($domain_id);
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */