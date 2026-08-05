@echo off
echo Cleaning generated build artifacts and compiled Java class files...
if exist out rd /s /q out
if exist target rd /s /q target
if exist "always-beautiful" rd /s /q "always-beautiful"
if exist api-auth\target rd /s /q api-auth\target
del /s /q dao\*.class 2>nul
del /s /q conexion\*.class 2>nul
del /s /q modelo\*.class 2>nul
echo Cleanup complete. Review the project structure after running this script.
echo Note: This script removes generated build outputs and compiled .class files only.
pause
