<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Moon.cfg.php 169111 2015-04-22 12:57:21Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Moon.cfg.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-04-22 12:57:21 +0000 (Wed, 22 Apr 2015) $
 * @version $Revision: 169111 $
 * @brief 
 *  
 **/
 
class MoonConf
{
	const MAX_GRID_NUM 			= 9;			// 每个副本的格子数目，九宫格
	public static $UNLOCK_GRID = array			// 对于九宫格，每个格子对应的上下左右的格子，总共就9个而已，直接写个map
	(
			1 => array(2, 4),
			2 => array(1, 3, 5),
			3 => array(2, 6),
			4 => array(1, 5, 7),
			5 => array(2, 4, 6, 8),
			6 => array(3, 5, 9),
			7 => array(4, 8),
			8 => array(5, 7, 9),
			9 => array(6, 8),
	);
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */