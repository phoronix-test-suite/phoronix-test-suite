#!/bin/bash -e
# Install Quake II RTX on Linux and generate launcher scripts and preference templates

# Base constants
#
export STEAM_GAME_ID=1089130
export GAME_INSTALL_DIR_BASE="steamapps/common/Quake II RTX/"
export DEFAULT_STEAM_INSTALL_BASE="${DEBUG_REAL_HOME}/.steam/steam/"
export FULL_GAME_DIR="${DEFAULT_STEAM_INSTALL_BASE}/${GAME_INSTALL_DIR_BASE}"
export QUAKE_LOG_FILE=".quake2rtx/baseq2/logs/console.log"


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
if [ ! -f "${GAME_INSTALL_DIR}/q2rtx" ]; then
    >&2 echo "Missing game executable in install dir - ${GAME_INSTALL_DIR}/q2rtx"
    exit 1
fi

# Create the game launching script
#
echo "Generating run script"
cat > q2rtx.sh <<- EOM
#!/bin/bash
# Generated run script for Quake II RTX
# $( date )
# Inputs
#
GLOBAL_ILLUMINATION=\$3
FLT_ENABLE=\$4
RT_BACKEND=\$5
# Run the game
#
cd "${FULL_GAME_DIR}"
./q2rtx +set ray_tracing_api \${RT_BACKEND} +demo q2demo1.dm2 +timedemo 1 +set vid_fullscreen 1 +set pt_num_bounce_rays \${GLOBAL_ILLUMINATION} +set flt_enable \${FLT_ENABLE} +set nextserver quit
cd -
# Grab the output from the Quake II RTX console log file
#
RESULT_LINE=\$( grep "frames" $QUAKE_LOG_FILE )
# Trim everything except the FPS; this syntax should work in most shells
FPS_VALUE="\${RESULT_LINE##*: }"
echo "\${FPS_VALUE}" >> "\$LOG_FILE"
EOM
chmod +x q2rtx.sh
