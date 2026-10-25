<?php
if(!defined('access') || !access) die();
// Read-only account state; no VIP offers or game configuration are defined here.
function panicRechargeVipStatus($account) {
    if(!preg_match('/^[A-Za-z0-9_]{1,10}$/D',(string)$account)) return null;
    try {
        $db=Connection::Database('MuOnline');
        $rows=$db?$db->query_fetch('SELECT CASE WHEN AccountLevel BETWEEN 1 AND 3 AND AccountExpireDate > GETDATE() THEN 1 ELSE 0 END AS active, CASE WHEN AccountLevel BETWEEN 1 AND 3 AND AccountExpireDate > GETDATE() THEN CEILING(DATEDIFF_BIG(second,GETDATE(),AccountExpireDate)/86400.0) ELSE 0 END AS days_remaining FROM dbo.MEMB_INFO WHERE memb___id = ?',[$account]):null;
        if(is_array($rows) && count($rows)===1 && isset($rows[0]['active'],$rows[0]['days_remaining']) && in_array($rows[0]['active'],[0,1,'0','1'],true) && is_numeric($rows[0]['days_remaining']) && $rows[0]['days_remaining']>=0) return ['active'=>(bool)$rows[0]['active'],'days'=>(int)$rows[0]['days_remaining']];
    } catch(Throwable $ignored) {}
    return null;
}
