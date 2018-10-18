<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnFsReborn.class.php 200753 2015-09-28 06:20:54Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/fsreborn/EnFsReborn.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-28 06:20:54 +0000 (Mon, 28 Sep 2015) $
 * @version $Revision: 200753 $
 * @brief 
 *  
 **/
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/FsReborn.def.php";

class EnFsReborn
{
	public static function readFsRebornCSV($arrData)
	{	
		$index = 1;
		$arrConfKey = array(
				FsRebornDef::NUMS => $index++,
				FsRebornDef::RATE => $index++,
		);
		
		$arrKeyV1 = array();
		$arrKeyV2 = array(FsRebornDef::NUMS);
		
		$confList = array();
		foreach ($arrData as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
			
			$conf = array();
			foreach ($arrConfKey as $key => $index)
			{
				if (in_array($key, $arrKeyV1, true))
				{
					$conf[$key] = array2Int(str2array($data[$index]));
				}
				elseif (in_array($key, $arrKeyV2, true))
				{
					$conf[$key] = array();
					$arr = str2array($data[$index]);
					foreach ($arr as $value)
					{
						$ary = array2Int(str2Array($value, '|'));
						$conf[$key][$ary[0]] = $ary[1];	
					}
				}
				else
				{
					$conf[$key] = intval($data[$index]);
				}
			}
			$confList = $conf;
		}
		
		return $confList;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */