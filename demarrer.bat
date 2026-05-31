@echo off
cd /d "%~dp0"

set "PHP_EXE="
for /d %%D in ("C:\wamp64\bin\php\php*") do set "PHP_EXE=%%D\php.exe"
if not defined PHP_EXE (
  echo [ERREUR] PHP introuvable dans C:\wamp64\bin\php
  echo Demarrez WAMP, ou ajustez le chemin dans ce script.
  pause & exit /b 1
)

echo Demarrage de l'API PHP sur http://localhost:8000 ...
start "SmartCampus - API PHP" "%PHP_EXE%" -S localhost:8000 -t "%~dp0api"

cd /d "%~dp0frontend"
if not exist node_modules (
  echo Premiere utilisation : installation des dependances npm ...
  call npm install
)

echo Demarrage du frontend React sur http://localhost:5173 ...
start "SmartCampus - Frontend React" cmd /k "npm run dev"

echo.
echo ================================================================
echo   SmartCampus demarre !
echo   Ouvrez votre navigateur sur :  http://localhost:5173
echo ================================================================
pause
