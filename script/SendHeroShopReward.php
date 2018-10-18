<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendHeroShopReward.php 84556 2014-01-02 13:39:31Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/SendHeroShopReward.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-02 13:39:31 +0000 (Thu, 02 Jan 2014) $
 * @version $Revision: 84556 $
 * @brief 
 *  
 **/
class SendHeroShopReward extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        HeroShopLogic::rewardUser();
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */