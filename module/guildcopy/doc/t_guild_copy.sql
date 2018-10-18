set names utf8;
create table if not exists t_guild_copy
(
	guild_id int unsigned not null comment '军团id，主键',
	curr int unsigned not null comment '当前攻打的军团副本id',
	next int unsigned not null comment '明天攻打的军团副本id',
	max_pass_copy int unsigned not null comment '通关的最大军团副本id',
	refresh_num int unsigned not null comment '今天全团突击次数，为军团成员加n次攻击次数',
	pass_time int unsigned not null comment '通关当日军团副本的时间',
    max_pass_time int unsigned not null comment '最大副本的通关时间，用于排序',
	update_time int unsigned not null comment '更新时间',
	va_extra blob not null comment '
                                   copy => array                        当前攻打的副本信息
                                   [
                                       base_id => array                 据点信息
                                       {
                                           hp => array                  据点血量信息，如果没有被攻打过，没有hp
                                           [
                                               hid => hp 
                                           ]
                                           maxHp => int                 这个据点的总血量
                                           type => array(a,b)           据点类型，两个元素，每一元素取值都是1,2,3,4,代表魏蜀吴群
                                           max_damager => array         造成最大伤害的玩家信息
                                           {
                                               uid => int
                                               htid => int
                                               uname => string
                                               damage => damage
                                           }
                                       }
                                   ]
                                   box                                  通关宝箱领取信息
                                   [
                                       id => array(uid,htid,uname,reward)    领取的玩家信息和奖励信息
                                   ]
                                   refresher(uname1,uname2,...)         今天使用全团突击的军团成员uname
                                   ',
    va_last_box blob not null comment '
                                          date => array
                                          {
                                              last => int
                                              box => array
                                              [
                                                  id => array(uid,htid,uname,reward)    昨天领取的玩家信息和奖励信息，用于补发
                                              ]
                                          }
                                      ',
                                      
    va_boss blob not null comment '{ 
									
									cd => int, 刷新CD
									arrHero => {
										hp => int, 当前血量 
										max_hp => int, 本次最高血量，用于计算下一回合血量
										}
									}',
	primary key(guild_id)
)engine = InnoDb default charset utf8;