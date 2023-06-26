#!/bin/sh
unzip -o 3dmark-attan-extreme-1.1.2.1-workload-bin.zip
chmod +x bin/windows/x64/workload.exe
echo "#!/bin/sh
echo \"{
    \\\"workload_name\\\": \\\"WildLifeGt1XC\\\",
    \\\"rendering_resolution\\\": [\$1, \$2],
    \\\"asset_root\\\": \\\"assets_desktop\\\",
    \\\"timeline\\\": \\\"timelines_heavy/attan_gt1_heavy_timeline.txt\\\",
    \\\"fullscreen\\\": true,
    \\\"debug_api\\\": false,
    \\\"enable_debug_log\\\": false,
    \\\"base_log_path\\\": \\\"\\\",
    \\\"threads\\\": 0,
    \\\"vsync\\\": false,
    \\\"loop\\\": false,
    \\\"loop_count\\\": 0
}\" > settings/pts.json
rm -f result.json
./bin/windows/x64/workload.exe --in=settings/pts.json --out=result.json
echo \$? > ~/test-exit-status
cat result.json > \$LOG_FILE" > 3dmark
chmod +x 3dmark
