<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Friend.cfg.php 81882 2013-12-19 11:02:26Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Friend.cfg.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-12-19 11:02:26 +0000 (Thu, 19 Dec 2013) $
 * @version $Revision: 81882 $
 * @brief 
 *  
 **/
class FriendCfg
{
	//最大好友数量
	const MAX_FRIEND_NUM = 100;
	//最大好友拉取数量
	const MAX_FETCH = 100;
	//推荐好友等级约束偏移
	const RECMOD_LEVEL_OFFSET = 10;
	//推荐好友每次拉取最大数量
	const RECOND_FETCH_NUM = 100;
	//推荐好友总的拉取数量
	const RECOND_TOTAL_NUM = 120;
	
	const MAX_KEEP_TIME = 1296000;//15*24*3600
	
	const MAX_KEEP_NUM = 60;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */