#!/bin/sh

tar -xjf vdrift-2010-06-30.tar.bz2
unzip -o vdrift-benchmark-4.zip

cd vdrift-2010-06-30/
scons release=1
cd ..

mkdir -p ~/.vdrift/replays/
mv benchmark.vdr ~/.vdrift/replays/

echo "#!/bin/sh

echo \"[ control ]
autoclutch = true
autotrans = true
button_ramp = 0.000000
mousegrab = true
speed_sens_steering = 1.000000

[ display ]
width = \$1
height = \$2
FOV = 45.000000
anisotropic = 0
antialiasing = 0
bloom = true
camerabounce = 1.000000
contrast = 1.000000
depth = 16
fullscreen = true
input_graph = true
lighting = 2
mph = true
normalmaps = false
racingline = false
reflections = 2
shaders = true
shadow_distance = 2
shadow_quality = 2
shadows = true
show_fps = true
show_hud = true
skin = simple
texture_size = large
trackmap = true
view_distance = 2500.000000
zdepth = 24\" > ~/.vdrift/VDrift.config

cd vdrift-2010-06-30/
./build/vdrift -nosound -benchmark \$@ > \$LOG_FILE 2>&1" > vdrift
chmod +x vdrift
