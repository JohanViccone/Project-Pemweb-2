<?php
// layout/icons.php
function svg_icon(string $name, int $size = 22): string {
  $s = (int)$size;
  $common = "width='{$s}' height='{$s}' viewBox='0 0 24 24' fill='none' xmlns='http://www.w3.org/2000/svg'";
  switch ($name) {
    case 'calendar':
      return "<svg {$common}><path d='M7 2v3M17 2v3M3 8h18M5 5h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z' stroke='currentColor' stroke-width='2' stroke-linecap='round'/></svg>";
    case 'user':
      return "<svg {$common}><path d='M20 21a8 8 0 1 0-16 0' stroke='currentColor' stroke-width='2' stroke-linecap='round'/><path d='M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z' stroke='currentColor' stroke-width='2'/></svg>";
    case 'home':
      return "<svg {$common}><path d='M3 11.5 12 4l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1v-9.5Z' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/></svg>";
    case 'file':
      return "<svg {$common}><path d='M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6Z' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/><path d='M14 2v6h6' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/></svg>";
    case 'monitor':
      return "<svg {$common}><path d='M4 5h16v11H4V5Z' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/><path d='M8 19h8' stroke='currentColor' stroke-width='2' stroke-linecap='round'/></svg>";
    case 'settings':
      return "<svg {$common}><path d='M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z' stroke='currentColor' stroke-width='2'/><path d='M19.4 15a7.9 7.9 0 0 0 .1-1 7.9 7.9 0 0 0-.1-1l2-1.6-2-3.4-2.4 1a8.3 8.3 0 0 0-1.7-1l-.4-2.6h-4l-.4 2.6a8.3 8.3 0 0 0-1.7 1l-2.4-1-2 3.4 2 1.6a7.9 7.9 0 0 0-.1 1c0 .3 0 .7.1 1l-2 1.6 2 3.4 2.4-1c.5.4 1.1.7 1.7 1l.4 2.6h4l.4-2.6c.6-.3 1.2-.6 1.7-1l2.4 1 2-3.4-2-1.6Z' stroke='currentColor' stroke-width='1.5' stroke-linejoin='round'/></svg>";
    case 'plus':
      return "<svg {$common}><path d='M12 5v14M5 12h14' stroke='currentColor' stroke-width='2' stroke-linecap='round'/></svg>";
    case 'pencil':
      return "<svg {$common}><path d='M12 20h9' stroke='currentColor' stroke-width='2' stroke-linecap='round'/><path d='M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5Z' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/></svg>";
    case 'trash':
      return "<svg {$common}><path d='M3 6h18' stroke='currentColor' stroke-width='2' stroke-linecap='round'/><path d='M8 6V4h8v2' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/><path d='M6 6l1 16h10l1-16' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/></svg>";
    case 'bed':
      return "<svg {$common}><path d='M3 11h18v8' stroke='currentColor' stroke-width='2' stroke-linecap='round'/><path d='M5 19v-4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v4' stroke='currentColor' stroke-width='2' stroke-linecap='round'/><path d='M7 11V8a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v3' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/></svg>";
    case 'check':
      return "<svg {$common}><path d='M20 6 9 17l-5-5' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/></svg>";
    case 'filter':
      return "<svg {$common}><path d='M4 5h16l-6 7v6l-4 2v-8L4 5Z' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/></svg>";
    case 'lock':
      return "<svg {$common}><path d='M7 11V8a5 5 0 0 1 10 0v3' stroke='currentColor' stroke-width='2' stroke-linecap='round'/><path d='M6 11h12v10H6V11Z' stroke='currentColor' stroke-width='2' stroke-linejoin='round'/></svg>";
    default:
      return '';
  }
}
