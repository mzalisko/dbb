/* DataBridge · Icon set */
const Ic = ({ d, size = 16, stroke = 1.5, fill = "none", style }) => (
  <svg width={size} height={size} viewBox="0 0 24 24" fill={fill}
    stroke="currentColor" strokeWidth={stroke} strokeLinecap="round" strokeLinejoin="round" style={style}>{d}</svg>
);
const I = {
  Dash:    p => <Ic {...p} d={<><rect x="3" y="3" width="7" height="9" rx="1.2"/><rect x="14" y="3" width="7" height="5" rx="1.2"/><rect x="14" y="12" width="7" height="9" rx="1.2"/><rect x="3" y="16" width="7" height="5" rx="1.2"/></>}/>,
  Sites:   p => <Ic {...p} d={<><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 9h18"/><circle cx="6.5" cy="7" r="0.6" fill="currentColor"/><circle cx="9" cy="7" r="0.6" fill="currentColor"/></>}/>,
  Groups:  p => <Ic {...p} d={<><path d="M12 3 4 7v10l8 4 8-4V7Z"/><path d="m4 7 8 4 8-4"/><path d="M12 11v10"/></>}/>,
  Data:    p => <Ic {...p} d={<><ellipse cx="12" cy="5" rx="8" ry="2.5"/><path d="M4 5v6c0 1.4 3.6 2.5 8 2.5s8-1.1 8-2.5V5"/><path d="M4 11v6c0 1.4 3.6 2.5 8 2.5s8-1.1 8-2.5v-6"/></>}/>,
  Team:    p => <Ic {...p} d={<><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><circle cx="17" cy="9.5" r="2.5"/><path d="M16.5 20a5.5 5.5 0 0 1 5.5-5.4"/></>}/>,
  Logs:    p => <Ic {...p} d={<><path d="M4 4h12l4 4v12a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z"/><path d="M16 4v4h4"/><path d="M8 13h8"/><path d="M8 17h5"/></>}/>,
  Settings:p => <Ic {...p} d={<><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 0 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 0 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8L4.2 7a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 0 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 0 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/></>}/>,
  Search:  p => <Ic {...p} d={<><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></>}/>,
  Plus:    p => <Ic {...p} d={<><path d="M12 5v14"/><path d="M5 12h14"/></>}/>,
  Close:   p => <Ic {...p} d={<><path d="m6 6 12 12"/><path d="M18 6 6 18"/></>}/>,
  Check:   p => <Ic {...p} d={<path d="m5 12 5 5L20 7"/>}/>,
  Star:    p => <Ic {...p} stroke="currentColor" fill="currentColor" d={<path d="m12 3 2.7 5.6 6.3.8-4.6 4.3 1.2 6.1L12 17l-5.6 2.8 1.2-6.1L3 9.4l6.3-.8Z"/>}/>,
  StarO:   p => <Ic {...p} d={<path d="m12 3 2.7 5.6 6.3.8-4.6 4.3 1.2 6.1L12 17l-5.6 2.8 1.2-6.1L3 9.4l6.3-.8Z"/>}/>,
  Eye:     p => <Ic {...p} d={<><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></>}/>,
  EyeOff:  p => <Ic {...p} d={<><path d="m3 3 18 18"/><path d="M10.6 6.2A10 10 0 0 1 12 6c6.5 0 10 6 10 6a17 17 0 0 1-3.3 4"/><path d="M6.6 6.6A17 17 0 0 0 2 12s3.5 6 10 6a10 10 0 0 0 4.6-1.1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></>}/>,
  Drag:    p => <Ic {...p} d={<><circle cx="9" cy="6" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="18" r="1" fill="currentColor"/><circle cx="15" cy="6" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="18" r="1" fill="currentColor"/></>}/>,
  Trash:   p => <Ic {...p} d={<><path d="M4 7h16"/><path d="M9 7V4h6v3"/><path d="M6 7v13a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V7"/></>}/>,
  Edit:    p => <Ic {...p} d={<><path d="M4 20h4l11-11-4-4L4 16Z"/><path d="m14 6 4 4"/></>}/>,
  MoreV:   p => <Ic {...p} d={<><circle cx="12" cy="6" r="1.2" fill="currentColor"/><circle cx="12" cy="12" r="1.2" fill="currentColor"/><circle cx="12" cy="18" r="1.2" fill="currentColor"/></>}/>,
  Arrow:   p => <Ic {...p} d={<><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></>}/>,
  ArrowL:  p => <Ic {...p} d={<><path d="M19 12H5"/><path d="m11 6-6 6 6 6"/></>}/>,
  ChevD:   p => <Ic {...p} d={<path d="m6 9 6 6 6-6"/>}/>,
  Refresh: p => <Ic {...p} d={<><path d="M4 9a8 8 0 0 1 14-3l2 2"/><path d="M20 4v4h-4"/><path d="M20 15a8 8 0 0 1-14 3l-2-2"/><path d="M4 20v-4h4"/></>}/>,
  Bolt:    p => <Ic {...p} d={<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>}/>,
  Bell:    p => <Ic {...p} d={<><path d="M6 9a6 6 0 1 1 12 0v3l2 4H4l2-4Z"/><path d="M10 19a2 2 0 0 0 4 0"/></>}/>,
  Sun:     p => <Ic {...p} d={<><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="M4 12H2"/><path d="M22 12h-2"/><path d="m5 5 1.4 1.4"/><path d="m17.6 17.6 1.4 1.4"/><path d="m5 19 1.4-1.4"/><path d="m17.6 6.4 1.4-1.4"/></>}/>,
  Moon:    p => <Ic {...p} d={<path d="M21 13a9 9 0 1 1-10-10 7 7 0 0 0 10 10Z"/>}/>,
  Filter:  p => <Ic {...p} d={<path d="M4 5h16l-6 8v6l-4-2v-4Z"/>}/>,
  Layers:  p => <Ic {...p} d={<><path d="m12 2 10 6-10 6L2 8Z"/><path d="m2 14 10 6 10-6"/></>}/>,
  ChevR:   p => <Ic {...p} d={<path d="m9 6 6 6-6 6"/>}/>,
  Globe:   p => <Ic {...p} d={<><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18"/><path d="M12 3a14 14 0 0 0 0 18"/></>}/>,
  Map:     p => <Ic {...p} d={<><path d="M9 3 3 5v16l6-2 6 2 6-2V3l-6 2Z"/><path d="M9 3v16"/><path d="M15 5v16"/></>}/>,
  Plug:    p => <Ic {...p} d={<><path d="M9 2v6"/><path d="M15 2v6"/><path d="M6 8h12v3a6 6 0 0 1-6 6 6 6 0 0 1-6-6Z"/><path d="M12 17v5"/></>}/>,
  Card:    p => <Ic {...p} d={<><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18"/><path d="M7 15h3"/></>}/>,
  Lock:    p => <Ic {...p} d={<><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></>}/>,
  Tag:     p => <Ic {...p} d={<><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 12.6V3h9.6l8 8a2 2 0 0 1 0 2.4Z"/><circle cx="7" cy="7" r="1.5"/></>}/>,
  Share:   p => <Ic {...p} d={<><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 7 4"/><path d="m15.6 6.5-7 4"/></>}/>,
  Phone:   p => <Ic {...p} d={<path d="M5 4h4l2 5-2 1a11 11 0 0 0 5 5l1-2 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2Z"/>}/>,
  Chat:    p => <Ic {...p} d={<path d="M21 12a8 8 0 1 1-3.3-6.5L21 4l-1.4 3.4A8 8 0 0 1 21 12Z"/>}/>,
  Layout:  p => <Ic {...p} d={<><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 21V9"/></>}/>,
  List:    p => <Ic {...p} d={<><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><circle cx="4" cy="6" r="1" fill="currentColor"/><circle cx="4" cy="12" r="1" fill="currentColor"/><circle cx="4" cy="18" r="1" fill="currentColor"/></>}/>,
  Export:  p => <Ic {...p} d={<><path d="M12 3v12"/><path d="m7 8 5-5 5 5"/><path d="M4 17v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3"/></>}/>,
  Copy:    p => <Ic {...p} d={<><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/></>}/>,
  Key:     p => <Ic {...p} d={<><circle cx="8" cy="15" r="4"/><path d="M10.8 12.2 21 2"/><path d="m15 5 4 4"/></>}/>,
  Logo:    ({ size = 22 }) => (
    <svg width={size} height={size} viewBox="0 0 24 24" fill="none">
      <rect x="3" y="3" width="8" height="8" rx="1.5" stroke="currentColor" strokeWidth="1.6"/>
      <rect x="13" y="13" width="8" height="8" rx="1.5" fill="currentColor"/>
      <path d="M11 7h2" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"/>
      <path d="M7 11v2" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round"/>
    </svg>
  ),
};
window.I = I;
