<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnGrowUp.class.php 61220 2013-08-24 06:28:32Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/growup/EnGrowUp.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-24 06:28:32 +0000 (Sat, 24 Aug 2013) $
 * @version $Revision: 61220 $
 * @brief 
 *  
 **/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
class EnGrowUp
{
	//成长计划解析函数
	public static function readGrowupCSV($arr)
	{
		$confList = array();
		foreach ( $arr as $data )
		{
			if ( empty( $data )||empty( $data[0] ) )
			{
				break;
			}
			$index = 3;
			$confList[ 'needVip' ] = intval( $data[ $index++ ] );
			$confList[ 'lvAndGold' ] = $data[ $index++ ];
			$confList[ 'needGold' ] = intval( $data[ ($index+=2)-1 ] );
			
			$Conf = str2Array( $confList[ 'lvAndGold' ] );
			foreach ( $Conf as $key => $val )
			{
				$Conf[ $key ] = array2Int( str2Array( $val , '|') );
			}
			$standarConf = array();
			foreach ( $Conf as $key => $val )
			{
				$standarConf[ $key ][ 'needLevel' ] = $val[ 0 ];
				$standarConf[ $key ][ 'fundGold' ] = $val[ 1 ];
			}
			$confList[ 'lvAndGold' ] = $standarConf;
		}
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */