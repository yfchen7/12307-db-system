create type seattype as enum('yz','rz','yws','ywz','ywx','rws','rwx');
create type order_status as enum('0','1','2');
create table city    (c_cityid      integer primary key,
                      c_name        varchar(20) unique
                    );   
create table station (s_stationid   integer primary key,
                      s_cityid      integer not null,
                      s_name        varchar(20) unique,
                      foreign key (s_cityid) references city(c_cityid)
                    );
create table train   (t_trainid     integer primary key,
                      t_trainno     char(6) unique
                    );                    
create table seat    (se_trainid    integer not null,
                      se_seattype   seattype,
                      primary key (se_trainid , se_seattype),
                      foreign key (se_trainid) references train(t_trainid)
                    );
create table stop    (sp_stationid      integer not null,
                      sp_trainid        integer not null,
                      sp_arrivetime     time,
                      sp_departtime     time,
                      sp_count          integer not null,
                      primary key (sp_stationid , sp_trainid),
                      foreign key (sp_stationid) references station(s_stationid),
                      foreign key (sp_trainid) references train(t_trainid)
                    );
create table price   (p_stationid  integer not null,
                      p_trainid    integer not null,
                      p_seattype   seattype,
                      p_price      decimal(15,2) not null,
                      primary key (p_stationid , p_trainid, p_seattype),
                      foreign key (p_stationid) references station(s_stationid),
                      foreign key (p_trainid) references train(t_trainid),
                      foreign key (p_trainid , p_seattype) references seat(se_trainid , se_seattype)
                      );
create table runday  (r_day           Date,
                      r_trainid      integer not null,
                      primary key (r_day , r_trainid),
                      foreign key (r_trainid) references train(t_trainid)
                      );
create table seatleft (sl_stationid      integer not null,
                       sl_trainid        integer not null,
                       sl_day            Date,
                       sl_seattype       seattype,
                       sl_seatleft       integer not null,
                       primary key (sl_stationid , sl_day , sl_trainid , sl_seattype),
                       foreign key (sl_stationid) references station(s_stationid),
                       foreign key (sl_trainid) references train(t_trainid), 
                       --foreign key (sl_day) references runday(r_day),
                       --foreign key (sl_seattype) references seat(se_seattype),
                       foreign key (sl_trainid , sl_day) references runday(r_trainid , r_day),
                       foreign key (sl_trainid , sl_seattype) references seat(se_trainid , se_seattype)
                     );
create table usr    (u_userid       integer primary key,
                      u_realid       char(18) unique,
                      u_passwdhash   char(32) not null,
                      u_phone        bigint unique,
                      u_realname     varchar(20) not null,
                      u_username     varchar(20) not null,
                      u_ccard        char(16) not null
                      );
create table orders   (o_orderid       integer primary key,
                      o_userid        integer not null,
                      o_trainid       integer not null,
                      o_status        order_status,
                      o_price         decimal(15,2) not null,
                      o_travelday     Date,
                      o_departtime    time,
                      o_arrivetime    time,
                      o_arrivestation integer not null,
                      o_departstation integer not null,
                      o_seattype      seattype,
                      foreign key (o_userid) references usr(u_userid),
                      foreign key (o_trainid) references train(t_trainid),
                      --foreign key (o_travelday) references runday(r_day),
                      foreign key (o_arrivestation) references station(s_stationid),
                      foreign key (o_departstation) references station(s_stationid),
                      foreign key (o_trainid , o_travelday) references runday(r_trainid, r_day)
                    );




