
INSERT INTO settings (setting_key, setting_value) 
VALUES ('sepay_api_key', 'YOUR_SEPAY_API_KEY_HERE')
ON DUPLICATE KEY UPDATE 
    setting_value = 'YOUR_SEPAY_API_KEY_HERE';