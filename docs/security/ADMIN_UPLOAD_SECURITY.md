# Administrative upload security - ZL-19

Status: PARTIAL. Static audit covers collections/movies/series/episodes poster and
thumbnail uploads plus trailers.php video/ffmpeg handling. Legacy handlers retain
unsafe filename/extension/public-storage assumptions and missing CSRF/granular
permissions; trailers shell command and deletion paths must not be deployed as
secure ingestion. No upload or historical asset was moved, deleted or executed.

The independent Upload inspector accepts only a simple one-extension PNG/JPEG
name, rejects path separators/double extensions/executable names, partial upload
errors and PHP markers, caps bytes at 2 MiB, checks server finfo MIME and image
header dimensions (4096 per axis / 16 million pixels), and generates a random
opaque name. Caller MIME and original filename are not trusted as stored values.
The inspector does not move files, create a route or run a shell command.

persist() fails UPLOAD_STORAGE_NOT_CONFIGURED. No authorized production upload
exists until trusted per-type permissions/MFA, CSRF, object ownership, canonical
image decoding/re-encoding, failure cleanup and required audit are integrated with
verified storage. Header inspection is not a full decode or malware scan; polyglot,
malformed decoder and decompression defenses need that pipeline. Licensing PDF,
subtitle, video, SVG and arbitrary private documents are intentionally unsupported.

Private agreements require non-public storage and a verified authorized download
controller. No scanner is installed/claimed; files are NOT certified malware-free.
Antivirus availability, quarantine retention and lifecycle belong to the approved
ingest plan. Full transcoding/cloud ingest remain ZL-42/ZL-43 and were not started.
