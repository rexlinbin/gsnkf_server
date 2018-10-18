<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Vip.script.php 172199 2015-05-11 09:36:41Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/script/Vip.script.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2015-05-11 09:36:41 +0000 (Mon, 11 May 2015) $
 * @version $Revision: 172199 $
 * @brief 
 *  
 **/



require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Creature.def.php";

$csvFile = 'vip.csv';
$outFileName = 'VIP';


if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
	trigger_error("Please input enough arguments:!{$csvFile}\n");
}

$index = 0;
$fieldNames = array(
        'id'                            => $index++  ,//id
		'vipLevel'						=> $index++  ,//VIP等级
		'totalRecharge'					=> $index++  ,//累积充值金额
		'goldFeedTimes'					=> $index++  ,//金币喂养次数
		'freeStaminaTimes'				=> $index++  ,//每日补充耐力次数//暂时不用
		'buyItemLimit'					=> $index++  ,//每日购买物品次数
		'vipGift'        				=> $index++  ,//购买VIP礼包数组
		'pitOccupyTimeAddition'         => $index++  ,//VIP占领资源矿增加的时间
		'buyGoodsIds'                   => $index++  ,//VIP购买商品ID限制 
		'fatalWeight'                   => $index++  ,//装备强化暴击权重
		'competeNum'                    => $index++  ,//增加比武次数
		'mysteryRfrTimes'               => $index++  ,//神秘商店刷新次数 
		'goldAtkTree'                   => $index++  ,//摇钱树额外挑战花费金币数
		'armRefreshLv'                  => $index++  ,//洗练开启档次
		'bossReviveOpen'                => $index++  ,//世界boss是否开启复活
		'towerBuyLimit'                 => $index++  ,//爬塔购买失败次数限制
		'bossOnHook'                    => $index++  ,//世界boss是否可以挂机
		'robTombFreeNum'                => $index++  ,//挖宝模块中用户免费挖宝次数限制
		'robTombGoldNum'                => $index++  ,//挖宝模块中用户金币挖宝次数限制
		'goldOpenExplore'               => $index++  ,//金币开启探索  
		'huntTenNeedLevel'              => $index++  ,//战魂猎10次开启
		'maxPetFence'                   => $index++,//宠物栏位上限
		'copyShopTime'                  => $index++,//副本神秘商人刷新次数
		'isSkipFight'                   => $index++, //  是否直接跳过战斗
		'mysSysRfrNum'                  => $index++,//神秘商店累计免费刷新次数
		'ecopyBuyNum'                   =>  $index++,//购买精英副本次数金币组
        'teamcopyBuyNum'                =>  $index++,//每日组队次数购买
        'towerBuyNum'                   =>  $index++,//每日爬塔次数购买	
        'resetDivineNum'                =>  $index++,//每日占星奖励重置
        'goldtreeBuyNum'                =>  $index++,//每日摇钱树次数购买
        'exptreasBuyNum'                =>  $index++,//经验宝物副本次数购买
        'buyGuildReward'                =>  $index++,//军团参拜次数金币组
        'secPitGold'                    =>  $index++,//第2资源矿消耗金币
        'isChatOpen'                    =>  $index++,//是否开启聊天
        'exploreLongNum'                =>  $index++,//寻龙探宝次数
        'exploreLongActNum'             =>  $index++,//寻龙探宝行动力次数
        'openMysMerchant'               =>  $index++,//永久开启神秘商人
        'openSpecailTower'              =>  $index++,//金币开启神秘塔层
        'maxExploreLongNum'             =>  $index++,//寻龙探宝累积次数上限
        'offlineEnter'                  =>  $index++,//离线入场
        'aiExploreFreeNum'              =>  $index++,//自动寻龙免费次数
        'starChallengeLimit'			=>  ($index+=2)-1,//武艺切磋金币购买次数上限
        'starDrawLimit'					=>  $index++,//铜雀翻牌金币购买次数
        'weekendShopLimit'              =>  $index++,//周末商店购买次数限制
		'huntFiftyNeedLevel'            => 	$index++,//战魂猎50次开启
		'fragseizeQuickFuse'			=>	$index++,//宝物一键合成开启
		'rouletteFreeNum'               =>  $index++,//积分轮盘免费次数
		'rouletteTotalNum'              =>  $index++,//积分轮盘金币购买次数
		'refreshFieldCost'				=>  $index++,//军团粮田采集次数全部刷新
		'noSweepCD'                     =>  $index++,//是否有副本连战CD
		'passBuyNum'					=>  ($index+=2)-1,
        'expUserBuyNum'                 =>  $index++,//主角经验副本次数购买
        'athenaLimitNum'                =>  $index++,//星魂每日购买技能树材料数量上限
        'allAttackOpen'					=>	$index++,//军团副本全团突击功能是否开启
        'tgShopRefreshLimit'			=>	$index++,//天工阁商品刷新上限
);

