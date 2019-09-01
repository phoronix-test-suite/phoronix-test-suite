#!/bin/sh

unzip -o phpbench-081-patched1.zip

echo "#!/bin/sh
cd phpbench-0.8.1-patched1/
cmd /c \"$PHP_BIN\" phpbench.php \$@ > \$LOG_FILE" > phpbench
chmod +x phpbench
