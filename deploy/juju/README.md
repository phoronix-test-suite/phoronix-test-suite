# Overview

[Phoronix Test Suite](http://www.phoronix-test-suite.com/) (PTS) is a comprehensive
testing and benchmarking suite.

This charm requires Juju 1.23 or later and uses [Juju
Actions](https://jujucharms.com/docs/latest/authors-charm-actions/) to run the
benchmarks.


# Usage

First bootstrap Juju and then deploy pts:

    juju bootstrap
    juju deploy pts

# Configuration

You can list all of the actions available with the following command:

    juju action defined pts

This charm supports the following benchmarks:

- `cpu`: CPU centric stress tests
- `custom`: Custom stress tests
- `io`: IO centric tests.
- `memory`: Memory centric stress tets
- `smoke`: Smoke test, tests that complete quickly.

To execute a benchmark you can use a Juju action, in this example we run the cpu
stress tests on the first pts unit launched:

    juju action do pts/0 cpu

or in this case, do a io test on pts unit 3:

    juju action do pts/3 io

You can also run benchmarks across the entire pts service:

    juju deploy pts             # Deploys one unit
    juju add-unit -n10 pts      # Add 10 more nodes
    juju action do pts memory   # Exec the memory benchmark on all 11 pts nodes

# Check on actions

`juju action status` allows you to see the current status of an action. The benchmark results will be available once the action status has changed to `completed`.

```
juju action status 7707a291-be29-46aa-8d02-2daa8ee24ebf
actions:
- id: 7707a291-be29-46aa-8d02-2daa8ee24ebf
  status: running
  unit: pts/0
```

# Get results
Once an action has completed, you can fetch the results in yaml or json, in addition to the default *smart* format.
```
juju action fetch 7707a291-be29-46aa-8d02-2daa8ee24ebf
results:
  results:
    cachebench-read:
      units: MB/s
      value: "1129.95"
    cachebench-read-modify-write:
      units: MB/s
      value: "5158.10"
    cachebench-write:
      units: MB/s
      value: "3601.54"
    phpbench:
      units: Score
      value: "35783"
    stream-add:
      units: MB/s
      value: "13187.50"
    stream-copy:
      units: MB/s
      value: "12179.08"
    stream-scale:
      units: MB/s
      value: "12292.20"
    stream-triad:
      units: MB/s
      value: "13069.26"
status: completed
timing:
  completed: 2015-03-23 18:47:32 +0000 UTC
  enqueued: 2015-03-23 17:51:59 +0000 UTC
  started: 2015-03-23 17:52:03 +0000 UTC
```

# Contact Information

- Maintainer: Adam Israel <aisrael@canonical.com>
- [Phoronix Test Suite](http://www.phoronix-test-suite.com/) homepage
