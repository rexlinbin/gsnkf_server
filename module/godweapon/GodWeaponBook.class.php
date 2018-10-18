<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class GodWeaponBook
{
    /**
     * 神兵录属性加成
     */
    public static function getAddAttrByGodWeaponBook($uid)
    {
        $arrGodWeaponBook = ItemInfoLogic::getGodWeaponBook($uid);
        $arrAddAttr = array();

        if(empty($arrGodWeaponBook)) {
            return array();
        }
        foreach($arrGodWeaponBook as $godWeaponBook)
        {
            $extraAbility = ItemAttr::getItemAttr($godWeaponBook, GodWeaponDef::ITEM_ATTR_NAME_DICT_EXTRA_ABILITY);
            foreach($extraAbility as $abilityId => $abilityValue)
            {
                if(isset($arrAddAttr[$abilityId]))
                {
                    $arrAddAttr[$abilityId] += $abilityValue;
                }
                else
                {
                    $arrAddAttr[$abilityId] = $abilityValue;
                }
            }
        }

        $arrRet = HeroUtil::adaptAttr($arrAddAttr);
        Logger::trace('getAddAttrByGodWeaponBook. uid:%d, arr:%s', $uid, $arrRet);
        return $arrRet;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */