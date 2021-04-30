#!/bin/bash -e
# Install Total War Three Kingdoms on Linux and generate launcher scripts and preference templates

# Base constants
#
export STEAM_GAME_ID=779340
export GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Three Kingdoms"
export GAME_INSTALL_DIR_BASE="steamapps/common/Total War THREE KINGDOMS/"
export DEFAULT_STEAM_INSTALL_BASE="$DEBUG_REAL_HOME/.steam/steam"


# Try and install the game in case it isn't already
#
echo "Ensuring game is installed"
HOME="$DEBUG_REAL_HOME" steam "steam://install/$STEAM_GAME_ID"


# Work out the steam install directory
#
export CONFIG_PATH="$DEBUG_REAL_HOME/.steam/steam/config/config.vdf"
echo "Searching ${CONFIG_PATH} for install directories"
_INSTALL_PATHS=$( awk '/BaseInstallFolder/ { gsub(/"/, "", $2); print $2 }' "${CONFIG_PATH}" )

# Find one that contains the game
while read -r STEAM_PATH; do
    _NEW_FULL_PATH="${STEAM_PATH}/${GAME_INSTALL_DIR_BASE}"
    echo "Checking for game install: ${_NEW_FULL_PATH}"
    if [ -d "${_NEW_FULL_PATH}" ]; then
        echo "Found game install: ${_NEW_FULL_PATH}"
        export GAME_INSTALL_DIR="${_NEW_FULL_PATH}"
    fi
done <<< "${_INSTALL_PATHS}"

# Allow the default location as well
if [ ! -d "${GAME_INSTALL_DIR}" ]; then
    export GAME_INSTALL_DIR="${DEFAULT_STEAM_INSTALL_BASE}/${GAME_INSTALL_DIR_BASE}"
    echo "Using default directory for game install: ${GAME_INSTALL_DIR}"
fi

# Bail if we still couldn't find the game
if [ ! -f "${GAME_INSTALL_DIR}/ThreeKingdoms.sh" ]; then
    >&2 echo "Missing run script in install dir - ${GAME_INSTALL_DIR}/ThreeKingdoms.sh"
    exit 1
fi

# Gather the steam env variables the game runs with
#
echo "Gathering environment variables for game"
HOME="$DEBUG_REAL_HOME" steam steam://run/$STEAM_GAME_ID &
sleep 6
GAME_PID=$( pidof ThreeKingdoms | cut -d' ' -f1 )
if [ -z "$GAME_PID" ]; then
    echo "Could not find process ThreeKingdoms"
    exit 1
fi

echo '#!/bin/bash' > steam-env-vars.sh
echo "# Collected steam environment for Total War: Three Kingdoms\n# PID : $GAME_PID" >> steam-env-vars.sh
while read -rd $'\0' ENV ; do
    NAME=$(echo "$ENV" | cut -zd= -f1); VAL=$(echo "$ENV" | cut -zd= -f2)
    case $NAME in
	*DBUS*) true
	;;
	*)
        echo "export $NAME=\"$VAL\""
	;;
    esac
done < "/proc/$GAME_PID/environ" >> steam-env-vars.sh
killall -9 ThreeKingdoms
sleep 6



if [ -z "${STEAM_ACCOUNT_ID}" ]; then
    pushd "${GAME_PREFS}/SaveData/"
    STEAM_ACCOUNT_ID="$(ls |head -1)"
    popd
else
    STEAM_ACCOUNT_ID="Steam Saves (${STEAM_ACCOUNT_ID})"
fi

RESULTS_PREFIX="${GAME_PREFS}/VFS/User/AppData/Roaming/The Creative Assembly/ThreeKingdoms/"


# Create the game launching script
#
echo "Generating run script"
cat > twtk.sh <<- EOM
#!/bin/bash
# Generated run script for Total War: Three Kingdoms
# $( date )

# Source the steam runtime environment
#
. steam-env-vars.sh

# Run the game
#
cd "${GAME_INSTALL_DIR}/bin"
./ThreeKingdoms

# Grab the output (most recent non _frametimes txt file)
RESULTS_DIR="${RESULTS_PREFIX}benchmarks/"
mkdir -p "\${RESULTS_DIR}"
cd "\${RESULTS_DIR}"
true > "\$LOG_FILE"
FPS_VALUES=\$( grep -A3 "frames per second" \$(ls -t | grep -P "benchmark_.*[0-9]+.txt" | head -n 1) | tail -n 3 )
cat benchmark_*.txt >>  "\$LOG_FILE"
echo "\${FPS_VALUES}" >> "\$LOG_FILE"
EOM
chmod +x twtk.sh


# Create the template preferences file
#
echo "Generating settings template"
cat > preferences.template.xml <<- EOM
<?xml version="1.0" encoding="UTF-8"?>
<registry>
    <key name="HKEY_CURRENT_USER">
        <key name="Software">
            <key name="Feral Interactive">
                <key name="Three Kingdoms">
                    <key name="Setup">
                        <!-- resolution -->
                        <value name="ScreenH" type="integer">@screen_height@</value>
                        <value name="ScreenW" type="integer">@screen_width@</value>
                        
                        <!-- disable pausing -->
                        <value name="AllowPausing" type="integer">0</value>
                        <value name="PauseMoviesOnPause" type="integer">0</value>
                        <value name="PauseOnSuspend" type="integer">0</value>
                        <value name="PauseSoundOnPause" type="integer">0</value>
                        <value name="PauseTimersOnPause" type="integer">0</value>
                        <value name="DisableAllMods" type="integer">1</value>
                        <value name="AddSteamCloudAlias" type="integer">1</value>
                        <value name="AllowSendCrashReports" type="integer">0</value>
                        <value name="AllowSendUsageData" type="integer">0</value>


                        <!-- Don't show splash screen -->
                        <value name="GameOptionsDialogLastTab" type="integer">60000</value>
                        <value name="GameOptionsDialogShouldShow" type="integer">0</value>
                        <value name="GameOptionsDialogShouldShowBigPicture" type="integer">0</value>
                        <value name="GameOptionsDialogShown" type="integer">1</value>

                        <!-- Disable Splash Screen Warnings -->
                        <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                        
                        <!-- Skip default settings -->
                        <value name="SkipDefaultSettings" type="integer">1</value>
                        <value name="SkipDriverWarnings" type="integer">1</value>
                        <value name="SkipOSWarnings" type="integer">1</value>

                        <key name="GraphicsSettings">
                            <value name="gfx_aa" type="integer">@gfx_aa@</value>
                            <value name="gfx_aa_initial" type="integer">1</value>
                            <value name="gfx_alpha_blend" type="integer">0</value>
                            <value name="gfx_blood_effects" type="integer">1</value>
                            <value name="gfx_building_quality" type="integer">@gfx_building_quality@</value>
                            <value name="gfx_depth_of_field" type="integer">0</value>
                            <value name="gfx_distortion" type="integer">1</value>
                            <value name="gfx_effects_quality" type="integer">@gfx_effects_quality@</value>
                            <value name="gfx_first_run" type="integer">0</value>
                            <value name="gfx_gamma_setting" type="binary">0000000000000040</value>
                            <value name="gfx_gpu_select" type="integer">0</value>
                            <value name="gfx_grass_quality" type="integer">@gfx_grass_quality@</value>
                            <value name="gfx_lighting_quality" type="integer">@gfx_lighting_quality@</value>
                            <value name="gfx_post_mode" type="integer">0</value>
                            <value name="gfx_resolution_scale" type="binary">000000000000f03f</value>
                            <value name="gfx_screen_space_reflections" type="integer">0</value>
                            <value name="gfx_shadow_quality" type="integer">@gfx_shadow_quality@</value>
                            <value name="gfx_sharpening" type="integer">1</value>
                            <value name="gfx_sky_quality" type="integer">@gfx_sky_quality@</value>
                            <value name="gfx_ssao" type="integer">@gfx_ssao@</value>
                            <value name="gfx_terrain_quality" type="integer">@gfx_terrain_quality@</value>
                            <value name="gfx_tesselation" type="integer">0</value>
                            <value name="gfx_texture_filtering" type="integer">@gfx_texture_filtering@</value>
                            <value name="gfx_texture_quality" type="integer">@gfx_texture_quality@</value>
                            <value name="gfx_tree_quality" type="integer">@gfx_tree_quality@</value>
                            <value name="gfx_unit_quality" type="integer">@gfx_unit_quality@</value>
                            <value name="gfx_unit_size" type="integer">@gfx_unit_size@</value>
                            <value name="gfx_unlimited_video_memory" type="integer">0</value>
                            <value name="gfx_vignette" type="integer">0</value>
                            <value name="gfx_vsync" type="integer">0</value>
                            <value name="gfx_water_quality" type="integer">@gfx_water_quality@</value>
                            <value name="porthole_3d" type="integer">@porthole_3d@</value>
                        </key>
                        <key name="SpecificationAlerts">
                            <value name="LnxCPUGovernorSubOptimal" type="integer">1</value>
                            <value name="ModsHaveBeenDisabled_1_0_5" type="integer">1</value>
                        </key>
                    </key>
                </key>
            </key>
            
            <key name="MacDoze">
                <key name="Config">
                    <value name="ExtraCommandLine" type="string">game_startup_mode benchmark_auto_quit script/benchmarks/@benchmark_name@</value>
                    <value name="ExtraCommandLineEnabled" type="integer">1</value>
                </key>
            </key>
        </key>
    </key>
</registry>
EOM
