#!/bin/bash -e
set -o xtrace
exec > /tmp/test
exec 2>&1

# Input settings
WIDTH=$1
HEIGHT=$2
SETTING=$3
AA=$4

# Game preferences
export HOME=$DEBUG_REAL_HOME
GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Rise of the Tomb Raider"

# Set up (and back up) the game preferences files
DATETIME=$( date +%Y-%d-%m-%H-%M )
echo "$DATETIME" >/tmp/rotr-bkp-dt
GAME_PREFS_BKP="${GAME_PREFS}.pts-$DATETIME-bkp"
cp -r "$GAME_PREFS" "$GAME_PREFS_BKP"

# clear previous runs
rm -rf "${GAME_PREFS:?}"
mkdir -p "${GAME_PREFS}"

# Set up the files to use
cp "preferences.template.xml" "$GAME_PREFS/preferences"

# Replace the resolutions
cd "$GAME_PREFS" || exit

# Replace settings with those chosen
sed -i "s/@ScreenW@/$WIDTH/g"          preferences
sed -i "s/@ScreenH@/$HEIGHT/g"         preferences
sed -i "s/@DefaultAntialiasing@/$AA/g" preferences
sed -i "s/@DefaultPreset@/$SETTING/g"   preferences
