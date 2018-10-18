<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnBarter.class.php 61220 2013-08-24 06:28:32Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/barter/EnBarter.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-08-24 06:28:32 +0000 (Sat, 24 Aug 2013) $
 * @version $Revision: 61220 $
 * @brief 
 *  
 **/
class EnBarter
{
	public static function readBarterCSV( $arr )
	{
		$index = 0;
		$confKeyArr = array(
				'exchangeId' => $index++,
				
				'needSilver' => ( $index += 2 )-1,
				'needGold' => $index++,
				'needSoul' => $index++,
				'needHero' => $index++,
				'needItem' => $index++,
				'needLevel' => $index++,
				'needVip' => $index++,
				
				'gainSilver' => $index++,
				'gainGold' => $index++,
				'gainSoul' => $index++,
				'gainHero' => $index++,
				'gainItem' => $index++,
		);
		
		$arrNeedSta = array( 'needHero' , 'needItem' , 'gainHero' , 'gainItem' );
		
		$confList = array();
		foreach ( $arr as $data )
		{
			$conf = array();
			if ( empty( $data ) || empty( $data[0] ) )
			{
				break;
			}
			foreach ( $confKeyArr as $key => $index )
			{
				if ( is_numeric( $data[ $index ] ) || empty( $data[ $index ] )  )
				{
					$conf[ $key ] = intval( $data[ $index ] ); 
				}
				else 
				{
					$conf[ $key ] = explode( ',' , $data[ $index ] );
					foreach ( $conf[ $key ] as $key2 => $val )
						if ( is_numeric( $val ) )
						{
							$conf[ $key ][ $key2 ] = intval( $val );
						}
						else 
						{
							$conf[ $key ][ $key2 ] = array_map( 'intval' , explode( '|' , $val ) );
						}
				}
				
			}
			
			foreach ( $arrNeedSta as $val )
			{
				if ( !empty( $conf[ $val] ) )
				{
					$conf[ $val ] = self::standard( $conf[ $val ] );
				}
			}
			$confList[ $conf[ 'exchangeId' ] ] = $conf;
		}
		return $confList;
	}
	
	public static function standard( $arr )
	{
		$arrRet = array();
		if ( !empty( $arr ) )
		{
			foreach ( $arr as $val )
			{
				$arrRet[ $val[ 0 ] ] = $val[ 1 ];
			}
		}
		return $arrRet;
	}
	
	
	public static function readBarterFrontCSV( $arr )
	{
		//该解析函数所解析的数据后端用不到
		$confList = array();
		foreach ( $arr as $data )
		{
			if ( empty( $data[ 3 ] ) )
			{
				throw new ConfigException( 'empty?' );
			}
			$confList = explode( ',' , $data[ 3 ]);
		}
		return $confList;
	}
	
	/**
	 * 给定一个时间判定是不是处于当前的活动时间
	 * @param unix timestamp $time
	 * @return boolean
	 */
	public static function isInCurRound( $time )
	{
		if ( !EnActivity::isOpen( ActivityName::BARTER ) )
		{
			throw new FakeException( 'activity not on' );
		}
		$conf = EnActivity::getConfByName( ActivityName::BARTER );
		if ( empty( $conf ) )
		{
			throw new FakeException( 'no cfg for activity' );
		}
		if ( $time >= $conf[ 'start_time' ] && $time <= $conf[ 'end_time' ])
		{
			return true;
		}
		return false;
	}
	
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */