<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RewardTest.php 84205 2014-01-01 12:00:12Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/RewardTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-01 12:00:12 +0000 (Wed, 01 Jan 2014) $
 * @version $Revision: 84205 $
 * @brief 
 *  
 **/
class RewardTest extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $heroShop = new HeroShop();
        $heroShop->rewardUser();
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */