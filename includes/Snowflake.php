<?php
/**
 * Snowflake ID Generator
 * Tạo ID duy nhất theo thuật toán Snowflake (tương tự Twitter)
 */

class Snowflake {
    private static $epoch = 1672531200000; // 2023-01-01 00:00:00 UTC
    private static $datacenterIdBits = 5;
    private static $workerIdBits = 5;
    private static $sequenceBits = 12;
    
    private static $maxDatacenterId = 31; // 2^5 - 1
    private static $maxWorkerId = 31; // 2^5 - 1
    private static $maxSequence = 4095; // 2^12 - 1
    
    private static $workerIdShift = 12;
    private static $datacenterIdShift = 17;
    private static $timestampShift = 22;
    
    private static $datacenterId = 1;
    private static $workerId = 1;
    private static $sequence = 0;
    private static $lastTimestamp = -1;
    
    /**
     * Tạo Snowflake ID mới
     */
    public static function generateId() {
        $timestamp = self::getTimestamp();
        
        if ($timestamp < self::$lastTimestamp) {
            throw new Exception("Clock moved backwards!");
        }
        
        if ($timestamp == self::$lastTimestamp) {
            self::$sequence = (self::$sequence + 1) & self::$maxSequence;
            if (self::$sequence == 0) {
                $timestamp = self::waitNextMillis(self::$lastTimestamp);
            }
        } else {
            self::$sequence = 0;
        }
        
        self::$lastTimestamp = $timestamp;
        
        $id = (($timestamp - self::$epoch) << self::$timestampShift)
            | (self::$datacenterId << self::$datacenterIdShift)
            | (self::$workerId << self::$workerIdShift)
            | self::$sequence;
        
        return $id;
    }
    
    /**
     * Lấy timestamp hiện tại (milliseconds)
     */
    private static function getTimestamp() {
        return floor(microtime(true) * 1000);
    }
    
    /**
     * Đợi đến millisecond tiếp theo
     */
    private static function waitNextMillis($lastTimestamp) {
        $timestamp = self::getTimestamp();
        while ($timestamp <= $lastTimestamp) {
            $timestamp = self::getTimestamp();
        }
        return $timestamp;
    }
    
    /**
     * Chuyển đổi ID thành timestamp
     */
    public static function getTimestampFromId($id) {
        return ($id >> self::$timestampShift) + self::$epoch;
    }
}
?>
