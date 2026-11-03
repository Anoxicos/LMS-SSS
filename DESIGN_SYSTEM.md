# SafeSky LMS design system v2

The SafeSky public experience and admin now share one cinematic aviation palette: night surfaces, warm paper, and a restrained electric signal. The public UI retains its current structure while using shared tokens; the admin keeps its dense Velzon layout and adopts the same semantic colors and warm neutral scale.

## Color tokens

| Token | Value | Use |
|---|---|---|
| Ink | `#070B14` | Main dark surface, footer, dark text |
| Night | `#0E1626` | Raised dark surfaces |
| Midnight | `#16233D` | Dark hover and active surfaces |
| Paper | `#F2EFE8` | Main light surface and page background |
| Chalk | `#FFFFFF` | Cards on Paper, text on dark |
| Sand | `#E2DED3` | Light borders and separators |
| Slate | `#3A4256` | Body text on Paper |
| Stone | `#8A8F9C` | Large metadata and captions |
| Mist | `#B7BDCB` | Supporting text on dark |
| Jet | `#38E1FF` | Accent on dark surfaces and focus rings |
| Signal | `#1F5CFF` | Actions and links on light surfaces |
| Signal Deep | `#1546CC` | Hover and pressed actions |
| Flare | `#FF5A36` | Rare spot accent |
| Success | `#19C39A` | Completion state |
| Success Deep | `#077264` | Readable success text and hover |
| Warning | `#FFB638` | Attention state |
| Warning Deep | `#7A4B00` | Readable warning text |
| Danger | `#FF4D4D` | Error and destructive state |
| Danger Deep | `#B42318` | Readable error text |

The canonical tokens live in `resources/scss/_design-tokens.scss`. It emits CSS custom properties for Vue pages and Sass variables for the corporate admin theme. Use Jet on dark and Signal on light; neither is intended as text on the opposite surface. Stone is reserved for large metadata, while Slate carries small text on Paper.

## Color application

- Alternate clear Ink and Paper sections with hard edges. Keep most surfaces flat.
- Use a restrained Jet radial glow over Ink for hero moments. Keep the Jet-to-Signal gradient to a single small focal element.
- Use Chalk cards on Paper, Sand borders on light surfaces, and Midnight dividers on dark surfaces.
- Keep Flare and semantic colors to small marks. Pair state colors with text or an icon, not color alone.
- Public controls use Signal with Chalk text on light surfaces and Jet with Ink text on dark surfaces. Secondary controls use a one-pixel outline and fill on hover.
- Focus rings use 2px Jet on dark and Signal on light with a 3px offset.

## Admin theme

The Velzon corporate theme maps Bootstrap primary to Signal, secondary to Flare, success/warning/danger to their semantic tokens, info to Jet, and indigo to Midnight. The sidebar uses Ink with a Jet active state. The gray scale is warm and interpolated between Paper, Sand, Stone, Slate, and Ink; 6px admin radii remain for dashboard density.

## Typography and layout direction

Use the existing readable UI font pairing while the design is migrated. Display typography should be oversized and tightly tracked when the public pages are next reworked. Favor generous spacing, a 12-column grid, and sharp card corners or pill controls. This change does not add external fonts or alter page structure.

## Motion and accessibility

Use the shared `--ease-out` curve for future motion work. Respect `prefers-reduced-motion`; keep existing transitions and animations until they are explicitly migrated. Verify text contrast at its rendered size: body copy should reach WCAG AA 4.5:1, large text 3:1, and non-text controls 3:1. Avoid Stone for small text on Paper and avoid Flare as small text on light surfaces.

## Implemented surfaces

- Public home, course list, course detail, login, registration, individual requests, learner account form, and anonymous navigation use the shared CSS tokens.
- Corporate admin Sass variables and dark sidebar use the shared Sass palette.
- Setup and local preview instructions are in `SETUP.md`.

