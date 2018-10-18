<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnValidityCheck.class.php 190978 2015-08-13 10:16:42Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/validitycheck/EnValidityCheck.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-08-13 10:16:42 +0000 (Thu, 13 Aug 2015) $
 * @version $Revision: 190978 $
 * @brief 
 *  
 **/
class EnValidity
{
	//消费累计解析函数
	public static function readValidityCSV( $arr )
	{
		$index = 0;
		$keyArr = array(
				'id' => $index++,
				'platId' => ($index+=2)-1,
				'switch' => $index++,
		);
		
		$arrTwo = array();
		
		$confList = array();
		foreach ( $arr as $data )
		{
			$conf = array();
			if ( empty( $data ) || empty( $data[0] ) )
			{
				break;
			}
			foreach ( $keyArr as $key => $index )
			{
				if ( is_numeric( $data[ $index ] ) || empty( $data[ $index ] ) )
				{
					$conf[ $key ] = intval( $data[ $index ] );
				}	
			}
			$confList[$conf['id']] = $conf;
		}
		return $confList;
	}
	}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */