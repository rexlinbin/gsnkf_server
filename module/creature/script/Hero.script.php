<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Hero.script.php 259834 2016-09-01 02:37:07Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/creature/script/Hero.script.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2016-09-01 02:37:07 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259834 $
 * @brief 
 *  
 **/

require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Creature.def.php";

$csvFile = 'heroes.csv';
$outFileName = 'HEROES';


if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}


if ( $argc < 3 )
{
    trigger_error("Please input enough arguments:!{$csvFile}\n");
}

function getConflist($csvFile,$arrConfKey)
{
    $ref = new ReflectionClass('CreatureAttr');
    $usefulKey	=	$ref->getConstants();
    $fiveStarHero    =    array();
    //格式是3,4
    $arrKeyV1 = array(
            //输出array(0=>3,1=>4.....)
            CreatureAttr::ARR_IMMUNED_BUFF=>1,
            CreatureAttr::ARR_IMMUNED_SKILL_TYPE=>1,
            CreatureAttr::ARR_IMMUNED_TARGET_TYPE=>1,
            CreatureAttr::RANDOM_ATTACK_SKILL=>1,
            CreatureAttr::AWAKE_ABILITY_INIT=>1,
            CreatureAttr::UNION_PROFIT=>1,
            CreatureAttr::GODWEAPON_UNITPROFIG => 1,
            //输出array(1=>array('weight'=>3),2=>array('weight'=>4).....)
            CreatureAttr::TALENT_GROUP_WEIGHT_1=>2,
            CreatureAttr::TALENT_GROUP_WEIGHT_2=>2,
            CreatureAttr::TALENT_GROUP_WEIGHT_3=>2,
            CreatureAttr::TALENT_GROUP_WEIGHT_4=>2,
            //格式是3|4
            CreatureAttr::DROP_HERO_PRB=>3,
    );

    //格式是1|1,2|2,3|3,4|4,5|5
    $arrKeyV2 = array(
            //输出是array(0=>array(1,1),1=>array(2,2),2=>array(3,3)....)
            CreatureAttr::AWAKE_ABILITY_GROW=>1,
            //输出是array(1=>array(1,1),2=>array(2,2),3=>array(3,3)....)
            CreatureAttr::SKILLBOOK_POS_OPEN_NEED=>2,
            CreatureAttr::TALENT_GROUP_LIST_1=>2,
            CreatureAttr::TALENT_GROUP_LIST_2=>2,
            CreatureAttr::TALENT_GROUP_LIST_3=>2,
            CreatureAttr::TALENT_GROUP_LIST_4=>2,
            CreatureAttr::TALENT_ACTIVATE_NEED=>2,
            CreatureAttr::TALENT_ARR_COPY=>2,
            //输出是array(1=>1,2=>2,3=>3....)
            CreatureAttr::EVOLVE_TBLID=>3,
    		CreatureAttr::FATE_ATTR=>3,
    		CreatureAttr::LOYAL_ATTR=>3,
    		CreatureAttr::RESOLVE_2_SOUL_FRAG_INFO=>3,
    		//输出是array(1=>array(1,,,N), 2=>array(2,,,N)...)
    		CreatureAttr::DESTINY_AWAKE=>4,
    		//输出是array(1=>array(array(1,,,N), array(2,,,N)))
    		CreatureAttr::DESTINY_COST=>5,
            );
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
        $conf = array();
        foreach ( $arrConfKey as $key => $index )
        {
            if(in_array($key, $usefulKey , TRUE) == FALSE)
            {
                continue;
            }
            if( isset($arrKeyV1[$key]))
            {
                if($arrKeyV1[$key] == 3)
                {
                    $conf[$key] = array2Int( str2array($data[$index],'|') );
                }
                else if($arrKeyV1[$key] == 2)
                {
                    $tmp = array2Int( str2array($data[$index]) );
                    foreach($tmp as $index => $tmpInfo)
                    {
                        $conf[$key][$index+1]['weight'] = $tmpInfo;
                    }
                }
                else if($arrKeyV1[$key] == 1)
                {
                    $conf[$key] = array2Int( str2array($data[$index]) );
                }
            }
            else if( isset($arrKeyV2[$key]))
            {
                $arr = str2array($data[$index]);
                $conf[$key] = array();
                foreach( $arr as $indexTmp=>$value )
                {
                    if(!strpos($value, '|'))
                    {
                        trigger_error( "invalid $key,value $value need v2\n" );
                    }
                    //字段格式：99|0,99|1,99|2|60002（等级|进阶等级|物品ID（可选））
                    //解析格式：array(1（技能书栏位从1开始）=>array(99,0),2=>array(99,1),3=>array(99,2,60002));表示开启3个技能书栏位的条件
                    if($arrKeyV2[$key] == 2)
                    {
                        $conf[$key][$indexTmp+1] = array2Int( str2Array($value, '|'));
                    }
                    //字段格式：0|20001,1|20101,2|20102,3|20103,4|20104,5|20105
                    //解析格式：array(0=>20001,1=>20101......);
                    else if($arrKeyV2[$key] == 3)
                    {
                        $temp    =    array2Int( str2Array($value, '|') );
                        $conf[$key][$temp[0]]    =    $temp[1];
                    }
                    //字段格式：1|1,1|2,1|3,2|1,2|2,2|3
                    //解析格式：array(1=>array(1,2,3),2=>array(1,2,3).....);
                    else if ($arrKeyV2[$key] == 4) 
                    {
                    	$temp = array2Int( str2Array($value, '|') );
                    	$conf[$key][$temp[0]][] = $temp[1]; 
                    }
                    //字段格式：1|7|410009|20,1|7|410008|20
                    //解析格式：array(1=>array(array(7,410009,20),array(7,410008,20)));
                    else if ($arrKeyV2[$key] == 5)
                    {
                    	$temp = array2Int( str2Array($value, '|') );
                    	$conf[$key][$temp[0]][] = array_slice($temp, 1);
                    }
                    //字段格式：2|1|100,2|2|401,2|3|9302,2|4|1103,2|5|1204,2|6|3605
                    //解析格式：array(0=>array(2,1,100),1=>array(2,2,401).....);
                    else
                    {
                        $conf[$key][] = array2Int( str2Array($value, '|') );
                    }
                }
            }
            else
            {
                $val = intval($data[$index]);
                if( $val != 0 || ($key == CreatureAttr::STAR_LEVEL))
                {
                    $conf[$key] = $val;
                }
            }
        }
        if(!isset($conf[CreatureAttr::BASE_HTID]) 
                || empty($conf[CreatureAttr::BASE_HTID]))
        {
            $htid = $conf[CreatureAttr::HTID];
            trigger_error("@cehua:Please confirm baseHtid of hero $htid is not empty.");
        }
        if(!empty($conf[CreatureAttr::GODWEAPON_UNITPROFIG]))
        {
            $conf[CreatureAttr::UNION_PROFIT] = array_merge($conf[CreatureAttr::UNION_PROFIT],$conf[CreatureAttr::GODWEAPON_UNITPROFIG]);
        }
        $confList[$conf[CreatureAttr::HTID]] = $conf;
        if($conf[CreatureAttr::STAR_LEVEL] == 5)
        {
            $fiveStarHero[]    =    $conf[CreatureAttr::HTID];
        }
    }
    fclose($file);
    return array('conf'=>$confList,'fiveStar'=>$fiveStarHero);
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
        CreatureAttr::HP							=> $index++  ,//英雄基础生命
        CreatureAttr::RAGE							=> $index++  ,//英雄基础怒气
        CreatureAttr::REIGN_INIT 					=> $index++  ,//英雄基础统帅
        CreatureAttr::STRENGTH_INIT						=> $index++  ,//卡牌基础力量
        CreatureAttr::INTELLIGENCE_INIT 				=> $index++  ,//英雄基础智慧
        CreatureAttr::GENERAL_ATTACK_INIT               => $index++  ,// 基础通用攻击
        CreatureAttr::PHYSIC_ATK_INIT					=> $index++  ,//英雄基础物理攻击
        CreatureAttr::MAGIC_ATK_INIT 					=> $index++  ,//英雄基础魔法攻击
        CreatureAttr::PHYSIC_DEF_INIT					=> $index++  ,//英雄基础物理防御
        CreatureAttr::MAGIC_DEF_INIT 					=> $index++  ,//英雄基础魔法防御
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
        CreatureAttr::CAN_BE_RESOLVED					=> $index++  ,//能否被分解
        CreatureAttr::SOUL								=> $index++  ,//分解获得的初始将魂
        CreatureAttr::UNION_PROFIT						=> $index++	 ,//连携附加属性
        CreatureAttr::CANBE_STAR						=> $index++  ,//可否为红颜
        CreatureAttr::STAR_ID							=> $index++  ,//红颜id
        CreatureAttr::STAR_EXPID						=> $index++  ,//红颜经验表id
        CreatureAttr::MAX_ENFORCE_LV					=> $index++  ,//英雄强化等级上限
        'backgroundProfile'								=> $index++  ,//卡牌出处描述
        CreatureAttr::EVOLVE_TBLID						=> $index++  ,//进化经验表id
        CreatureAttr::LVLUP_RATIIO						=> $index++  ,//强化所需金币系数
        'isMonster'                                     => $index++  ,//是否是小怪
        CreatureAttr::EVOLVE_BASE_RATIO                 => $index++  ,//进阶基础值系数
        CreatureAttr::EVOLVE_INIT_LEVEL                 => $index++  ,//进阶初始等级
        CreatureAttr::EVOLVE_GAP_LEVEL                  => $index++  ,//进阶间隔等级
        'roleTransfer'                                  => $index++  ,//主角晋阶表ID
        'transform'                                     => $index++  ,//进化表ID
        CreatureAttr::ENFORCE_GAP_LEVEL                 => $index++  ,//强化间隔等级
        CreatureAttr::REBORN_GOLD_BASE                  => $index++  ,//武将重生花费金币基础值
        CreatureAttr::JEWEL_NUM                         => $index++  ,//武将分解获得魂玉数目
        CreatureAttr::QUALIFICATION                     => $index++  ,//武将资质 
        CreatureAttr::TALENT_ACTIVATE_NEED              => $index++  ,//武将天赋消耗物品
        CreatureAttr::TALENT_ARR_COPY                   => $index++  ,//武将列传副本
        'talent_poetry'                                 => $index++  ,//武将列传诗句  
