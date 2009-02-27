#!/bin/sh

tar -xjf vdrift-benchmark-1.tar.bz2
tar -xjf vdrift-2009-02-15-src.tar.bz2
cd vdrift-2009-02-15/
tar -xvf bullet-2.73-sp1.tgz
cd bullet-2.73/
./autogen.sh
./configure
make
cd ..
patch -p0 < ../VDRIFT-ADD-BENCHMARK-MODE.patch
scons

# TODO: Drop in benchmark.vdr to ~/.vdrift/replays/
# Config file at ~/.vdrift/VDrift.config

cd ..

mkdir -p ~/.vdrift/replays/
mv benchmark.vdr ~/.vdrift/replays/

echo "#!/bin/sh

echo \"[ control ]
autoclutch = true
autotrans = true
button_ramp = 0.000000
mousegrab = true
speed_sens_steering = 0.000000

[ display ]
width = \$1
height = \$2
FOV = 45.000000
anisotropic = 0
antialiasing = 0
bloom = true
camerabounce = 0.000000
depth = 16
fullscreen = true
input_graph = false
lighting = 0
mph = true
racingline = false
reflections = 0
shaders = true
shadow_distance = 1
shadow_quality = 1
shadows = true
show_fps = true
show_hud = true
skin = simple
texture_size = large
trackmap = true
view_distance = 1000.000000
zdepth = 24\" > ~/.vdrift/VDrift.config

cd vdrift-2009-02-15/
./build/vdrift -multithreaded -nosound -benchmark \$@ > \$LOG_FILE 2>&1" > vdrift
chmod +x vdrift
