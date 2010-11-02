#!/bin/sh

unzip -o tscp181.zip
cd tscp181/

patch -p0 <<'EOT'
--- main.c.orig	2003-02-05 01:02:40.000000000 -0500
+++ main.c	2009-08-14 16:17:04.000000000 -0400
@@ -70,7 +70,12 @@
 			gen();
 			print_result();
 			continue;
-		}
+		}
+
+		// Hack, Just bench at start-up and quit
+		computer_side = EMPTY;
+		bench();
+		break;
 
 		/* get user input */
 		printf("tscp> ");
EOT

cc *.c -o tscp

cd ..

echo "#!/bin/sh
cd tscp181/
./tscp \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > tscp
chmod +x tscp
