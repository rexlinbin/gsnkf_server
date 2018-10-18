<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NormalConfig.script.php 258060 2016-08-24 03:40:23Z GuohaoZheng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/script/NormalConfig.script.php $
 * @author $Author: GuohaoZheng $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-08-24 03:40:23 +0000 (Wed, 24 Aug 2016) $
 * @version $Revision: 258060 $
 * @brief
 *
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/NormalConfig.def.php";

$csvFile = 'normal_config.csv';
$outFileName = 'NORMAL_CONFIG';


if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName \n");
}

if ( $argc < 3 )
{
    trigger_error( "Please input enough arguments:!{$csvFile}\n" );
}
$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
    trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$arrConf = array();
while ( true )
{
    $data = fgetcsv($file);
    if ( empty($data) )
    {
        break;
    }
    $conf = array();
    $id = $data[0];
    $columnId = 1;
    while(TRUE)
    {
        if(!isset($data[$columnId]))
        {
            break;
        }
        switch($columnId)
        {
            case NormalConfigDef::CONFIG_ID_FIGHTSOUL_POSOPEN:
            case NormalConfigDef::CONFIG_ID_TREAS_INLAY:
            case NormalConfigDef::CONFIG_ID_POCKET_POS_OPENLV:
            case NormalConfigDef::CONFIG_ID_POCKET_REBORN:
            
                $tmp = array2Int(str2Array($data[$columnId], ','));
                foreach($tmp as $index => $level)
                {
                    $arrConf[$columnId][$index+1] = $level;
                }
                break;
            case NormalConfigDef::CONFIG_ID_CHANGE_NAME:
                $tmp = array2Int(str2Array($data[$columnId], '|'));
                $arrConf[$columnId]['need_gold'] = 0;
                $arrConf[$columnId]['need_item'] = array();
                if(isset($tmp[0]))
                {
                    $arrConf[$columnId]['need_gold'] = $tmp[0];
                }
                if(isset($tmp[1]))
                {
                    $arrConf[$columnId]['need_item'] = array($tmp[1]=>1);
                }
                break;
            case NormalConfigDef::CONFIG_ID_CLEAR_SWEEPCD:
                $tmp = array2Int(str2Array($data[$columnId], '|'));
                if(count($tmp) != 3)
                {
                    trigger_error('error config for clear sweepcd.field num is not 3.config is '.$data[$columnId]);
                }
                $arrConf[$columnId]['init_gold'] = $tmp[0];
                $arrConf[$columnId]['inc_gold'] = $tmp[1];
                $arrConf[$columnId]['max_gold'] = $tmp[2];
                break;
            //一维数组
            case NormalConfigDef::CONFIG_ID_GOLDBOX_SERIAL:
            case NormalConfigDef::CONFIG_ID_GUILD_FIGHTEACHOTHER_LIMITS:
            case NormalConfigDef::CONFIG_ID_FRIEND_PK_NUM:
            case NormalConfigDef::CONFIG_ID_TALENT_INHERIT_NEEDGOLD:
            case NormalConfigDef::CONFIG_ID_ORANGE_HEROFRAG_DROP:
            case NormalConfigDef::CONFIG_ID_ARR_DRESS_ROOM_ID:
            case NormalConfigDef::CONFIG_ID_FORMULA_ITEM:
            case NormalConfigDef::CONFIG_ID_OPEN_GOLD_BOX:
            case NormalConfigDef::CONFIG_ID_RAPID_HUNT_SOUL:
            case NormalConfigDef::CONFIG_ID_ONE_KEY_SEIZE:
            case NormalConfigDef::CONFIG_ID_GODWEAPON_TRANSFER_COST:
            case NormalConfigDef::CONFIG_ID_UNION_OPEN_LEVEL_ARR:
            case NormalConfigDef::CONFIG_ID_ADESACT_BUY_LIMIT:
            case NormalConfigDef::CONFIG_ID_TALLY_TRANSFER_ARR:
            case NormalConfigDef::CONFIG_ID_TALLY_TRANSFER_COST:
        		$tmp = array2Int(str2Array($data[$columnId], '|'));
                $arrConf[$columnId] = $tmp;
            	break;
            //数值类型
            case NormalConfigDef::CONFIG_ID_GOLDBOX_DROP:
            case NormalConfigDef::CONFIG_ID_HELPARMYINCOME_RATIO:
            case NormalConfigDef::CONFIG_ID_ONEHELPARMY_ENHANCE:
            case NormalConfigDef::CONFIG_ID_LOOTHELPARMY_COSTEXCETION:
            case NormalConfigDef::CONFIG_ID_RESPLAYER_LV:
            case NormalConfigDef::CONFIG_ID_PIT_HELPER_TIMELIMIT:
            case NormalConfigDef::CONFIG_ID_RESETBASENUM_NEED_ITEM:
            case NormalConfigDef::CONFIG_ID_GOLDPIT_NEED_GOLD:
            case NormalConfigDef::CONFIG_ID_GOLD_TREE_EXP_TBL:
            case NormalConfigDef::CONFIG_ID_ROB_GOLDPIT_MINCAPTURE:
            case NormalConfigDef::CONFIG_ID_DRAGON_CONTRIBUTE_MAX_NUM:
            case NormalConfigDef::CONFIG_ID_OPEN_DRAGON_MIN_POINT:
            case NormalConfigDef::CONFIG_ID_PASS_FREE_NUM:
            case NormalConfigDef::CONFIG_ID_SWEEP_NOCD_NEEDLV:
            case NormalConfigDef::CONFIG_ID_GUILD_CHANGE_NAME:
            case NormalConfigDef::CONFIG_ID_GUILD_JOIN_SHARE:
            case NormalConfigDef::CONFIG_ID_COPY_DOUBLE_EXP_LEVEL:
            case NormalConfigDef::CONFIG_ID_AUTO_ATTACK_BOSS:
            case NormalConfigDef::CONFIG_ID_HUNT_DROP_SWITCH_LEVEL:
            case NormalConfigDef::CONFIG_ID_DROP_HERO_2_SOUL_NEED_LEVEL:
            case NormalConfigDef::CONFIG_ID_REMOVE_PILL_COST_SILVER:
            case NormalConfigDef::CONFIG_ID_REINFORCE_GOD_WEAPON_LEVEL:
            case NormalConfigDef::CONFIG_ID_RESOLVE_HERO_JH_COST_SILVE:
            case NormalConfigDef::CONFIG_ID_RAPID_HUNT_QUALITY:
            case NormalConfigDef::CONFIG_ID_TREASURE_TRANSFER_NEED_LEVEL:
            case NormalConfigDef::CONFIG_ID_GODWEAPON_TRANSFER_NEED_LEVEL:
            case NormalConfigDef::CONFIG_ID_TALLY_TRANSFER_NEED_LEVEL:
            case NormalConfigDef::CONFIG_ID_TREASURE_TRANSFER_COST:
            case NormalConfigDef::CONFIG_ID_FS_2_RED:
            case NormalConfigDef::CONFIG_ID_RESET_HERO_DESTINY:
            case NormalConfigDef::CONFIG_ID_CHANGE_SEX:
            case NormalConfigDef::CONFIG_ID_MAX_SYS_REWARD_EVERYDAY:
            case NormalConfigDef::CONFIG_ID_GUILDROB_OFFLINE_NEED_LEVEL:
            	$arrConf[$columnId] = intval($data[$columnId]);
            	break;
            case NormalConfigDef::CONFIG_ID_RESETGOLDTREE_NEED_ITEM:
            case NormalConfigDef::CONFIG_ID_DIVINE_NEED_ITEM:
            case NormalConfigDef::CONFIG_ID_DRAGON_RESET_ITEM:
                $tmp = array2Int(str2Array($data[$columnId], '|'));
                if(count($tmp) != 2)
                {
                    trigger_error('data config error.config id is '.NormalConfigDef::CONFIG_ID_RESETGOLDTREE_NEED_ITEM);
                }
                $arrConf[$columnId][$tmp[0]] = $tmp[1];
                break;
            case NormalConfigDef::CONFIG_ID_RES_DELAY_DATA:
            case NormalConfigDef::CONFIG_ID_FORMULA:
            case NormalConfigDef::CONFIG_ID_TREASURE_TRANSFER_ARR:
            case NormalConfigDef::CONFIG_ID_GODWEAPON_TRANSFER_ARR:
            case NormalConfigDef::CONFIG_ID_PILL_RESULT:
            case NormalConfigDef::CONFIG_ID_CHARIOT_POS_TYPE_LV:
            	$tmp = str2Array($data[$columnId], ',');
            	foreach($tmp as $key => $value)
            	{
            		$arrConf[$columnId][$key] = array2Int(str2Array($value, '|'));
            	}
            	break;
            case NormalConfigDef::CONFIG_ID_TRANSFER_COST:
            case NormalConfigDef::CONFIG_ID_TRANSFER_ASSIGN_COST:
            case NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_WEI:
            case NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_SHU:
            case NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_WU:
            case NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_QUN:
                $tmp = str2Array($data[$columnId], ',');
                foreach($tmp as $key => $value)
                {
                    $arrConf[$columnId][$key + 12] = array2Int(str2Array($value, '|'));
                }
                break;
            case NormalConfigDef::CONFIG_ID_TALENT_GROUP_WEIGHT_1:
            case NormalConfigDef::CONFIG_ID_TALENT_GROUP_WEIGHT_2:
            case NormalConfigDef::CONFIG_ID_TALENT_GROUP_WEIGHT_3:
            case NormalConfigDef::CONFIG_ID_TALENT_GROUP_WEIGHT_4:
                $arrConf[$columnId] = array();
                $tmp = array2Int( str2array($data[$columnId]) );
                foreach($tmp as $index => $tmpInfo)
                {
                    $arrConf[$columnId][$index+1]['weight'] = $tmpInfo;
                }
                break;
            case NormalConfigDef::CONFIG_ID_TALENT_GROUP_LIST_1:
            case NormalConfigDef::CONFIG_ID_TALENT_GROUP_LIST_2:
            case NormalConfigDef::CONFIG_ID_TALENT_GROUP_LIST_3:
            case NormalConfigDef::CONFIG_ID_TALENT_GROUP_LIST_4:
                $arrConf[$columnId] = array();
                $arr = str2array($data[$columnId]);
                foreach( $arr as $index=>$value )
                {
                    $arrConf[$columnId][$index+1] = array2Int( str2Array($value, '|'));
                }
                break;
            case NormalConfigDef::CONFIG_ID_GODWEAPON_POS_OPENLV:
            case NormalConfigDef::CONFIG_ID_GODWEAPON_LEGEND:
            case NormalConfigDef::CONFIG_ID_FORMULA_GOLD:
            case NormalConfigDef::CONFIG_ID_HERO_2_SOUL_COST:
            case NormalConfigDef::CONFIG_ID_RAPID_HUNT_SOUL_TYPE:
            case NormalConfigDef::CONFIG_ID_TALLY_POS_OPENLV:
                $arrConf[$columnId] = array();
                $arr = str2array($data[$columnId]);
                foreach( $arr as $index=>$value )
                {
                    $arrLvInfo = array2Int( str2Array($value, '|'));
                    $arrConf[$columnId][$arrLvInfo[0]] = $arrLvInfo[1];
                }
                break;
            case NormalConfigDef::CONFIG_ID_TREAS_DEVELOP_LEVEL:
            case NormalConfigDef::CONFIG_ID_MASTER_HERO_AWAKE_ABILITY:
            	$arrConf[$columnId] = array2Int(str2Array($data[$columnId], ','));
            	break;
            case NormalConfigDef::CONFIG_ID_MINERAL_GUILD_EXTRA_RES:
            	$arrConf[$columnId] = array();
            	$arr = str2Array($data[$columnId],',');
            	foreach ($arr as $tmp)
            	{
            		$tmp=str2Array($tmp,'|' );
            		$arrConf[$columnId][$tmp[0]]=intval($tmp[1]);
            	}
            	break;
            case NormalConfigDef::CONFIG_ID_PILL_PORMULA:
            	$arrConf[$columnId]=array();
            	$arr = str2Array($data[$columnId],';');
            	$arrCost1=str2Array($arr[0], ',');
            	$arrCost2=str2Array($arr[1], ',');
            	foreach ($arrCost1 as $va)
            	{
            		$arrConf[$columnId][0][]=array2Int(str2Array($va,'|' ));
            	}
            	foreach ($arrCost2 as $va)
            	{
            		$arrConf[$columnId][1]=array2Int(str2Array($va,'|' ));
            	}
            	break;
        }
        $columnId++;
    }
}
fclose($file);
//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
    trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($arrConf));
fclose($file);
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */