<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: InitMineralToDb.php 238387 2016-04-14 11:58:39Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/InitMineralToDb.php $
 * @author $Author: QingYao $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-04-14 11:58:39 +0000 (Thu, 14 Apr 2016) $
 * @version $Revision: 238387 $
 * @brief 
 *  
 **/
/**
 * 此脚本的作用：
 * 1.初始化资源矿数据
 * 2.增加资源矿时，更新到数据库
 * 如果需要删除已有资源矿，需要清空数据库
 * @author dell
 *
 */
class InitMineralToDb extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $conf    =    btstore_get()->MINERAL->toArray();
        foreach($conf as $domainId => $domainInfo)
        {
            $pits     =    $domainInfo['pits'];
            $types = $domainInfo['type'];
            foreach($pits as $pitId => $pitInfo)
            {
                $pitDbInfo    =    array(
                        TblMineralField::DOMAINID=>$domainId,
                        TblMineralField::PITID=>$pitId,
                        TblMineralField::DOMAINTYPE=>$domainInfo['domain_type'],
                		TblMineralField::PITTYPE=>$types["$pitId"],
                        TblMineralField::UID=>0,
                        TblMineralField::OCCUPYTIME=>0,
                        TblMineralField::DUETIMER=>0,
                        TblMineralField::DELAYTIMES=>0,
                        TblMineralField::TOTALGUARDSTIME=>0,
                		TblMineralField::GUILDID=>0,
                		TblMineralField::VA_INFO=>array(),
                        );
                $data	=	new CData();
                $data->insertIgnore('t_mineral')
                    ->values($pitDbInfo)
                    ->query();
            }
        }
        
        echo "ok\n";
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */