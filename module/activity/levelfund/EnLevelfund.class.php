<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnLevelfund.class.php 62043 2013-08-29 12:46:15Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/levelfund/EnLevelfund.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-29 12:46:15 +0000 (Thu, 29 Aug 2013) $
 * @version $Revision: 62043 $
 * @brief 
 *  
 **/
class EnLevelfund
{
// 	public static function readLevelfundCSV( $arr )
// 	{
// 		$index = 0;
// 		$confKeyArr = array(
// 				'id' => $index++,
// 				'needLevel' => $index++,
// 				'prizeSilver' => $index++,
// 				'prizeSoul' => $index++,
// 				'prizeGold' => $index++,
// 				'prizeHero' => $index++,
// 				'prizeItem' => $index++,
// 		);
// 		$conflist = array();
// 		foreach ( $arr as $data )
// 		{
// 			$conf = array();
// 			if ( empty( $data ) || empty( $data[0] ) )
// 			{
// 				break;
// 			}
// 			foreach ( $confKeyArr as $key => $index )
// 			{
// 				if ( is_numeric( $data[ $index ] ) || empty( $data[ $index ]) )
// 				{
// 					$conf[ $key ] = intval( $data[ $index ] );
// 				}
// 				else 
// 				{
// 					$conf[ $key ] = explode( ',' , $data[ $index ] );
// 					foreach ( $conf[ $key ] as $key2 => $val )
// 					{
// 						if ( is_numeric( $val ) )
// 						{
// 							$conf[ $key ][ $key2 ] = intval( $val );
// 						}
// 						else 
// 						{
// 							$conf[ $key ][ $key2 ] = array_map( 'intval' , explode( '|' , $val ) );
// 						}
// 					}
// 				}
// 				$conflist[ $conf[ 'id' ] ] = $conf;
// 			}
// 		}
// 		return $conflist;
// 	}
	
	public static function readLevelfundCSV( $arr )
	{
		$conflist = array();
		foreach ( $arr as $data )
		{
			if ( empty( $data[ 0 ] )|| empty( $data ))
			{
				break;
			}
			$index = 2;
			$rewardConf = array();
			while ( true )
			{
				if ( empty( $data[ $index ] ) || empty( $data[ 0 ] ) )
				{
					break;
				}
				if ( intval ( $data[ $index ] ) == RewardConfType::ITEM_MULTI )
				{
					if ( empty( $data[ $index + 2 ]  ))
					{
						$rewardConf [] = array();
					}
					else 
					{
						$itemArr = explode( ',' , $data[ $index + 2 ]);
						foreach ( $itemArr as $key => $val )
						{
							$itemArr[ $key ] = array_map('intval', explode( '|' , $val ));
						}
						$rewardConf [] = array(
								'type' => intval ( $data[ $index ] ),
								'val'  => $itemArr,
						) ;
					}
					
				}
				else
				{
					$rewardConf[]= array(
							'type' => intval ( $data[ $index ] ),
							'val'  => intval ( $data[ $index + 2 ] ),
					) ;
				}
				$index += 4;
			}
		
			$confList [ intval( $data[ 0 ] ) ][ 'needLevel' ] = intval( $data[ 1 ] );
			$confList [ intval( $data[ 0 ] ) ][ 'rewardArr' ] = $rewardConf;
		}
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */