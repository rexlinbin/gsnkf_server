set names utf8;

create table t_active( 
    uid int unsigned not null comment '用户ID',
    point int unsigned not null comment '总积分',
    last_point int unsigned not null comment '记录昨天的总积分，用以平台统计核心用户',
    update_time int unsigned not null comment '上次更新时间',
    va_active blob not null comment 'array(step(哪步),task($taskId(任务)=>$num(完成次数)),prize($prizeId(领取过的宝箱奖励id)),taskReward($taskId(领取过任务奖励的任务id)))',
    primary key(uid)
)engine = InnoDb default charset utf8;