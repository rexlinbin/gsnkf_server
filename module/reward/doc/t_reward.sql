

CREATE TABLE IF NOT EXISTS `t_reward_0`(
    rid             int unsigned not null comment '奖励id',
    uid             int unsigned not null comment '',
    source          tinyint unsigned not null comment '奖励类型，1系统补偿，2首充奖励，...',
    send_time       int unsigned not null comment '发奖时间',
    recv_time       int unsigned not null comment '领奖时间', 
    delete_time     int unsigned not null comment '删除时间，取消奖励时使用',
    va_reward       blob not null comment '具体的奖励信息',
    
    primary key(rid),
    index uid_send_time_recv_time(uid, send_time, recv_time)
)engine = InnoDb default charset utf8;

CREATE TABLE IF NOT EXISTS `t_reward_1` LIKE `t_reward_0`;
CREATE TABLE IF NOT EXISTS `t_reward_2` LIKE `t_reward_0`;
CREATE TABLE IF NOT EXISTS `t_reward_3` LIKE `t_reward_0`;
CREATE TABLE IF NOT EXISTS `t_reward_4` LIKE `t_reward_0`;
CREATE TABLE IF NOT EXISTS `t_reward_5` LIKE `t_reward_0`;
CREATE TABLE IF NOT EXISTS `t_reward_6` LIKE `t_reward_0`;
CREATE TABLE IF NOT EXISTS `t_reward_7` LIKE `t_reward_0`;
CREATE TABLE IF NOT EXISTS `t_reward_8` LIKE `t_reward_0`;
CREATE TABLE IF NOT EXISTS `t_reward_9` LIKE `t_reward_0`;


