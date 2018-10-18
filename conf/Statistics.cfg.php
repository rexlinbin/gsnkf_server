<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Statistics.cfg.php 63059 2013-09-05 06:32:54Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Statistics.cfg.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2013-09-05 06:32:54 +0000 (Thu, 05 Sep 2013) $
 * @version $Revision: 63059 $
 * @brief
 *
 **/

class StatisticsConfig
{
	const DEFAULT_SERVER_ID						=			0;

	const DB_NAME								=			'sanguo_stat';
	
	static $ARR_WHITE_IP = array('124.205.151.82', '119.255.38.86');
	
	const WHITE_IP_TO = '127.0.0.1';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */