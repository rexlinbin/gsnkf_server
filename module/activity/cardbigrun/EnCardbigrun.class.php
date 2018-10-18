<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnCardbigrun.class.php 61362 2013-08-26 10:28:07Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/cardbigrun/EnCardbigrun.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-26 10:28:07 +0000 (Mon, 26 Aug 2013) $
 * @version $Revision: 61362 $
 * @brief 
 *  
 **/
class EnCardbigrun
{
	public static function readCardbigrunCSV( $dataArr )
	{
		$confList = array();
		foreach ( $dataArr as $key => $data )
		{
			if ( empty( $data ) || empty( $data[0] ) )
			{
				break;
			}
			
			$index = 0;
			$id = intval( $data[ $index ] );
			$confList[ $id ][ 'needGold' ] = intval( $data[ $index += 2 ] );
			$confList[ $id ][ 'dropId' ] = intval( $data[ $index += 4 ] );
			$confList[ $id ][ 'heroNum' ] = intval( $data[ ++$index ] );
		}
		
		return $confList;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */