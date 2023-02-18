#!/bin/bash -e
GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Total War WARHAMMER III"

GAME_PREFS_BKP="${GAME_PREFS}.pts-bkp"

# Put back our game prefs
rm -rf "${GAME_PREFS:?}/"
mv "$GAME_PREFS_BKP" "$GAME_PREFS"
