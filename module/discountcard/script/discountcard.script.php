<?php
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php"; 
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Reward.def.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Define.def.php";

$csvFile = 'vip_card.csv';
$outFileName = 'MONTHLYCARD';

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
$arrFieldName = array(
		 'id' => $ZERO,   //优惠卡id
		 'duration' => ++$ZERO, //月卡持续时间  (天数)
		 'limitTime' => ++$ZERO, //购买月卡限制时间
		 'profile' => ++$ZERO,//活动描述
		 'needMoney' => ++$ZERO,//花费金额
		 'reward' => ++$ZERO,//奖励数组			
		 'productId' => ++$ZERO,//商品ID
		 'buyProfile' => ++$ZERO,//购买描述
		 'gift' => ++$ZERO,//大礼包
		 'needChargeGold' => ++$ZERO,//购买月卡需要充值金币
		);

$file = fopen($argv[1]."/$csvFile", 'r');
// 略过前两行
$line = fgetcsv($file);
$line = fgetcsv($file);
$arrCard = array();
$card = array();
$arrProduct = array();
while(TRUE)
{
	$card = array();
	$line = fgetcsv($file);
	if(empty($line))
	{
		break;
	}
	foreach($arrFieldName as $key => $v)
	{
		switch($key)
		{
		    case 'profile':
		    case 'buyProfile':
		        break;
		    case 'reward':
		    case 'gift':
		        $tmpReward = array();
		        $originalReward = array();
		        $arrReward = str2Array($line[$v], ',');
		        foreach($arrReward as $rewardInfo)
		        {
		            $arrInfo = array2Int(str2Array($rewardInfo, '|'));
		            if(count($arrInfo) != 3)
		            {
		                trigger_error('error config in reward field.');
		            }
		            $type = $arrInfo[0];
		            $tmplId = $arrInfo[1];
		            $num = $arrInfo[2];
		            switch($type)
		            {
		                case RewardConfType::SILVER:
		                case RewardConfType::SOUL:
		                case RewardConfType::JEWEL:
		                case RewardConfType::GOLD:
		                case RewardConfType::EXECUTION:
		                case RewardConfType::STAMINA:
		                case RewardConfType::SILVER_MUL_LEVEL:
		                case RewardConfType::SOUL_MUL_LEVEL:
		                case RewardConfType::EXP_MUL_LEVEL:
		                    $tmpReward[$type] = $num;
		                    $originalReward[] = array('type'=>$type,'val'=>$num);
		                    break;
		                case RewardConfType::HERO_MULTI:
		                case RewardConfType::ITEM_MULTI:
		                case RewardConfType::TREASURE_FRAG_MULTI:
		                    if(!isset($tmpReward[$type][$tmplId]))
		                    {
		                        $tmpReward[$type][$tmplId] = 0;
		                    }
		                    $tmpReward[$type][$tmplId] += $num;
		                    $originalReward[] = array('type'=>$type,'val'=>array(array($tmplId,$num)));
		                    break;
		                case RewardConfType::HERO:
		                    if(!isset($tmpReward[RewardConfType::HERO_MULTI][$tmplId]))
		                    {
		                        $tmpReward[RewardConfType::HERO_MULTI][$tmplId] = 0;
		                    }
		                    $tmpReward[RewardConfType::HERO_MULTI][$tmplId] += $num;
		                    $originalReward[] = array('type'=>RewardConfType::HERO_MULTI,'val'=>array(array($tmplId,$num)));
		                    break;
		                case RewardConfType::ITEM:
		                    if(!isset($tmpReward[RewardConfType::ITEM_MULTI][$tmplId]))
		                    {
		                        $tmpReward[RewardConfType::ITEM_MULTI][$tmplId] = 0;
		                    }
		                    $tmpReward[RewardConfType::ITEM_MULTI][$tmplId] += $num;
		                    $originalReward[] = array('type'=>RewardConfType::ITEM_MULTI,'val'=>array(array($tmplId,$num)));
		                    break;
		            }
		        }
		        if($key == 'reward')
		        {
		            $card['originalReward'] = $originalReward;
		        }
		        else if($key == 'gift')
		        {
		            $card['originalgift'] = $originalReward;
		        }
		        $card[$key] = $tmpReward;
		        break;
		    default:
		        $card[$key] = intval($line[$v]);
		}
	}
	$arrProduct[$card['productId']] = $card['id'];
	$card['limitTime'] = SECONDS_OF_DAY * $card['limitTime'];
	$arrCard[$card['id']] = $card;
}
fclose($file);
$arrInfo = array('card'=>$arrCard,'product'=>$arrProduct);
//将内容写入BASE文件中
$file = fopen($argv[2]."/$outFileName", 'w');
fwrite($file, serialize($arrInfo));
fclose($file);
