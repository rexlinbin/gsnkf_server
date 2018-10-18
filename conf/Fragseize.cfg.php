<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Fragseize.cfg.php 135642 2014-10-10 07:39:34Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Fragseize.cfg.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-10-10 07:39:34 +0000 (Fri, 10 Oct 2014) $
 * @version $Revision: 135642 $
 * @brief 
 *  
 **/
class FragseizeConf
{
	public static $default = array( 5013011 => 1, 5013012 => 1, 5013013 => 1 );
	
	const SEIZE_STAMINA = 2;
	
	const UID_NUM = 50;
	
	const RIO_BASE = 10000;
	
	const GOD_PROTECT_LEVEL = 13;
	
	const MAX_REQUEST_MATE = 10;
	
	const MAX_FUSE_NUM = 10;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */