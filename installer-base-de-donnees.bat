@echo off
REM ====================================================================
REM  SmartCampus - Creation de la base de donnees
REM  Importe le schema puis les donnees de demonstration dans MySQL.
REM  Prerequis : WAMP (MySQL) demarre.
REM ====================================================================
cd /d "%~dp0"

set "MYSQL_EXE="
for /d %%D in ("C:\wamp64\bin\mysql\mysql*") do set "MYSQL_EXE=%%D\bin\mysql.exe"
if not defined MYSQL_EXE (
  echo [ERREUR] MySQL introuvable dans C:\wamp64\bin\mysql
  echo Demarrez WAMP, ou ajustez le chemin dans ce script.
  pause & exit /b 1
)

echo Import du schema (creation de la base 'smartcampus' et des tables)...
"%MYSQL_EXE%" -u root < "database\schema.sql"
if errorlevel 1 (
  echo [ERREUR] Echec de l'import. WAMP MySQL est-il bien demarre ?
  pause & exit /b 1
)

echo Import des donnees de demonstration...
"%MYSQL_EXE%" -u root < "database\seed.sql"

echo.
echo La base de donnees 'smartcampus' a ete creee et remplie avec succes.
pause
