<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnFrontshow.class.php 153019 2015-01-16 06:59:54Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/frontshow/EnFrontshow.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-01-16 06:59:54 +0000 (Fri, 16 Jan 2015) $
 * @version $Revision: 153019 $
 * @brief 
 *  
 **/
class EnFrontshow
{
	public static function readFrontShowCSV( $array )
	{
		if( empty( $array ) )
		{
			throw new FakeException( 'front show file is empty' );
		}
		return array('dummy' => true);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */