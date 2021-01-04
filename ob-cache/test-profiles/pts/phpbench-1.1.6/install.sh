#!/bin/sh

unzip -o phpbench-081-patched2.zip

echo "#!/bin/sh
cd phpbench-0.8.1-patched2/
\$PHP_BIN phpbench.php \$@ > \$LOG_FILE" > phpbench
chmod +x phpbench
