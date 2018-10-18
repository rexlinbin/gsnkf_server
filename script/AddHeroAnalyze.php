<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AddHeroAnalyze.php 68982 2013-10-15 07:36:06Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/AddHeroAnalyze.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-10-15 07:36:06 +0000 (Tue, 15 Oct 2013) $
 * @version $Revision: 68982 $
 * @brief 
 *  
 **/
require_once ('/home/pirate/rpcfw/lib/TableGenerator.class.php');
require_once ('/home/pirate/rpcfw/lib/Util.class.php');
//addFile的获取：grep addNewHero rpc.log.date* |grep NOTICE > addFile
//requestFile的获取：grep TRACE rpc.log.date* > requestFile
function logIdToMethod($requestFile)
{
    $pattern = '#.+logid:([0-9]+)].+uid:([0-9]+)].+method:(.+), err.+#';
    $logIdToMethod = array();
    //[20131015 14:00:19 187762][NOTICE][logid:8864594410][client:192.168.28.8]
    //[server:192.168.1.91][group:game001][pid:547][uid:22973]
    //[lib/RPCFramework.class.php:694]method:hero.getAllHeroes, 
    //err:ok, request count:8, total cost:2342(ms), framework cost:88(ms), 
    //method cost:2254(ms), request size:3555(byte), response size:11224(byte)
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
            $logIdToMethod[$logid] = array('method'=>$method,'uid'=>$uid);
        }
        else
        {
//             echo sprintf ( "line:%s not match\n", $line );
        }
    }
    fclose($file);
    return $logIdToMethod;
}

function addHeroAnalyzeByLogId($addFile)
{
    $pattern = '#.+logid:([0-9]+)].+htid ([0-9]+)#';
    $addNumByRequest = array();//array(logid=>addnum)
    $addHeroesByRequest = array();//array(logid=>array(htid))
    //rpc.log:[20131015 11:19:20 324648][TRACE][logid:8428939600]
    //[client:192.168.29.30][server:192.168.1.91][group:game001]
    //[pid:95544554][uid:27352][module/hero/HeroManager.class.php:233]
    //addNewHero.htid 30001.
    //提取出logid htid
    $file = fopen ( $addFile, 'r' );
    if (empty ( $file ))
    {
        echo sprintf ( "open file:%s failed\n", $addFile );
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
            $htid = $arrMatch[2];
            if(!isset($addNumByRequest[$logid]))
            {
                $addNumByRequest[$logid] = 0;
            }
            $addNumByRequest[$logid]++;
            if(!isset($addHeroesByRequest[$logid]))
            {
                $addHeroesByRequest[$logid] = array();
            }
            if(!isset($addHeroesByRequest[$logid][$htid]))
            {
                $addHeroesByRequest[$logid][$htid] = 0;
            }
            $addHeroesByRequest[$logid][$htid]++;
        }
        else
        {
            echo sprintf ( "line:%s not match\n", $line );
        }
    }
    fclose($file);
    return array('num'=>$addNumByRequest,'hero'=>$addHeroesByRequest);
}

function addHeroAnalyzeByMethod($addHero,$logIdToMethod)
{
    $addNumByRequest = $addHero['num'];
    $addHeroesByRequest = $addHero['hero'];
    $addNumByMethod = array();
    $addHeroesByMethod = array();
    $userNumByMethod = array();//使用此请求掉武将的用户个数
    $requestNumByMethod = array();
    $userNumByRequest = array();//使用此请求的用户个数
    $arrRet = array();
    foreach($logIdToMethod as $logid => $info)
    {
        $method = $info['method'];
        if(!isset($requestNumByMethod[$method]))
        {
            $requestNumByMethod[$method] = 0;
        }
        $requestNumByMethod[$method] ++;
        $uid = $info['uid'];
        if(!isset($userNumByRequest[$method]))
        {
            $userNumByRequest[$method] = array();
        }
        if(!in_array($uid, $userNumByRequest[$method]))
        {
            $userNumByRequest[$method][] = $uid;
        }
    }
    foreach($addNumByRequest as $logid => $num)
    {
        if(!isset( $logIdToMethod[$logid]))
        {
            continue;
        }
        $method = $logIdToMethod[$logid]['method'];
        $uid = $logIdToMethod[$logid]['uid'];
        if(!isset($userNumByMethod[$method]))
        {
            $userNumByMethod[$method] = array();
        }
        if(!in_array($uid, $userNumByMethod[$method]))
        {
            $userNumByMethod[$method][] = $uid;
        }
        if(!isset($addNumByMethod[$method]))
        {
            $addNumByMethod[$method] = 0;
        }
        $addNumByMethod[$method] += $num;
        $arrHero = $addHeroesByRequest[$logid];
        if(!isset($addHeroesByMethod[$method]))
        {
            $addHeroesByMethod[$method] = array();
        }
        foreach($arrHero as $htid => $num)
        {
            if(!isset($addHeroesByMethod[$method][$htid]))
            {
                $addHeroesByMethod[$method][$htid] = 0;
            }
            $addHeroesByMethod[$method][$htid] += $num;
        }
    }
    foreach($addNumByMethod as $method => $num)
    {
        $arrHero = $addHeroesByMethod[$method];
        $heroStr = '';
        foreach($arrHero as $htid => $hnum)
        {
            $heroStr = $heroStr.$htid.','.$hnum.'|';
        }
        $usernumgethero = count($userNumByMethod[$method]);
        $usernum = count($userNumByRequest[$method]);
        $requestnum = $requestNumByMethod[$method];
        $arrRet[$method] = array(
                'method'=>$method,
                'num'=>$num,
                'hero'=>$heroStr,
                'usernum'=>$usernum,
                'usernumgethero'=>$usernumgethero,
                'preuser'=>$num/$usernum,
                'requestnum'=>$requestnum,
                'prerequest'=>$num/$requestnum
                );
    }    
    $arrConfig = array ('method' => array ('colName' => '方法名' ),
            'num' => array ('colName' => '添加武将数目' ),
            'hero' => array('colName' => '添加武将的htid'),
            'usernum'=>array('colName'=>'使用此请求的用户个数'),
            'usernumgethero'=>array('colName' => '使用此请求获取武将的用户个数'),
            'preuser' => array('colName' => '平均每个用户获取武将个数'),
            'requestnum'=>array('colName' => '请求个数'),
            'prerequest'=>array('colName' => '平均每个请求添加武将个数')
    );
    global $argc, $argv;
    $day = $argv[1];
    $tableGen = new TableGenerator ( $arrConfig );
    $content = $tableGen->generateCsv ( $arrRet );
    $handle = fopen ( "csv/addhero." . $day . ".csv", "w" );
    fwrite ( $handle, $content );
    fclose ( $handle );
}


function main()
{
    global $argc, $argv;
    if ($argc < 3)
    {
        echo "usage: php $argv[0] day file1 file2\n";
        exit ( 0 );
    }
    
    $day = $argv[1];
    $addFile = $argv[2];
    $requestFile = $argv[3];
    $logIdToMethod = logIdToMethod($requestFile);
    $addHero = addHeroAnalyzeByLogId($addFile);
    addHeroAnalyzeByMethod($addHero, $logIdToMethod); 
}
main();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */