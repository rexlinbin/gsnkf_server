<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 * 
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(wuqilin@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief 
 *  
 **/

/**
 *
 * 示例：
 * btscript game001 SetDayOffset.php 14:30:00
 * @author wuqilin
 *
 */
class SetDayOffset extends BaseScript
{
	protected function executeScript ($arrOption)
	{
		$refreshTime = Util::getTime();
		if( isset($arrOption[0]) )
		{
			$refreshTime = strtotime( date('Y-m-d')." ". $arrOption[0]);
		}
		
		$offset = $refreshTime - strtotime( date('Y-m-d') );
		
		if($offset > 86400 || $offset < 0)
		{
			printf("invalid offset:%d\n", $offset);
			return;
		}
		
		popen("/bin/sed -i '/DAY_OFFSET_SECOND/{s/[0-9-]\+/$offset/;}' /home/pirate/rpcfw/conf/Framework.cfg.php", 'r');
			
		printf("set offset:%d, %s\n", $offset, date('H:i:s', $refreshTime) );
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */