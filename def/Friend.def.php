<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Friend.def.php 81863 2013-12-19 10:45:45Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Friend.def.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-12-19 10:45:45 +0000 (Thu, 19 Dec 2013) $
 * @version $Revision: 81863 $
 * @brief 
 *  
 **/
class FriendDef
{
	//数据库表名
	const TBL_NAME = 't_friend';
	const LOVE_TBL_NAME = 't_friendlove';

	//朋友关系
	const STATUS_OK = 0;
	const STATUS_DEL = 1;
	
	//发送好友相关邮件的类型
	const APPLY	= 11;
	const REJECT = 12;
	const ADD = 13;
	const DEL = 14;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */