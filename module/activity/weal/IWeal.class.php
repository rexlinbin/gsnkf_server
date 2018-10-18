<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IWeal.class.php 94522 2014-03-20 10:35:16Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/weal/IWeal.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-03-20 10:35:16 +0000 (Thu, 20 Mar 2014) $
 * @version $Revision: 94522 $
 * @brief 
 *  
 **/
interface  IWeal
{
	/**
	 * 获取翻卡信息
	 * return
	 * {
	 * 		'point_today' => int,
	 * 		'refresh_time' => int,
	 * }
	 */
	public function getKaInfo();
	
	/**
	 * 翻一次卡
	 * {
	 * 		//掉落信息待完善
	 * }
	 */
	public function kaOnce();
} 
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */