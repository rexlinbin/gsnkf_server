<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GodWeaponFrag.php 148895 2014-12-24 12:56:29Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/scripts/GodWeaponFrag.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-12-24 12:56:29 +0000 (Wed, 24 Dec 2014) $$
 * @version $$Revision: 148895 $$
 * @brief 
 *  
 **/
function readGodWeaponFrag($inputDir)
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
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRAG_NEED_FRAG_NUM => ($index+=2)-1,
        GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRAG_AIM_ID => $index++,
    );

    $file = fopen("$inputDir/item_godarm_fragment.csv", 'r');
    echo "read $inputDir/item_godarm_fragment.csv\n";

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
        foreach($arrConfKey as $key => $index)
        {
            $conf[$key] = intval($data[$index]);
        }
        //check
        if(empty($conf[GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRAG_NEED_FRAG_NUM]))
        {
            trigger_error("godWeapon:need_part_num num is empty!\n");
        }

        //如果合成所需的神兵的碎片数量不等于碎片的可叠加数，则抛出错误
        if($conf[ItemDef::ITEM_ATTR_NAME_STACKABLE] != $conf[GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRAG_NEED_FRAG_NUM])
        {
            trigger_error('godWeaponFrag maxStacking is not equal need_part_num');
        }

        if(empty($conf[GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRAG_AIM_ID]))
        {
            trigger_error('godWeaponFrag aimGodarm is empty');
        }

        $conf[ItemDef::ITEM_ATTR_NAME_USE_REQ] = array();
        $conf[ItemDef::ITEM_ATTR_NAME_USE_ACQ] = array(
            ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS => array(
                intval($conf[GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRAG_AIM_ID]) => 1,
            ),
        );
        unset($conf[GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRAG_AIM_ID]);
        $confList[$conf[ItemDef::ITEM_ATTR_NAME_TEMPLATE]] = $conf;
    }
    fclose($file);
    return $confList;
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */