#!/bin/bash -e
# Install Rise of the Tomb Raider on Linux and generate launcher scripts and preference templates

# Base constants
#
export HOME="$DEBUG_REAL_HOME"
export STEAM_GAME_ID=391220
export GAME_PREFS="$DEBUG_REAL_HOME/.local/share/feral-interactive/Rise of the Tomb Raider"
export GAME_INSTALL_DIR_BASE="steamapps/common/Rise of the Tomb Raider/"
export DEFAULT_STEAM_INSTALL_BASE="$HOME/.steam/steam"


# Try and install the game in case it isn't already
#
HOME="$DEBUG_REAL_HOME" steam "steam://install/$STEAM_GAME_ID"


# Work out the steam install directory
#
export CONFIG_PATH="$HOME/.steam/steam/config/config.vdf"
_INSTALL_PATHS=$( awk '/BaseInstallFolder/ { gsub(/"/, "", $2); print $2 }' "${CONFIG_PATH}" )

# Find one that contains the game
while read -r STEAM_PATH; do
	_NEW_FULL_PATH="${STEAM_PATH}/${GAME_INSTALL_DIR_BASE}"
	if [ -d "${_NEW_FULL_PATH}" ]; then
		export GAME_INSTALL_DIR="${_NEW_FULL_PATH}"
	fi
done <<< "${_INSTALL_PATHS}"

# Allow the default location as well
if [ ! -d "${GAME_INSTALL_DIR}" ]; then
	export GAME_INSTALL_DIR="${DEFAULT_STEAM_INSTALL_BASE}/${GAME_INSTALL_DIR_BASE}"
fi

# Bail if we still couldn't find the game
if [ ! -f "${GAME_INSTALL_DIR}/RiseOfTheTombRaider.sh" ]; then
	>&2 echo "Missing run script in install dir - ${GAME_INSTALL_DIR}/RiseOfTheTombRaider.sh"
	exit 1
fi


# Gather the steam env variables the game runs with
#
steam steam://run/$STEAM_GAME_ID &
sleep 6
GAME_PID=$( pgrep RiseOfTheTomb | tail -1 )
echo '#!/bin/sh' > steam-env-vars.sh
echo "# Collected steam environment for Rise of the Tomb Raider\n# PID : $GAME_PID" >> steam-env-vars.sh
while read -rd $'\0' ENV ; do
	NAME=$(echo "$ENV" | cut -d= -f1); VAL=$(echo "$ENV" | cut -d= -f2)
	echo "export $NAME=\"$VAL\""
done < "/proc/$GAME_PID/environ" >> steam-env-vars.sh
killall -9 RiseOfTheTombRaider
sleep 6


# Create the game launching script
#
cat > riseofthetombraider <<- EOM
#!/bin/bash
# Generated run script for Rise of the Tomb Raider
# $( date )

# Source the steam runtime environment
#
. steam-env-vars.sh

# Run the game
#
cd "${GAME_INSTALL_DIR}"
./RiseOfTheTombRaider.sh

# Grab the output (most recent xml file results line)
#
# NOTE: There's also a location_machine_frametimes_datetime.txt file for more detailed results
RESULTS_DIR="${GAME_PREFS}/VFS/User/AppData/Roaming/Rise of the Tomb Raider/"
mkdir -p "\${RESULTS_DIR}"
cd "\${RESULTS_DIR}"
BENCH_NAMES="SpineOfTheMountain ProphetsTomb GeothermalValley"
true > "\$LOG_FILE"
for BENCH in \$BENCH_NAMES
do
	FPS_VALUES=\$( grep --text --no-filename FPS \${BENCH}_*.txt | head -3 | tr -d '\\r' | paste -s )
	echo "\${BENCH}: \${FPS_VALUES}" >> "\$LOG_FILE"
done
EOM
chmod +x riseofthetombraider


# Create the template preferences file
#
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
                <key name="Rise of the Tomb Raider">
                    <key name="Setup">
                        <!-- screen resolutions -->
                        <value name="ScreenH" type="integer">@ScreenH@</value>
                        <value name="ScreenW" type="integer">@ScreenW@</value>

                        <value name="FullScreen" type="integer">1</value>

                        <value name="QuitAfterBenchmark" type="integer">1</value>

                        <!-- disable all forms of pausing -->
                        <value name="PauseMoviesOnPause" type="integer">0</value>
                        <value name="PauseOnSuspend" type="integer">0</value>
                        <value name="PauseSoundOnPause" type="integer">0</value>
                        <value name="PauseTimersOnPause" type="integer">0</value>
                        <value name="AllowPausing" type="integer">0</value>

                        <!-- don't send data without consent -->
                        <value name="AllowSendUsageData" type="integer">0</value>

                        <!-- stop the game options dialogue showing -->
                        <value name="GameOptionsDialogShouldShow" type="integer">0</value>
                        <value name="GameOptionsDialogShouldShowBigPicture" type="integer">0</value>
                        <value name="GameOptionsDialogShown" type="integer">1</value>

                        <!-- stop any potential warnings -->
                        <value name="SoftwareUpdatedAskedUser" type="integer">1</value>
                        <value name="SoftwareUpdatedCanCheck" type="integer">0</value>
                        <value name="SkipDriverWarnings" type="integer">1</value>
                        <value name="SkipOSWarnings" type="integer">1</value>
                        <key name="SpecificationAlerts">
                            <!-- NOTE: Un-comment this if comparing the effects of the CPU governor -->
                            <!-- <value name="LnxCPUGovernorSubOptimal" type="integer">1</value> -->
                        </key>

                        <!-- Default Graphics Settings -->
                        <value name="SkipDefaultSettings" type="integer">1</value>
                        <value name="ForceDefaultPreset"  type="integer">1</value>
                        <value name="DefaultPreset"       type="string">@DefaultPreset@</value>
                        <value name="DefaultAntialiasing" type="integer">@DefaultAntialiasing@</value>

                        <!-- VSync off to avoid monitor refresh-rate changing results -->
                        <value name="DefaultVSync"        type="integer">0</value>
                    </key>
                </key>
            </key>
            <key name="MacDoze">
                <key name="Config">
                    <value name="ExtraCommandLine" type="string">-benchmark</value>
                    <value name="ExtraCommandLineEnabled" type="integer">1</value>
                </key>
            </key>
        </key>
    </key>
</registry>
EOM
