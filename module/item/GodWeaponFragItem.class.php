<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GodWeaponFragItem.class.php 146962 2014-12-18 06:15:44Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/GodWeaponFragItem.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-12-18 06:15:44 +0000 (Thu, 18 Dec 2014) $$
 * @version $$Revision: 146962 $$
 * @brief 
 *  
 **/
class GodWeaponFragItem extends DirectItem
{
    /**
     * 得到合成所需要的碎片数量
     */
    public function getFragNum()
    {
        return ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRAG_NEED_FRAG_NUM);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */