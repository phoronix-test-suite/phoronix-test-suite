#!/bin/bash -e
# Install Shadow of the Tomb Raider on Linux and generate launcher scripts and preference templates

# Base constants
#
export STEAM_GAME_ID=750920
export GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Shadow of the Tomb Raider"
export GAME_INSTALL_DIR_BASE="steamapps/common/Shadow of the Tomb Raider/"
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
if [ ! -f "${GAME_INSTALL_DIR}/ShadowOfTheTombRaider.sh" ]; then
    >&2 echo "Missing run script in install dir - ${GAME_INSTALL_DIR}/ShadowOfTheTombRaider.sh"
    exit 1
fi

# Gather the steam env variables the game runs with
#
echo "Gathering environment variables for game"
HOME="$DEBUG_REAL_HOME" steam steam://run/$STEAM_GAME_ID &
sleep 6
GAME_PID=$( pidof ShadowOfTheTombRaider | cut -d' ' -f1 )
if [ -z "$GAME_PID" ]; then
    echo "Could not find process ShadowOfTheTombRaider"
    exit 1
fi

echo '#!/bin/bash' > steam-env-vars.sh
echo "# Collected steam environment for Shadow of the Tomb Raider\n# PID : $GAME_PID" >> steam-env-vars.sh
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
killall -9 ShadowOfTheTombRaider
sleep 6



if [ -z "${STEAM_ACCOUNT_ID}" ]; then
    pushd "${GAME_PREFS}/SaveData/"
    STEAM_ACCOUNT_ID="$(ls |head -1)"
    popd
else
    STEAM_ACCOUNT_ID="Steam Saves (${STEAM_ACCOUNT_ID})"
fi

# Create the game launching script
#
echo "Generating run script"
cat > shadowofthetombraider <<- EOM
#!/bin/bash
# Generated run script for Shadow of the Tomb Raider
# Mon Oct 28 13:46:00 PDT 2019

# Source the steam runtime environment
#
. steam-env-vars.sh

# Clear the results dir

RESULTS_DIR="${GAME_PREFS}/SaveData"
if [ -d "\${RESULTS_DIR}" ]; then
    rm -R "\${RESULTS_DIR}"
    mkdir -p "\${RESULTS_DIR}"
fi

# Run the game
#
cd "${GAME_INSTALL_DIR}"
./ShadowOfTheTombRaider.sh

# Grab the output (most recent xml file results line)
#
# NOTE: There's also a location_machine_frametimes_datetime.txt file for more detailed results
cd "\${RESULTS_DIR}"
true > "\$LOG_FILE"
FPS_VALUES=\$(grep --text --no-filename FPS -R --include="*.txt" | head -3 | tr -d '\r' | paste -s )
echo "\${FPS_VALUES}" >> "\${LOG_FILE}"
EOM
chmod +x shadowofthetombraider


