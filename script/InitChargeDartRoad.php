<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: InitChargeDartRoad.php 239816 2016-04-22 12:31:47Z ShuoLiu $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/InitChargeDartRoad.php $
 * @author $Author: ShuoLiu $(hoping@babeltime.com)
 * @date $Date: 2016-04-22 12:31:47 +0000 (Fri, 22 Apr 2016) $
 * @version $Revision: 239816 $
 * @brief 
 *  
 **/

class InitChargeDartRoad extends BaseScript
{
    /* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        $allPageNum = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_ALL_PAGE_NUM]);
        $allRoadNum = intval(btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_ALL_ROAD_NUM]);
        
        for ($stageId = 1;$stageId <= ChargeDartDef::DEFAULT_MAX_STAGE;$stageId ++)
        {
            for ($pageId = 1;$pageId <= $allPageNum;$pageId ++)
            {
                for ($roadId = 1;$roadId <= $allRoadNum;$roadId ++)
                {
                    $arrFeild = array(
                        ChargeDartDef::SQL_STAGE_ID => $stageId,
                        ChargeDartDef::SQL_PAGE_ID => $pageId,
                        ChargeDartDef::SQL_ROAD_ID => $roadId,
                        ChargeDartDef::SQL_PREVIOUS_TIME => 0,
                    );
                    
                    $data	=	new CData();
                    $data->insertIgnore('t_charge_dart_road')
                    ->values($arrFeild)
                    ->query();
                }
            }
        }
        
        echo "ok\n";
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */