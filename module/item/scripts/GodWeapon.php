<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GodWeapon.php 181662 2015-06-30 09:35:24Z MingTian $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/GodWeapon.php $$
 * @author $$Author: MingTian $$(ShijieHan@babeltime.com)
 * @date $$Date: 2015-06-30 09:35:24 +0000 (Tue, 30 Jun 2015) $$
 * @version $$Revision: 181662 $$
 * @brief 
 *  
 **/
function readGodWeapon($inputDir)
{
    //数据对应表
    $index = 0;
    $arrConfKey = array(
        ItemDef::ITEM_ATTR_NAME_TEMPLATE                => $index++,
        ItemDef::ITEM_ATTR_NAME_QUALITY                 => ($index+=7)-1,
        ItemDef::ITEM_ATTR_NAME_SELL                    => $index++,
        ItemDef::ITEM_ATTR_NAME_SELL_TYPE               => $index++,
        ItemDef::ITEM_ATTR_NAME_SELL_PRICE              => $index++,
        ItemDef::ITEM_ATTR_NAME_STACKABLE               => $index++,
        ItemDef::ITEM_ATTR_NAME_BIND                    => $index++,
        ItemDef::ITEM_ATTR_NAME_DESTORY                 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_TYPE    => ($index+=2)-1,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_QUALITY  => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_REINFORCE_LEVEL_LIMIT => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_ID => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_REINFORCE_EXP_ID => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GIVE_EXP => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID1 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID2 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID3 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID4 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID1 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID2 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID3 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID4 => $index++,
        //神兵洗练
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_AWAKE_OPEN_QUALITY => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_NORMAL_WASH_COST => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY1 => ($index+=2)-1,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT1 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY2 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT2 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY3 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT3 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY4 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT4 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY5 => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT5 => $index++,

        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRIEND_ID => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_RESOLVE_ID => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_OPEN_EFFECT_EVOLVE_LEVEL => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_CONSUME_RATIO => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_INIT_REINFORCE_LEVEL => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_IS_GOD_EXP => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_REBORN_COST => ($index+=2)-1,
        GodWeaponDef::ITEM_ATTR_NAME_FRIEND_OPEN => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_GOLD_WASH_COST => $index++,
        //神兵录
        GodWeaponDef::ITEM_ATTR_NAME_DICT_EXTRA_ABILITY => ($index+=3)-1,
    	GodWeaponDef::ITEM_ATTR_NAME_SCORE => $index++,
        GodWeaponDef::ITEM_ATTR_NAME_IS_STRENGTHEN => $index++,
    	UnionDef::FATE_ATTR => ($index+=2)-1,
    );

    //1|0|10000,7|60022|10 类似这种格式
    $arrKeyV2 = array(
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_QUALITY,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_REINFORCE_LEVEL_LIMIT,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_ID,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID1,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID2,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID3,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID4,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID1,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID2,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID3,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID4,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_REBORN_COST,
        GodWeaponDef::ITEM_ATTR_NAME_DICT_EXTRA_ABILITY,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_NORMAL_WASH_COST,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY1,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY2,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY3,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY4,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY5,
        GodWeaponDef::ITEM_ATTR_NAME_GOLD_WASH_COST,
        UnionDef::FATE_ATTR,
    );

    //10,000,022,000,002 类似这种格式
    $arrKeyV1 = array(
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRIEND_ID,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_AWAKE_OPEN_QUALITY,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT1,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT2,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT3,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT4,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT5,
    );

    $file = fopen("$inputDir/item_godarm.csv", 'r');
    echo "read $inputDir/item_godarm.csv\n";

    //略过前两行
    $data = fgetcsv($file);
    $data = fgetcsv($file);

    $confList = array();
    while(true)
    {
        $data = fgetcsv($file);
        if ( empty($data) || empty($data[0]) )
        {
            break;
        }
        $conf = array();
        $evolveLimit = 0;//进化次数限制
        foreach($arrConfKey as $key => $index)
        {
            if(in_array($key, $arrKeyV2, true))
            {
                if(empty($data[$index]))
                {
                    $conf[$key] = array();
                }
                else
                {
                    $arr = str2Array($data[$index]);
                    $conf[$key] = array();
                    foreach($arr as $k => $v)
                    {
                        if(!strpos($v, '|'))
                        {
                            trigger_error("invalid $key, need v2\n");
                        }
                        $ary = array2Int(str2Array($v, '|'));
                        //分情况，为了解析方便
                        switch($key)
                        {
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_QUALITY:
                                $conf[$key][$ary[0]] = array($ary[1], $ary[2]);
                                if($ary[0] > $evolveLimit)
                                {
                                    $evolveLimit = $ary[0];
                                }
                                break;
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID1:
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID2:
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID3:
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_BASE_ABILITY_ID4:
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID1:
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID2:
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID3:
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GROW_ABILITY_ID4:
                                $conf[$key][$ary[0]] = array($ary[1], $ary[2]);
                                break;
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_REINFORCE_LEVEL_LIMIT:
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_ID:
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_REBORN_COST:
                            case GodWeaponDef::ITEM_ATTR_NAME_DICT_EXTRA_ABILITY:
                            case UnionDef::FATE_ATTR:      	
                                $conf[$key][$ary[0]] = $ary[1];
                                break;
                            case GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_NORMAL_WASH_COST:
                                $conf[$key][GodWeaponDef::NEED_ITEM][$k + 1][$ary[0]] = $ary[1];
                                $conf[$key][GodWeaponDef::NEED_SILVER][$k + 1] = $ary[2];
                                break;
                            case GodWeaponDef::ITEM_ATTR_NAME_GOLD_WASH_COST:
                                if($ary[1] > 0)
                                {
                                    $conf[$key][GodWeaponDef::NEED_ITEM][$k + 1][$ary[0]] = $ary[1];
                                }
                                $conf[$key][GodWeaponDef::NEED_GOLD][$k + 1] = $ary[2];
                                break;
                            default:
                                $conf[$key][$k] = $ary;
                                break;
                        }
                    }
                }
            }
            else if(in_array($key, $arrKeyV1, true))
            {
                if(empty($data[$index]))
                {
                    $conf[$key] = array();
                }
                else
                {
                    $conf[$key] = array2Int(str2Array($data[$index]));
                }
            }
            else
            {
                $conf[$key] = intval($data[$index]);
            }

            $conf[GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_LIMIT] = $evolveLimit;
            $confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
        }

        //检测策划的配置--防止配错
        if(count(GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY1) !=
            count(GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT1)
            || count(GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY2) !=
            count(GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT2)
            || count(GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY3) !=
            count(GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT3)
            || count(GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_ABILITY4) !=
            count(GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_WASH_WEIGHT4)
        )
        {
            throw new ConfigException("error wash config count wrong");
        }
    }
    fclose($file);
    return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */