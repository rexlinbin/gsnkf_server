<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 


$csvFile = 'res.csv';
$outFileName = 'MINERAL';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}

if ( $argc < 3 )
{
	echo "Please input enough arguments:!COPY.csv output\n";
	trigger_error ("input error parameters.");	
}

$ZERO = 0;
$field_names = array(
		'id' => $ZERO, //资源ID	
		'domain_name' => ++$ZERO,//资源名称
		'domain_type' =>  ++$ZERO,//资源区域类型
		'domain_img'  =>  ++$ZERO,//资源图片ID
		'pit_1_type'  => ++$ZERO,//资源矿1类型
		'pit_1_name'=> ++$ZERO,
		'pit_1_arr'=> ++$ZERO,
		'pit_1_img'=> ++$ZERO,
        'pit_2_type'  => ++$ZERO,//资源矿2类型
		'pit_2_name'=> ++$ZERO,
		'pit_2_arr'=> ++$ZERO,
		'pit_2_img'=> ++$ZERO,
        'pit_3_type'  => ++$ZERO,//资源矿3类型
		'pit_3_name'=> ++$ZERO,
		'pit_3_arr'=> ++$ZERO,
		'pit_3_img'=> ++$ZERO,
        'pit_4_type'  => ++$ZERO,//资源矿4类型
		'pit_4_name'=> ++$ZERO,
		'pit_4_arr'=> ++$ZERO,
		'pit_4_img'=> ++$ZERO,
        'pit_5_type'  => ++$ZERO,//资源矿5类型
		'pit_5_name'=> ++$ZERO,
		'pit_5_arr'=> ++$ZERO,
		'pit_5_img'=> ++$ZERO,
		'iron_num'=>++$ZERO,//产出物品
		);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$domains = array();
$domain = array();
while(TRUE)
{
	$army = array();
	$line = fgetcsv($file);
	// 	var_dump($line);
	if(empty($line))
	{
		break;
	}
	$domain    =    array();
	foreach($field_names as $key => $v)
	{
		switch($key)
		{
			case 'domain_name':
			case 'domain_img':
				break;	
			case 'id':
			case 'domain_type':
				$domain[$key] = intval($line[$v]);
				break;
			case 'iron_num':
				$domain[$key]=array2Int(str2Array($line[$v], '|'));
				break;
			default:
				$tmp = str2Array($key,'_');
				$pitId	=	$tmp[1];
				if($tmp[2] == 'arr')
				{
					$arr = array_map('intval', str2Array($line[$v],','));
					if(count($arr) == 5)
					{
						$domain['pits'][$pitId] = $arr;
					}
					else 
					{
					    var_dump($line);
					    trigger_error('the size of pitattr must be 5');
					}
				}
				else if($tmp[2] == 'type')
				{
				    $type = intval($line[$v]);
				    $domain['type'][$pitId] = $type;
				}
		}		
	}	
	$domains[$domain['id']] = $domain;	
}
fclose($file);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($domains));
fclose($file);