$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
	trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n");
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
$conf	=	array();
while ( true )
{
	$data = fgetcsv($file);
	if ( empty($data) )
	{
		break;
	}
	$conf = array();
	foreach ( $fieldNames as $key => $index )
	{
		switch($key)
		{
			case 'buyItemLimit':
			    $arr = str2array($data[$index]);
			    foreach($arr as $index=>$value)
			    {
			        $temp    =    array_map('intval',str2Array($value, '|'));
			        if(count($temp) < 2)
			        {
			            continue;
			        }
			        $conf[$key][$temp[0]]    =    $temp[1];
			    }
				break;
			case 'vipGift':
			    $temp = array_map('intval', str2Array($data[$index], '|'));
			    if(!empty($temp))
			    {
			        $temp[1]    =    $temp[2];
			        unset($temp[2]);
			    }
			    $conf[$key]    =    $temp;
			    break;	
			case 'buyGoodsIds':
			case 'openSpecailTower':
            case 'athenaLimitNum':
			    $temp = str2Array($data[$index], ',');
			    foreach($temp as $index => $goods)
			    {
			        $goodsInfo = array2Int(str2Array($goods, '|'));
			        if(count($goodsInfo) != 2)
			        {
			            trigger_error('error config in buyGoodsIds');
			        }
			        $conf[$key][$goodsInfo[0]] = $goodsInfo[1];
			    }
			    break;
			case 'fatalWeight':
			    $temp = str2Array($data[$index], ',');
			    foreach($temp as $index => $weight)
			    {
			        $weightInfo = array2Int(str2Array($weight, '|'));
			        if(count($weightInfo) != 2)
			        {
			            trigger_error('error config in fatalweight');
			        }
			        $conf[$key][$weightInfo[0]] = array('weight'=>$weightInfo[1]);
			    }
			    break;
			case 'competeNum':
			case 'armRefreshLv':
			case 'goldtreeBuyNum':
			case 'ecopyBuyNum':    
			case 'exptreasBuyNum':
			case 'towerBuyNum':
			case 'exptreasBuyNum':
		    case 'teamcopyBuyNum':
			case 'resetDivineNum':
			case 'buyGuildReward':
 			case 'openMysMerchant':
 			case 'refreshFieldCost':
            case 'expUserBuyNum':
			    $conf[$key]	= array2Int(str2Array($data[$index], '|'));
			    break;
			case 'goldOpenExplore':
			    $conf[$key] = array2Int(str2Array($data[$index], '|'));
			    if(count($conf[$key]) != 4)
			    {
			        $conf[$key] = array();
			    }
			    break;
			case 'huntTenNeedLevel':
			case 'huntFiftyNeedLevel':
			case 'fragseizeQuickFuse' :
			    $tmp = array2Int(str2Array($data[$index], '|'));
			    if(count($tmp) != 2)
			    {
			        trigger_error('error config in column huntTenNeedLevel.column size is not 2');
			    }
			    if($tmp[0] == 0)
			    {
			        $conf[$key] = $tmp[1];
			    }
			    else if($tmp[0] == 1)
			    {
			        $conf[$key] = 1;
			    }
			    else 
			    {
			        trigger_error('error config.in column huntTenNeedLevel.first element is not 0 or 1.');
			    }
			    break;
			case 'passBuyNum':
				$conf[$key]	= array2Int(str2Array($data[$index], ','));
				break;
			default:
				$conf[$key]	=	intval($data[$index]);
		}
	}	
	$confList[$conf['vipLevel']] = $conf;
}
fclose($file);

var_dump($confList);
//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */