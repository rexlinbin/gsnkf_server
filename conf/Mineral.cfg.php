<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mineral.cfg.php 117501 2014-06-26 11:02:25Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Mineral.cfg.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-26 11:02:25 +0000 (Thu, 26 Jun 2014) $
 * @version $Revision: 117501 $
 * @brief 
 *  
 **/
class MineralConf
{
    //占领一个资源矿消耗行动力
	public static $CAPTURE_PIT_NEED_EXECUTION = 5;
	//每天能免费占矿的时间
	public static $CAPTURE_PIT_START_TIME    =    9;
	public static $CAPTURE_PIT_END_TIME    =    23;
	//强夺矿坑花费金币
	public static $GRAB_PIT_BY_GOLD_NUM    =    20;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */