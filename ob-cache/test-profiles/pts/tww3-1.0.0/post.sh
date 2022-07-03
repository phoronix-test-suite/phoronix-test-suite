#!/bin/bash -e
GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Total War WARHAMMER III"

# Grab the old prefs bkp location
DATETIME=$( cat /tmp/tww3-bkp-dt )
rm /tmp/tww3-bkp-dt
GAME_PREFS_BKP="${GAME_PREFS}.pts-$DATETIME-bkp"

# Put back our game prefs
rm -rf "${GAME_PREFS:?}/"
mv "$GAME_PREFS_BKP" "$GAME_PREFS"
