SELECT 
    t_trainno,
    s_name,
    p_seattype,
    p_price,
    sl_seatleft,
    SP1.sp_arrivetime,
    SP1.sp_departtime,
    SP1.sp_count
FROM
    seatleft,
    price,
    station,
    train,
    (
        SELECT  
            sp_trainid,
            sp_stationid,
            sp_arrivetime,
            sp_departtime,
            sp_count
        FROM 
            stop,
            train
        WHERE
            t_trainno='K3',
            sp_trainid=t_trainid
        ORDER BY
            sp_arrivetime
    ) as SP1
WHERE
    sl_trainid=p_trainid and
    sl_trainid=SP1.sp_trainid and
    sl_trainid=t_trainid and
    sl_day='2021-5-26' and
    SP1.sp_stationid=s_stationid and
    SP1.sp_stationid=p_stationid and
    SP1.sp_stationid=sl_stationid and
    p_seattype=sl_seattype
GROUP BY
    t_trainno,sl_seatleft,s_name,p_price,p_seattype,SP1.sp_arrivetime,SP1.sp_departtime,SP1.sp_count
ORDER BY
    SP1.sp_count,SP1.sp_arrivetime;