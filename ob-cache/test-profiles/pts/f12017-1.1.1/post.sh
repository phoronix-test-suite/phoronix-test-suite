#!/bin/bash
FERAL_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive"
GAME_PREFS="$FERAL_PREFS/F1 2017"

# Grab the old prefs bkp location
DATETIME=$( cat /tmp/f12017-bkp-dt )
rm /tmp/f12017-bkp-dt
GAME_PREFS_BKP="${FERAL_PREFS}/F1 2017.pts-$DATETIME-bkp"

# Remove our benchmarked preferences
rm -rf "${GAME_PREFS:?}/"

# Put back the old prefs
mv "$GAME_PREFS_BKP" "$GAME_PREFS"
