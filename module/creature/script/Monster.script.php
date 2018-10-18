<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Monster.script.php 259834 2016-09-01 02:37:07Z BaoguoMeng $
 * 
 **********************************************************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/creature/script/Monster.script.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-09-01 02:37:07 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259834 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : 
 * Description : 
 * Inherit     : 
 **********************************************************************************************************************/
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Creature.def.php";

$csvFile = 'monsters_tmpl.csv';
$csvFileMst = 'monsters.csv';
$outFileName = 'MONSTERS';


if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $csvFileMst $outFileName\n");
}


if ( $argc < 3 )
{
	trigger_error( "Please input enough arguments:!{$csvFile}\n" );
}

function getConflist($csvFile,$arrConfKey)
{
    $ref = new ReflectionClass('CreatureAttr');
    $usefulKey	=	$ref->getConstants();
    //格式是3,4
    $arrKeyV1 = array(CreatureAttr::ARR_IMMUNED_BUFF,
            CreatureAttr::ARR_IMMUNED_SKILL_TYPE,
            CreatureAttr::ARR_IMMUNED_TARGET_TYPE,
            CreatureAttr::RANDOM_ATTACK_SKILL,
            CreatureAttr::AWAKE_ABILITY_INIT,
            CreatureAttr::UNION_PROFIT,
            //格式是3|4
            CreatureAttr::DROP_HERO_PRB
    );

    //格式是1|1,2|2,3|3,4|4,5|5
    $arrKeyV2 = array(
    //输出是array(0=>array(1,1),1=>array(2,2),2=>array(3,3)....)
            CreatureAttr::AWAKE_ABILITY_GROW,
            //输出是array(1=>array(1,1),2=>array(2,2),3=>array(3,3)....)
            CreatureAttr::SKILLBOOK_POS_OPEN_NEED,
            CreatureAttr::EVOLVE_TBLID);
    $file = fopen($csvFile, 'r');
    if ( $file == FALSE )
    {
        trigger_error( "/{$csvFile} open failed! exit!\n");
    }

    $data = fgetcsv($file);
    $data = fgetcsv($file);

    $confList = array();
    while ( true )
    {
        $data = fgetcsv($file);
        if ( empty($data) || empty($data[0] )  )
        {
            break;
        }
        if(count($data) < count($arrConfKey))
        {
            trigger_error('delete field in config table');
        }
        //         if(count($data) > count($arrConfKey))
            //         {
            //             trigger_error('add field in config table');
            //         }
        $conf = array();
        foreach ( $arrConfKey as $key => $index )
        {
            if(in_array($key, $usefulKey , TRUE) == FALSE)
            {
                continue;
            }
            if( in_array($key, $arrKeyV1, TRUE) )
            {
                if($key == CreatureAttr::DROP_HERO_PRB)
                {
                    var_dump($data[$index]);
                    $conf[$key] = array2Int( str2array($data[$index],'|') );
                }
                else
                {
                    $conf[$key] = array2Int( str2array($data[$index]) );
                }
            }
            else if( in_array($key, $arrKeyV2, TRUE) )
            {
                $arr = str2array($data[$index]);
                $conf[$key] = array();
                foreach( $arr as $indexTmp=>$value )
                {
                    if(!strpos($value, '|'))
                    {
                        $dataindex    =    $data[$index];
                        trigger_error( "invalid $key,value $dataindex need v2\n" );
                    }
                    if($key    ==    CreatureAttr::SKILLBOOK_POS_OPEN_NEED)
                    {
                        $conf[$key][$indexTmp+1] = array2Int( explode('|', $value) );
                    }
                    else if($key == CreatureAttr::EVOLVE_TBLID)
                    {
                        $temp    =    array2Int( explode('|', $value) );
                        $conf[$key][]    =    $temp[1];
                    }
                    else
                    {
                        $conf[$key][] = array2Int( explode('|', $value) );
                    }
                }
            }
            else
            {
                $val = intval($data[$index]);
                if( $val != 0 )
                {
                    $conf[$key] = $val;
                }
            }
        }
        $confList[$conf[CreatureAttr::HTID]] = $conf;
    }
    fclose($file);
    return $confList;
}

$index = 0;
$arrConfKey = array(
        CreatureAttr::HTID  						=> $index++,//英雄模版ID
        'htName'									=> $index++,//卡牌模板名称
        CreatureAttr::NAME 							=> $index++,//英雄名称
        CreatureAttr::LEVEL 						=> $index++ ,//英雄基础等级
        CreatureAttr::GENDER						=> $index++,//卡牌性别
        'profile'									=> $index++,//卡牌描述
        'actionTmpId'								=> $index++,//卡牌动作模型ID
        'headImg'									=> $index++,//卡牌头像图片ID
        'bustImg'									=> $index++,//卡牌半身像ID
        'fullPortraitImg'							=> $index++,//卡牌全身像ID
        'rageHeadImg'								=> $index++,//卡牌怒气头像ID
        CreatureAttr::EXP_ID  						=> $index++  ,//经验表ID
        CreatureAttr::VOCATION  					=> $index++  ,//英雄职业
        CreatureAttr::RAGE_GET_BASE 				=> $index++  ,//怒气获得基础值
        CreatureAttr::RAGE_GET_AMEND 				=> $index++  ,//怒气获得修正值
        CreatureAttr::RAGE_GET_RATIO 				=> $index++  ,//怒气获得倍率
        CreatureAttr::PARRY_SKILL 					=> $index++  ,//格挡技能id
        CreatureAttr::CHARM_SKILL 					=> $index++  ,//魅惑技能id
        CreatureAttr::CHAOS_SKILL 					=> $index++  ,//混乱技能id
        CreatureAttr::DODGE_SKILL 					=> $index++  ,//闪避技能
        CreatureAttr::DEATH_SKILL 					=> $index++  ,//死亡后施放技能
        CreatureAttr::ROUND_BEGIN_SKILL				=> $index++  ,//回合前施放技能
        CreatureAttr::ROUND_END_SKILL	 			=> $index++  ,//行动后后施放技能
        CreatureAttr::ARR_IMMUNED_BUFF 				=> $index++  ,//状态效果ID
        CreatureAttr::ARR_IMMUNED_SKILL_TYPE		=> $index++  ,//跳过技能类型判定ID
        CreatureAttr::ARR_IMMUNED_TARGET_TYPE		=> $index++  ,//跳过技能范围判定ID
        CreatureAttr::ATTACK_SKILL 					=> $index++  ,//默认普通技能
        CreatureAttr::RANDOM_ATTACK_SKILL			=> $index++  ,//随机普通技能
        CreatureAttr::RAGE_SKILL 					=> $index++  ,//怒气攻击技能
        CreatureAttr::SKILLBOOK_POS_OPEN_NEED		=> $index++  ,//技能书开启条件
        CreatureAttr::PRICE 						=> $index++ ,//招募价格
        CreatureAttr::RAGE							=> $index++  ,//英雄基础怒气
        CreatureAttr::REIGN_INIT 					=> $index++  ,//英雄基础统帅
        CreatureAttr::STRENGTH_INIT						=> $index++  ,//卡牌基础力量
        CreatureAttr::INTELLIGENCE_INIT 				=> $index++  ,//英雄基础智慧
        CreatureAttr::GENERAL_ATTACK_INIT               => $index++  ,// 基础通用攻击
        CreatureAttr::PHYSICAL_ATK_RATIO_INIT 			=> $index++  ,//英雄固定物理伤害倍率
        CreatureAttr::MAGIC_ATK_RATIO_INIT 				=> $index++  ,//英雄固定魔法伤害倍率
        CreatureAttr::PHYSICAL_IGNORE_RATIO_INIT 		=> $index++  ,//英雄固定物理免伤倍率
        CreatureAttr::MAGIC_IGNORE_RATIO_INIT 			=> $index++  ,//英雄固定魔法免伤倍率
        CreatureAttr::ABSOLUTE_ATTACK 					=> $index++  ,//英雄基础最终伤害
        CreatureAttr::ABSOLUTE_DEFEND 					=> $index++  ,//英雄基础最终免伤
        CreatureAttr::HP_INC 							=> $index++  ,//英雄生命成长
        CreatureAttr::GENERAL_ATTACK_INC                => $index++  ,//基础通用攻击成长
        CreatureAttr::PHYSIC_ATK_INC					=> $index++  ,//英雄物理攻击成长
        CreatureAttr::MAGIC_ATK_INC 					=> $index++  ,//英雄魔法攻击成长
        CreatureAttr::PHYSIC_DEF_INC 					=> $index++  ,//英雄物理防御成长
        CreatureAttr::MAGIC_DEF_INC						=> $index++  ,//英雄魔法防御成长
        'vocationImg'									=> $index++  ,//职业图标标识
        CreatureAttr::BOOK_SKILL						=> $index++  ,//恶魔果实ID 更改为技能书ID
        CreatureAttr::STAR_LEVEL 						=> $index++  ,//卡牌星级
        'bigHeadImg'									=> $index++  ,//卡牌大头像
        'bossHeadImg'									=> $index++  ,//BOSS头像ID
        CreatureAttr::QUALITY 							=> $index++  ,//卡牌品质
        'nameColor'										=> $index++  ,//名字颜色
        CreatureAttr::BASE_HTID 						=> $index++  ,//英雄原型ID
        CreatureAttr::COUNTRY							=> $index++  ,//所属国家
        CreatureAttr::FATAL_INIT						=> $index++  ,//卡牌暴击率基础值
        CreatureAttr::FATAL_RATIO					    => $index++  ,//卡牌暴击伤害倍数
        CreatureAttr::HIT_INIT							=> $index++  ,//武将基础命中
        CreatureAttr::DODGE_INIT						=> $index++  ,//武将基础闪避
        CreatureAttr::PARRY_INIT						=> $index++  ,//武将基础格挡率
        CreatureAttr::AWAKE_ABILITY_INIT				=> $index++  ,//武将初始觉醒能力ID组
        CreatureAttr::AWAKE_ABILITY_GROW				=> $index++  ,//******武将成长觉醒能力ID组
        CreatureAttr::UNION_PROFIT						=> $index++	 ,//连携附加属性
        'backgroundProfile'								=> $index++  ,//卡牌出处描述
        CreatureAttr::DROP_HERO_PRB                     => $index++  ,//掉落卡牌概率
		CreatureAttr::LAUGH_SKILL 						=> $index++  ,//嘲讽技能id
		CreatureAttr::BIG_ROUND_BEGIN_SKILL				=> $index++  ,//大回合前施放技能
);

