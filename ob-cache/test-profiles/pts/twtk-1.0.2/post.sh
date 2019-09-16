#!/bin/bash -e
GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Three Kingdoms"

# Grab the old prefs bkp location
DATETIME=$( cat /tmp/twtk-bkp-dt )
rm /tmp/twtk-bkp-dt
GAME_PREFS_BKP="${GAME_PREFS}.pts-$DATETIME-bkp"

# Put back our game prefs
rm -rf "${GAME_PREFS:?}/"
mv "$GAME_PREFS_BKP" "$GAME_PREFS"
