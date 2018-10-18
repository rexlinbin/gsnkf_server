<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: requestAnalyze.php 204091 2015-10-23 02:52:33Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/requestAnalyze.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-10-23 02:52:33 +0000 (Fri, 23 Oct 2015) $
 * @version $Revision: 204091 $
 * @brief 
 *  
 **/

$arrRet = array();

function queryClause($args)
{
	$table = $args['table'];
	$command = $args['command'];
	if ($command == 'select' || $command == 'selectCount')
	{
		$where = $args['where'];
		$key = key($where);
		$op = $where[$key][0];
		$value = $where[$key][1];
		if (is_array($value))
		{
			$value = implode(",", $value);
		}
		$cond = $key." ".$op." ".$value;
		$select = $args['select'];
		$fields = implode(",", $select);
		return $command." ".$fields." from ".$table." where ".$cond;
	}
	elseif ($command == 'update' || $command == 'insertInto' || $command == "insertOrUpdate" || $command == "insertIgnore")
	{
		$values = $args['values'];
		$fields = array_keys($values);
		$fields = implode(",", $fields);
		return $command." ".$table." values ".$fields;
	}
}

function countFile($filename)
{
	$pattern = '/.+proxy request:(.+)/s';

	global  $arrRet;
	
	$arrRet['request'] = array();
	$arrRet['total'] = array();
	$arrRet['total']['num'] = 0;
	
	$file = file_get_contents($filename);
	if (empty ( $file ))
	{
		echo sprintf ( "get file:%s content failed\n", $filename );
		exit ( 0 );
	}
	$content = explode('[201', $file); 
	foreach ($content as $request)
	{	
		$arrMatch = array ();

		if (preg_match ( $pattern, $request, $arrMatch ))
		{
			eval('$request = ' . $arrMatch[1] . ';');

			if ($request['method'] == 'query') 
			{
				$args = $request['args'][0];
				$table = $args['table'];
				$command = $args['command'];
				$arrRet['request'][] = queryClause($args);
			}
			elseif ($request['method'] == 'multiQuery')
			{
				foreach ($request['args'][0] as $args)
				{
					$table = $args['table'];
					$command = $args['command'];
					$arrRet['request'][] = queryClause($args);
		
					if (isset($arrRet['total'][$table][$command]))
					{
						$arrRet['total'][$table][$command] ++;
					}
					else
					{
						$arrRet['total'][$table][$command] = 1;
					}
					$arrRet['total']['num'] ++;
				}
			}
			elseif ($request['method'] == 'mcGet')
			{
				$table = 'mcGet';
				$command = $request['args'][0];
				$arrRet['request'][] = $table." ".$command;
			}
			elseif ($request['method'] == 'mcSet')
			{
				$table = 'mcSet';
				$command = $request['args'][0];
				$arrRet['request'][] = $table." ".$command;
			}
			elseif ($request['method'] == 'doHero')
			{
				$table = 'doHero';
				$command = 'doHero';
				$arrRet['request'][] = $command;
			}
			else 
			{
				$table = $request['method'];
				$command = $request['args'][0];
				$arrRet['request'][] = $table." ".$command;
			}
			
			if ($request['method'] != 'multiQuery') 
			{
				if (isset($arrRet['total'][$table][$command]))
				{
					$arrRet['total'][$table][$command] ++;
				}
				else
				{
					$arrRet['total'][$table][$command] = 1;
				}
				$arrRet['total']['num'] ++;
			}
		}
		else
		{
			continue;
		}
	}
	fclose($filename);
}

function main()
{
	global $argc, $argv;
	if ($argc < 2)
	{
		echo "usage: php $argv[0] logid\n";
		exit ( 0 );
	}
	
	$logid = $argv[1];
	if (!empty($argv[2])) 
	{
		$log = $argv[2];
		$logid = $logid . " " . $log;
	}
	$filename = "/tmp/tmp.tmp";
	exec("~/bin/get_request " . $logid . " > " . $filename);
	
	countFile($filename);
	
	exec("rm -f " . $filename);
	global $arrRet;
	ksort ( $arrRet );
	
	print_r($arrRet);
	
}

main ();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */