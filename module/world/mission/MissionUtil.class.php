<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MissionUtil.class.php 196650 2015-09-06 09:36:28Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/mission/MissionUtil.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-09-06 09:36:28 +0000 (Sun, 06 Sep 2015) $
 * @version $Revision: 196650 $
 * @brief 
 *  
 **/
class MissionUtil
{
	static function getCrossDbName()
	{
		return MissionDef::CROSS_DB_PRE.PlatformConfig::PLAT_NAME;
	}
	
	static function getCrossUserTableName( $teamId )
	{
		return MissionDef::CROSS_USR_TBL_PRE.$teamId;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */