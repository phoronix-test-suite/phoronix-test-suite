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
GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Shadow of the Tomb Raider"

# Set up (and back up) the game preferences files
DATETIME=$( date +%Y-%d-%m-%H-%M )
echo "$DATETIME" > /tmp/sotr-bkp-dt
GAME_PREFS_BKP="${GAME_PREFS}.pts-$DATETIME-bkp"
cp -r "$GAME_PREFS" "$GAME_PREFS_BKP"

if [ -f "$GAME_PREFS/preferences" ]; then
    rm "$GAME_PREFS/preferences"
fi
# clear previous runs
rm -rf "${GAME_PREFS}.pts*"

# Set up the files to use

cp "preferences.template.xml" "$GAME_PREFS/preferences"

# Replace the resolutions
cd "$GAME_PREFS" || exit

# Replace settings with those chosen
sed -i "s/@ScreenW@/$WIDTH/g"          preferences
sed -i "s/@ScreenH@/$HEIGHT/g"         preferences
sed -i "s/@gfx_aa@/$AA/g"              preferences

if [ $SETTING = "Lowest" ]; then
	sed -i "s/@gfx_ao@/0/g"            preferences
	sed -i "s/@gfx_bloom@/0/g"                preferences
	sed -i "s/@gfx_dof_quality@/0/g"	  preferences
	sed -i "s/@gfx_lod@/0/g"                  preferences
	sed -i "s/@gfx_motion_blur@/0/g"          preferences
	sed -i "s/@gfx_contact_shadows@/0/g"      preferences
	sed -i "s/@gfx_preset@/0/g"		  preferences
	sed -i "s/@gfx_reflections@/0/g"          preferences
	sed -i "s/@gfx_shadow_quality@/0/g"       preferences
	sed -i "s/@gfx_tessellation@/0/g"         preferences
	sed -i "s/@gfx_tex_filter@/0/g"           preferences
	sed -i "s/@gfx_tex_quality@/0/g"          preferences
	sed -i "s/@gfx_tressfx@/0/g"              preferences
    sed -i "s/@gfx_volumetric@/0/g"           preferences
elif [ $SETTING = "Low" ]; then
	sed -i "s/@gfx_ao@/0/g"                   preferences
	sed -i "s/@gfx_bloom@/1/g"                preferences
	sed -i "s/@gfx_dof_quality@/0/g"	  preferences
	sed -i "s/@gfx_lod@/1/g"                  preferences
	sed -i "s/@gfx_motion_blur@/0/g"          preferences
	sed -i "s/@gfx_contact_shadows@/0/g"      preferences
	sed -i "s/@gfx_reflections@/0/g"          preferences
	sed -i "s/@gfx_preset@/1/g"		  preferences
	sed -i "s/@gfx_shadow_quality@/1/g"       preferences
	sed -i "s/@gfx_tessellation@/0/g"         preferences
	sed -i "s/@gfx_tex_filter@/0/g"           preferences
	sed -i "s/@gfx_tex_quality@/0/g"          preferences
	sed -i "s/@gfx_tressfx@/0/g"              preferences
    sed -i "s/@gfx_volumetric@/1/g"           preferences
elif [ $SETTING = "Medium" ]; then
	sed -i "s/@gfx_ao@/1/g"                   preferences
	sed -i "s/@gfx_bloom@/1/g"                preferences
	sed -i "s/@gfx_dof_quality@/1/g"	  preferences
	sed -i "s/@gfx_lod@/2/g"                  preferences
	sed -i "s/@gfx_motion_blur@/1/g"          preferences
	sed -i "s/@gfx_contact_shadows@/0/g"      preferences
	sed -i "s/@gfx_reflections@/1/g"          preferences
	sed -i "s/@gfx_preset@/2/g"	          preferences
	sed -i "s/@gfx_shadow_quality@/1/g"       preferences
	sed -i "s/@gfx_tessellation@/0/g"         preferences
	sed -i "s/@gfx_tex_filter@/1/g"           preferences
	sed -i "s/@gfx_tex_quality@/1/g"          preferences
	sed -i "s/@gfx_tressfx@/1/g"              preferences
    sed -i "s/@gfx_volumetric@/1/g"           preferences
elif [ $SETTING = "High" ]; then
	sed -i "s/@gfx_ao@/1/g"                   preferences
	sed -i "s/@gfx_bloom@/1/g"                preferences
	sed -i "s/@gfx_dof_quality@/1/g"	  preferences
	sed -i "s/@gfx_lod@/2/g"                  preferences
	sed -i "s/@gfx_motion_blur@/1/g"          preferences
	sed -i "s/@gfx_contact_shadows@/0/g"      preferences
	sed -i "s/@gfx_reflections@/1/g"          preferences
	sed -i "s/@gfx_shadow_quality@/2/g"       preferences
	sed -i "s/@gfx_preset@/3/g"		  preferences
	sed -i "s/@gfx_tessellation@/1/g"         preferences
	sed -i "s/@gfx_tex_filter@/2/g"           preferences
	sed -i "s/@gfx_tex_quality@/2/g"          preferences
	sed -i "s/@gfx_tressfx@/1/g"              preferences
    sed -i "s/@gfx_volumetric@/1/g"           preferences
elif [ $SETTING = "Highest" ]; then
	sed -i "s/@gfx_ao@/1/g"                   preferences
	sed -i "s/@gfx_bloom@/1/g"                preferences
	sed -i "s/@gfx_dof_quality@/2/g"	      preferences
	sed -i "s/@gfx_lod@/3/g"                  preferences
	sed -i "s/@gfx_motion_blur@/1/g"          preferences
	sed -i "s/@gfx_contact_shadows@/1/g"      preferences
	sed -i "s/@gfx_reflections@/1/g"          preferences
	sed -i "s/@gfx_preset@/4/g"		          preferences
	sed -i "s/@gfx_shadow_quality@/3/g"       preferences
	sed -i "s/@gfx_tessellation@/1/g"         preferences
	sed -i "s/@gfx_tex_filter@/3/g"           preferences
	sed -i "s/@gfx_tex_quality@/3/g"          preferences
	sed -i "s/@gfx_tressfx@/1/g"              preferences
    sed -i "s/@gfx_volumetric@/1/g"           preferences
else
	echo "Failed to set graphics preset"
	exit 2
fi
