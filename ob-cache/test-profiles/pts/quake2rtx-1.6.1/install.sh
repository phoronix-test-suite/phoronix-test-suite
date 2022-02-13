#!/bin/bash -e
# Install Quake II RTX on Linux and generate launcher scripts and preference templates

# Base constants
#
export QUAKE_LOG_FILE=".quake2rtx/baseq2/logs/console.log"

tar -xf q2rtx-1.6.0-linux.tar.gz

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
cd q2rtx
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
