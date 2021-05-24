--重复性检查
SELECT 
    count(*)
FROM
    usr
WHERE
    u_phone=[PHONE] or
    u_realid=[REALID];
--分配用户id
SELECT 
    count(*)+1 as userid
FROM
    usr;
--查询用户的历史订单信息(具体信息)
SELECT  
    o_orderid,
    t_trainno,
    o_status,
    o_price,
    (o_travelday+o_departtime) as departtime,
    (o_travelday+SP2.sp_count-SP1.sp_count+o_arrivetime) as arrivetime, 
    S1.s_name,
    S2.s_name,
    o_seattype
FROM 
    orders,
    stop as SP1,
    stop as SP2,
    train,
    station as S1,
    station as S2
WHERE
    o_userid=[USERID] and
    o_travelday>=[DATE1] and
    o_travelday<=[DATE2] and
    o_trainid=t_trainid and
    SP1.sp_trainid=o_trainid and
    SP1.sp_stationid=o_departstation and
    SP2.sp_trainid=o_trainid and
    SP2.sp_stationid=o_arrivestation and
    S1.s_stationid=o_departstation and
    S2.s_stationid=o_arrivestation
--预定时生成订单号
SELECT 
    count(*)+1 as orderid
FROM
    orders;
