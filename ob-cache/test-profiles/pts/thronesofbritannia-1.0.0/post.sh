#!/bin/bash -e
GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Thrones of Britannia"

# Grab the old prefs bkp location
DATETIME=$( cat /tmp/tob-bkp-dt )
rm /tmp/tob-bkp-dt
GAME_PREFS_BKP="${GAME_PREFS}.pts-$DATETIME-bkp"

# Put back our game prefs
rm -rf "${GAME_PREFS:?}/"
mv "$GAME_PREFS_BKP" "$GAME_PREFS"
