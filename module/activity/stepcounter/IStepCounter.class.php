<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: IStepCounter.class.php 136576 2014-10-17 06:09:20Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/stepcounter/IStepCounter.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-10-17 06:09:20 +0000 (Fri, 17 Oct 2014) $$
 * @version $$Revision: 136576 $$
 * @brief 
 *  
 **/

interface IStepCounter
{
    /**
     * 当天是否领奖
     * @return string 'no'没领 'yes'已领
     */
    function checkStatus();

    /**
     * 领奖
     * @return string 'ok'
     */
    function recReward();
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */