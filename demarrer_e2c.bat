@echo off
cd /d "C:\Users\HP PROBOOK 440 G10\Desktop\OPOUE\e2c_v2_2complete"
start "Serveur E2C PHP 8.4" cmd /k "cd /d C:\Users\HP PROBOOK 440 G10\Desktop\OPOUE\e2c_v2_2complete && C:\PHP8416\php.exe -S 127.0.0.1:8088 -t public"
timeout /t 3 /nobreak >nul
start "" "http://127.0.0.1:8088/login"
