<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DesactChangeConf.script.php 202950 2015-10-17 11:18:07Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/DesactChangeConf.script.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-17 11:18:07 +0000 (Sat, 17 Oct 2015) $
 * @version $Revision: 202950 $
 * @brief 
 *  
 **/
class DesactChangeConf extends BaseScript
{
    // 新类型福利活动（desact） 脚本
    protected function executeScript($arrOption)
    {
        /**
         * 关活动的话，先让策划把活动配成结束时间小于当前时间的
         * 改活动的话，就让策划把改完的配置上传一下
         */
        if (count($arrOption) < 2)
        {
            echo "invalid param\n";
            echo "关活动：btscript gameXXX DesactChangeConf.script.php close desact \n";
            echo "重开活动：btscript gameXXX DesactChangeConf.script.php reopen desact \n";
            echo "改配置：btscript gameXXX DesactChangeConf.script.php change conf \n";
            echo "改奖励：btscript gameXXX DesactChangeConf.script.php change reward \n";
            return;
        }
        
        $op = $arrOption[0];
        $args = $arrOption[1];
        
        switch ($op)
        {
            case 'close':
                
                $arrCrossConf = DesactDao::getLastCrossConfig(array('sess', 'update_time', 'version', 'va_config'));
                
                $conf = empty($arrCrossConf[0]) ? array() : $arrCrossConf[0];
                
                if ( empty($conf) )
                {
                    printf('cross conf is empty. why are you so renxing.');
                    return ;
                }
                
                if (!empty($conf['va_config']['config']))
                {
                    $conf['va_config']['config'] = array();
                    DesactDao::updateCrossConfig($conf[DesactCrossDef::SQL_SESS], $conf);
                }
                
                break;
                
            case 'reopen':
                
                $arrCrossConf = DesactDao::getLastCrossConfig(array('sess', 'update_time', 'version', 'va_config'));
                
                $conf = empty($arrCrossConf[0]) ? array() : $arrCrossConf[0];
                
                if ( empty($conf) )
                {
                    printf('cross conf is empty. why are you so renxing.');
                    return ;
                }
                
                if (!empty($conf['va_config']['config']))
                {
                    printf('desact is not closed!');
                    return ;
                }
                
                $conf['update_time'] = 0;
                $conf['va_config']['config'] = array(
                    array(
                        DesactDef::ID => 0,
                        DesactDef::LAST_DAY => 0,
                    )
                );
                
                DesactDao::updateCrossConfig($conf[DesactCrossDef::SQL_SESS], $conf);
                
                break;
            
            case 'change':
                
                $arrCrossConf = DesactDao::getLastCrossConfig(array('sess', 'update_time', 'version', 'va_config'));
                
                $conf = empty($arrCrossConf[0]) ? array() : $arrCrossConf[0];
                
                if ( empty($conf) )
                {
                    printf('cross conf is empty. why are you so renxing.');
                    return ;
                }
                
                $newConf = EnActivity::getConfByName(ActivityName::DESACT);
                
                if ( empty($newConf['data']) || $newConf['start_time'] > Util::getTime() || $newConf['end_time'] < Util::getTime())
                {
                    printf('invalid inner conf. conf:%s.',$newConf);
                    return ;
                }
                
                $newConfData = $newConf['data'];
                
                switch ($args)
                {   
                    case 'reward':
                        
                        foreach ($conf['va_config']['config'] as $key => $value)
                        {
                            if ( !isset( $newConfData[$value[DesactDef::ID]] ) )
                            {
                                printf('lack id in new conf. %d.',$value[DesactDef::ID]);
                                return ;
                            }
                            
                            $conf['va_config']['config'][$key] = $newConfData[$value[DesactDef::ID]];
                        }
                        
                        DesactDao::updateCrossConfig($conf[DesactCrossDef::SQL_SESS], $conf);
                        
                        break;
                        
                    case 'conf':
                        
                        $conf['update_time'] = 0;
                        
                        DesactDao::updateCrossConfig($conf[DesactCrossDef::SQL_SESS], $conf);
                        
                        break;
                        
                    default:
                        printf("unknown change args:%d. \n",$args);
                        return ;
                }
                
                break;
                
            default:
                printf("unknown op:%d. \n",$op);
        }
        
        printf('done!');
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */