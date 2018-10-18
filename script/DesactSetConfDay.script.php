<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DesactSetConfDay.script.php 204111 2015-10-23 07:18:27Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/DesactSetConfDay.script.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-23 07:18:27 +0000 (Fri, 23 Oct 2015) $
 * @version $Revision: 204111 $
 * @brief 
 *  
 **/
class DesactSetConfDay extends BaseScript
{
    // 新类型福利活动（desact） 更改某个id任务天数的脚本
    protected function executeScript($arrOption)
    {
        if (count($arrOption) < 2)
        {
            echo "invalid param\n";
            echo "btscript gameXXX DesactSetConfDay.script.php id day \n";
            return;
        }

        $id = intval( $arrOption[0] );
        $days = intval( $arrOption[1] );

        $arrCrossConf = DesactDao::getLastCrossConfig(array('sess', 'update_time', 'version', 'va_config'));
        
        $conf = empty($arrCrossConf[0]) ? array() : $arrCrossConf[0];
        
        if ( empty($conf) || empty($conf['va_config']['config']))
        {
            printf('cross conf is empty or closed.');
            return ;
        }
        
        $sess = $conf['sess'];
        
        foreach ($conf['va_config']['config'] as $key => $value)
        {
            if ($id == $value[DesactDef::ID])
            {
                $conf['va_config']['config'][$key][DesactDef::LAST_DAY] = $days;
                break;
            }
        }
        
        DesactDao::updateCrossConfig($sess, $conf);
        
        printf("done!\n");
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */