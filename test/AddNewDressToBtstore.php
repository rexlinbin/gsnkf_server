<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddNewDressToBtstore.php 156432 2015-02-02 11:13:28Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/AddNewDressToBtstore.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2015-02-02 11:13:28 +0000 (Mon, 02 Feb 2015) $
 * @version $Revision: 156432 $
 * @brief 
 *  
 **/

/**
 20141222  暂时只支持ITEMS
 */




$inputDir = $argv[1];
$outputDir = $argv[2];

$outFileName = 'ITEMS';

require_once '/home/pirate/rpcfw/module/item/scripts/DressItem.php';
require_once '/home/pirate/rpcfw/def/Item.def.php';
require_once '/home/pirate/rpcfw/lib/ParserUtil.php';

$arrNewItem = call_user_func('readDressItem', $inputDir);

$data = file_get_contents('/home/pirate/rpcfw/data/btstore/ITEMS');
$allItem = unserialize($data);

foreach( $arrNewItem as $itemId => $itemConf)
{
	$arrNewItem[$itemId][ItemDef::ITEM_ATTR_NAME_TYPE] = ItemDef::ITEM_TYPE_DRESS;
	
	if( isset( $allItem[$itemId] ) )
	{
		printf("itemId:%d found in old btstore\n", $itemId);
		exit();
	}
	$allItem[$itemId] = $arrNewItem[$itemId];
}



//输出文件
$file = fopen("$outputDir/$outFileName", "w");
if ( $file == FALSE )
{
	trigger_error( "$outputDir/$outFileName open failed! exit!\n" );
}
fwrite($file, serialize($allItem));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */