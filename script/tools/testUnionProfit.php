<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: testUnionProfit.php 197762 2015-09-10 05:23:06Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/testUnionProfit.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-10 05:23:06 +0000 (Thu, 10 Sep 2015) $
 * @version $Revision: 197762 $
 * @brief 
 *  
 **/
class testUnionProfit extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript ($arrOption)
	{
		$conf = btstore_get()->HEROES;
        foreach ($conf as $key => $value)
        {
            $unionProfit = Creature::getCreatureConf($key, CreatureAttr::UNION_PROFIT);
            foreach ($unionProfit as $unionId)
            {
                if (!isset(btstore_get()->UNION_PROFIT[$unionId]))
                {
                    echo "hero:".$key."\n";;
                }
            }
        }
        $conf = btstore_get()->MONSTERS;
        foreach ($conf as $key => $value)
        {
            $unionProfit = Creature::getCreatureConf($key, CreatureAttr::UNION_PROFIT);
            foreach ($unionProfit as $unionId)
            {
                if (!isset(btstore_get()->UNION_PROFIT[$unionId]))
                {
                    echo "monster:".$key."\n";;
                }
            }
        }
		
		echo "ok\n";
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */