<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class WorldGrouponEntry extends BaseScript
{

    private function printUsage()
    {
        printf("Usage:\n");
        printf("btscript game001 WorldGrouponEntry team do|check 分组\n");
        printf("btscript game001 WorldGrouponEntry reward do|check 发奖\n");
    }

    protected function executeScript($arrOption)
    {
        if(count($arrOption) < 2)
        {
            $this->printUsage();
        }

        $validType = array("team", "reward");
        $type = strtolower($arrOption[0]);
        array_shift($arrOption);
        if(!in_array($type, $validType))
        {
            $this->printUsage();
            exit(0);
        }

        $validOp = array("do", "check");
        $op = strtolower($arrOption[0]);
        array_shift($arrOption);
        if(!in_array($op, $validOp))
        {
            $this->printUsage();
            exit(0);
        }
        $commit = ($op == "do" ? true: false);

        Logger::info('***************** WORLD_GROUPON_ENTRY : type[%s] op[%s] Begin!!!! ********************', $type, $op);

        if($type == "reward")   //补发奖励
        {
            WorldGrouponScriptLogic::reward($commit, $this->group);
        }
        else if($type == "team")    //分组
        {
        	RPCContext::getInstance()->getFramework()->setDb(WorldGrouponUtil::getCrossDbName());
        	$curVersion = ActivityConfLogic::getTrunkVersion();
        	ActivityConfLogic::doRefreshConf($curVersion, TRUE, FALSE);
        	
            WorldGrouponScriptLogic::syncAllTeamFromPlat2Cross($commit);
        }
        else
        {
            Logger::warning("error type:[%s]", $type);
        }

        Logger::info('***************** WORLD_GROUPON_ENTRY : type[%s] op[%s] End!!!!! ********************', $type, $op);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */