<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OlympicScript.php 122243 2014-07-22 13:27:30Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/OlympicScript.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-22 13:27:30 +0000 (Tue, 22 Jul 2014) $
 * @version $Revision: 122243 $
 * @brief 
 *  
 **/
class OlympicScript extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        $stage = intval($arrOption[0]);
        Logger::info('executeScript start. stage:%d', $stage);
        
        if(in_array($stage, OlympicStage::$ALL_STAGE) == FALSE)
        {
            throw new FakeException('unvalid stage %d allstage is %s',$stage,OlympicStage::$ALL_STAGE);
        }
        if($stage == OlympicStage::PRE_OLYMPIC)
        {
            OlympicLogic::startPreOlympicStage();
        }
        else if($stage == OlympicStage::PRELIMINARY_MATCH)
        {
            OlympicLogic::startPreliminary();
        }
        else if($stage == OlympicStage::OLYMPIC_GROUP)
        {
            OlympicLogic::startGroup();
        }
        else if($stage == OlympicStage::AFTER_OLYMPIC)
        {
            OlympicLogic::startAfterOlympic();
        }
        else
        {
            OlympicLogic::startFinal($stage);
        }
        
        Logger::info('executeScript end. stage:%d', $stage);
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */