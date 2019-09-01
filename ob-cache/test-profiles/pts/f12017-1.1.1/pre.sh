#!/bin/bash
set -o xtrace
exec > /tmp/test
exec 2>&1
export HOME=$DEBUG_REAL_HOME

# Game identity
FERAL_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive"
GAME_PREFS="$FERAL_PREFS/F1 2017"

# Input settings
WIDTH=$1
HEIGHT=$2
SETTING=$3
AA=$4

# Calc the graphics settings
case $SETTING in
ultralow)
	gfxconfig_advanced_smoke_shadows=off
	gfxconfig_ambient_occlusion=off
	gfxconfig_crowd=low
	gfxconfig_dynamic_hair=low
	gfxconfig_ground_cover=low
	gfxconfig_hdr_mode=0
	gfxconfig_lighting=low
	gfxconfig_mirrors=low
	gfxconfig_particles=off
	gfxconfig_postprocess=low
	gfxconfig_screen_space_reflections=off
	gfxconfig_shadows=low
	gfxconfig_skidmarks=off
	gfxconfig_skidmarks_blending=off
	gfxconfig_smoke_shadows=off
	gfxconfig_ssrt_shadows=off
	gfxconfig_texture_streaming=ultralow
	gfxconfig_vehicle_reflections=ultralow
	gfxconfig_weather_effects=low
	;;
low)
	gfxconfig_advanced_smoke_shadows=off
	gfxconfig_ambient_occlusion=off
	gfxconfig_crowd=low
	gfxconfig_dynamic_hair=low
	gfxconfig_ground_cover=low
	gfxconfig_hdr_mode=0
	gfxconfig_lighting=low
	gfxconfig_mirrors=low
	gfxconfig_particles=low
	gfxconfig_postprocess=low
	gfxconfig_screen_space_reflections=off
	gfxconfig_shadows=low
	gfxconfig_skidmarks=off
	gfxconfig_skidmarks_blending=off
	gfxconfig_smoke_shadows=low
	gfxconfig_ssrt_shadows=off
	gfxconfig_texture_streaming=low
	gfxconfig_vehicle_reflections=low
	gfxconfig_weather_effects=low
	;;
medium)
	gfxconfig_advanced_smoke_shadows=off
	gfxconfig_ambient_occlusion=off
	gfxconfig_crowd=low
	gfxconfig_dynamic_hair=high
	gfxconfig_ground_cover=medium
	gfxconfig_hdr_mode=0
	gfxconfig_lighting=medium
	gfxconfig_mirrors=medium
	gfxconfig_particles=medium
	gfxconfig_postprocess=medium
	gfxconfig_screen_space_reflections=medium
	gfxconfig_shadows=medium
	gfxconfig_skidmarks=low
	gfxconfig_skidmarks_blending=off
	gfxconfig_smoke_shadows=low
	gfxconfig_ssrt_shadows=off
	gfxconfig_texture_streaming=medium
	gfxconfig_vehicle_reflections=medium
	gfxconfig_weather_effects=medium
	;;
high)
	gfxconfig_advanced_smoke_shadows=off
	gfxconfig_ambient_occlusion=on
	gfxconfig_crowd=high
	gfxconfig_dynamic_hair=high
	gfxconfig_ground_cover=high
	gfxconfig_hdr_mode=0
	gfxconfig_lighting=high
	gfxconfig_mirrors=high
	gfxconfig_particles=high
	gfxconfig_postprocess=high
	gfxconfig_screen_space_reflections=high
	gfxconfig_shadows=high
	gfxconfig_skidmarks=high
	gfxconfig_skidmarks_blending=off
	gfxconfig_smoke_shadows=high
	gfxconfig_ssrt_shadows=off
	gfxconfig_texture_streaming=high
	gfxconfig_vehicle_reflections=high
	gfxconfig_weather_effects=high
	;;
ultrahigh)
	gfxconfig_advanced_smoke_shadows=off
	gfxconfig_ambient_occlusion=hbao
	gfxconfig_crowd=high
	gfxconfig_dynamic_hair=high
	gfxconfig_ground_cover=ultra
	gfxconfig_hdr_mode=0
	gfxconfig_lighting=high
	gfxconfig_mirrors=ultra
	gfxconfig_particles=high
	gfxconfig_postprocess=high
	gfxconfig_screen_space_reflections=ultra
	gfxconfig_shadows=ultra
	gfxconfig_skidmarks=high
	gfxconfig_skidmarks_blending=off
	gfxconfig_smoke_shadows=high
	gfxconfig_ssrt_shadows=off
	gfxconfig_texture_streaming=ultra
	gfxconfig_vehicle_reflections=ultra
	gfxconfig_weather_effects=ultra
	;;
esac

# Set up (and back up) the game preferences files
DATETIME=$( date +%Y-%d-%m-%H-%M )
echo "$DATETIME" >/tmp/f12017-bkp-dt
GAME_PREFS_BKP="${FERAL_PREFS}/F1 2017.pts-$DATETIME-bkp"
cp -r "$GAME_PREFS" "$GAME_PREFS_BKP"

# clear previous runs
rm -rf "${GAME_PREFS:?}"
mkdir -p "${GAME_PREFS}"

# Set up the files to use
cp "preferences.template.xml" "$GAME_PREFS/preferences"
mkdir -p "$GAME_PREFS/SaveData/feral_bench/"
cp "basic_benchmark.xml" "$GAME_PREFS/SaveData/feral_bench/"

# Replace the resolutions
cd "$GAME_PREFS" || exit

# Replace the resolution with the one selected
sed -i "s/RESOLUTION_WIDTH/$WIDTH/g" preferences
sed -i "s/RESOLUTION_HEIGHT/$HEIGHT/g" preferences
sed -i "s/ANTIALIASING/$AA/g" preferences
sed -i "s/ADVANCED_SMOKE_SHADOWS/$gfxconfig_advanced_smoke_shadows/g" preferences
sed -i "s/AMBIENT_OCCLUSION/$gfxconfig_ambient_occlusion/g" preferences
sed -i "s/CROWD/$gfxconfig_crowd/g" preferences
sed -i "s/DYNAMIC_HAIR/$gfxconfig_dynamic_hair/g" preferences
sed -i "s/GROUND_COVER/$gfxconfig_ground_cover/g" preferences
sed -i "s/HDR_MODE/$gfxconfig_hdr_mode/g" preferences
sed -i "s/LIGHTING/$gfxconfig_lighting/g" preferences
sed -i "s/MIRRORS/$gfxconfig_mirrors/g" preferences
sed -i "s/PARTICLES/$gfxconfig_particles/g" preferences
sed -i "s/POSTPROCESS/$gfxconfig_postprocess/g" preferences
sed -i "s/SCREEN_SPACE_REFLECTIONS/$gfxconfig_screen_space_reflections/g" preferences
sed -i "s/SMOKE_SHADOWS/$gfxconfig_smoke_shadows/g" preferences
sed -i "s/SSRT_SHADOWS/$gfxconfig_ssrt_shadows/g" preferences
sed -i "s/SHADOWS/$gfxconfig_shadows/g" preferences
sed -i "s/SKIDMARKS_BLENDING/$gfxconfig_skidmarks_blending/g" preferences
sed -i "s/SKIDMARKS/$gfxconfig_skidmarks/g" preferences
sed -i "s/TEXTURE_STREAMING/$gfxconfig_texture_streaming/g" preferences
sed -i "s/VEHICLE_REFLECTIONS/$gfxconfig_vehicle_reflections/g" preferences
sed -i "s/WEATHER_EFFECTS/$gfxconfig_weather_effects/g" preferences



