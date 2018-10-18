<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroAnalysis.php 73629 2013-11-08 06:00:38Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/HeroAnalysis.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-08 06:00:38 +0000 (Fri, 08 Nov 2013) $
 * @version $Revision: 73629 $
 * @brief 
 *  
 **/
require_once ('/home/pirate/rpcfw/lib/TableGenerator.class.php');
require_once ('/home/pirate/rpcfw/lib/Util.class.php');
//delUnusedHeroByHid:grep delUnusedHeroByHid $LOGROOT/rpc.log.${DAY}* |grep INFO > delunused.tmp
//delUsedHeroByHid:grep delUsedHeroByHid $LOGROOT/rpc.log.${DAY}* |grep INFO > delused.tmp
//addHeroToHeroTbl:grep addHeroToHeroTbl $LOGROOT/rpc.log.${DAY}* |grep INFO > addtotbl.tmp
//addNewHero.htid:grep addNewHero.htid $LOGROOT/rpc.log.${DAY}* |grep TRACE > add.tmp
/**
 * 获取uid，
 */
function getInfoFromLog($requestFile)
{
    $request = array('num'=>0,'user'=>array());
    $pattern = '#.+logid:([0-9]+)].+uid:([0-9]+)].+#';
    $file = fopen ( $requestFile, 'r' );
    if (empty ( $file ))
    {
        echo sprintf ( "open file:%s failed\n", $requestFile );
        exit ( 0 );
    }
    while ( ! feof ( $file ) )
    {
        $line = fgets ( $file );
        $line = trim ( $line );
        if (empty ( $line ))
        {
            continue;
        }
        $arrMatch = array ();
        if (preg_match ( $pattern, $line, $arrMatch ))
        {
            $logid = $arrMatch[1];
            $uid = $arrMatch[2];
            $method = $arrMatch[3];
            $request['num'] ++; 
            $users = $request['user'];
            if(!in_array($uid, $users))
            {
                $request['user'][] = $uid;
            }
        }
    }
    fclose($file);
    return $request;
}

function generateCsvFile($delunused,$delused,$addherototbl,$addhero)
{
    $arrConfig = array ('type' => array ('colName' => '统计类别' ),
            'num' => array ('colName' => '武将总数目' ),
            'usernum'=>array('colName'=>'进行此操作的用户个数'),
            'peruser' => array('colName' => '平均每个用户进行此操作个数'),
    );
    $per = 0;
    $num = $delunused['num'];
    $usernum = count($delunused['user']);
    if($num == 0 || ($usernum == 0))
    {
        $per = 0;
    }
    else
    {
        $per = $num/$usernum;
    }    
    $arrRet['delunused'] = array(
            'type'=>'删除unused武将','num'=>$num,
            'usernum'=>$usernum,
            'peruser'=>$per);
    
    
    
    $per = 0;
    $num = $delused['num'];
    $usernum = count($delused['user']);
    if($num == 0 || ($usernum == 0))
    {
        $per = 0;
    }
    else
    {
        $per = $num/$usernum;
    }
    $arrRet['delused'] = array(
            'type'=>'删除used武将','num'=>$num,
            'usernum'=>$usernum,
            'peruser'=>$per);
    
    
    $per = 0;
    $num = $addherototbl['num'];
    $usernum = count($addherototbl['user']);
    if($num == 0 || ($usernum == 0))
    {
        $per = 0;
    }
    else
    {
        $per = $num/$usernum;
    }
    $arrRet['addherototbl'] = array(
            'type'=>'添加武将到hero表','num'=>$num,
            'usernum'=>$usernum,
            'peruser'=>$per);
    
    
    
    $per = 0;
    $num = $addhero['num'];
    $usernum = count($addhero['user']);
    if($num == 0 || ($usernum == 0))
    {
        $per = 0;
    }
    else
    {
        $per = $num/$usernum;
    }
    $arrRet['addhero'] = array(
            'type'=>'添加武将','num'=>$num,
            'usernum'=>$usernum,
            'peruser'=>$per);
    global $argc, $argv;
    $day = $argv[1];
    $tableGen = new TableGenerator ( $arrConfig );
    $content = $tableGen->generateCsv ( $arrRet );
    $handle = fopen ( "csv/heroanalysis." . $day . ".csv", "w" );
    fwrite ( $handle, $content );
    fclose ( $handle );
}

function main()
{
    global $argc, $argv;
    if ($argc < 5)
    {
        echo "usage: php $argv[0] day delunusedfile delusedfile addherototbl addherofile\n";
        exit ( 0 );
    }
    $day = $argv[1];
    $delunusedfile = $argv[2];
    $delusedfile = $argv[3];
    $addherototblfile = $argv[4];
    $addherofile = $argv[5];
    $delunused = getInfoFromLog($delunusedfile);
    $delused = getInfoFromLog($delusedfile);
    $addherototbl = getInfoFromLog($addherototblfile);
    $addhero = getInfoFromLog($addherofile);
    var_dump($delunused);
    var_dump($delused);
    var_dump($addherototbl);
    var_dump($addhero);
    generateCsvFile($delunused, $delused, $addherototbl, $addhero);
}
main();

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */