#!/bin/bash
# Switch to the directory where the script is located to handle absolute path calls
cd "$(dirname "$0")" || exit

export COMPOSE_PROJECT_NAME=fhooe-web-dock

echo "Stopping all running fhooe-web-dock containers"
docker compose --profile "*" stop

echo "These containers will be deleted and recreated. You will be asked whether to preserve the database volume."
echo ""
docker compose --profile "*" ps -a

while true; do
    read -p "Continue? [Y/n] " -r answer
    answer=${answer:-Y}

    case $answer in
        [Yy]* ) 
            break;;
        [Nn]* ) 
            echo "Containers, images and volumes are not removed. Keeping the current versions and restarting..."
            docker compose --profile "*" start
            read -p "Press any key to exit ..."
            exit 1;;
        * ) 
            echo "Please answer Y or n to continue.";;
    esac
done

echo ""
echo "Y = Keep database volume (tables and data will be preserved after rebuild)"
echo "n = Delete everything (full reinstall with empty database)"
echo ""
while true; do
    read -p "Should the database data be preserved? [Y/n] " -r dbanswer
    dbanswer=${dbanswer:-Y}

    case $dbanswer in
        [Yy]* )
            echo "Database volume will be preserved. Containers and images will be removed."
            docker compose --profile "*" down --rmi all --remove-orphans
            break;;
        [Nn]* )
            echo "All data will be deleted, including the database volume."
            docker compose --profile "*" down --rmi all --volumes --remove-orphans
            break;;
        * )
            echo "Please answer Y or n.";;
    esac
done

echo ""
echo "=============================================="
echo "EXPERIMENTAL FEATURES"
echo "=============================================="
read -p "Install experimental features (FrankenPHP)? [y/N] " -r expanswer
expanswer=${expanswer:-N}
PROFILE_ARG=""

case $expanswer in
    [Yy]* )
        echo "Experimental features enabled."
        PROFILE_ARG="--profile experimental"
        ;;
    * )
        echo "Experimental features disabled."
        ;;
esac
echo ""

echo "Remove any dangling images related to this project"
docker image prune --force --filter "label=com.docker.compose.project=$COMPOSE_PROJECT_NAME"

echo "Update fhooe-web-dock from GitHub"
if ! command -v git &>/dev/null; then
    echo "Skipping git pull (git not available)."
    echo "To get updates, download the latest version from https://github.com/Digital-Media/fhooe-web-dock"
elif ! git rev-parse --git-dir &>/dev/null; then
    echo "Skipping git pull (not a git repository)."
    echo "To get updates, download the latest version from https://github.com/Digital-Media/fhooe-web-dock"
else
    git pull
fi

echo "Build the images from scratch"
docker compose $PROFILE_ARG build --no-cache

echo "Create and start the containers in the background (detached)"
docker compose $PROFILE_ARG up --detach

echo "All finished. Enjoy your updated version of fhooe-web-dock!"
read -p "Press any key to exit ..."