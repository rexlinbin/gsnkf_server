<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: WorldUtil.class.php 218625 2015-12-30 05:48:02Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/WorldUtil.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-30 05:48:02 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218625 $
 * @brief 
 *  
 **/
class WorldUtil
{
	static function isCrossGroup()
	{
		if( CountryWarUtil::isCrossGroup() )
		{
			Logger::debug('is countrywar cross group');
			return true;
		}
		//新来的往后加
		
		
		return false;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */