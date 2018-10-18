<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Online.def.php 56564 2013-07-25 09:38:26Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Online.def.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-07-25 09:38:26 +0000 (Thu, 25 Jul 2013) $
 * @version $Revision: 56564 $
 * @brief 
 *  
 **/
class OnlineDef
{
	public static $arrField = array(
			'uid',
			'step',
			'begin_time',
			'end_time',
			'accumulate_time',
	);
	const SESSIONKEY = 'online.info';
	const TBL = 't_online';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */