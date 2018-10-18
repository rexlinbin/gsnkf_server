<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: WhiteList.cfg.php 60628 2013-08-21 09:49:35Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/conf/WhiteList.cfg.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2013-08-21 17:49:35 +0800 (三, 2013-08-21) $
 * @version $Revision: 60628 $
 * @brief
 *
 **/

class WhiteListConfig
{
	/**
	 * 允许访问的机器白名单
	 * @var array
	 */
	static $ARR_WHITE_LIST = array ('127.0.0.1', '58.220.3.170' );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
