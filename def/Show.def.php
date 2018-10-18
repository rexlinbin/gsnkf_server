<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Show.def.php 250248 2016-07-06 09:32:12Z QingYao $
 * 
 **************************************************************************/
 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Show.def.php $
 * @author $Author: QingYao $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-07-06 09:32:12 +0000 (Wed, 06 Jul 2016) $
 * @version $Revision: 250248 $
 * @brief 
 *  
 **/
class ShowDef
{
    const ARM_SHOW = 'arm_show';
    const TREASURE_SHOW = 'treasure_show';
    const HERO_SHOW = 'hero_show';
    const GODWEAPON_SHOW = 'godweapon_show';
    const TALLY_SHOW = 'tally_show';
    
    const ARM_SESSION = 'arm.book';
    const TREASURE_SESSION = 'treasure.book';
    const GODWEAPON_SESSION = 'godweapon.book';
    const TALLY_SESSION = 'tally.book';
    const CHARIOT_SESSION='chariot.book';
    
    static $ARR_ARM_SHOW_ID = array(
            101,102,103,104
            );
    
    static $ARR_TREASURE_SHOW_ID = array(
            201,202
            );
    
    static $ARR_HERO_SHOW_ID = array(
            1,2,3,4
            );
    
    static $ARR_GODWEAPON_SHOW_ID = array(
    		301,302,303,304,305
    );
    
    static $ARR_TALLY_SHOW_ID = array(
    		401,402,403
    );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */