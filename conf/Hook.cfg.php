<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Hook.cfg.php 94911 2014-03-22 08:43:27Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Hook.cfg.php $
 * @author $Author: ShiyuZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-03-22 08:43:27 +0000 (Sat, 22 Mar 2014) $
 * @version $Revision: 94911 $
 * @brief 
 *  
 **/
class HookConfig
{
    
    //加物品到背包中的请求
    static $DROP_ITEM_TO_BAG_REQUEST = array(
            'ncopy'=>array('enterBaseLevel'=>true,
                    'getPrize'=>true,'doBattle'=>true,
                    'sweep'=>true),
            'ecopy'=>array('enterCopy'=>true,'doBattle'=>true),
             'acopy'=>array('enterBaseLevel'=>true,'doBattle'=>true),
    		
    		'arena' 		=> array( 'challenge' => true ),
    		'compete' 		=> array( 'contest' => true ),
    		'fragseize' 	=> array( 'seizeRicher' => true ),
            'mysteryshop'   => array(  'rebornHero' => true),
    		
    		'weal'			=> array( 'kaOnce' => true),
    );
    
    //会掉卡牌的请求 商店招将
    static $DROP_HERO_REQUEST    =    array(
            'ncopy'=>array('enterBaseLevel'=>true,
                    'sweep'=>true,'doBattle'=>true),
            'ecopy'=>array('enterCopy'=>true,'doBattle'=>true),
            'acopy'=>array('enterBaseLevel'=>true,'doBattle'=>true),
    		
    		'arena' 		=> array( 'challenge' => true ),
    		'compete' 		=> array( 'contest' => true ),
    		'fragseize' 	=> array( 'seizeRicher' => true ),
            'mysteryshop'   => array(  'rebornHero' => true),
    		
    		'weal'			=> array( 'kaOnce' => true),
    );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */