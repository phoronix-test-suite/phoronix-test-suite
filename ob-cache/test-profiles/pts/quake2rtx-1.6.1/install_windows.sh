#!/bin/bash -e
# Install Quake II RTX on Linux and generate launcher scripts and preference templates

# Base constants
#
export QUAKE_LOG_FILE="/cygdrive/c/Games/Quake2RTX/baseq2/logs/console.log"

./q2rtx-1.6.0-windows.exe

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
cd /cygdrive/c/Games/Quake2RTX
./q2rtx.exe +set ray_tracing_api \${RT_BACKEND} +demo q2demo1.dm2 +timedemo 1 +set r_mode -1 +set r_customwidth \$1 +set r_customheight \$2 +set vid_fullscreen 1 +set pt_num_bounce_rays \${GLOBAL_ILLUMINATION} +set flt_enable \${FLT_ENABLE} +set nextserver quit

# Grab the output from the Quake II RTX console log file
#
RESULT_LINE=\$( grep "frames" $QUAKE_LOG_FILE )
# Trim everything except the FPS; this syntax should work in most shells
FPS_VALUE="\${RESULT_LINE##*: }"
echo "\${FPS_VALUE}" >> "\$LOG_FILE"
EOM
chmod +x q2rtx.sh
