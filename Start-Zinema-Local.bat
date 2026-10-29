@echo off
setlocal DisableDelayedExpansion
title Zinema - Local System
pushd "%~dp0"
if errorlevel 1 goto location_error

echo ============================================================
echo   Zinema local website, database, extractor and WhatsApp bot
echo   WhatsApp uses the owner-configured mode shown below.
echo   Facebook, email and OAuth remain local substitutes.
echo ============================================================
echo.
echo Keep this window open while using the local system.
echo Double-click Stop-Zinema-Local.bat to stop safely and keep data.
echo The website opens after the local services are ready.
echo.

set "ZINEMA_PYTHON=python"
python -c "import sys; sys.exit(0 if sys.version_info >= (3,11) else 1)" >nul 2>&1
if not errorlevel 1 goto launch
set "ZINEMA_PYTHON=py -3"
py -3 -c "import sys; sys.exit(0 if sys.version_info >= (3,11) else 1)" >nul 2>&1
if errorlevel 1 goto python_error

:launch
%ZINEMA_PYTHON% -X utf8 "%~dp0dev\native.py" up --open-browser
set "ZINEMA_EXIT=%errorlevel%"
echo.
if not "%ZINEMA_EXIT%"=="0" goto launch_error
echo Local launcher finished. Saved local data has been retained.
echo If the services were already running, their existing window owns them.
pause
popd
exit /b 0

:python_error
echo Python 3.11 or newer was not found. Install Python or add it to PATH.
echo No services or packages were installed by this launcher.
pause
popd
exit /b 1

:launch_error
echo Local startup stopped with an error. Review the message above.
echo Do not delete the runtime folder or stop unrelated services.
pause
popd
exit /b %ZINEMA_EXIT%

:location_error
echo Cannot open the Zinema project folder.
pause
exit /b 1
