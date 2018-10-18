<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RecoverMainCfg.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/RecoverMainCfg.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/
require_once ('/home/pirate/rpcfw/conf/Script.cfg.php');

function main()
{

	global $argc, $argv;
	if ($argc != 4)
	{
		echo "Usage: php $0 target_ip target_logic target_dir\n";
		exit ( 0 );
	}

	$targetHost = $argv [1];
	$targetLogic = $argv [2];
	$targetDir = $argv [3];

	if (file_exists ( $targetDir ))
	{
		echo "target_dir:$targetDir exists\n";
		exit ( 0 );
	}

	mkdir ( $targetDir );

	$lcserverRoot = '/pirate/lcserver';
	$mapRoot = '/pirate/map';
	$zk = new Zookeeper ( ScriptConf::ZK_HOSTS );
	$arrGame = $zk->getChildren ( $lcserverRoot );
	foreach ( $arrGame as $game )
	{
		$path = $lcserverRoot . '/' . $game;
		$data = $zk->get ( $path );
		$arrData = amf_decode ( chr ( 0x11 ) . $data, 7 );

		if ($arrData ['host'] != $targetHost)
		{
			continue;
		}

		if (isset ( $arrData ['refer'] ))
		{
			echo "ignore $game\n";
			continue;
		}

		$game = explode ( '#', $game, 2 );
		$game = $game [1];

		if (isset ( $arrData ['db'] ))
		{
			$db = $arrData ['db'];
		}
		else
		{
			$db = str_replace ( 'game', 'pirate', $game );
			$ret = $zk->get ( $mapRoot . '/' . $db );
			$arrRet = amf_decode ( chr ( 0x11 ) . $ret, 7 );
			if ($arrRet ['group'] != $game)
			{
				echo "game:$game db not match\n";
				$db = 'unknown';
			}
		}

		$wanPort = $arrData ['wan_port'];
		$lanPort = $arrData ['port'];

		$data = "-d $db -W $wanPort -L $lanPort -i $targetLogic\n";
		file_put_contents ( $targetDir . "/$game.args", $data );
	}
}

main ();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */