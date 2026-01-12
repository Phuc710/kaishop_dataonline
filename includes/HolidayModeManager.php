<?php
class HolidayModeManager
{
    const MODE_NORMAL = 'normal';
    const MODE_TET = 'tet';
    const MODE_NOEL = 'noel';
    const MODE_HALLOWEEN = 'halloween';

    /**
     * Lấy danh sách các mode hỗ trợ
     */
    public static function getModes()
    {
        return [
            self::MODE_NORMAL => 'Mặc định',
            self::MODE_TET => 'Tết Nguyên Đán',
            self::MODE_NOEL => 'Giáng Sinh',
            self::MODE_HALLOWEEN => 'Halloween'
        ];
    }

    /**
     * Lấy mode hiện tại từ Database
     */
    public static function getCurrentMode()
    {
        // 'site_holiday_mode' là key trong bảng settings
        return get_setting('site_holiday_mode', self::MODE_NORMAL);
    }

    /**
     * Kiểm tra có đang bật mode nào không
     */
    public static function isActive()
    {
        return self::getCurrentMode() !== self::MODE_NORMAL;
    }

    /**
     * Lấy class để gắn vào thẻ <body>
     */
    public static function getBodyClass()
    {
        $mode = self::getCurrentMode();
        return $mode !== self::MODE_NORMAL ? 'holiday-mode-' . $mode : '';
    }
}
?>