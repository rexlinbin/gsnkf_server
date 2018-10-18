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



class SetDayOffset extends BaseScript
{
	protected function executeScript ($arrOption)
	{

		if( count($arrOption) < 1 )
		{
			printf("param: offset[11:00:00] ");
		}
		
		$startTime = strtotime( sprintf('%s %s', date('Y-m-d'), $arrOption[0]) );
		
		$dayOffset = $startTime - strtotime(date("Y-m-d"));

		popen("/bin/sed -i '/DAY_OFFSET_SECOND/{s/[0-9]\+/$dayOffset/;}' /home/pirate/rpcfw/conf/Framework.cfg.php", 'r');

		printf("done\n");
	}

	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */