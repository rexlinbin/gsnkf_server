<?php
/***************************************************************************
 * 
 * Copyright (c) 2014 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(huangqiang@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/
 
class UpdateAchieve
{
	function execute ($arrResponse)
	{
		if( WorldUtil::isCrossGroup() )
		{
			Logger::debug('is cross group');
			return $arrResponse;
		}
		
		Transaction::commit();
		return $arrResponse;
	}
}
 