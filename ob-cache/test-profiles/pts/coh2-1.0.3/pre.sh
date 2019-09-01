#!/bin/bash

STEAM_GAME_ID=231430
GAME_BINARY="Company Of Hero"

export HOME=$DEBUG_REAL_HOME

steam steam://run/$STEAM_GAME_ID &
sleep 6
GAME_PID=`pgrep "$GAME_BINARY"`
echo '#!/bin/sh' > steam-env-vars.sh
while read -d $'\0' ENV; do NAME=`echo $ENV | cut -d= -f1`; VAL=`echo $ENV | cut -d= -f2`; echo "export $NAME=\"$VAL\""; done < /proc/$GAME_PID/environ >> steam-env-vars.sh
chmod +x steam-env-vars.sh

kill -9 $GAME_PID
