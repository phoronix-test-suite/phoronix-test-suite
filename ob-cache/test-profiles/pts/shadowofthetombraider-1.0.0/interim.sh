#!/bin/bash
GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Shadow of the Tomb Raider"

# Clean out the game temp files to ensure we have a clean run
rm -rf "${GAME_PREFS:?}/VFS"

