<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: btstoreString2Int.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/btstoreString2Int.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/

if ( $argc != 2 )
{
        echo "err args!please input conv file name!\n";
        exit;
}

$file = $argv[1];

if ( !file_exists($file) )
{
        echo "$file is not exist!\n";
        exit;
}

$array = unserialize(file_get_contents($file));

function dealString2Int($array)
{
        if ( !is_array($array) )
        {
                if ( is_numeric($array) && $array == intval($array) )
                {
                        return intval($array);
                }
                return $array;
        }
        else
        {
                foreach ( $array as $key => $value )
                {
                        $array[$key] = dealString2Int($value);
                }
                return $array;
        }
}

$data = dealString2Int($array);
echo serialize($data);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */