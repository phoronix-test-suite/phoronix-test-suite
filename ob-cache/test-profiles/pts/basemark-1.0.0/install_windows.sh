#!/bin/sh

unzip -o BasemarkGPU_windows_x64.zip

echo "<?php

\$result = file_get_contents(\$argv[1]);
\$json = json_decode(\$result, TRUE);

echo 'AVERAGE FPS: ' . \$json['result']['averageFPS'] . PHP_EOL;
echo 'MINIMUM FPS: ' . \$json['result']['minFPS'] . PHP_EOL;
echo 'MAXIMUM FPS: ' . \$json['result']['maxFPS'] . PHP_EOL;
echo '---- GPU FRAMES ----' . PHP_EOL;
//echo implode(' ', \$json['frames']['frameTimes']);" > resources/parser.php

echo "#!/bin/bash
cd resources

./binaries/\$1.exe TestType Custom AssetPath assets/pkg TextureCompression bc7 ReportPath output.json BenchmarkMode true SkipZPrepass false Fullscreen true \$@
echo \$? > ~/test-exit-status

\$PHP_BIN parser.php output.json > \$LOG_FILE
" > basemark
chmod +x basemark
