#!/bin/bash
GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Total War WARHAMMER III"

if [ -z "${STEAM_ACCOUNT_ID}" ]; then
    pushd "${GAME_PREFS}/SaveData/"
    STEAM_ACCOUNT_ID="$(ls |head -1)"
    popd
else
    STEAM_ACCOUNT_ID="Steam Saves (${STEAM_ACCOUNT_ID})"
fi

RESULTS_PREFIX="${GAME_PREFS}/SaveData/${STEAM_ACCOUNT_ID}/"

rm -rf "${RESULTS_PREFIX:?}"
