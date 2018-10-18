<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GetRandomNameSql.php 126261 2014-08-12 04:00:47Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/GetRandomNameSql.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-08-12 04:00:47 +0000 (Tue, 12 Aug 2014) $
 * @version $Revision: 126261 $
 * @brief 
 *  
 **/

require_once '/home/pirate/rpcfw/lib/Util.class.php';
require_once '/home/pirate/rpcfw/lib/TrieFilter.class.php';

$csvFile1 = '/home/pirate/random_name_man.csv';
$csvFile2 = '/home/pirate/random_name_woman.csv';
$outPut    =    '/home/pirate/insert_random_name.sql';


function checkName($uname)
{
	$arrRet['ret'] = Util::checkName($uname);
	if ('ok'!=$arrRet['ret'])
	{
		printf("invalid uname:%s\n", $uname);
		return false;
	}

	$len = (mb_strlen($uname, 'utf8')+strlen($uname))/2; 
	if (UserConf::MAX_USER_NAME_LEN < $len || UserConf::MIN_USER_NAME_LEN > $len)
	{
		printf("invalid uname len:%s\n", $uname);
		return false;
	}
	return true;
}

function getNames($filePath)
{
    $names    =    array();
    $file = fopen($filePath, 'r');
    while(TRUE)
    {
        $line = fgetcsv($file);
        if(empty($line))
        {
            break;
        }
        $name    =    trim($line[0]);
        if(in_array($name, $names) == FALSE)
        {
            if(Util::checkName($name) == 'ok')
            {
                $names[] = $name;
            }
        }
        //echo $line[0]."\n";
    }
    fclose($file);
    return $names;
}

function disorder($arrname)
{
    echo "start disorder\n";
    $len    =    count($arrname);
    for($i=0;$i<$len;$i++)
    {
        $ti = rand($i, $len-1);
        $temp = $arrname[$ti];
        $arrname[$ti] = $arrname[$i];
        $arrname[$i] = $temp;
    }
    echo "end disorder\n";
    return $arrname;
}
//gender:::0：女, 1:男
//status:::0：可用，1：已经被使用
echo "start\n";
$woman1    =    getNames($csvFile2);
$woman = disorder($woman1);
$man1    =    getNames($csvFile1);
$man = disorder($man1);
$sqlLines    =    'set names utf8;';
foreach($woman as $name)
{
    $sql    =    "INSERT IGNORE INTO t_random_name  (name,status, gender)  VALUES(\"$name\",0,0);";
    //echo $sql."\n";
    $sqlLines = $sqlLines."\n".$sql;
}
foreach($man as $name) 
{
    $sql    =    "INSERT IGNORE INTO t_random_name  (name,status, gender)  VALUES(\"$name\",0,1);";
    //echo $sql."\n";
    $sqlLines = $sqlLines."\n".$sql;
}
$file = fopen($outPut, 'w');
fwrite($file, $sqlLines );
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */