<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 

$csvFile = 'copy.csv';
$outFileName = 'COPY';

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
		'id' => $ZERO,  //副本id
		'name' => ++$ZERO, //副本名称:黄巾之乱		
		'profile' => ++$ZERO, //副本介绍 
		'img' => ++$ZERO, // 副本图片
		'type' => ++$ZERO, // 副本类型
		'base_open' => ++$ZERO, // 击败某据点开启此副本  10 **************int
		'level_open' => ++$ZERO, // 用户达到某个等级开启此副本
		'pass_open_elite' => ++$ZERO, // 通关此副本开启的精英副本ID 10,20,30 ***array
		'pass_open_actcopy'=> ++$ZERO,// 通关此副本开启的活动副本ID 10,20,30 ***array
		'reward_item_ids' => ++$ZERO, // 通关奖励物品ID和数量组  2100001|1,2100002|2  ****array	
		'reward_silver' => ++$ZERO, //通关奖励银币
		'star_arrays' => ++$ZERO,//副本星数数组 10,20,30  ******array
		'rewards_01' => ++$ZERO,//白银宝箱奖励资源类型和数值  0|2100002|1, 0|2100003|1,1|1|50,2|2|50000,3|3|5000	****array	
		'rewards_02' => ++$ZERO,//黄金宝箱奖励资源类型和数值
		'rewards_03' => ++$ZERO,//白金宝箱奖励资源类型和数值
		'total_star' => ++$ZERO,//副本总星数
		'small_img' => ++$ZERO,//副本缩略图片
		'base_num' => ++$ZERO,//据点个数
							 //将所有的base放到一个数组base中
		'base01' => ++$ZERO,//据点id1
		'base02' => ++$ZERO,
		'base03' => ++$ZERO,
		'base04' => ++$ZERO,
		'base05' => ++$ZERO,
		'base06' => ++$ZERO,
		'base07' => ++$ZERO,
		'base08' => ++$ZERO,
		'base09' => ++$ZERO,
		'base10' => ++$ZERO,
		'base11' => ++$ZERO,
		'base12' => ++$ZERO,
		'base13' => ++$ZERO,
		'base14' => ++$ZERO,
		'base15' => ++$ZERO,
		'base16' => ++$ZERO,
		'base17' => ++$ZERO,
		'base18' => ++$ZERO,
		'base19' => ++$ZERO,
		'base20' => ++$ZERO,
        'base21' => ++$ZERO,
        'base22' => ++$ZERO,
        'base23' => ++$ZERO,
        'base24' => ++$ZERO,
        'base25' => ++$ZERO,
        'base26' => ++$ZERO,
        'base27' => ++$ZERO,
        'base28' => ++$ZERO,
        'base29' => ++$ZERO,
        'base30' => ++$ZERO,
		'into_copy_dialog' => ++$ZERO,//进入副本对话ID
		'music_path' => ++$ZERO,//副本音乐路径
		'copy_show_name'=>++$ZERO,//触发神秘商人的概率
		'open_mysmerchant_chance'=>++$ZERO,//触发神秘商人概率
		);

// 读取 —— 副本选择表.csv
$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$copies = array();
$copy = array();
while(TRUE)
{
	$copy = array();
	$line = fgetcsv($file);
	if(empty($line))
	{
		break;
	}
	foreach($field_names as $key => $v)
	{
		switch($key)
		{			
			case 'name':
			case 'profile':
			case 'img':
			case 'small_img':
			case 'into_copy_dialog':
			case 'music_path':
				break;
			case 'pass_open_elite':
			case 'pass_open_actcopy':
			case 'star_arrays':
				$copy[$key] = array2Int(str2Array($line[$v], ','));		
				break;
			case 'reward_item_ids':
				$rewards = str2Array($line[$v],',');//0|2100002
				$tmp = array();
				foreach($rewards as $reward)
				{
				    $rewardInfo    =    array2Int(str2Array($reward, '|'));
				    if(!empty($rewardInfo))
				    {
				        $tmp[] =$rewardInfo;
				    }
					
				}
				$copy[$key] = $tmp;
				break;
			case 'rewards_01':
			case 'rewards_02':
			case 'rewards_03':
				$rewards = str2Array($line[$v],',');//0|2100002|2
				$tmp = array();
				foreach($rewards as $reward)
				{
				    $rewardInfo    =    array2Int(str2Array($reward, '|'));
				    if(!empty($rewardInfo))
				    {
				        $tmp[] = $rewardInfo;
				    }
				}
				$index = 0;
				if( $key == 'rewards_01')
				{
					$index =0;
				}
				if ($key == 'rewards_02')
				{
					$index =1;
				}
				if ($key == 'rewards_03')
				{
					$index =2;
				}
				$copy['prize'][$index] = $tmp;				
				break;
			default:
				if( strpos($key,'base') === 0 && $key != 'base_num' && ($key != 'base_open'))
				{
					$copy['base'][$v-18] = intval($line[$v]);
				}
				else 
				{
					$copy[$key] = intval($line[$v]);
				}				
		}
	}
	
	$copies[$copy['id']] = $copy;
}
fclose($file);
//将内容写入COPY文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($copies));
fclose($file);