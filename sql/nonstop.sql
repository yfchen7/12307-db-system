SELECT
  t_trainno,
  S1.s_name,--始发站
  S2.s_name,--终点站
  to_timestamp('$sday','yyyy-MM-dd')+SL.departtime,--从出发站离开日期时间
  SL.slday + SL.count2 + SL.arrivetime,
  (case when SL.departtime>=SL.arrivetime0
        then to_date('2020-9-1','yyyy-MM-dd')+SL.count2-SL.count1+SL.arrivetime-SL.departtime-to_timestamp('2020-9-1 00:00:00','yyyy-MM-dd hh24:mi:ss')
        else to_date('2020-9-1','yyyy-MM-dd')+SL.count2-SL.count1-1+SL.arrivetime-SL.departtime-to_timestamp('2020-9-1 00:00:00','yyyy-MM-dd hh24:mi:ss')
        end) 
    as total_time, 
  max(SL.seatleft) filter(where SL.seattype='硬座') as yz_left,
  max(SL.seatleft) filter(where SL.seattype='软座') as rz_left,
  max(SL.seatleft) filter(where SL.seattype='硬卧上') as yws_left,
  max(SL.seatleft) filter(where SL.seattype='硬卧中') as ywz_left,
  max(SL.seatleft) filter(where SL.seattype='硬卧下') as ywx_left,
  max(SL.seatleft) filter(where SL.seattype='软卧上') as rws_left,
  max(SL.seatleft) filter(where SL.seattype='软卧下') as rwx_left,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='硬座') as yz_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='软座') as rz_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='硬卧上') as yws_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='硬卧中') as ywz_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='硬卧下') as ywx_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='软卧上') as rws_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='软卧下') as rwx_price,
  min(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and SL.seatleft>0) as price
FROM    
  station as S1,
  station as S2,
  train,
  price as P1,
  price as P2,
  (
      SELECT
          ST1.sp_stationid as departstation,
          ST3.sp_stationid as arrivestation,
          sl_trainid as trainid,
          sl_seattype as seattype,
          min(sl_seatleft) as seatleft,
          ST1.sp_departtime as departtime,
          ST1.sp_arrivetime as arrivetime0,
          ST3.sp_arrivetime as arrivetime,
          ST1.sp_count as count1,
          ST3.sp_count as count2,
          sl_day as slday
      FROM
          seatleft,
          stop as ST1,
          stop as ST2,
          stop as ST3,
          city as CI1,
          city as CI2,
          station as SI1,
          station as SI2
      WHERE
          CI1.c_name='$scity' and
          CI2.c_name='$ecity' and
          CI1.c_cityid=SI1.s_cityid and
          CI2.c_cityid=SI2.s_cityid and
          ST1.sp_stationid=SI1.s_stationid and
          ST3.sp_stationid=SI2.s_stationid and
          ST1.sp_trainid=ST3.sp_trainid and
          ST2.sp_trainid = ST3.sp_trainid and
          ST1.sp_departtime >= '$stime' and
          sl_trainid=ST1.sp_trainid and
          sl_stationid=ST2.sp_stationid and
          (
            (
              ST1.sp_departtime>=ST1.sp_arrivetime and
              '$sday'=ST1.sp_count+sl_day
            )
              or
            (
              ST1.sp_departtime<ST1.sp_arrivetime and
              '$sday'=ST1.sp_count+sl_day+1
            )
          )
          and 
          (
              ST2.sp_count>ST1.sp_count
              or
              (
                  ST2.sp_count=ST1.sp_count and
                  ST1.sp_arrivetime<=ST2.sp_arrivetime
              )
          )and
          (
              ST3.sp_count>ST2.sp_count
              or
              (
                  ST3.sp_count=ST2.sp_count and
                  ST2.sp_arrivetime<ST3.sp_arrivetime
              )
          )
      GROUP BY
          departstation,
          arrivestation,
          trainid,
          seattype,
          departtime,
          arrivetime0,
          arrivetime,
          count1,
          count2,
          slday
  )as SL
WHERE 
  S1.s_stationid=SL.departstation and
  S2.s_stationid=SL.arrivestation and
  SL.seattype=P2.p_seattype and
  P1.p_seattype=P2.p_seattype and
  P1.p_stationid=SL.departstation and
  P2.p_stationid=SL.arrivestation and
  P1.p_trainid=SL.trainid and
  P2.p_trainid=SL.trainid and
  t_trainid=SL.trainid 
GROUP BY
  S1.s_name,--始发站
  S2.s_name,--终点站
  t_trainno,
  SL.departtime,--从始发站离开时间
  SL.arrivetime0,
  SL.arrivetime,--到达终点站时间
  SL.count1,
  SL.count2,
  SL.slday
ORDER BY
   price,total_time,SL.departtime
limit 10;