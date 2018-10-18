<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GenerateMineralInitSql.php 117291 2014-06-26 02:44:16Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/GenerateMineralInitSql.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-26 02:44:16 +0000 (Thu, 26 Jun 2014) $
 * @version $Revision: 117291 $
 * @brief 
 *  
 **/
class GenerateMineralInitSql extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $arrConfPit = btstore_get()->MINERAL;
        foreach($arrConfPit as $domainId => $domainInfo)
        {
            $domainType = $domainInfo['domain_type'];
            foreach($domainInfo['pits'] as $pitId => $pitInfo)
            {
                $pitType = $domainInfo['type'][$pitId];
                $str = "INSERT IGNORE INTO t_mineral values($domainId, $pitId, $domainType, $pitType, 0, 0, 0, 0, 0);";
                echo $str."\n";
            }
        }
        
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */