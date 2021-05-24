--创建订单
insert into orders 
values (...)
--创建订单后更新余票
UPDATE
    seatleft
SET
    sl_seatleft=sl_seatleft-1
FROM 
    stop as SP1,
    stop as SP2
WHERE
    sl_trainid=[TRAINID] and
    sl_day=[DATE] and
    SP1.sp_trainid=sl_trainid and
    SP1.sp_trainid=SP2.sp_trainid and
    sl_stationid=SP1.sp_stationid and
    SP2.sp_stationid=[ARRIVE_STATION_ID] and
    (
        SP1.sp_count<SP2.sp_count or
        (
            SP1.sp_count=SP2.sp_count and
            SP1.sp_arrivetime<SP2.sp_arrivetime
        )
    );
--取消订单
UPDATE 
    orders
SET 
    o_status=2
WHERE 
    o_orderid=[ORDERID];
--取消订单后更新余票
UPDATE
    seatleft
SET
    sl_seatleft=sl_seatleft+1
FROM
    stop as SP1,
    stop as SP2
WHERE
    sl_trainid=[TRAINID] and
    sl_day=[DATE] and
    SP1.sp_trainid=sl_trainid and
    SP1.sp_trainid=SP2.sp_trainid and
    sl_stationid=SP1.sp_stationid and
    SP2.sp_stationid=[ARRIVE_STATION_ID] and
    (
        SP1.sp_count<SP2.sp_count or
        (
            SP1.sp_count=SP2.sp_count and
            SP1.sp_arrivetime<SP2.sp_arrivetime
        )
    );
--放票
INSERT INTO 
    seatleft(
        sl_seatleft,
        sl_trainid,
        sl_stationid,
        sl_seattype,
        sl_day
    )
SELECT
    5,
    p_trainid, 
    p_stationid, 
    p_seattype,
    '2020.10.1'
FROM
    price;
--发车日期
INSERT INTO 
    runday(
        r_day,
        r_trainid
    )
SELECT
    '2020.10.1',
    t_trainid
FROM
    train;
