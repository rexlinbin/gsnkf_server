<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: StatisticsUtil.class.php 60628 2013-08-21 09:49:35Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/statistics/StatisticsUtil.class.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2013-08-21 09:49:35 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60628 $
 * @brief
 *
 **/

class StatisticsUtil
{
	/**
	 *
	 * 得到服务器ID
	 *
	 * @return int
	 */
	public static function getServerId()
	{
		return Util::getServerId();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */