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
