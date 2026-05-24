# open-phase1.ps1 — відкриває Windows Terminal з 5 панями для Phase 1
wt -d "C:\Dev\ddbv2" `; split-pane -V -d "C:\Dev\ddbv2" `; split-pane -H -d "C:\Dev\ddbv2" `; move-focus left `; split-pane -H -d "C:\Dev\ddbv2" `; move-focus right `; split-pane -H -d "C:\Dev\ddbv2"
