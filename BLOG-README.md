# The blog — how it works, and how to put it on Hostinger

The Learn section is no longer hand-written HTML. One markdown file in
`posts/` is one post, and the site builds the index and the post pages from
that folder every time someone loads them. There is no build step to run and
no file to edit when you add a post.

You write posts in a browser at **`yourdomain.com/admin`**.

---

## 1. Upload to Hostinger

Everything goes into `public_html`. Upload these:

```
.htaccess          admin/            learn.php         reels.css
.well-known/       apple-touch-icon  lib/              reels.js
apple-touch-icon   assets/           post.php          robots.php
case-*.html        contacts.html     posts/            rss.php
favicon.svg        fx.css  fx.js     product.html      script.js
index.html         styles.css        team.html         sitemap.php
work.html
```

**Do not upload** — these are local-only and one of them is huge:

| Skip | Why |
| --- | --- |
| `codex 3.zip` | 495 MB, nothing uses it |
| `_archive/` | the old hand-built `learn.html` / `learn-hooks.html`, kept as a backup |
| `dev-router.php` | only used by the local preview server |
| `serve.py`, `serve.command` | local preview helpers |
| `BLOG-README.md` | this file |

Then in hPanel:

1. **Advanced → PHP Configuration** — PHP **8.0 or newer** (8.1+ is the
   default). The `fileinfo` extension must be on; it is by default. If either
   is wrong the site says so in plain English instead of breaking.
2. Check that `posts/` and `assets/posts/` are set to **755**. That is what
   lets the admin write your posts. File Manager → right-click → Permissions.

---

## 2. Set your password (once)

1. In File Manager, open `public_html/admin/.install-token` and copy the line
   inside it.
2. Go to **`yourdomain.com/admin/setup.php`**.
3. Paste the token, choose a password (12 characters or more — use a password
   manager), submit.

The password is hashed and written to `reels-blog-config.php` **one level above
`public_html`**, where no URL can reach it. The token deletes itself and
`setup.php` refuses to run ever again.

Do this straight after uploading, before you point the domain at the site.

> There is no "forgot password" link. To reset, delete
> `reels-blog-config.php`, re-create `admin/.install-token` with any random
> text in it, and run setup again.

---

## 3. Writing a post

Go to `yourdomain.com/admin`, sign in, hit **New post**.

| Field | What it is |
| --- | --- |
| Headline | The full headline. Also what Google shows. |
| Serif tail | The end of the headline, set in italic serif. For *The first two seconds*, this is `two seconds.` |
| Web address | Fills itself in from the headline. Don't change it after publishing — old links break. |
| Category | The word on the card, e.g. `Hooks`. |
| Read time | e.g. `4 min`. |
| Topics | Comma separated. The small chips under the headline. |
| Summary | Shown on the blog index and used as the Google description. |
| Cover image | Optional. Sits under the headline and becomes the link preview image. |
| Card colour | Leave on Automatic — the cards rotate white / pink / navy by themselves. |

Leave **"Keep as a draft"** ticked and the post shows on the blog as
*"Dropping soon"* with no link, exactly like the placeholder cards do now.
Untick it to publish. Either way it is live the moment you press Save.

### Formatting the body

| Type this | You get |
| --- | --- |
| `## [The problem] Nobody chose your reel` | A new section. The bit in brackets is the small pink label above the heading; leave it out if you don't want one. |
| `*two words*` | Italic serif, the way the site sets accents in headings. |
| `**bold**` | Bold. |
| `1. Name — hint` | Numbered rows with a note on the right. Needs a real dash with a space either side. |
| `1. Just text` | Numbered cards on a coloured band. |
| `- point` | Bullet list. |
| `> a line worth pulling out` | Large italic pull quote. |
| `![what it shows](assets/posts/x.jpg)` | An image. An `.mp4` becomes a looping video. |
| `![...](x.mp4 "portrait")` | A 9:16 clip beside the text, in two columns. |
| `[text](https://…)` | A link. |
| `## Heading {pink}` | Force a section's colour: `white`, `pink` or `navy`. |
| `---` on its own line | A new section with no heading. |

Sections alternate automatically: a section that is only a numbered list gets
a coloured band, everything else is white. `{pink}` overrides that when you
want it.

---

## 4. What happens on its own

- The blog index renumbers and re-colours the cards.
- `/sitemap.xml` picks the post up, with its date.
- `/rss.xml` picks it up. Drafts are in neither.
- `/robots.txt` points at the sitemap using your real domain.
- Old links `/learn.html` and `/learn-hooks.html` redirect to the new URLs.
- `posts/`, `lib/` and `_archive/` cannot be opened in a browser at all.

---

## 5. Editing files directly instead

The admin is a convenience, not a dependency. A post is a plain text file —
you can always open `public_html/posts/` in File Manager and edit, add or
delete `.md` files by hand. Name them `YYYY-MM-DD-slug.md`. The site picks up
whatever is in that folder.

---

## 6. Previewing changes on this Mac

`php -S` does not read `.htaccess`, so `dev-router.php` reproduces the URL
rules for local work:

```bash
cd ~/Desktop/reels-ag-claude && php -S 127.0.0.1:8788 dev-router.php
```

Then open `http://127.0.0.1:8788/learn`. Locally the admin password file lands
in `~/Desktop/reels-blog-config.php` (one level above the site folder), which
is why it never gets uploaded.

---

## 7. If you change the CSS or JS

Bump `ASSET_V` in [`lib/config.php`](lib/config.php) **and** the `?v=` number
in the static `.html` pages. They are on `50` now. Without it, browsers keep
serving the old stylesheet.
