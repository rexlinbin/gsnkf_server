<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: EnMysMerchant.class.php 147016 2014-12-18 07:48:56Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysmerchant/EnMysMerchant.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-12-18 07:48:56 +0000 (Thu, 18 Dec 2014) $$
 * @version $$Revision: 147016 $$
 * @brief 
 *  
 **/
class EnMysMerchant{
    /**
     * 触发神秘商人
     * @return mixed
     */
    public static function trigMysMerchant($uid)
    {
        if(!MyMysMerchant::checkUserLevelLimit())
        {
            return array();
        }
        $mysMerchant = new MyMysMerchant($uid);
        if(!$mysMerchant->checkIfForever())
        {
            $mysMerchant->trigMysMerchant();
        }
        else
        {
            return array();
        }

        $mysMerchant->update();
        $ret = $mysMerchant->getGoodsList();  //当前商品列表
        return $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */