#!/bin/bash

STEAM_GAME_ID=222880
GAME_BINARY=insurgency_linu

export HOME=$DEBUG_REAL_HOME

steam steam://run/$STEAM_GAME_ID &
sleep 5
GAME_PID=`pgrep $GAME_BINARY`
echo '#!/bin/sh' > steam-env-vars.sh
while read -d $'\0' ENV; do NAME=`echo $ENV | cut -d= -f1`; VAL=`echo $ENV | cut -d= -f2`; echo "export $NAME=\"$VAL\""; done < /proc/$GAME_PID/environ >> steam-env-vars.sh
chmod +x steam-env-vars.sh
sleep 1
kill -9 $GAME_PID
