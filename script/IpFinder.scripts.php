<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IpFinder.scripts.php 60629 2013-08-21 09:51:53Z wuqilin $
 * 
 **********************************************************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/IpFinder.scripts.php $
 * @author $Author: wuqilin $(liuyang@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief 
 *  
 **/


if ( $argc < 3 )
{
	echo "Please input enough arguments:!all_ip.log output\n";
	exit;
}

// 得到IP包
$ipPack = unserialize(file_get_contents('IP', 'r'));
echo "Unserialize over.\n";

$allKeys = array_keys($ipPack);
echo "Array keys over.\n";

// 常量定义
$length = 150000;
$ct10000 = "电信";
$t10010 = "联通";
$t10086 = "其他";

// 查找数组
function findInPack($array, $num, $index)
{
	if (count($array) == 1)
		return $array[0];

	$count = count($array);

//var_dump($count);
//var_dump($index);
//var_dump($array);

	if ($array[$index] < $num)
	{
		$next = intval(($count - $index) / 2);
		return findInPack(array_slice($array, $index, $count - $index), $num, $next);
	}
	$next = intval(($index) / 2);
	return findInPack(array_slice($array, 0, $index), $num, $next);
}

$file = fopen($argv[1].'/all_ip.log', 'r');

$countAll = 0;

// 输出用分组
$ipList = array();
// 统计用分组
$areaList = array();
$telList = array();


//数据对应表
$ZERO = 0;
$name = array (
'pid' => $ZERO,									// PID
'ad_id' => ++$ZERO,								// 来源广告ID
'login_time' => ++$ZERO,						// 注册日期
'ip' => ++$ZERO,								// IP
'pay_times' => ++$ZERO,							// 支付次数
'gold' => ++$ZERO								// 支付金额(金子)
);



while ( TRUE )
{
	$data = fgetcsv($file);
	if ( empty($data) )
		break;

	// 这个是中间存储用的临时数组
	$array = array();
	foreach ( $name as $key => $v )
	{
		// 普通数组
		if ($key == 'ip')
		{
			if (empty($data[$v]))
				continue 2;

			$array[$key] = ip2long($data[$v]);
		}
		else 
		{
			$array[$key] = intval($data[$v]);
		}
	}

	// 检查不符合规范的数据
	if ($array['pay_times'] != 0 && $array['gold'] == 0)
		continue;

	$key = findInPack($allKeys, $array['ip'], $length);
//	$ipList[] = $ipPack[$key]['area_1'].' '.$ipPack[$key]['area_2'];

	// 获取城市名
	$city = substr($ipPack[$key]['area_1'], 0, 6);
	// 记录一个城市
	if (isset($areaList[$city]['num']))
		++$areaList[$city]['num'];
	else 
		$areaList[$city]['num'] = 1;

	// 统计消费
	if ($array['gold'] != 0)
	{
		if (isset($areaList[$city]['gold']))
			++$areaList[$city]['gold'];
		else 
			$areaList[$city]['gold'] = 1;
	}
	else 
	{
		if (!isset($areaList[$city]['gold']))
		{
			$areaList[$city]['gold'] = 0;
		}
	}

	// 检查运营商
	if (strstr($ipPack[$key]['area_2'], $ct10000))
	{
		if (isset($telList[$ct10000]['num']))
			++$telList[$ct10000]['num'];
		else 
			$telList[$ct10000]['num'] = 1;

		// 统计消费
		if ($array['gold'] != 0)
		{
			if (isset($telList[$ct10000]['gold']))
				++$telList[$ct10000]['gold'];
			else 
				$telList[$ct10000]['gold'] = 1;
		}
		else 
		{
			if (!isset($telList[$ct10000]['gold']))
			{
				$telList[$ct10000]['gold'] = 0;
			}
		}
	}
	else if (strstr($ipPack[$key]['area_2'], $t10010))
	{
		if (isset($telList[$t10010]['num']))
			++$telList[$t10010]['num'];
		else 
			$telList[$t10010]['num'] = 1;

		// 统计消费
		if ($array['gold'] != 0)
		{
			if (isset($telList[$t10010]['gold']))
				++$telList[$t10010]['gold'];
			else 
				$telList[$t10010]['gold'] = 1;
		}
		else 
		{
			if (!isset($telList[$t10010]['gold']))
			{
				$telList[$t10010]['gold'] = 0;
			}
		}
	}
	else
	{
		if (isset($telList[$t10086]['num']))
			++$telList[$t10086]['num'];
		else 
			$telList[$t10086]['num'] = 1;

		// 统计消费
		if ($array['gold'] != 0)
		{
			if (isset($telList[$t10086]['gold']))
				++$telList[$t10086]['gold'];
			else 
				$telList[$t10086]['gold'] = 1;
		}
		else 
		{
			if (!isset($telList[$t10086]['gold']))
			{
				$telList[$t10086]['gold'] = 0;
			}
		}
	}

	// 单纯用做指示用的，显示进度
	++$countAll;
	if ($countAll % 1000 == 0)
		echo $countAll."\n";
}


fclose($file); //var_dump($ipList);


// 统计百分比
// 统计城市
foreach ($areaList as $city => $v)
{
	$areaList[$city]['per'] = round($areaList[$city]['num'] * 100 / $countAll, 2);
	$areaList[$city]['gold_per'] = round($areaList[$city]['gold'] * 100 / $countAll, 2);
}
// 统计运营商
foreach ($telList as $tel => $v)
{
	$telList[$tel]['per'] = round($telList[$tel]['num'] * 100 / $countAll, 2);
	$telList[$tel]['gold_per'] = round($telList[$tel]['gold'] * 100 / $countAll, 2);
}



//$file = fopen($argv[2].'/AREA', 'w');
//foreach ($ipList as $ars)
//{
//	fwrite($file, $ars);
//	fwrite($file, "\n");
//}
//fclose($file);


$file = fopen($argv[2].'/PERCENT', 'w');
foreach ($areaList as $area => $ars)
{
	$str = $area." ".$ars['num']." ".$ars['per']." ".$ars['gold_per']."\n";
	fwrite($file, $str);
}
fwrite($file, "\n");
fwrite($file, "\n");
fwrite($file, "\n");
foreach ($telList as $tel => $ars)
{
	$str = $tel." ".$ars['num']." ".$ars['per']." ".$ars['gold_per']."\n";
	fwrite($file, $str);
}
fclose($file);

echo 'ok'."\n";
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */