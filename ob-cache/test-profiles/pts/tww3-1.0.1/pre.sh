#!/bin/bash -e
set -o xtrace
exec > /tmp/test
exec 2>&1

# Input settings
WIDTH=$1
HEIGHT=$2
SETTING=$3
SCENARIO=$4

# Game preferences
export HOME=$DEBUG_REAL_HOME
GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Total War WARHAMMER III"

# Set up (and back up) the game preferences files
GAME_PREFS_BKP="${GAME_PREFS}.pts-bkp"
cp -r "$GAME_PREFS" "$GAME_PREFS_BKP"

# clear previous runs
rm -rf "${GAME_PREFS:?}"
mkdir -p "${GAME_PREFS}"

# Set up the files to use
cp "preferences.template.xml" "$GAME_PREFS/preferences"

# Replace the resolutions
cd "$GAME_PREFS" || exit 1

# Replace settings with those chosen
sed -i "s/@screen_height@/$HEIGHT/g"          preferences
sed -i "s/@screen_width@/$WIDTH/g"         preferences

# Replace benchmark scenario, use commas as delimiters since this is replacing a
# path string
sed -i "s,@benchmark_name@,$SCENARIO,g"         preferences

# Lowest
if [ $SETTING -eq "0" ]; then
	sed -i "s/@gfx_aa@/0/g"                       preferences
	sed -i "s/@gfx_building_quality@/0/g"         preferences
	sed -i "s/@gfx_effects_quality@/0/g"          preferences
	sed -i "s/@gfx_grass_quality@/0/g"            preferences
	sed -i "s/@gfx_shadow_quality@/0/g"           preferences
	sed -i "s/@gfx_sky_quality@/0/g"              preferences
	sed -i "s/@gfx_ssao@/0/g"                     preferences
	sed -i "s/@gfx_terrain_quality@/0/g"          preferences
	sed -i "s/@gfx_texture_filtering@/0/g"        preferences
	sed -i "s/@gfx_texture_quality@/2/g"          preferences
	sed -i "s/@gfx_tree_quality@/0/g"             preferences
	sed -i "s/@gfx_unit_quality@/0/g"             preferences
	sed -i "s/@gfx_unit_size@/0/g"                preferences
	sed -i "s/@gfx_water_quality@/0/g"            preferences
	sed -i "s/@gfx_fog@/0/g"                      preferences
	sed -i "s/@gfx_lighting_quality@/0/g"         preferences
	sed -i "s/@porthole_3d@/0/g"                  preferences
elif [ $SETTING -eq "1" ]; then
	sed -i "s/@gfx_aa@/0/g"                       preferences
	sed -i "s/@gfx_building_quality@/1/g"         preferences
	sed -i "s/@gfx_effects_quality@/1/g"          preferences
	sed -i "s/@gfx_grass_quality@/1/g"            preferences
	sed -i "s/@gfx_shadow_quality@/1/g"           preferences
	sed -i "s/@gfx_sky_quality@/1/g"              preferences
	sed -i "s/@gfx_ssao@/0/g"                     preferences
	sed -i "s/@gfx_terrain_quality@/1/g"          preferences
	sed -i "s/@gfx_texture_filtering@/2/g"        preferences
	sed -i "s/@gfx_texture_quality@/2/g"          preferences
	sed -i "s/@gfx_tree_quality@/1/g"             preferences
	sed -i "s/@gfx_unit_quality@/1/g"             preferences
	sed -i "s/@gfx_unit_size@/1/g"                preferences
	sed -i "s/@gfx_water_quality@/1/g"            preferences
	sed -i "s/@gfx_fog@/0/g"                      preferences
	sed -i "s/@gfx_lighting_quality@/1/g"         preferences
	sed -i "s/@porthole_3d@/1/g"                  preferences
elif [ $SETTING -eq "2" ]; then
	sed -i "s/@gfx_aa@/0/g"                       preferences
	sed -i "s/@gfx_building_quality@/2/g"         preferences
	sed -i "s/@gfx_effects_quality@/2/g"          preferences
	sed -i "s/@gfx_grass_quality@/2/g"            preferences
	sed -i "s/@gfx_shadow_quality@/2/g"           preferences
	sed -i "s/@gfx_sky_quality@/2/g"              preferences
	sed -i "s/@gfx_ssao@/0/g"                     preferences
	sed -i "s/@gfx_terrain_quality@/2/g"          preferences
	sed -i "s/@gfx_texture_filtering@/3/g"        preferences
	sed -i "s/@gfx_texture_quality@/2/g"          preferences
	sed -i "s/@gfx_tree_quality@/2/g"             preferences
	sed -i "s/@gfx_unit_quality@/2/g"             preferences
	sed -i "s/@gfx_unit_size@/2/g"                preferences
	sed -i "s/@gfx_water_quality@/2/g"            preferences
	sed -i "s/@gfx_fog@/1/g"                      preferences
	sed -i "s/@gfx_lighting_quality@/1/g"         preferences
	sed -i "s/@porthole_3d@/1/g"                  preferences
elif [ $SETTING -eq "3" ]; then
	sed -i "s/@gfx_aa@/1/g"                       preferences
	sed -i "s/@gfx_building_quality@/3/g"         preferences
	sed -i "s/@gfx_effects_quality@/3/g"          preferences
	sed -i "s/@gfx_grass_quality@/3/g"            preferences
	sed -i "s/@gfx_shadow_quality@/3/g"           preferences
	sed -i "s/@gfx_sky_quality@/3/g"              preferences
	sed -i "s/@gfx_ssao@/1/g"                     preferences
	sed -i "s/@gfx_terrain_quality@/3/g"          preferences
	sed -i "s/@gfx_texture_filtering@/4/g"        preferences
	sed -i "s/@gfx_texture_quality@/2/g"          preferences
	sed -i "s/@gfx_tree_quality@/3/g"             preferences
	sed -i "s/@gfx_unit_quality@/3/g"             preferences
	sed -i "s/@gfx_unit_size@/3/g"                preferences
	sed -i "s/@gfx_water_quality@/3/g"            preferences
	sed -i "s/@gfx_fog@/1/g"                      preferences
	sed -i "s/@gfx_lighting_quality@/1/g"         preferences
	sed -i "s/@porthole_3d@/1/g"                  preferences
else
	echo "Failed to set graphics preset"
	exit 2
fi
