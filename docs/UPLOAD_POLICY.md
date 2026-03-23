# Upload Policy and Scaling Plan

## Current Product Upload Policy

- Per image limit (Laravel validation): `6 MB` (`max:6144` KB).
- Recommended target after client compression: `~1.5 MB` per image.
- Recommended soft total per request: `<= 25 MB`.
- Multi-upload create form supports up to `20` products per request.

## User Experience Controls

- Client-side pre-submit total size summary on product create form.
- Automatic browser-side image compression for selected product images.
- Inline per-file status message showing original/compressed size.
- If compression is not enough, users are directed to external compression:
  - `https://tinypng.com` (opens in new tab).

## Server and Platform Limits (Production)

Ensure these values are aligned so large form bodies are accepted:

- PHP:
  - `upload_max_filesize=10M` to `15M`
  - `post_max_size=64M` (or higher if needed)
  - `max_file_uploads=50`
- Web server (Nginx):
  - `client_max_body_size 64M;`

## Monitoring and Incident Handling

- `413 Content Too Large` requests are logged with:
  - path, method, content length, user id, IP, user agent.
- Log keyword:
  - `Upload rejected: request exceeds server body size limit`
- Track 413 rate in log dashboard; alert if spikes occur.

## Long-Term Architecture (Best at Scale)

Move to direct-to-object-storage uploads:

1. Browser uploads image directly to S3/Cloudinary.
2. Laravel receives only metadata + file URL.
3. Laravel creates products without processing large multipart payloads.

Benefits:

- Avoids large POST bodies to app server.
- Lowers 413 risk significantly.
- Improves reliability under high concurrent seller uploads.