# Create the template preferences file
#
echo "Generating settings template"
cat > preferences.template.xml <<- EOM
<?xml version="1.0" encoding="UTF-8"?>
<registry>
    <key name="HKEY_CLASSES_ROOT">
    </key>
    <key name="HKEY_CURRENT_CONFIG">
    </key>
    <key name="HKEY_CURRENT_USER">
        <key name="Software">
            <key name="Eidos Montreal">
                <key name="Shadow of the Tomb Raider">
                    <value name="ChannelFormat" type="integer">0</value>
                    <value name="FirstRun" type="integer">0</value>
                    <value name="SteamLanguage" type="integer">0</value>
                    <value name="TextLanguage" type="integer">0</value>
                    <value name="VOLanguage" type="integer">0</value>
                    <key name="Graphics">
                        <key name="Defaults">
                            <value name="AA" type="integer">@gfx_aa@</value>
                            <value name="AmbientOcclusionQuality" type="integer">@gfx_ao@</value>
                            <value name="Bloom" type="integer">@gfx_bloom@</value>
                            <value name="DOFQuality" type="integer">@gfx_dof_quality@</value>
                            <value name="Fullscreen" type="integer">1</value>
                            <value name="FullscreenHeight" type="integer">@ScreenH@</value>
                            <value name="FullscreenWidth" type="integer">@ScreenW@</value>
                            <value name="LevelOfDetail" type="integer">@gfx_lod@</value>
                            <value name="MotionBlur" type="integer">@gfx_motion_blur@</value>
                            <value name="Preset" type="integer">@gfx_preset@</value>
                            <value name="ScreenSpaceContactShadows" type="integer">@gfx_contact_shadows@</value>
                            <value name="ScreenSpaceReflections" type="integer">@gfx_reflections@</value>
                            <value name="ShadowQuality" type="integer">@gfx_shadow_quality@</value>
                            <value name="Tessellation" type="integer">@gfx_tessellation@</value>
                            <value name="TextureFiltering" type="integer">@gfx_tex_filter@</value>
                            <value name="TextureQuality" type="integer">@gfx_tex_quality@</value>
                            <value name="TressFX" type="integer">@gfx_tressfx@</value>
                            <value name="VolumetricLighting" type="integer">@gfx_volumetric@</value>
                        </key>
                    </key>
                </key>
            </key>
            <key name="Feral Interactive">
                <key name="Shadow of the Tomb Raider">
                    <key name="Setup">
                        <value name="AllowPausing" type="integer">0</value>
                        <value name="AllowSendCrashReports" type="integer">0</value>
                        <value name="AllowSendUsageData" type="integer">0</value>
                        <value name="AvoidSwapInjectionDuringPGOW" type="integer">1</value>
                        <value name="ConstrainLiveWindowResize" type="integer">1</value>
                        <value name="DoneMinOS" type="integer">0</value>
                        <value name="DonePromotional" type="integer">0</value>
                        <value name="DoneUnsupported" type="integer">0</value>
                        <value name="GameOptionsDialogShouldShow" type="integer">0</value>
                        <value name="GameOptionsDialogShouldShowBigPicture" type="integer">0</value>
                        <value name="GameOptionsDialogShown" type="integer">1</value>
                        <value name="GameSelected" type="integer">0</value>
                        <value name="IgnoreLanguageHeadings" type="integer">1</value>
                        <value name="LiveWindowResizePercentage" type="integer">0</value>
                        <value name="LiveWindowResizeThreshold" type="integer">0</value>
                        <value name="MinWindowedHeight" type="integer">0</value>
                        <value name="MinWindowedWidth" type="integer">0</value>
                        <value name="PauseMoviesOnPause" type="integer">0</value>
                        <value name="PauseOnSuspend" type="integer">0</value>
                        <value name="PauseSoundOnPause" type="integer">0</value>
                        <value name="PauseTimersOnPause" type="integer">0</value>
                        <value name="RestoreMouseOnSuspend" type="integer">1</value>
                        <value name="RestoreResOnSuspend" type="integer">1</value>
                        <value name="ScreenH" type="integer">0</value>
                        <value name="ScreenW" type="integer">0</value>
                        <value name="ShowAssertAlerts" type="integer">0</value>
                        <value name="ShowLevelSelect" type="integer">1</value>
                        <value name="ShowTheHideDockCheckbox" type="integer">0</value>
                        <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                        <value name="SoftwareUpdatedCanCheck" type="integer">1</value>
                        <value name="SpecificationFirstLaunchCheck" type="integer">0</value>
                        <key name="SpecificationAlerts">
                            <value name="DriverOrHardwareUnsupported" type="integer">1</value>
                            <value name="LnxCPUGovernorSubOptimal" type="integer">1</value>
                        </key>               
                    </key>
                </key>
            </key>
            <key name="MacDoze">
                <key name="Config">
                    <value name="ClearSavesEnabled" type="integer">0</value>
                    <value name="DisableClearSaveDataAlert" type="integer">0</value>
                    <value name="ExtraCommandLine" type="string">--feral-benchmark</value>
                    <value name="ExtraCommandLineEnabled" type="integer">1</value>
                </key>
            </key>
        </key>
    </key>
</registry>
EOM