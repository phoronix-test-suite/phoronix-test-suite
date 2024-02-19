#!/bin/sh
tar -xf ffmpeg-6.1.tar.xz
tar -xf x265-20221028.tar.xz
tar -xf x264-20221005.tar.xz
mkdir ffmpeg_/

export PKG_CONFIG_PATH="$HOME/ffmpeg_/lib/pkgconfig"
cd ~/x264
./configure --prefix=$HOME/ffmpeg_/ --enable-static --enable-lto --enable-pic
make -j $NUM_CPU_CORES
make install

cd ~/x265_git/build
cmake ../source/ -DCMAKE_BUILD_TYPE=Release -DCMAKE_INSTALL_PREFIX=$HOME/ffmpeg_/
make -j $NUM_CPU_CORES
make install

cd ~/ffmpeg-6.1/
./configure --disable-zlib --disable-doc --prefix=$HOME/ffmpeg_/ --extra-cflags="-I$HOME/ffmpeg_/include" --extra-ldflags="-L$HOME/ffmpeg_/lib -ldl" --bindir="$HOME/ffmpeg_/bin" --pkg-config-flags="--static" --enable-gpl --enable-libx264 --enable-libx265
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~
rm -rf ffmpeg-6.1/

unzip -o vbench-01.zip
cd vbench/code
patch -p0 <<'EOF'
diff -Naur reference.py.orig  reference.py
--- reference.py.orig	2021-03-21 17:47:18.000000000 -0400
+++ reference.py	2022-10-30 13:36:31.346904399 -0400
@@ -13,7 +13,7 @@
     p = subprocess.Popen(cmd,stdout=subprocess.PIPE, stderr=subprocess.PIPE,shell=True)
     out, err = p.communicate()
 
-    m = re.search("average:([0-9]+\.[0-9]+)",err)
+    m = re.search("average:([0-9]+\.[0-9]+)",err.decode('utf-8'))
 
     # cleanup
     try:
@@ -22,7 +22,7 @@
         pass
 
     if m is None:
-        m = re.search("average:(inf)",err)
+        m = re.search("average:(inf)",err.decode('utf-8'))
         assert m is not None
         return 100.0
     else:
@@ -34,7 +34,7 @@
     p = subprocess.Popen(cmd,stdout=subprocess.PIPE, stderr=subprocess.PIPE)
     out, err = p.communicate()
 
-    m = re.search("bitrate: ([0-9]+) kb/s",err)
+    m = re.search("bitrate: ([0-9]+) kb/s",err.decode('utf-8'))
     assert m is not None
     return int(m.group(1))*1000 #report in b/s
 
@@ -45,24 +45,29 @@
     cmd = [ffprobe,"-show_entries","stream=width,height",video]
     p = subprocess.Popen(cmd,stdout=subprocess.PIPE, stderr=subprocess.PIPE)
     out, err = p.communicate()
-
     # grep resolution
-    width = re.search("width=([0-9]+)",out)
+    width = re.search("width=([0-9]+)",out.decode('utf-8'))
     assert width is not None, "Problem in fetching video width with {} on {}".format(ffprobe,video)
-    height = re.search("height=([0-9]+)",out)
+    height = re.search("height=([0-9]+)",out.decode('utf-8'))
     assert height is not None, "Problem in fetching video height with {} on {}".format(ffprobe,video)
     resolution = int( width.group(1) ) * int( height.group(1) )
 
     # grep framerate
-    frame = re.search("([0-9\.]+) fps",err)
+    frame = re.search("([0-9\.]+) fps",err.decode('utf-8'))
     assert frame is not None, "Problem in fetching framerate with {} on {}".format(ffprobe,video)
     framerate = float(frame.group(1))
 
-    return resolution, framerate
+    cmd = [ffprobe,"-select_streams", "v:0", "-count_frames", "-show_entries", "stream=nb_read_frames",video]
+    p = subprocess.Popen(cmd,stdout=subprocess.PIPE, stderr=subprocess.PIPE)
+    out, err = p.communicate()
+    num_frames = re.search("nb_read_frames=([0-9]+)",out.decode('utf-8'))
+    frame_count = int(num_frames.group(1))
+
+    return resolution, framerate, frame_count
 
-def encode(ffmpeg, video, settings, output):
+def encode(ffmpeg, video, settings, output, encoder):
     ''' perform the transcode operation using ffmpeg '''
-    cmd =  [ffmpeg,"-i",video,"-c:v","libx264","-threads",str(1)]+settings+["-y",output]
+    cmd =  [ffmpeg,"-i",video,"-c:v",encoder,"-threads",str(1)]+settings+["-y",output]
     start = timer()
     p = subprocess.Popen(cmd,stdout=subprocess.PIPE, stderr=subprocess.PIPE)
     out, err = p.communicate()
@@ -70,10 +75,10 @@
 
     return elapsed
 
-def encode_2pass(ffmpeg, video, settings, output_file):
+def encode_2pass(ffmpeg, video, settings, output_file, encoder):
     ''' perform two pass transcoding '''
-    time_to_encode1 = encode(ffmpeg, video, ["-pass", str(1) ,"-f", "null", "-an", "-sn"]+settings, "/dev/null")
-    time_to_encode2 = encode(ffmpeg, video, ["-pass", str(2)]+settings, output_file)
+    time_to_encode1 = encode(ffmpeg, video, ["-pass", str(1) ,"-f", "null", "-an", "-sn"]+settings, "/dev/null", encoder)
+    time_to_encode2 = encode(ffmpeg, video, ["-pass", str(2)]+settings, output_file, encoder)
 
     return time_to_encode1+time_to_encode2
 
@@ -89,6 +94,8 @@
                         help="Transcoding scenario")
     parser.add_argument("--output_dir", type=str,default="/tmp",
                         help="Where to save transcoded videos")
+    parser.add_argument("--encoder", type=str,default="libx264",
+                        help="FFmpeg encoder to use")
     parser.add_argument("--ffmpeg_dir", type=str,
                         default=os.path.join(vbench_root,"code/bin"), 
                         help="Path to ffmpeg installation folder")
@@ -106,6 +113,7 @@
     else:
         video_dir = os.path.join(os.getenv("VBENCH_ROOT"),"videos/crf18")
 
+    ffmpeg_encoder = args.encoder
     ffmpeg = os.path.join(args.ffmpeg_dir, "ffmpeg")
     ffprobe = os.path.join(args.ffmpeg_dir, "ffprobe")
     assert(os.path.isfile(ffmpeg) and os.access(ffmpeg, os.X_OK)), \
@@ -129,7 +137,9 @@
     # perform transcoding
     ###############################################
 
-    print "# video_name, transcoding time, psnr compared to original, transcode bitrate"
+    print("# video_name, transcoding time, psnr compared to original, transcode bitrate")
+    total_elapsed = 0
+    total_frames = 0
     for v_name in input_files:
         video = os.path.join(video_dir, v_name)
 
@@ -138,10 +148,12 @@
 
         if args.scenario == "upload":
             settings = [ "-crf","18" ]
-            elapsed = encode(ffmpeg,video,settings,output_video)
+            resolution, framerate, num_frames = get_video_stats(ffprobe, video)
+            elapsed = encode(ffmpeg,video,settings,output_video, ffmpeg_encoder)
         else:
             # get stats of the video and use to compute target_bitrate
-            resolution, framerate = get_video_stats(ffprobe, video)
+            resolution, framerate, num_frames = get_video_stats(ffprobe, video)
+            total_frames += num_frames
 
             # fixed number of bits per pixel as target bitrate
             if framerate > 30:
@@ -163,13 +175,15 @@
                 else:
                     settings += [ "-preset","veryfast","-tune","zerolatency" ]
 
-                elapsed = encode(ffmpeg,video,settings,output_video)
+                elapsed = encode(ffmpeg,video,settings,output_video, ffmpeg_encoder)
             elif args.scenario in ["vod","platform"]:
                 settings += [ "-preset","medium" ]
-                elapsed = encode_2pass(ffmpeg, video, settings, output_video)
+                elapsed = encode_2pass(ffmpeg, video, settings, output_video, ffmpeg_encoder)
+                num_frames *= 2
             elif args.scenario == "popular":
                 settings += [ "-preset","veryslow" ]
-                elapsed = encode_2pass(ffmpeg, video, settings, output_video)
+                elapsed = encode_2pass(ffmpeg, video, settings, output_video, ffmpeg_encoder)
+                num_frames *= 2
             else:
                 raise NotImplementedError
 
@@ -177,7 +191,13 @@
         psnr              = get_psnr(ffmpeg, output_video, video)
         transcode_bitrate = get_bitrate(ffprobe, output_video)
 
-        print "{},{},{},{}".format(v_name, elapsed, psnr, transcode_bitrate)
+        print("{},{},{},{}".format(v_name, elapsed, psnr, transcode_bitrate))
+        total_elapsed += elapsed
+        total_frames += num_frames
+     
+    print("Total Elaped Time (s): {}".format(total_elapsed))
+    print("Total Frames: {}".format(total_frames))
+    print("Average FPS: {}".format(total_frames / total_elapsed))
 
     # cleanup
     try:
EOF

cd ~
echo "#!/bin/sh
cd vbench/code
export LD_LIBRARY_PATH=\$HOME/ffmpeg_/lib/:\$LD_LIBRARY_PATH
VBENCH_ROOT=\$HOME/vbench/ python3 reference.py --ffmpeg_dir=\$HOME/ffmpeg_/bin/ \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > ffmpeg
chmod +x ffmpeg
