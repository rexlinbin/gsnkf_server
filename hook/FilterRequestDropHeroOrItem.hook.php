<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FilterRequestDropHeroOrItem.hook.php 218745 2015-12-30 09:57:41Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/hook/FilterRequestDropHeroOrItem.hook.php $
 * @author $Author: ShiyuZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-12-30 09:57:41 +0000 (Wed, 30 Dec 2015) $
 * @version $Revision: 218745 $
 * @brief 
 *  
 **/
class FilterRequestDropHeroOrItem
{
    function execute($arrRequest)
    {
    	if( WorldUtil::isCrossGroup() )
    	{
    		Logger::debug('is cross group');
    		return $arrRequest;
    	}
    	
        $uid    =    RPCContext::getInstance()->getUid();
        $method =    $arrRequest ['method'];
        $arrMethod = explode ( '.', $method );
        if (count ( $arrMethod ) != 2)
        {
            Logger::fatal ( "invalid request:%s, invalid method", $arrRequest );
            throw new Exception ( 'close' );
        }
        
        $clazz = $arrMethod [0];
        $method = $arrMethod [1];
        if( isset(HookConfig::$DROP_ITEM_TO_BAG_REQUEST[$clazz][$method]))
        {
            $bag    =    BagManager::getInstance()->getBag($uid);
            if($bag->isFull())
            {
                throw new FakeException('bag is full;can not request clazz %s,method %s.',$clazz,$method);
            }
        }
        if( isset(HookConfig::$DROP_HERO_REQUEST[$clazz][$method]) )
        {
        	$userObj = EnUser::getUserObj($uid);
            if($userObj->getHeroManager()->hasTooManyHeroes())
            {
                throw new FakeException('hero num to the limit;can not request clazz %s,method %s.',$clazz,$method);
            }
        }
        return $arrRequest;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */