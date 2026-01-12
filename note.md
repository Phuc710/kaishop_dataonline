curl -X POST http://localhost/kaishop/api/sepay-webhook.php ^
-H "Content-Type: application/json" ^
-d "{\"id\": \"TEST_123456\", \"amount\": 20000, \"content\": \"kaiNKW9F4MGNE4GBTM\", \"transfer_type\": \"in\"}"

C:\xampp\php\php.exe api/test-webhook.php kaiNKW9F4MGNE4GBTM 20000



# Environment Configuration

APP_URL=http://localhost/kaishop
APP_NAME=KaiShop

# Database Configuration
DB_HOST=localhost
DB_NAME=kaishop
DB_USER=root
DB_PASS=

# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=kaishop365@gmail.com
SMTP_PASS=nkycgmhjkqreyghr
EMAIL_FROM=kaishop@gmail.com
EMAIL_FROM_NAME='KaiShop'
EMAIL_RECIPIENT=Kaishop@gmail.com


RECAPTCHA_SECRET_KEY=6LcAwyskAAAAAAkN-v8tl_ASoL.Jb7nMzoTIuwcRy4NLL

# Currency Settings
DEFAULT_CURRENCY=VND
EXCHANGE_RATE=24000

# Upload Settings
MAX_FILE_SIZE=5242880

# reCAPTCHA Enterprise Configuration
RECAPTCHA_SITE_KEY=6Lf2cSosAAAAAI0UuvpT-i9XE9Qw5sxpK3GNEn6m
RECAPTCHA_SECRET_KEY=6Lf2cSosAAAAAI0UuvpT-i9XE9Qw5sxpK3GNEn6m
RECAPTCHA_PROJECT_ID=kaishop-b0f1d
