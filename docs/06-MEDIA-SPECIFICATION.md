# 06 - Media & Image Optimization Specification

## 1. First-Class Media Integration Mandate
In Apex SEO Platform, the Media Library is not a secondary tab. Image optimization, conversion, and image SEO metadata are integrated directly into the WordPress core media management interfaces.

```
                      ATTACHMENT LIFECYCLE
                               │
                               ▼
                    ┌─────────────────────┐
                    │  Upload Interceptor │
                    │ (wp_handle_upload)  │
                    └──────────┬──────────┘
                               │
            ┌──────────────────┴──────────────────┐
            ▼                                     ▼
┌─────────────────────────┐           ┌─────────────────────────┐
│   Automated Image SEO   │           │    Image Compression    │
│ - Sanitized Filename    │           │ - Lossy/Lossless (GD/IM)│
│ - Auto ALT Text         │           │ - WebP Variant Creation │
│ - Title & Caption Meta  │           │ - AVIF Variant Creation │
└─────────────────────────┘           │ - Original Saved Backup │
                                      └─────────────────────────┘
```

---

## 2. Core Image Capabilities

### 2.1 Library Auto-Detection Hierarchy
The image processor probes server capabilities in order of preference:
1. **PHP Imagick Extension** (ImageMagick binary wrapper) -> Best compression quality, full color profile retention.
2. **PHP GD Library** -> Built-in fallback with `imagecreatefromjpeg`, `imagewebp`, `imageavif`.
3. **Command Line Encoders** (where `exec()` is permitted) -> `cwebp`, `avifenc`, `pngquant`, `mozjpeg`.

### 2.2 Backup & Non-Destructive Restore
- Originals are backed up to `/wp-content/uploads/apex-backups/{year}/{month}/`.
- Metadata and file checksums are recorded in `wp_apex_image_history`.
- Full one-click restore functionality is available per image and via bulk operations.

### 2.3 Automatic Frontend Next-Gen Serving
1. **HTML Rewrite Mode**: Modifies `<img>` tags to `<picture>` tags with `<source type="image/avif">` and `<source type="image/webp">`.
2. **Server Direct Rewrite Mode**: Generates `.htaccess` or Nginx rules intercepting `image.jpg` requests and returning `image.jpg.webp` if `Accept: image/webp` HTTP header is present.

### 2.4 Media List Table & Attachment Edit Screen
- Custom columns in `upload.php`: Optimization Status, Original Size, Compressed Size, Savings %, WebP Status, AVIF Status, Image SEO Score.
- Custom metabox in `post.php?post={attachment_id}&action=edit`: Full historical metrics, manual compression slider, re-convert buttons, restore original button.
