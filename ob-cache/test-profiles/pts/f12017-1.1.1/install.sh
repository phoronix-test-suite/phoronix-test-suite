#!/bin/bash
export STEAM_GAME_ID=515220
export GAME_BINARY="F12017"
export HOME="$DEBUG_REAL_HOME"
export GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/F1 2017"

HOME="$DEBUG_REAL_HOME" steam "steam://install/$STEAM_GAME_ID"
mkdir -p "$DEBUG_REAL_HOME/.local/share/feral-interactive/F1 2017"

# Gather the steam env variables the game runs with
steam steam://run/$STEAM_GAME_ID &
sleep 6
GAME_PID=$( pgrep "$GAME_BINARY" | tail -1 )
echo '#!/bin/sh' > steam-env-vars.sh
echo "# PID: $GAME_PID" >> steam-env-vars.sh
while read -rd $'\0' ENV ; do
	NAME=$(echo "$ENV" | cut -d= -f1); VAL=$(echo "$ENV" | cut -d= -f2)
	echo "export $NAME=\"$VAL\""
done < "/proc/$GAME_PID/environ" >> steam-env-vars.sh
chmod +x steam-env-vars.sh

# We're done, kill the game, sleep for a little to allow it to die
killall -9 $GAME_BINARY
sleep 5

# Create the game launch script
cat > f12017 <<- EOM
#!/bin/bash
. steam-env-vars.sh

# cd to steam install location (if this fails, check where you installed F1 to)
cd "$DEBUG_REAL_HOME/.steam/steam/steamapps/common/F1 2017/" || exit

# Run the game as if from steam
./F12017.sh

# Grab the output (most recent xml file results line)
cd "$GAME_PREFS/SaveData/feral_bench/"
cat \$( ls -t *.xml | head -1 ) | grep results | sed "s/\\"/ /g"  > "\$LOG_FILE"
EOM
chmod +x f12017

# Create the benchmark file
# This matches the default benchmark in game
cat > basic_benchmark.xml <<- EOM
<?xml version="1.0" standalone="yes" ?>
<config infinite_loop="false" hardware_settings="hardware_settings_config.xml" season="2017" show_fps="true" >
  <track name="melbourne" laps="1" weather="clear" num_cars="22" camera_mode="cycle" />
</config>
EOM

# Create the preferences file
cat > preferences.template.xml <<- EOM
<?xml version="1.0" encoding="UTF-8"?>
<registry>
    <key name="HKEY_CLASSES_ROOT">
    </key>
    <key name="HKEY_CURRENT_CONFIG">
    </key>
    <key name="HKEY_CURRENT_USER">
        <key name="Software">
            <key name="Feral Interactive">
                <key name="F1 2017">
                    <key name="Setup">
                        <!-- gfs_config settings -->
                        <value name="gfxconfig_advanced_smoke_shadows" type="string">ADVANCED_SMOKE_SHADOWS</value>
                        <value name="gfxconfig_ambient_occlusion" type="string">AMBIENT_OCCLUSION</value>
                        <value name="gfxconfig_crowd" type="string">CROWD</value>
                        <value name="gfxconfig_dynamic_hair" type="string">DYNAMIC_HAIR</value>
                        <value name="gfxconfig_ground_cover" type="string">GROUND_COVER</value>
                        <value name="gfxconfig_hdr_mode" type="integer">HDR_MODE</value>
                        <value name="gfxconfig_lighting" type="string">LIGHTING</value>
                        <value name="gfxconfig_mirrors" type="string">MIRRORS</value>
                        <value name="gfxconfig_particles" type="string">PARTICLES</value>
                        <value name="gfxconfig_postprocess" type="string">POSTPROCESS</value>
                        <value name="gfxconfig_screen_space_reflections" type="string">SCREEN_SPACE_REFLECTIONS</value>
                        <value name="gfxconfig_shadows" type="string">SHADOWS</value>
                        <value name="gfxconfig_skidmarks" type="string">SKIDMARKS</value>
                        <value name="gfxconfig_skidmarks_blending" type="string">SKIDMARKS_BLENDING</value>
                        <value name="gfxconfig_smoke_shadows" type="string">SMOKE_SHADOWS</value>
                        <value name="gfxconfig_ssrt_shadows" type="string">SSRT_SHADOWS</value>
                        <value name="gfxconfig_texture_streaming" type="string">TEXTURE_STREAMING</value>
                        <value name="gfxconfig_vehicle_reflections" type="string">VEHICLE_REFLECTIONS</value>
                        <value name="gfxconfig_weather_effects" type="string">WEATHER_EFFECTS</value>

                        <value name="gfxconfig_antialiasing" type="string">ANTIALIASING</value>

                        <!-- screen resolutions -->
                        <value name="ScreenH" type="integer">RESOLUTION_HEIGHT</value>
                        <value name="ScreenW" type="integer">RESOLUTION_WIDTH</value>
                        <value name="FullScreen" type="integer">1</value>

                        <!-- disable all forms of pausing -->
                        <value name="PauseMoviesOnPause" type="integer">0</value>
                        <value name="PauseOnSuspend" type="integer">0</value>
                        <value name="PauseSoundOnPause" type="integer">0</value>
                        <value name="PauseTimersOnPause" type="integer">0</value>
                        <value name="AllowPausing" type="integer">0</value>

                        <!-- don't send data without consent -->
                        <value name="AllowSendUsageData" type="integer">0</value>

                        <!-- stop any potential warnings -->
                        <value name="GameOptionsDialogShouldShow" type="integer">0</value>
                        <value name="GameOptionsDialogShouldShowBigPicture" type="integer">0</value>
                        <value name="GameOptionsDialogShown" type="integer">1</value>
                        <value name="SeenSpecificationAlertUUIDDefaultHardwareSpecificationsClass" type="integer">1</value>
                        <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                        <value name="SoftwareUpdatedCanCheck" type="integer">0</value>
                        <key name="SpecificationAlerts">
                            <value name="LnxDistributionUnsupported" type="integer">1</value>
                            <value name="NewRecommendedSettingsAlertShown" type="integer">1</value>
                        </key>
                    </key>
                </key>
            </key>
            <key name="MacDoze">
                <key name="Config">
                    <value name="ExtraCommandLine" type="string">-benchmark basic_benchmark.xml</value>
                    <value name="ExtraCommandLineEnabled" type="integer">1</value>
                    <value name="GameCannotQuit" type="integer">1</value>
                </key>
            </key>
        </key>
    </key>
</registry>
EOM
