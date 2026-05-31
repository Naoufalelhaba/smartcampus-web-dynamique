@echo off
REM ====================================================================
REM  SmartCampus - Demarrage de l'application
REM  Lance l'API PHP (port 8000) et le frontend React (port 5173).
REM  Prerequis : WAMP (MySQL) demarre + base importee (voir installer-base-de-donnees.bat).
REM ====================================================================
cd /d "%~dp0"

REM --- Recherche de PHP dans WAMP ---
set "PHP_EXE="
for /d %%D in ("C:\wamp64\bin\php\php*") do set "PHP_EXE=%%D\php.exe"
if not defined PHP_EXE (
  echo [ERREUR] PHP introuvable dans C:\wamp64\bin\php
  echo Demarrez WAMP, ou ajustez le chemin dans ce script.
  pause & exit /b 1
)

echo Demarrage de l'API PHP sur http://localhost:8000 ...
start "SmartCampus - API PHP" "%PHP_EXE%" -S localhost:8000 -t "%~dp0api"

REM --- Installation des dependances du frontend si necessaire ---
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
