<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: AwakeAbility.script.php 259834 2016-09-01 02:37:07Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/script/AwakeAbility.script.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2016-09-01 02:37:07 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259834 $
 * @brief 
 *  
 **/



require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/ParserUtil.php";
require_once dirname ( dirname( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Property.def.php";

$csvFile = 'awake_ability.csv';
$outFileName = 'AWAKE_ABILITY';

if( isset($argv[1]) &&  $argv[1] == '-h' )
{
    exit("usage: $csvFile $outFileName\n");
}


if ( $argc < 3 )
{
	trigger_error( "Please input enough arguments:!{$csvFile}\n" );
}


$index = 1;
$arrConfKey = array(
        'ability_tmpl_name'                             =>          $index++,//觉醒能力模板名称
        'ability_name'                                  =>          $index++,//觉醒能力名称
        'ability_profile'                               =>          $index++,//觉醒能力描述
		'arrAttrId'					 					=>			$index++,//增加的属性ID组
		'arrAttrValue'				 					=>			$index++,//增加的属性的数值组
		PropertyKey::ARR_IMMUNED_BUFF					=>			$index++,//附加免疫状态ID组
		PropertyKey::DODGE_SKILL						=>			$index++,//替换闪避技能ID
		PropertyKey::PARRY_SKILL						=>			$index++,//替换反击技能ID
		PropertyKey::DEATH_SKILL						=>			$index++,//替换死亡施放技能ID
		PropertyKey::ROUND_BEGIN_SKILL					=>			$index++,//替换回合前技能ID
		PropertyKey::ROUND_END_SKILL					=>			$index++,//替换回合后技能ID
		PropertyKey::ARR_SKILL							=>			$index++,//增加被动技能ID
		PropertyKey::ARR_ATTACK_SKILL					=>			$index++,//普通技能附加子技能ID
		PropertyKey::ARR_RAGE_SKILL						=>			$index++,//怒气技能附加子技能ID
		PropertyKey::ARR_DODGE_SKILL					=>			$index++,//闪避技能附加子技能ID
		PropertyKey::ARR_PARRY_SKILL					=>			$index++,//格挡技能附加子技能ID
		PropertyKey::ARR_ROUND_BEGIN_SKILL				=>			$index++,//回合前技能附加子技能
		PropertyKey::ARR_ROUND_END_SKILL				=>			$index++,//回合后技能附加子技能
		PropertyKey::ARR_ATTACK_BUFF					=>			$index++,//普通技能附加buffID组
		PropertyKey::ARR_RAGE_BUFF						=>			$index++,//怒气技能附加buffID组
		PropertyKey::ARR_PARRY_BUFF						=>			$index++,//闪避技能附加buffID组
		PropertyKey::ARR_DODGE_BUFF						=>			$index++,//格挡技能附加buffID组
		PropertyKey::ARR_ROUND_BEGIN_BUFF				=>			$index++,//回合前技能附加buffID组
		PropertyKey::ARR_ROUND_END_BUFF					=>			$index++,//回合后技能附加buffID组
		PropertyKey::ARR_IMMUNED_TRIGGER_CONDITION      =>          $index++,//增加免疫联动技能时间点
		'arrAddAttrForFmt'								=>			$index++,//觉醒能力为阵上武将提供加成的配置
		PropertyKey::ATTACK_SKILL						=>			$index++,//替换普通技能ID
		PropertyKey::RAGE_SKILL							=>			$index++,//替换怒气技能ID
		'icon'											=>			$index++,//暂时不用
		PropertyKey::BIG_ROUND_BEGIN_SKILL				=>			$index++,//替换大回合前技能ID
			
);

//进行技能替换的字段
$replace	=	array(
		PropertyKey::PARRY_SKILL,//替换闪避技能ID
		PropertyKey::DODGE_SKILL,//替换反击技能ID
		PropertyKey::DEATH_SKILL,//替换死亡施放技能ID
		PropertyKey::ROUND_BEGIN_SKILL,//替换回合前技能ID
		PropertyKey::ROUND_END_SKILL,//替换回合后技能ID
		PropertyKey::ATTACK_SKILL,//替换普通技能ID
		PropertyKey::RAGE_SKILL,//替换怒气技能ID
		PropertyKey::BIG_ROUND_BEGIN_SKILL,//替换大回合前技能ID
);
//进行技能重复拼接（直接array_merge不管重复不重复）
$attach		=	array(
		PropertyKey::ARR_IMMUNED_BUFF,//附加免疫buffID组
		PropertyKey::ARR_SKILL,//增加被动技能ID
		PropertyKey::ARR_ATTACK_SKILL,//普通技能附加子技能ID
		PropertyKey::ARR_RAGE_SKILL	,//怒气技能附加子技能ID
		PropertyKey::ARR_PARRY_SKILL,//闪避技能附加子技能ID
		PropertyKey::ARR_DODGE_SKILL,//反击技能附加子技能ID
		PropertyKey::ARR_ROUND_BEGIN_SKILL,//回合前技能附加子技能
		PropertyKey::ARR_ROUND_END_SKILL,//回合后技能附加子技能
);
//进行buff非重复添加 （如果有就不加，即不重复添加）
$add	=	array(
		PropertyKey::ARR_ATTACK_BUFF,//普通技能附加buffID组
		PropertyKey::ARR_RAGE_BUFF,//怒气技能附加buffID组
		PropertyKey::ARR_PARRY_BUFF,//闪避技能附加buffID组
		PropertyKey::ARR_DODGE_BUFF,//格挡技能附加buffID组
		PropertyKey::ARR_ROUND_BEGIN_BUFF,//回合前技能附加buffID组
		PropertyKey::ARR_ROUND_END_BUFF,//回合后技能附加buffID组
		PropertyKey::ARR_IMMUNED_TRIGGER_CONDITION,//增加免疫联动技能时间点
);
//忽略的字段
$ignore =  array(
		'icon',
);

$delField    =    array(
        'ability_tmpl_name','ability_name','ability_profile'
        );

$file = fopen($argv[1]."/$csvFile", 'r');
if ( $file == FALSE )
{
	trigger_error( $argv[1]."/{$csvFile} open failed! exit!\n" );
}

$data = fgetcsv($file);
$data = fgetcsv($file);

$confList = array();
while ( true )
{
	$data = fgetcsv($file);
	if ( empty($data) || empty($data[0]) )
	{
		break;
	}
	$id = intval($data[0]);
	if(count($data) > (count($arrConfKey)+1))
	{
		trigger_error('add new field,please inform programmer.');
	}
	if(count($data) < (count($arrConfKey)+1))
	{
		trigger_error('delete field,please inform programmer.');
	}
	$conf = array();
	foreach ( $arrConfKey as $key => $index )
	{
	    if(in_array($key, $delField,true))
	    {
	        continue;
	    }
		else if(in_array($key, $replace,true))
		{
			$conf['replace'][$key]	=	intval($data[$index]);
		}
		else if(in_array($key, $attach,true))
		{
			$conf['attach'][$key]	=	array_map('intval',str2Array($data[$index], ','));
		}
		else if(in_array($key, $add,true))
		{
			$conf['add'][$key]		=	array_map('intval', str2Array($data[$index], ','));
		}
		else if ($key == 'arrAddAttrForFmt') 
		{
			$conf['arrAddAttrForFmt'] = array();
			$arrConf = str2Array($data[$index], ',');
			foreach ($arrConf as $i => $aConf)
			{
				$aConf = array_map('intval', str2Array($aConf, '|'));
				if (count($aConf) != 5)
				{
					trigger_error('invalid arrAddAttrForFmt conf, num less than 5');
				}
				$conf['arrAddAttrForFmt'][] = $aConf;
			}
		}
		else if (in_array($key, $ignore))
		{
			continue;
		}
		else
		{
			$conf[$key]	=	array_map('intval', str2Array($data[$index], ','));
		}
	}
	
	if( count($conf['arrAttrId']) != count($conf['arrAttrValue']))
	{
		echo "arrAttrId not match with arrAttrValue. id：%d";
	}	
	$confList[$id] = $conf;
}
fclose($file);

//输出文件
$file = fopen($argv[2].'/'.$outFileName, "w");
if ( $file == FALSE )
{
	trigger_error( $argv[2].'/'.$outFileName. " open failed! exit!\n");
}
fwrite($file, serialize($confList));
fclose($file);


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */