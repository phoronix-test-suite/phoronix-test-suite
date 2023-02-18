#!/bin/sh

if which npm>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: NPM is not found on the system! This test profile needs a working Node.js installation with npm in the PATH."
	echo 2 > ~/install-exit-status
	exit
fi

rm -rf web-tooling-benchmark-0.5.3/
tar -xf web-tooling-benchmark-0.5.3.tar.gz
cd web-tooling-benchmark-0.5.3/
# Fixes build
cat > update.patch <<'EOT'
commit 4a12828c6a1eed02a70c011bd080445dd319a05f
Author: Benedikt Meurer <benedikt.meurer@googlemail.com>
Date:   Mon Feb 4 17:36:04 2019 +0100

    Update dependencies (#64)

diff --git a/.npmrc b/.npmrc
deleted file mode 100644
index c1ca392..0000000
--- a/.npmrc
+++ /dev/null
@@ -1 +0,0 @@
-package-lock = false
diff --git a/package-lock.json b/package-lock.json
index 8b9bdb0..3c00bf0 100644
--- a/package-lock.json
+++ b/package-lock.json
@@ -907,7 +907,7 @@
     },
     "babel-helper-is-nodes-equiv": {
       "version": "0.0.1",
-      "resolved": "http://registry.npmjs.org/babel-helper-is-nodes-equiv/-/babel-helper-is-nodes-equiv-0.0.1.tgz",
+      "resolved": "https://registry.npmjs.org/babel-helper-is-nodes-equiv/-/babel-helper-is-nodes-equiv-0.0.1.tgz",
       "integrity": "sha1-NOmzALFHnd2Y7HfqC76TQt/jloQ="
     },
     "babel-helper-is-void-0": {
@@ -3042,7 +3042,7 @@
     },
     "external-editor": {
       "version": "2.2.0",
-      "resolved": "http://registry.npmjs.org/external-editor/-/external-editor-2.2.0.tgz",
+      "resolved": "https://registry.npmjs.org/external-editor/-/external-editor-2.2.0.tgz",
       "integrity": "sha512-bSn6gvGxKt+b7+6TKEv1ZycHleA7aHhRHyAqJyp5pbUFuYYNIzpZnQDk7AsYckyWdEnTeAnay0aCy2aV6iTk9A==",
       "dev": true,
       "requires": {
@@ -3428,6 +3428,7 @@
           "resolved": "https://registry.npmjs.org/block-stream/-/block-stream-0.0.9.tgz",
           "integrity": "sha1-E+v+d4oDIFz+A3UUgeu0szAMEmo=",
           "dev": true,
+          "optional": true,
           "requires": {
             "inherits": "~2.0.0"
           }
@@ -3455,7 +3456,8 @@
           "version": "1.0.0",
           "resolved": "https://registry.npmjs.org/buffer-shims/-/buffer-shims-1.0.0.tgz",
           "integrity": "sha1-mXjOMXOIxkmth5MCjDR37wRKi1E=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "caseless": {
           "version": "0.12.0",
@@ -3475,13 +3477,15 @@
           "version": "1.1.0",
           "resolved": "https://registry.npmjs.org/code-point-at/-/code-point-at-1.1.0.tgz",
           "integrity": "sha1-DQcLTQQ6W+ozovGkDi7bPZpMz3c=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "combined-stream": {
           "version": "1.0.5",
           "resolved": "https://registry.npmjs.org/combined-stream/-/combined-stream-1.0.5.tgz",
           "integrity": "sha1-k4NwpXtKUd6ix3wV1cX9+JUWQAk=",
           "dev": true,
+          "optional": true,
           "requires": {
             "delayed-stream": "~1.0.0"
           }
@@ -3496,19 +3500,22 @@
           "version": "1.1.0",
           "resolved": "https://registry.npmjs.org/console-control-strings/-/console-control-strings-1.1.0.tgz",
           "integrity": "sha1-PXz0Rk22RG6mRL9LOVB/mFEAjo4=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "core-util-is": {
           "version": "1.0.2",
           "resolved": "https://registry.npmjs.org/core-util-is/-/core-util-is-1.0.2.tgz",
           "integrity": "sha1-tf1UIgqivFq1eqtxQMlAdUUDwac=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "cryptiles": {
           "version": "2.0.5",
           "resolved": "https://registry.npmjs.org/cryptiles/-/cryptiles-2.0.5.tgz",
           "integrity": "sha1-O9/s3GCBR8HGcgL6KR59ylnqo7g=",
           "dev": true,
+          "optional": true,
           "requires": {
             "boom": "2.x.x"
           }
@@ -3553,7 +3560,8 @@
           "version": "1.0.0",
           "resolved": "https://registry.npmjs.org/delayed-stream/-/delayed-stream-1.0.0.tgz",
           "integrity": "sha1-3zrhmayt+31ECqrgsp4icrJOxhk=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "delegates": {
           "version": "1.0.0",
@@ -3590,7 +3598,8 @@
           "version": "1.0.2",
           "resolved": "https://registry.npmjs.org/extsprintf/-/extsprintf-1.0.2.tgz",
           "integrity": "sha1-4QgOBljjALBilJkMxw4VAiNf1VA=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "forever-agent": {
           "version": "0.6.1",
@@ -3727,6 +3736,7 @@
           "resolved": "https://registry.npmjs.org/hawk/-/hawk-3.1.3.tgz",
           "integrity": "sha1-B4REvXwWQLD+VA0sm3PVlnjo4cQ=",
           "dev": true,
+          "optional": true,
           "requires": {
             "boom": "2.x.x",
             "cryptiles": "2.x.x",
@@ -3780,6 +3790,7 @@
           "resolved": "https://registry.npmjs.org/is-fullwidth-code-point/-/is-fullwidth-code-point-1.0.0.tgz",
           "integrity": "sha1-754xOG8DGn8NZDr4L95QxFfvAMs=",
           "dev": true,
+          "optional": true,
           "requires": {
             "number-is-nan": "^1.0.0"
           }
@@ -3795,7 +3806,8 @@
           "version": "1.0.0",
           "resolved": "https://registry.npmjs.org/isarray/-/isarray-1.0.0.tgz",
           "integrity": "sha1-u5NdSFgsuhaMBoNJV6VKPgcSTxE=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "isstream": {
           "version": "0.1.2",
@@ -3878,13 +3890,15 @@
           "version": "1.27.0",
           "resolved": "https://registry.npmjs.org/mime-db/-/mime-db-1.27.0.tgz",
           "integrity": "sha1-gg9XIpa70g7CXtVeW13oaeVDbrE=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "mime-types": {
           "version": "2.1.15",
           "resolved": "https://registry.npmjs.org/mime-types/-/mime-types-2.1.15.tgz",
           "integrity": "sha1-pOv1BkCUVpI3uM9wBGd20J/JKu0=",
           "dev": true,
+          "optional": true,
           "requires": {
             "mime-db": "~1.27.0"
           }
@@ -3968,7 +3982,8 @@
           "version": "1.0.1",
           "resolved": "https://registry.npmjs.org/number-is-nan/-/number-is-nan-1.0.1.tgz",
           "integrity": "sha1-CXtgK1NCKlIsGvuHkDGDNpQaAR0=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "oauth-sign": {
           "version": "0.8.2",
@@ -4035,7 +4050,8 @@
           "version": "1.0.7",
           "resolved": "https://registry.npmjs.org/process-nextick-args/-/process-nextick-args-1.0.7.tgz",
           "integrity": "sha1-FQ4gt1ZZCtP5EJPyWk8q2L/zC6M=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "punycode": {
           "version": "1.4.1",
@@ -4078,6 +4094,7 @@
           "resolved": "https://registry.npmjs.org/readable-stream/-/readable-stream-2.2.9.tgz",
           "integrity": "sha1-z3jsb0ptHrQ9JkiMrJfwQudLf8g=",
           "dev": true,
+          "optional": true,
           "requires": {
             "buffer-shims": "~1.0.0",
             "core-util-is": "~1.0.0",
@@ -4132,7 +4149,8 @@
           "version": "5.0.1",
           "resolved": "https://registry.npmjs.org/safe-buffer/-/safe-buffer-5.0.1.tgz",
           "integrity": "sha1-0mPKVGls2KMGtcplUekt5XkY++c=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "semver": {
           "version": "5.3.0",
@@ -4160,6 +4178,7 @@
           "resolved": "https://registry.npmjs.org/sntp/-/sntp-1.0.9.tgz",
           "integrity": "sha1-ZUEYTMkK7qbG57NeJlkIJEPGYZg=",
           "dev": true,
+          "optional": true,
           "requires": {
             "hoek": "2.x.x"
           }
@@ -4196,6 +4215,7 @@
           "resolved": "https://registry.npmjs.org/string-width/-/string-width-1.0.2.tgz",
           "integrity": "sha1-EYvfW4zcUaKn5w0hHgfisLmxB9M=",
           "dev": true,
+          "optional": true,
           "requires": {
             "code-point-at": "^1.0.0",
             "is-fullwidth-code-point": "^1.0.0",
@@ -4207,6 +4227,7 @@
           "resolved": "https://registry.npmjs.org/string_decoder/-/string_decoder-1.0.1.tgz",
           "integrity": "sha1-YuIA8DmVWmgQ2N8KM//A8BNmLZg=",
           "dev": true,
+          "optional": true,
           "requires": {
             "safe-buffer": "^5.0.1"
           }
@@ -4239,6 +4260,7 @@
           "resolved": "https://registry.npmjs.org/tar/-/tar-2.2.1.tgz",
           "integrity": "sha1-jk0qJWwOIYXGsYrWlK7JaLg8sdE=",
           "dev": true,
+          "optional": true,
           "requires": {
             "block-stream": "*",
             "fstream": "^1.0.2",
@@ -4300,7 +4322,8 @@
           "version": "1.0.2",
           "resolved": "https://registry.npmjs.org/util-deprecate/-/util-deprecate-1.0.2.tgz",
           "integrity": "sha1-RQ1Nyfpw3nMnYvvS1KKJgUGaDM8=",
-          "dev": true
+          "dev": true,
+          "optional": true
         },
         "uuid": {
           "version": "3.0.1",
@@ -5780,24 +5803,24 @@
       "integrity": "sha1-RsP+yMGJKxKwgz25vHYiF226s0s="
     },
     "jshint": {
-      "version": "2.9.5",
-      "resolved": "https://registry.npmjs.org/jshint/-/jshint-2.9.5.tgz",
-      "integrity": "sha1-HnJSkVzmgbQIJ+4UJIxG006apiw=",
+      "version": "2.9.7",
+      "resolved": "https://registry.npmjs.org/jshint/-/jshint-2.9.7.tgz",
+      "integrity": "sha512-Q8XN38hGsVQhdlM+4gd1Xl7OB1VieSuCJf+fEJjpo59JH99bVJhXRXAh26qQ15wfdd1VPMuDWNeSWoNl53T4YA==",
       "requires": {
         "cli": "~1.0.0",
         "console-browserify": "1.1.x",
         "exit": "0.1.x",
         "htmlparser2": "3.8.x",
-        "lodash": "3.7.x",
+        "lodash": "~4.17.10",
         "minimatch": "~3.0.2",
         "shelljs": "0.3.x",
         "strip-json-comments": "1.0.x"
       },
       "dependencies": {
         "lodash": {
-          "version": "3.7.0",
-          "resolved": "https://registry.npmjs.org/lodash/-/lodash-3.7.0.tgz",
-          "integrity": "sha1-Nni9irmVBXwHreg27S7wh9qBHUU="
+          "version": "4.17.11",
+          "resolved": "https://registry.npmjs.org/lodash/-/lodash-4.17.11.tgz",
+          "integrity": "sha512-cQKh8igo5QUhZ7lg38DYWAxMvjSAKG0A8wGSVimP07SIUEK2UO+arSRKbRZWtelMtN5V0Hkwh5ryOto/SshYIg=="
         }
       }
     },
@@ -6910,7 +6933,7 @@
     },
     "onetime": {
       "version": "1.1.0",
-      "resolved": "http://registry.npmjs.org/onetime/-/onetime-1.1.0.tgz",
+      "resolved": "https://registry.npmjs.org/onetime/-/onetime-1.1.0.tgz",
       "integrity": "sha1-ofeDj4MUxRbwXs78vEzP4EtO14k=",
       "dev": true
     },
@@ -9453,9 +9476,9 @@
       "dev": true
     },
     "typescript": {
-      "version": "2.7.2",
-      "resolved": "https://registry.npmjs.org/typescript/-/typescript-2.7.2.tgz",
-      "integrity": "sha512-p5TCYZDAO0m4G344hD+wx/LATebLWZNkkh2asWUFqSsD2OrDNhbAHuSjobrmsUmdzjJjEeZVU9g1h3O6vpstnw=="
+      "version": "3.3.1",
+      "resolved": "https://registry.npmjs.org/typescript/-/typescript-3.3.1.tgz",
+      "integrity": "sha512-cTmIDFW7O0IHbn1DPYjkiebHxwtCMU+eTy30ZtJNBPF9j2O1ITu5XH2YnBeVRKWHqF+3JQwWJv0Q0aUgX8W7IA=="
     },
     "uglify-js": {
       "version": "3.3.16",
diff --git a/package.json b/package.json
index b0e7382..ce01dee 100644
--- a/package.json
+++ b/package.json
@@ -52,7 +52,7 @@
     "compute-gmean": "^1.1.0",
     "espree": "3.5.4",
     "esprima": "4.0.0",
-    "jshint": "2.9.5",
+    "jshint": "2.9.7",
     "lebab": "2.7.7",
     "postcss": "6.0.21",
     "postcss-nested": "3.0.0",
@@ -61,7 +61,7 @@
     "source-map": "0.6.1",
     "string-align": "^0.2.0",
     "terser": "3.8.2",
-    "typescript": "2.7.2",
+    "typescript": "3.3.1",
     "uglify-js": "3.3.16",
     "virtualfs": "^2.1.0"
   },
EOT
patch < update.patch
npm install
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd web-tooling-benchmark-0.5.3/

node dist/cli.js > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

echo \"Nodejs \" > ~/pts-footnote
node --version >> ~/pts-footnote 2>/dev/null" > node-web-tooling
chmod +x node-web-tooling
