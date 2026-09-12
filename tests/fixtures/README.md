# Test fixtures

Image files used by the PHPUnit fuzz tests (`tests/test-fuzz-*.php`) and the
e2e theme (`tests/e2e/fixtures/theme/theme-image-block-e2e/images/`). Keep the
committed total under 8 MB; the SVGs matter more than the photos.

## Public domain (Wikimedia Commons, license field = Public domain)

| File | Source | Author |
|---|---|---|
| `svg/flag-of-japan.svg`, `../e2e/.../images/flag-of-japan.svg` | https://commons.wikimedia.org/wiki/File:Flag_of_Japan.svg | Public domain |
| `../e2e/.../images/tetons.jpg` (3000x2402) and the `-400`, `-800`, `-1600` renditions | https://commons.wikimedia.org/wiki/File:Adams_The_Tetons_and_the_Snake_River.jpg | Ansel Adams, US National Archives |

Renditions were made with `magick tetons.jpg -resize {400,800,1600}x -quality 82`.

## Generated (ImageMagick `plasma:fractal` and hand-written SVG, no copyright)

| File | What it exercises |
|---|---|
| `images/tiny-1x1.png` | 1x1, 1-bit PNG |
| `images/tiny-320x240.jpg` | Small baseline JPEG |
| `images/boundary-800x533.jpg` | Medium JPEG |
| `images/png16-640x427.png` | 16-bit PNG |
| `images/alpha-600x400.png` | 8-bit PNG with alpha (resized from a 1200x800 original) |
| `images/truncated-1600x1066.jpg` | JPEG cut off mid-stream |
| `images/wrong-extension-is-jpeg.png` | JPEG bytes behind a `.png` name |
| `images/not-an-image.jpg` | Text behind a `.jpg` name |
| `images/zero-byte.jpg` | Empty file |
| `images/UPPERCASE.JPG` | Case in the extension |
| `images/spaces and (parens) & ampersand.jpg` | URL-hostile file name |
| `images/ünïcödé-名前-🙂.jpg` | Non-ASCII file name |
| `../e2e/.../images/webp-1800x1200.webp` | WebP |
| `../e2e/.../images/animated-640x480.gif` | Three-frame animated GIF |
| `svg/svg-plain.svg` | Width, height, viewBox |
| `svg/svg-no-dimensions.svg` | No width or height |
| `svg/svg-comment-first.svg`, `../e2e/.../images/svg-comment-first.svg` | Leading comment, no prolog, root `style="fill:red"` |
| `svg/svg-with-prolog-and-doctype.svg`, `../e2e/.../images/svg-with-prolog-and-doctype.svg` | XML prolog, comment, DOCTYPE, existing `style` attribute |
| `svg/svg-with-script.svg` | `<script>`, `onload`, `javascript:` href |
| `svg/svg-entities.svg` | DOCTYPE with nested entity expansion |
| `svg/svg-nested.svg` | Nested `<svg>` and `<foreignObject>` |
| `svg/svg-unclosed.svg` | Missing closing tag |
| `svg/svg-utf16.svg` | UTF-16 with BOM |
| `svg/svg-empty.svg` | Empty file |
