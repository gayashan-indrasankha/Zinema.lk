# Owner-confirmed bot-machine inventory

Recorded 2026-10-28 from owner-supplied standalone collector output. The owner
confirmed this is the actual bot machine and identified the existing local media
directory privately. No agent inspection of its files, directories or contents
was performed for this follow-up. Collection timestamp was not provided.

| Observation | Reported value | Evidence limit |
| --- | --- | --- |
| OS / architecture | Windows / x64, release 10.0.26200 | Owner-reported metadata, not an OS patch/security review |
| CPU | 16 logical processors | Physical cores/model and sustained usable capacity not established |
| Total RAM | 16,868,970,496 bytes, approximately 15.71 GiB | Machine inventory, not a worker-load result |
| Free RAM at report | 1,661,399,040 bytes, approximately 1.55 GiB | Point-in-time snapshot; no process inventory or memory load test |
| Node | v24.13.1 | Matches the tested major; patch/dependencies remain unverified |
| Media volume size | 269,482,979,328 bytes, approximately 250.98 GiB | Entire volume, not a dedicated media allocation |
| Media volume available | 130,470,916,096 bytes, approximately 121.51 GiB | Other applications/files share the volume; folder use/quota unknown |
| Storage writability | Not tested | Read-only metadata collection deliberately did not create files |
| Upload speed / continuous operation | Unknown | No speed test, sleep/power inspection or continuous availability proof |

The report narrows sizing uncertainty but does not approve launch. Free RAM needs
measurement under the real-shaped local fake worker workload; total RAM alone does
not prove service capacity. Current boot age does not establish always-on operation.

At 2 GiB per file, the available volume space has a mathematical upper bound of
60 additional files **before** reserving space for ingestion, temporary copies,
quarantine, other applications and recovery. This is not an operational storage
limit or a catalogue-size promise. The proposed 150-file catalogue would require
300 GiB if every file used the maximum and cannot fit within the reported free
space. Its earlier 512 MiB-average model is 75 GiB, but actual file count/average,
existing folder use and independent backup storage remain unknown.

The generic collector still prints an unconfirmed machine-role/pending list on
each standalone run; it cannot learn the owner's conversation confirmation. The
hosting candidate separately records the owner-confirmed inventory provenance.
Independent machine/security/load verification flags remain false, and all live,
production/deployment/publishing controls stay disabled.

Follow-up verification: checked byte/GiB arithmetic, JSON/provenance/disabled flags
and documentation diff only. No application, filesystem write, live provider,
network throughput or new machine-capacity test was executed.

Next recommended task: **Implement the remaining Stellar-compatible private
bootstrap/storage and approved extractor design locally with fakes, then run a
bounded worker memory/disk-pressure rehearsal. Keep upload/network/always-on and
independent recovery acceptance explicitly pending; do not enable live services.**
