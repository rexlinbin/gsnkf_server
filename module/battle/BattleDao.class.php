<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BattleDao.class.php 155785 2015-01-28 12:50:57Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/battle/BattleDao.class.php $
 * @author $Author: BaoguoMeng $(hoping@babeltime.com)
 * @date $Date: 2015-01-28 12:50:57 +0000 (Wed, 28 Jan 2015) $
 * @version $Revision: 155785 $
 * @brief
 *
 **/

class BattleDao
{

	static function getRecord($brid)
	{

		$brid = intval ( $brid );
		$arrField = array ('record_data' );
		$arrCond = array ('brid', '=', $brid );
		$data = new CData ();
		$arrRet = $data->select ( $arrField )->from ( 't_battle_record' )->where ( $arrCond )->query ();
		if (empty ( $arrRet ))
		{
			throw new FakeException("battle record:%d not found", $brid );
		}

		return $arrRet [0] ['record_data'];
	}

	static function getFullRecord($brid)
	{

		$brid = intval ( $brid );
		$arrField = array ('record_data', 'record_type' );
		$arrCond = array ('brid', '=', $brid );
		$data = new CData ();
		$arrRet = $data->select ( $arrField )->from ( 't_battle_record' )->where ( $arrCond )->query ();
		if (empty ( $arrRet ))
		{
			throw new FakeException("battle record:%d not found", $brid );
		}

		return $arrRet [0];
	}

	static function getKfzRecord($brid, $db)
	{
	
		$brid = intval ( $brid );
		$arrField = array ('record_data' );
		$arrCond = array ('brid', '=', $brid );
		$data = new CData ();
		$data->useDb($db);
		$arrRet = $data->select ( $arrField )->from ( 't_battle_record' )->where ( $arrCond )->query ();
		if (empty ( $arrRet ))
		{
			Logger::warning ( "battle record:%d not found", $brid );
			throw new Exception ( 'fake' );
		}
	
		return $arrRet [0] ['record_data'];
	}
	
	
	static function getArrRecord($arrBrid, $arrField, $db)
	{
		$arrCond = array ('brid', 'IN', $arrBrid );
		$data = new CData ();
		
		if ( !empty($db) )
		{
			$data->useDb($db);
		}
		
		$arrRet = $data->select ( $arrField )->from ( 't_battle_record' )->where ( $arrCond )->query ();
		if (empty ( $arrRet ))
		{
			Logger::warning ( "battle record:%s not found", $arrBrid );
			throw new Exception ( 'fake' );
		}
		
		return $arrRet;
	}
	
	

	static function addRecord($brid, $recordData, $db = null)
	{

		$arrData = array ('record_data' => $recordData, 'record_time' => Util::getTime (),
				'record_type' => RecordType::TEMP, 'brid' => $brid );
		$data = new CData ();
		if( $db != null )
		{
			$data->useDb($db);
		}
		$arrRet = $data->insertInto ( 't_battle_record' )->values ( $arrData )->query ();
		return $arrRet;
	}

	static function updateRecord($brid, $arrBody)
	{

		$data = new CData ();
		return $data->update ( 't_battle_record' )->set ( $arrBody )->where (
				array ('brid', '=', $brid ) )->query ();
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
