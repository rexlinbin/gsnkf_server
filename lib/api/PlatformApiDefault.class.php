<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: PlatformApiDefault.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/api/PlatformApiDefault.class.php $
 * @author $Author: wuqilin $(lanhongyu@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/

class PlatformApiDefault extends PlatformApi
{
	public function users($method,$array=array())
    {
        $params=$array;
        $params['action'] = $method;
        $params['ts'] = time();
        //礼品卡往平台发请求, 其他直接返回空
        switch($method){
            case 'getGiftByCard':
            case 'getServerGroup':
            case 'getServerGroupAll':
            case 'getNameAll':
             case 'getServerGroupBySpanid':
//            	$ret = array('error'=>4);
//            	Logger::debug('PlatformApiDefault::getGiftByCard return %s', $ret);
            	//return $ret;
                break;

            default:
            	Logger::debug('PlatformApiDefault return nothing');
            	return '';
        }
        return $this->post_request($method,$params);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */