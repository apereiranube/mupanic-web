<?php
if(!defined('access') || !access) die();

/** Read-only mapping verified by wallet audit 20261001_163845. */
function panicRechargeWalletBalance($account) {
    if(!is_string($account) || !preg_match('/^[A-Za-z0-9_]{1,10}$/D',$account)) return null;
    try {
        // MuOnline is the CMS connection alias; MuOnline43 is the physical database.
        $db=Connection::Database('MuOnline');
        if(!$db) return null;
        $rows=$db->query_fetch('SELECT [WCoinC] AS [balance] FROM [MuOnline43].[dbo].[CashShopData] WHERE [AccountID] = ?',[$account]);
        if(!is_array($rows) || count($rows)!==1 || !isset($rows[0]['balance'])) return null;
        $value=$rows[0]['balance'];
        if(!is_int($value) && (!is_string($value) || !preg_match('/^[0-9]{1,10}$/D',$value))) return null;
        $value=(int)$value;
        if($value<0 || $value>2147483647) return null;
        return $value;
    } catch(Throwable $exception) {
        // Never render connection errors or credentials; unknown is not zero.
        return null;
    }
}
