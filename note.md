2️⃣ Để đổi tên hiển thị "kaishop-id-vn.firebaseapp.com" → "KaiShop"
Tao thấy trong Project Settings e đã có Public-facing name: KaiShop rồi. Nhưng cái popup Google vẫn hiện domain vì cần OAuth Brand Verification từ Google.

Cách fix:
Vào link này: Google Cloud Console - OAuth consent screen
Tìm phần App name → nhập KaiShop
Bấm Save
Hoặc trong Firebase settings có ghi:

"To update public-facing name or support email, submit a request via Google Cloud Console."

Click vào link Google Cloud Console đó và update tên app ở đó.





Invoke-RestMethod -Uri "https://kaishop.id.vn/api/sepay-webhook.php" -Method Post -Headers @{
    "Authorization" = "ApiKey sepay_MaiYeuEm_2026_a8f3d9c2e1b4f7a6"
    "Content-Type" = "application/json"
} -Body '{"id":"test12333","content":"Nap tien kaiL46TJ09H0QY3AZH","amount":10000,"account_number":"09696969690","transaction_date":"2026-01-20 20:00:00","transfer_type":"in","gate":"MB Bank"}'