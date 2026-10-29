@echo off
setlocal DisableDelayedExpansion
title Zinema - Stop Local System
pushd "%~dp0"
if errorlevel 1 goto location_error
echo Stopping the owned Zinema local services. Saved data is retained.
echo.
set "ZINEMA_PYTHON=python"
python -c "import sys; sys.exit(0 if sys.version_info >= (3,11) else 1)" >nul 2>&1
if not errorlevel 1 goto stop
set "ZINEMA_PYTHON=py -3"
py -3 -c "import sys; sys.exit(0 if sys.version_info >= (3,11) else 1)" >nul 2>&1
if errorlevel 1 goto python_error

:stop
%ZINEMA_PYTHON% -X utf8 "%~dp0dev\native.py" down
set "ZINEMA_EXIT=%errorlevel%"
echo.
if not "%ZINEMA_EXIT%"=="0" echo Stop could not complete. Review the message above; do not delete saved state.
pause
popd
exit /b %ZINEMA_EXIT%

:python_error
echo Python 3.11 or newer was not found. No services were changed.
pause
popd
exit /b 1

:location_error
echo Cannot open the Zinema project folder.
pause
exit /b 1
