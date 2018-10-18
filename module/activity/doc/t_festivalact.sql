set names utf8;

create table if not exists t_festivalact
(
    uid            int unsigned not null comment '用户id',
    update_time    int unsigned not null comment '最后一次修改数据时间',
    va_data        blob         not null comment '按季度分数组，然后任务的记录[今天之前,今天的,完成状态0|1|2,上一次时间],除任务外的记录[次数,上一次时间]',

    primary key(uid) 
)engine = InnoDb default charset utf8;