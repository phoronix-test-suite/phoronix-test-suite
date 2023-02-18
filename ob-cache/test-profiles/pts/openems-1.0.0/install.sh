#!/bin/sh
pip3 install --user h5py==3.7.0 matplotlib==3.6.3
rm -rf openEMS-Project
git clone --recurse-submodule https://github.com/thliebig/openEMS-Project.git
cd openEMS-Project
git checkout v0.0.35-86
sed -i 's/show()/ /g' openEMS/python/Tutorials/MSL_NotchFilter.py
sed -i 's/    FDTD.Run(Sim_Path, cleanup=True)/    FDTD.Run(Sim_Path, cleanup=True, numThreads=os.getenv("NUM_CPU_CORES"))/g' openEMS/python/Tutorials/MSL_NotchFilter.py
./update_openEMS.sh ~/openEMS-bin --python
export PATH=$HOME/openEMS-bin/bin/:$PATH
cd ~
rm -rf pyems
git clone https://github.com/matthuszagh/pyems.git
cd pyems
git checkout f63fcf85bf2e8e26ff685035b5d36b4a85860268
echo "diff --git a/examples/coupler.py b/examples/coupler.py
index 5454205..b8a69c5 100755
--- a/examples/coupler.py
+++ b/examples/coupler.py
@@ -125,18 +125,4 @@ write_footprint(coupler, \"coupler_20db\", \"coupler_20db.kicad_mod\")
 if os.getenv(\"_PYEMS_PYTEST\"):
     sys.exit(0)
 
-sim.run()
-sim.view_field()
-
-print_table(
-    data=[
-        sim.freq / 1e9,
-        np.abs(sim.ports[0].impedance()),
-        sim.s_param(1, 1),
-        sim.s_param(2, 1),
-        sim.s_param(3, 1),
-        sim.s_param(4, 1),
-    ],
-    col_names=[\"freq\", \"z0\", \"s11\", \"s21\", \"s31\", \"s41\"],
-    prec=[4, 4, 4, 4, 4, 4],
-)
+sim.run(csx=False,threads=os.getenv('NUM_CPU_CORES'))" > headless.patch
git apply headless.patch
python3 setup.py install --user
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/bash
cd \`dirname \"\$1\"\`
python3 \`basename \"\$1\"\` > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > openems
chmod +x openems