//         CreatureAttr::TALENT_GROUP_LIST_1                 => $index++  ,//武将可洗练天赋ID组1
//         CreatureAttr::TALENT_GROUP_WEIGHT_1               => $index++  ,//洗练天赋权重组1
//         CreatureAttr::TALENT_GROUP_LIST_2                 => $index++  ,//武将可洗练天赋ID组2
//         CreatureAttr::TALENT_GROUP_WEIGHT_2               => $index++  ,//洗练天赋权重组2
//         CreatureAttr::TALENT_GROUP_LIST_3                 => $index++  ,//武将可洗练天赋ID组3
//         CreatureAttr::TALENT_GROUP_WEIGHT_3               => $index++  ,//洗练天赋权重组3
//         CreatureAttr::TALENT_GROUP_LIST_4                 => $index++  ,//武将可洗练天赋ID组4
//         CreatureAttr::TALENT_GROUP_WEIGHT_4               => $index++  ,//洗练天赋权重组4
        CreatureAttr::TALENT_MAX_STAR                     => $index++  ,//天赋洗练最大星级
        CreatureAttr::DEVELOP_TBL_ID                      => $index++  ,//武将的进化表id
        CreatureAttr::UNDEVELOP_TBL_ID                    => $index++  ,//橙卡重生需要的进化版id
        CreatureAttr::UNDEVELOP_NEED_EXTRA_GOLD           => $index++  ,//武将重生消耗金币附加值
        'unused'                                          => $index++  ,//武将全身像偏移量
        'unused'                                          => $index++  ,//武将攻击类型
        CreatureAttr::GODWEAPON_UNITPROFIG                => $index++  ,//武将神兵羁绊
        'unused'										  => $index++  ,//武将小卡牌偏移量
		'unused'										  => $index++  ,//武将大卡牌偏移量
        CreatureAttr::FATE_ATTR							  => $index++  ,//武将缘分堂属性
		CreatureAttr::LOYAL_ATTR						  => $index++  ,//武将忠义堂属性
		CreatureAttr::RESOLVE_2_SOUL_FRAG_INFO			  => $index++  ,//武将化魂后的碎片模板id
		'unused'										  => $index++  ,//红卡原型htid
		'unused'										  => $index++  ,//是否有天命
		CreatureAttr::DESTINY_SUM						  => $index++  ,//总天命数
		CreatureAttr::DESTINY_AWAKE						  => $index++  ,//天命觉醒能力
		'unused'										  => $index++  ,//天命改名字
		CreatureAttr::DESTINY_COST						  => $index++  ,//特定ID额外消耗
		CreatureAttr::LAUGH_SKILL 						  => $index++  ,//嘲讽技能id
		CreatureAttr::BIG_ROUND_BEGIN_SKILL				  => $index++  ,//大回合前施放技能
		
		
);
$ret    =    getConflist($argv[1]."/$csvFile",$arrConfKey);;
$confList	=	$ret['conf'];
$fiveStars    =    $ret['fiveStar'];
//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
    trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($confList));
fclose($file);

$file = fopen($argv[2].'/FIVESTARHERO', "w");
if ( $file == FALSE )
{
    trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n" );
}
fwrite($file, serialize($fiveStars));
fclose($file);
echo("success\n");

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
