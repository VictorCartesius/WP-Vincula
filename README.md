# Vincula

> Article: https://cartesius.site/uestc_wiredanchor/

> Preview: https://cartesius.site/blogroll/

> Blogroll friend links for WordPress: curate entries in the admin and render them anywhere with a single shortcode.

- **Plugin slug:** `vincula`
- **Version:** 1.0.1
- **Author:** Victor Cartesius
- **Text domain:** `vincula`
- **License:** MIT

---

## Features

- **Dedicated link management** — registers a custom post type (`vinculum`) with its own admin menu, keeping friend links separate from posts and pages.
- **Shortcode rendering** — drop `[vincula]` into any shortcode-aware area to output a card grid.
- **Custom logos** — paste an image URL or pick one straight from the WordPress media library (`wp.media`).
- **Automatic fallback** — links without a logo fall back to the target site's favicon.
- **Priority ordering** — assign each link a numeric weight; lower numbers appear first.
- **At-a-glance admin list** — extra `Priority`, `URL`, and `Logo` columns on the link list screen.
- **CSV import / export** — move the whole list in and out of the site from a single screen.
- **Quick edit** — change URL, logo, priority and description straight from the list screen, without opening the editor.
- **Responsive by default** — two columns on desktop, single column below 600px.
- **No dependencies, no configuration** — activate and go; a small bundled stylesheet is enqueued on the front end.

---

## Requirements

| Item | Requirement |
| --- | --- |
| WordPress | 5.0 or later |
| PHP | 7.0 or later |
| Browser | Any modern browser with CSS Flexbox and `-webkit-line-clamp` support |

The media library uploader depends on `wp.media` and is only enqueued on `vinculum` edit screens.

---

## Installation

### From the admin dashboard

1. Compress the `vincula` folder into a `.zip` archive.
2. In your site admin, go to **Plugins → Add New → Upload Plugin**, choose the archive, and install it.
3. Click **Activate Plugin**.

### Manually via FTP / file manager

1. Upload the entire `vincula` folder to `wp-content/plugins/`.
2. Go to **Plugins** in the admin and activate **Vincula**.

The plugin flushes rewrite rules on both activation and deactivation.

---

## Usage

### 1. Add a friend link

Once activated, a **Vincula** menu appears in the admin sidebar (position 80, `dashicons-networking` icon). The list screen itself is titled **Friend Links**.

Click **Add New Friend Link** and fill in:

| Location | Field | Required | Description |
| --- | --- | --- | --- |
| Title field | Title | Yes | Site name shown on the card |
| Editor | Content | No | Short description; truncated to two lines on the front end |
| Meta box `URL` | URL | Yes | Full address including `https://`; links without it are skipped |
| Meta box `Logo` | Logo URL | No | Paste a URL or click **Upload Image** to pick from the media library; falls back to the favicon when empty |
| Sidebar `Priority` | Priority | No | Integer, defaults to `0`; lower numbers appear first |

Once saved, the list screen shows the extra `Priority`, `URL`, and `Logo` columns for quick review.

### 2. Display them on the front end

Insert the shortcode into a page, post, or shortcode block:

```
[vincula]
```

Supported locations:

- Page or post content (block editor or classic editor)
- Shortcode block in the block editor
- Text widget in the classic widgets screen
- Shortcode modules in third-party page builders

A common setup is to create a "Friends" page containing only `[vincula]` and add it to your navigation menu.

> The shortcode currently accepts no attributes — `[vincula]` is the only supported form. The legacy `[friend_cards]` alias is still registered so that pages created before the rename keep working.

### 3. Bulk import and export (CSV)

Go to **Vincula → Import / Export**.

**Export** downloads a UTF-8 CSV (with a BOM, so Excel detects the encoding) containing every entry, ordered by priority. Columns:

| Column | Notes |
| --- | --- |
| `name` | Site name. |
| `description` | Plain text of the editor content. |
| `url` | Full address including `https://`. |
| `logo` | Logo URL; empty means the favicon is used. |
| `priority` | Integer; lower numbers appear first. |
| `status` | `publish`, `draft`, `pending`, `private` or `future`. |

**Import** accepts the same format. The header row is required but the column order is free — `title`, `link`, `logo_url`, `order`, `post_status` and similar aliases are recognised.

- Rows are matched against existing entries by `url`.
- Leave **Update entries whose URL already exists** unchecked to only add new rows; check it to overwrite name, description, logo, priority and status of matching entries.
- Rows without a URL are skipped and counted in the report.

After importing, the screen reports how many rows were imported, updated and skipped.

#### Sample file

A ready-to-edit example ships with the plugin: [`samples/friend-links-sample.csv`](samples/friend-links-sample.csv). The same file is linked from the **Import / Export** screen, so you can download it before writing your own.

```csv
name,description,url,logo,priority,status
WordPress.org,Official plugin and theme directory,https://wordpress.org,,10,publish
CSS-Tricks,"Articles, demos and screencasts about front-end development",https://css-tricks.com,,20,publish
Smashing Magazine,Design and UX resources,https://www.smashingmagazine.com,https://www.smashingmagazine.com/images/favicon/favicon.png,30,publish
,Name is taken from the URL host when left empty,https://example.org,,40,publish
Example Draft,Hidden from the grid until published,https://example.net,,50,draft
```

What each row demonstrates:

| Row | Shows |
| --- | --- |
| `WordPress.org` | All six columns filled in. |
| `CSS-Tricks` | A description containing a comma, wrapped in double quotes. |
| `Smashing Magazine` | A custom logo URL instead of the favicon. |
| *(empty name)* | A blank `name` falls back to the URL host. |
| `Example Draft` | `status` set to `draft`, so the entry stays off the front end. |

Two adjacent commas (`...,,10,...`) mean the column is empty — that is how the `logo` is left blank above. A field that contains a comma, a double quote or a line break must be wrapped in double quotes, with inner quotes doubled (`""`).

### 4. Quick edit

Hover a row on the **Friend Links** list screen and click **Quick Edit**. The inline form exposes **URL**, **Logo**, **Priority** and **Description** next to the usual title and slug fields. Save with **Update**.

Quick edit relies on the standard `save_post` flow, which also runs the same capability check as the full editor. The list columns refresh on the next page load.

---

## Markup and styling

The shortcode outputs the following structure, so you can override styles with your own selectors:

```html
<div class="vincula-grid">
  <div class="vincula-item">
    <a class="vincula-link" href="..." target="_blank" rel="noopener">
      <img class="vincula-icon" src="..." alt="Site name" loading="lazy">
      <span class="vincula-name">Site name</span>
      <span class="vincula-desc">Site description</span>
    </a>
  </div>
  <!-- ... -->
</div>
```

| Class | Purpose |
| --- | --- |
| `.vincula-grid` | Cards container; flex layout with a `20px` gap |
| `.vincula-item` | Individual card; 50% width by default |
| `.vincula-link` | Inner link; stacks content vertically and centers it |
| `.vincula-icon` | Circular 72 × 72 logo |
| `.vincula-name` | Bold site name |
| `.vincula-desc` | Site description, clamped to two lines |

Styles live in `assets/vincula.css` and are enqueued on `wp_enqueue_scripts`, so they are part of the document head and survive themes that swap page content without a full reload (PJAX / AJAX navigation).

---

## Data model

Useful when extending the plugin or migrating data:

| Data | Storage | Key |
| --- | --- | --- |
| Friend link entry | Post type | `vinculum` |
| Site name | Post title | — |
| Site description | Post content | — |
| Link URL | Post meta | `_vincula_url` |
| Logo URL | Post meta | `_vincula_logo` |
| Priority | Post meta | `_vincula_order` |

Front-end query: `post_type=vinculum`, `post_status=publish`, ordered by `_vincula_order` numerically ascending, with no post limit.

---

## Migrating from Blogroll-FriendLinks

This release renamed every internal identifier. Existing data is migrated automatically, once, on the first request after switching plugins:

- Posts stored as `friend_card` are rewritten to `vinculum`.
- Meta keys `_friend_card_url`, `_friend_card_logo`, and `_friend_card_order` are renamed to their `_vincula_*` equivalents.
- The option `vincula_legacy_migrated` records that the migration has run.

Before upgrading, **deactivate the old plugin and back up the database**. Do not keep both versions active at the same time.

---

## FAQ

**I added a link but nothing shows up on the front end.**

1. Make sure the entry is published and that the `URL` field is filled in.
2. Make sure the entry has `_vincula_order` meta. The query sorts by that meta key, so entries without it are never returned. Fix: open the entry and click **Update**.
3. Make sure `[vincula]` (or the legacy `[friend_cards]`) is actually present in the content and that the area parses shortcodes.

**A logo is missing or loads slowly.**

Without a custom logo, the plugin requests `https://www.google.com/s2/favicons?domain=<target-domain>`, which is fetched by the visitor's browser. In regions where Google is unreachable, icons will fail to load. Uploading a logo for frequently listed sites is recommended.

**Can I show three or four columns instead of two?**

Yes. The styles live in `assets/vincula.css`. Adjust `flex` and `max-width` on `.vincula-item` (for example `calc(33.33% - 14px)` for three columns) and update the `@media (max-width: 600px)` breakpoint accordingly.

**The admin interface is in English — can it be translated?**

Strings are currently hard-coded in English and no translation files are loaded, so language packs won't apply. To localize, edit the strings in `includes/class-vincula-admin.php`.

**Can I order links by date or randomly instead?**

Ordering is fixed to ascending `_vincula_order`. To change it, modify the `WP_Query` arguments in `includes/class-vincula-public.php`.

**Do I need to update my existing pages after the rename?**

No. The `[friend_cards]` shortcode is still registered as an alias, and all entries are migrated automatically. Updating the shortcode to `[vincula]` is optional.

---

## File structure

```
vincula/
├── vincula.php                    # Entry point: constants, bootstrap, migration, activation hooks
├── includes/
│   ├── class-vincula-admin.php    # Admin: post type, meta boxes, saving, list columns, quick edit, media modal
│   ├── class-vincula-csv.php      # Admin: CSV import and export screen
│   └── class-vincula-public.php   # Front end: shortcode, query, markup, stylesheet enqueue
├── assets/
│   └── vincula.css                # Card grid styles, loaded in the document head
├── samples/
│   └── friend-links-sample.csv    # Example import file
├── .gitattributes                 # Line-ending rules
├── .gitignore                     # Files excluded from version control
├── LICENSE                        # MIT licence text
└── README.md                      # Documentation
```

---

## Change Log

### 1.0.1

- Moved the card styles from an inline `wp_footer` block into `assets/vincula.css`, enqueued on `wp_enqueue_scripts`. Fixes cards rendering unstyled on the first visit with themes that navigate via PJAX/AJAX and only swap the content container.

### 1.0.0

- Initial release as **Vincula**, formerly **Blogroll-FriendLinks**.
- `vinculum` custom post type and meta boxes (URL / Logo / Priority).
- `[vincula]` shortcode rendering a responsive card grid; `[friend_cards]` kept as an alias.
- Favicon fallback for links without a logo.
- CSV import and export on the **Vincula → Import / Export** screen.
- Inline quick edit for URL, logo, priority and description on the link list screen.
- One-time automatic migration of entries created by the earlier Blogroll-FriendLinks release (post type `friend_card`, meta keys `_friend_card_*`).

---

## License

Released under the MIT license.
