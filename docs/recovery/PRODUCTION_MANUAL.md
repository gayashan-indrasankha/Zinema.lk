# Manual browser audit — 2026-10-25

Executed against the owned Docker gateway at loopback 8088 with local fake OAuth,
fictional accounts/catalogue and original generated media. External resource CSP
and runtime network isolation remained active. No real recipient, live provider,
production account or public site was contacted. Desktop and 390 × 844 mobile
views were used in the in-app Chromium browser; the viewport was reset afterwards.

| Journey | Executed action and observed result |
| --- | --- |
| Shots | Viewed empty feed, then populated original test feed. Saved one shot and navigated to profile; the saved collection displayed it. |
| Catalogue/search | Viewed movies/pagination and clean movie detail. Reproduced broken popup search on a rewritten detail URL, then searched Product and followed the correct result after repair. |
| Member authentication | Guest profile showed sign-in. Completed the local fake Google flow and observed the fictional signed-in member profile. Real Google was not used. |
| Administrator login | Signed in as the fictional development administrator; dashboard rendered. Credentials stayed private. |
| Movie administration | Submitted an actual fictional movie form, followed that row's Edit control, changed its title and submitted Update Movie. Both success results were visible. No manual deletion. |
| Administrator navigation | Inspected series, episodes, collections, shots, settings, analytics and bot tracking pages/forms. Added the missing trailer sidebar entry, followed Manage Trailers and inspected its form/table. This is page/form inspection, not manual execution of every CRUD action. |
| Responsive administration | Reproduced content width 1,271 inside a 375 px content viewport. After repair, fresh document navigation measured content 375/375 with stacked sidebar; shots' formerly overflowing table was confined to its scroll wrapper. Desktop create/edit still worked. |
| Administrator logout | Clicked Logout and observed the login page. The separate member session persisted, as expected. |
| Trailer playback/share target | Reproduced stalled playback and inspected actual video state. After repairs, a shared feed URL selected its original four-second motion clip; readyState 4, unpaused, currentTime 2.8897 and hidden loading overlay were observed. The final buffered-loop recheck reached 2.7398 with overlay none, readyState 4 and width 390/390. The frame was visible. |
| Trailer save | Clicked Save on that selected trailer while the fictional member remained signed in; its button changed to Saved. Guest/error rollback is covered by executed Node fixtures, not misreported as this manual action. |

Playback qualification: the retained one-second/one-frame red fixture buffered but
did not advance in this browser. It is still retained for byte/transport tests.
The new original four-second 24 fps test pattern provides meaningful playback
evidence. It does not certify every codec, Safari/iOS, mobile device or real
Facebook source. No native share, clipboard publish, wa.me handoff, QR pairing,
password change, destructive browser action or real mail transaction was performed.

The browser's reload reused an older document during the CSS test. Independent
loopback HTTP confirmed fresh bytes; navigation with a fresh audit query confirmed
the content-hashed CSS/JS URLs. Application HTML returned no-store/no-cache.
Do not infer an application private-page cache leak from that tool behavior.

Sanitized screenshots (fictional content only):

- `evidence/production-manual-profile.png`: fictional member and saved shot.
- `evidence/production-manual-admin.png`: desktop movie creation result.
- `evidence/production-manual-admin-mobile.png`: repaired administrator mobile view.
- `evidence/production-manual-trailer-mobile.png`: original motion clip in feed.

Automatically exercised actions and unverified features are mapped separately in
PRODUCTION_FEATURES.md. Manual coverage is intentionally reported at action level;
there is no claim that every discovered feature was manually verified.
