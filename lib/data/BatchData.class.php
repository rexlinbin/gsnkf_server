<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BatchData.class.php 16418 2012-03-14 02:51:55Z HaopingBai $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/lib/data/BatchData.class.php $
 * @author $Author: HaopingBai $(hoping@babeltime.com)
 * @date $Date: 2012-03-14 10:51:55 +0800 (星期三, 14 三月 2012) $
 * @version $Revision: 16418 $
 * @brief
 *
 **/

class BatchData
{
	/**
	 * 所使用的数据库名
	 * @var string
	 */
	private $db;

	private $arrRequest;

	private static $proxy = null;

	public function __construct()
	{

		$this->arrRequest = array ();
	}
	
	/**
	 * 选择使用哪个库
	 * @param string $db
	 */
	public function useDb($db)
	{
	
		$this->db = $db;
		return $this;
	}

	/**
	 * 获取一个新cdata对象，由BatchUpdateData接管
	 * @return CData
	 */
	public function newData()
	{

		return new CData ( $this );
	}

	public function addRequest($arrRequest)
	{

		$this->arrRequest [] = $arrRequest;
	}

	public function query()
	{

		if (! self::$proxy)
		{
			self::$proxy = new PHPProxy ( 'data' );
		}
		
		if (! empty ( $this->db ))
		{
			self::$proxy->setDb ( $this->db );
		}

		if (empty ( $this->arrRequest ))
		{
			Logger::fatal ( "invalid request, empty request list" );
			throw new Exception ( "inter" );
		}
		else if (count ( $this->arrRequest ) == 1)
		{
			$ret = self::$proxy->query ( $this->arrRequest [0] );
			$arrRet = array($ret);
		}
		else
		{
			$arrRet = self::$proxy->multiQuery ( $this->arrRequest );
		}
		
		if( count($arrRet) != count($this->arrRequest) )
		{
			Logger::fatal('ret:%s not match request:%s', $arrRet, $this->arrRequest);
			throw new Exception('inter');
		}
		foreach( $this->arrRequest as $index => $request )
		{
			$pid = getmypid();
			CData::$QUERY_CACHE[$pid]->afterQuery($request, $arrRet[$index]);
		}
		
		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */