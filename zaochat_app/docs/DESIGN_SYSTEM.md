# ZaoChat App Design System

## Source

This mobile design system is extracted from:

- `https://zaochat.com/` live marketing site, checked on August 9, 2026
- existing Laravel portal UI in `resources/js/pages/portal/*`
- brand guidance provided in this workspace

## Brand direction

- Dark-first interface, but not black-only. Surfaces layer from `bg` to `surface` to `card`.
- Accent family is indigo and violet, with soft glow treatment rather than neon-heavy gradients everywhere.
- Typography is `Inter`.
- Borders are soft and low-contrast, especially in dark mode.
- Cards and sheets are rounded, compact, and dense rather than airy.
- Status colors should stay practical: green for live or healthy, amber for takeover or warning, red only for errors.

## Core tokens

### Typography

- Font family: `Inter, system-ui, sans-serif`
- Heading weight: `700-800`
- Body weight: `400-500`
- UI label weight: `500-600`

### Radii

- `radius-sm`: `12`
- `radius-md`: `20`
- `radius-lg`: `28`

### Dark theme

- `bg`: `#07070f`
- `surface`: `#0f0f1a`
- `card`: `#141428`
- `border`: `#ffffff12`
- `primary`: `#6366f1`
- `primary-hover`: `#4f46e5`
- `primary-glow`: `#6366f140`
- `accent`: `#a78bfa`
- `text`: `#f3f4f6`
- `muted`: `#9ca3af`
- `subtle`: `#4b5563`

### Light theme

- `bg`: `#ffffff`
- `surface`: `#f9fafb`
- `card`: `#f3f4f6`
- `border`: `#e5e7eb`
- `primary`: `#6366f1`
- `primary-hover`: `#4f46e5`
- `primary-glow`: `#6366f140`
- `accent`: `#8b5cf6`
- `text`: `#111827`
- `muted`: `#6b7280`
- `subtle`: `#9ca3af`

## Native translation rules

- Do not mirror CSS variable names directly into component props. Use semantic theme access through a single theme module.
- Use glow color sparingly. It is for hero treatment, active chips, and focused emphasis, not general backgrounds.
- Keep surfaces stacked consistently:
  `screen bg -> section card -> nested surface -> active accent`.
- In dark mode, avoid pure white text on large muted blocks. Use `text` for primary copy and `muted` for secondary copy.
- In light mode, preserve contrast on indigo buttons with `primaryText = #f8fafc`.

## Mobile component style guidance

- App header: dense, high-contrast, small supporting copy.
- Session row: avatar or status dot, name, one-line intent preview, compact state label.
- Statistic card: single metric, short label, minimal ornament.
- Feature chip: rounded rectangle, thin border, muted background.
- Modal or sheet: use `card` background with `md` radius or above.

## Implemented foundation in Expo

Current implementation in the app:

- theme module: `src/theme/index.ts`
- Inter font loading in `App.tsx`
- system-aware dark/light mode via `useColorScheme()`
- branded starter shell showing how sessions, leads, and portal feature chips should look

## Follow-up design tasks

- Add a reusable component layer: `Screen`, `Card`, `Button`, `Badge`, `SessionListItem`
- Add a navigation theme for Expo Router or React Navigation
- Add iconography rules
- Add spacing scale and elevation tokens
- Add form input states for login and widget settings screens