$monsterTemplateList = getConflist($argv[1].'/'.$csvFile,$arrConfKey);
echo('read monster template end.');
/*************************************************************/
$file = fopen($argv[1].'/'.$csvFileMst, "r");

$data = fgetcsv($file);
$data = fgetcsv($file);
$index=0;
$allMstFields    =    array(
       'mstId'=>$index++,//怪物ID
       'mstTmplId'=>$index++,//怪物模板名称
       'mstOrgId'=>$index++,// 怪物原型ID
       CreatureAttr::LEVEL=>$index++,//怪物等级
       CreatureAttr::RAGE_SKILL=>$index++,//怪物怒气技能
       'bossId'=>$index++,// BossID
       CreatureAttr::PHYSIC_ATK_INIT=>$index++,// 怪物基础物理攻击       
       CreatureAttr::MAGIC_ATK_INIT=>$index++,//怪物基础法术攻击
		CreatureAttr::PHYSIC_DEF_INIT=>$index++,//怪物基础物理防御
       CreatureAttr::MAGIC_DEF_INIT=>$index++,//怪物基础法术防御		
       CreatureAttr::HP=>$index++,//怪物基础生命  
       'normal_physicalAttackBase'=>$index++,//普通难度怪物基础物理攻击
		'normal_magicAttackBase'=>$index++,////普通难度怪物基础法术攻击
       'normal_physicalDefendBase'=>$index++,//普通难度怪物基础物理防御       
       'normal_magicDefendBase'=>$index++,//普通难度怪物基础法术防御
       'normal_hpBase'=>$index++, //普通难度怪物基础生命  
        'hard_physicalAttackBase'=>$index++,//困难难度怪物基础物理攻击
		'hard_magicAttackBase'=>$index++,//困难难度怪物基础法术攻击
        'hard_physicalDefendBase'=>$index++,//困难难度怪物基础物理防御        
        'hard_magicDefendBase'=>$index++,//困难难度怪物基础法术防御
        'hard_hpBase'=>$index++, //困难难度怪物基础生命
        CreatureAttr::EVOLVE_LEVEL => $index++, //怪物进阶等级
        );
$confList = array();
while ( true )
{
	$data = fgetcsv($file);
	if ( empty($data) || empty($data[0] )  )
	{
		break;
	}
	if(count($data) < count($allMstFields))
	{
	    trigger_error("delete fields please inform programmer!!!!!");
	}
// 	if(count($data) > count($allMstFields))
// 	{
// 	    trigger_error("add new fields please inform programmer!!!!!");
// 	}
	$mstid = intval($data[0]);
	$protoHtid = intval($data[2]);
	$conf = $monsterTemplateList[ $protoHtid ];
	foreach($allMstFields as $key=>$index)
	{
	    switch($key)
	    {
	        case 'normal_physicalAttackBase':
            case 'normal_physicalDefendBase':
            case 'normal_magicAttackBase':
            case 'normal_magicDefendBase':
            case 'normal_hpBase':
            case 'hard_physicalAttackBase':
            case 'hard_physicalDefendBase':
            case 'hard_magicAttackBase':
            case 'hard_magicDefendBase':
            case 'hard_hpBase':
                $splits    =    str2Array($key, '_');
                $baseLv    =    $splits[0];
                $attr      =    $splits[1];
                $conf[$baseLv][$attr]    =    intval($data[$index]);    
                break;
            default:
                $val = intval($data[$index]);
                if( $val != 0 )
                {
                    $conf[$key] = $val;
                }
	    }
	    
	}
	
	$confList[$mstid] = $conf;
}
fclose($file);


//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);
echo("success\n");


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */