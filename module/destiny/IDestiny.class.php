<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IDestiny.class.php 81737 2013-12-19 07:16:00Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/destiny/IDestiny.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-19 07:16:00 +0000 (Thu, 19 Dec 2013) $
 * @version $Revision: 81737 $
 * @brief 
 *  
 **/
interface IDestiny
{
    /**
     * @return array
     * <code>
     * [
     *     uid                   玩家uid 
     *     cur_break             当前的突破表id
     *     cur_destiny           当前的天命Id  
     *     va_destiny            暂时没用
     *     has_score             当前剩余的副本星数 
     *     all_score             所有的副本星数   
     * ]
     * </code>
     */
    public function getDestinyInfo();
    
    /**
     * 激活天命
     * @param int $destinyId
     * @return int        已经消耗的副本星数
     */
    public function activateDestiny($destinyId);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */