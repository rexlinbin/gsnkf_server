<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: BossInit.class.php 86081 2014-01-11 06:59:32Z ShiyuZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/BossInit.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-01-11 06:59:32 +0000 (Sat, 11 Jan 2014) $
 * @version $Revision: 86081 $
 * @brief
 *
 **/

/**
 *
 * 初始化boss
 *
 */
class BossInit extends BaseScript
{
    /* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        $bosses = btstore_get()->BOSS;
        foreach ( $bosses as $boss_id => $value )
        {
            $boss_info = BossDAO::getBoss($boss_id, TRUE);
            if ( empty($boss_info) )
            {
                BossLogic::initBoss($boss_id);
            }
            else
            {
                echo "failed\n";
                return;
            }
        }
        echo "done\n";
        return;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
                                                 