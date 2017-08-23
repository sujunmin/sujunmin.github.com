#!/usr/bin/php -q
<?php
//非瀏覽器存取
$no_http_headers = true;

//用 CACTI 的 DB 存取
include(dirname(__FILE__) . '/include/global.php');

//取出相關的資料
//fixme: retry 次數未考慮
$need_to_ping_hosts = db_fetch_assoc('SELECT id, hostname, ping_port, ping_timeout, status FROM host WHERE ping_method = '. PING_TCP);

if (sizeof($need_to_ping_hosts)) {
        foreach($need_to_ping_hosts as $ping_host) {
                $fp = fsockopen($ping_host['hostname'], $ping_host['ping_port'], $errCode, $errStr, $ping_host['ping_timeout']/1000.0);
                if($fp) {
                        //有通，原來的狀態是 down
                        if($ping_host['status'] == HOST_DOWN) {
                                db_execute('UPDATE host SET status = '. HOST_RECOVERING .', status_rec_date=NOW() WHERE id = '. $ping_host['id']);
                        }
                        fclose($fp);
                }else{
                        //沒通，原來的狀態是 up
                        if($ping_host['status'] == HOST_UP) {
                                db_execute('UPDATE host SET status = '. HOST_DOWN .', status_fail_date=NOW(), status_last_error=\''. $errStr .'\' WHERE id = '. $ping_host['id']);
                        }
                }
        }
}
?>
