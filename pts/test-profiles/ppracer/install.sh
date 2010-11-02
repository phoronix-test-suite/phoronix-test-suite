#!/bin/sh

mkdir $HOME/ppracer_

tar -jxvf ppracer-data-0.2.3.tar.bz2
tar -jxvf ppracer-0.5alpha.tar.bz2

cd ppracer-0.5alpha/
sed 's/RacingMode::RacingMode()/RacingMode()/' src/racingmode.h > racingmode.h
mv -f racingmode.h src/racingmode.h

./configure --prefix=$HOME/ppracer_
make
make install
cd ..
rm -rf ppracer-0.5alpha/

cd ppracer-data-0.2.3/
mv * ../ppracer_/share/ppracer/
cd ..
rm -rf ppracer-data-0.2.3/

echo "#!/bin/sh

echo \"ppconfig <- {
audio_format_mode = 1,
display_course_percentage = true,
ui_snow = true,
disable_fog = false,
write_diagnostic_log = false,
terrain_error_scale = 0.20000000298023,
joystick_paddle_button = 0,
joystick_x_axis = 0,
x_resolution_half_width = false,
track_marks = true,
bpp_mode = 0,
texture_filter = 5,
disable_collision_detection = false,
tux_shadow_sphere_divisions = 3,
jump_key = 101,
ui_language = \\\"en_GB\\\",
forward_clip_distance = 120,
backward_clip_distance = 20,
jump_key2 = 99,
y_resolution = \$2,
joystick_y_axis = 1,
music_enabled = false,
reset_key2 = 118,
terrain_envmap = true,
x_resolution = \$1,
course_detail_level = 150,
always_save_event_race_data = false,
view_mode2 = 1,
use_cva = true,
stencil_buffer = true,
do_intro_animation = true,
audio_stereo = true,
enable_fsaa = true,
data_dir = \\\"\$HOME/ppracer_/share/ppracer\\\",
terrain_blending = true,
disable_videomode_autodetection = false,
fullscreen = true,
audio_freq_mode = 2,
fov = 90,
joystick_brake_button = 2,
draw_particles = true,
view_mode = 1,
sound_enabled = false,
no_audio = true,
disable_joystick = false,
draw_tux_shadow = true,
multisamples = 2,
display_fps = true,
tux_sphere_divisions = 15,
};\" > pts-config.nut

cd ppracer_/bin/
./ppracer -c $HOME/pts-config.nut -a -f events/herring_run/snow_valley > \$LOG_FILE 2>&1" > ppracer
chmod +x ppracer
