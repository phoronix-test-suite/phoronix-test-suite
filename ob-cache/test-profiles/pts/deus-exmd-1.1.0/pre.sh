#!/bin/bash

STEAM_GAME_ID=337000
GAME_BINARY=DeusExMD

export HOME=$DEBUG_REAL_HOME

steam steam://run/$STEAM_GAME_ID &
sleep 10
GAME_PID=`pgrep $GAME_BINARY | head -n1`
echo '#!/bin/sh' > steam-env-vars.sh
echo $GAME_PID > PIDDY
while read -d $'\0' ENV; do NAME=`echo $ENV | cut -d= -f1`; VAL=`echo $ENV | cut -d= -f2`; echo "export $NAME=\"$VAL\""; done < /proc/$GAME_PID/environ >> steam-env-vars.sh
chmod +x steam-env-vars.sh

kill -9 $GAME_PID
