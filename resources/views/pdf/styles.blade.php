@font-face { font-family: 'Ubuntu'; font-weight: normal; font-style: normal; src: url('{{ $fonts }}/Ubuntu-Regular.ttf') format('truetype'); }
@font-face { font-family: 'Ubuntu'; font-weight: bold; font-style: normal; src: url('{{ $fonts }}/Ubuntu-Bold.ttf') format('truetype'); }
@font-face { font-family: 'Noto Sans'; font-weight: normal; font-style: normal; src: url('{{ $fonts }}/NotoSans-Regular.ttf') format('truetype'); }
@font-face { font-family: 'Noto Sans'; font-weight: bold; font-style: normal; src: url('{{ $fonts }}/NotoSans-Bold.ttf') format('truetype'); }
@font-face { font-family: 'Noto Sans'; font-weight: normal; font-style: italic; src: url('{{ $fonts }}/NotoSans-Italic.ttf') format('truetype'); }
@page { margin: 16mm 17mm 16mm 17mm; }
body { font-family: 'Noto Sans', sans-serif; font-size: 9pt; line-height: 1.3; color: #27272a; }
.display, h1, h2, .entry-title, .entry-org, .chip { font-family: 'Ubuntu', sans-serif; }
.accent { color: #0f766e; }
.muted { color: #71717a; }
h1 { font-size: 28pt; line-height: 1.1; margin: 0; color: #18181b; font-weight: bold; }
.contact { margin-top: 5pt; line-height: 1.6; font-size: 8.5pt; color: #52525b; }
.contact .sep { color: #0f766e; padding: 0 4pt; }
.contact a, .entry a, .credential a { color: #0f766e; text-decoration: none; }
.rule { height: 3pt; background: #0f766e; margin: 10pt 0 14pt; width: 46pt; }
h2 { font-size: 9pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1.6pt; color: #0f766e; margin: 18pt 0 7pt; padding-bottom: 3pt; border-bottom: 0.75pt solid #d4d4d8; }
p { margin: 0 0 7pt; }
.summary { font-size: 9.5pt; line-height: 1.4; color: #3f3f46; }
.entry { margin-bottom: 11pt; page-break-inside: avoid; }
.entry-head { margin-bottom: 3pt; }
.entry-title { font-size: 11pt; font-weight: bold; color: #18181b; }
.entry-title a { color: #18181b; }
.entry-org { color: #0f766e; font-weight: bold; font-size: 10pt; }
.period { float: right; font-size: 8pt; color: #71717a; }
ul { margin: 2pt 0 0; padding-left: 12pt; }
li { margin-bottom: 2pt; }
.chip { display: inline-block; background: #f0fdfa; border: 0.75pt solid #99f6e4; color: #115e59; border-radius: 9pt; padding: 1.5pt 7pt; margin: 0 3pt 4pt 0; font-size: 8pt; }
.credential { margin-bottom: 4pt; }
.letter p { font-size: 9.5pt; line-height: 1.5; margin-bottom: 10pt; }

/* Modern: indigo, airy, solid tags. */
.style-modern h1 { font-size: 26pt; color: #312e81; }
.style-modern .accent, .style-modern .contact .sep, .style-modern .contact a, .style-modern .entry a, .style-modern .credential a, .style-modern .entry-org { color: #4338ca; }
.style-modern .rule { width: auto; height: 1pt; background: #c7d2fe; margin: 9pt 0 12pt; }
.style-modern h2 { color: #312e81; letter-spacing: 1pt; border-bottom: 0; margin: 20pt 0 8pt; }
.style-modern .chip { background: #4338ca; border-color: #4338ca; color: #ffffff; border-radius: 3pt; }
.style-modern .entry-title, .style-modern .entry-title a { color: #1e1b4b; }

/* Minimal: black and grey, nothing decorative. */
body.style-minimal { color: #111111; }
.style-minimal h1 { font-family: 'Noto Sans', sans-serif; font-size: 20pt; font-weight: bold; color: #111111; }
.style-minimal .display, .style-minimal h2, .style-minimal .entry-title, .style-minimal .entry-org, .style-minimal .chip { font-family: 'Noto Sans', sans-serif; }
.style-minimal .accent, .style-minimal .contact .sep, .style-minimal .contact a, .style-minimal .entry a, .style-minimal .credential a, .style-minimal .entry-org { color: #111111; }
.style-minimal .contact { color: #555555; }
.style-minimal .rule { display: none; }
.style-minimal .header { padding-bottom: 8pt; border-bottom: 0.75pt solid #111111; }
.style-minimal h2 { color: #111111; letter-spacing: 1pt; border-bottom: 0; margin: 16pt 0 6pt; }
.style-minimal .entry-org { font-weight: normal; }
.style-minimal .chip { background: transparent; border: 0; color: #111111; border-radius: 0; padding: 0; margin: 0 8pt 2pt 0; font-size: 9pt; }

/* Bold: dark header band, amber accent. */
.style-bold .header { background: #18181b; padding: 14pt 16pt 12pt; }
.style-bold h1 { color: #ffffff; font-size: 26pt; }
.style-bold .contact { color: #d4d4d8; }
.style-bold .contact .sep { color: #f59e0b; }
.style-bold .contact a { color: #fbbf24; }
.style-bold .rule { width: auto; height: 3pt; background: #f59e0b; margin: 0 0 14pt; }
.style-bold .accent, .style-bold .entry-org { color: #b45309; }
.style-bold .entry a, .style-bold .credential a { color: #b45309; }
.style-bold h2 { color: #18181b; border-bottom: 0; border-left: 4pt solid #f59e0b; padding: 1pt 0 1pt 7pt; }
.style-bold .chip { background: #fffbeb; border-color: #fcd34d; color: #78350f; border-radius: 3pt; }
