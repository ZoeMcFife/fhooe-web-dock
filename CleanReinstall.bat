@echo off
set COMPOSE_PROJECT_NAME=fhooe-web-dock

echo Stopping all running fhooe-web-dock containers
docker compose stop

echo These containers will be deleted and recreated.
echo You will be asked whether to preserve the database volume.
echo.
docker compose ps -a

:prompt
set /p "answer=Continue? [Y/n] "
if "%answer%"=="" set answer=Y

if /i "%answer%"=="Y" goto proceed
if /i "%answer%"=="y" goto proceed
if /i "%answer%"=="N" goto cancel
if /i "%answer%"=="n" goto cancel

echo Please answer Y or n to continue.
goto prompt

:proceed
echo.
echo Y = Keep database volume (tables and data will be preserved after rebuild)
echo n = Delete everything (full reinstall with empty database)
echo.
:dbprompt
set "dbanswer="
set /p "dbanswer=Should the database data be preserved? [Y/n] "
if "%dbanswer%"=="" set dbanswer=Y

if /i "%dbanswer%"=="Y" goto keepdb
if /i "%dbanswer%"=="y" goto keepdb
if /i "%dbanswer%"=="N" goto removedb
if /i "%dbanswer%"=="n" goto removedb

echo Please answer Y or n.
goto dbprompt

:keepdb
echo Database volume will be preserved. Containers and images will be removed.
docker compose down --rmi all --remove-orphans
goto afterdown

:removedb
echo All data will be deleted, including the database volume.
docker compose down --rmi all --volumes --remove-orphans

:afterdown
echo.
echo ==============================================
echo EXPERIMENTAL FEATURES
echo ==============================================
set "expanswer=N"
set /p "expanswer=Install experimental features (FrankenPHP)? [y/N] "
set PROFILE_ARG=

if /i "%expanswer%"=="Y" (
    echo Experimental features enabled.
    set "PROFILE_ARG=--profile experimental"
) else (
    echo Experimental features disabled.
)
echo.

echo Remove any dangling images related to this project
docker image prune --force --filter "label=com.docker.compose.project=%COMPOSE_PROJECT_NAME%"

echo Update fhooe-web-dock from GitHub
git pull

echo Build the images from scratch
docker compose %PROFILE_ARG% build --no-cache

echo Create and start the containers in the background (detached)
docker compose %PROFILE_ARG% up --detach

echo All finished.
echo Enjoy your updated version of fhooe-web-dock!
pause
exit

:cancel
echo Containers, images and volumes are not removed.
echo Keeping the current versions and restarting...
docker compose start
pause
exit