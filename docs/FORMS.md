# Forms

Every form on the site goes through one small pipeline in the theme. There is
no form plugin. A new form takes one entry in PHP and one pattern of plain HTML.

## How a submission travels

1. **A pattern renders the form.** It posts to `form_action()`
   (`admin-post.php?action=vs_form`), has `data-vs-form`, and calls
   `form_fields( '<key>' )` and `form_error()`.
2. **`assets/js/forms.js` sends it with fetch.** The script (about 1.5KB) and
   `assets/css/forms.css` load only on pages where a form renders: any HTML
   block containing `data-vs-form` enqueues them. The script disables the
   button, shows its `data-busy` text and posts with `Accept: application/json`.
   Without JavaScript, or on a network error, the form posts normally and the
   visitor gets a plain message page.
3. **`handle_form()` checks it.** Honeypot (bots get a fake success), render
   time (sends under 3 seconds after page load are refused), rate limit (5 sends
   per form per IP per 10 minutes), each field against its definition, then
   Cloudflare Turnstile when the form has it on.
4. **It is saved as a Submission.** A private `vs_submission` post, tagged with
   the `vs_form` term for the form key. Field values live in `_vs_fields`, the
   email in `_vs_email`, the sending page in `_vs_page`, the last webhook result
   in `_vs_hook`.
5. **The visitor gets the reply first.** Then the server sends the webhook (if
   one is set) and two HTML emails: one to the team, one confirmation to the
   visitor.
6. **The page shows the result.** On error the message goes into the
   `form_error()` slot. On success any `[data-sent]` element shows its text, the
   form resets, and a `vs:sent` event fires on the form for page scripts that
   want a custom sent state.

## Where things live

| File | What it holds |
| --- | --- |
| `inc/forms.php` | `forms()` registry, `form_fields()`, `form_error()`, `handle_form()`, Turnstile, webhook, emails |
| `inc/forms-admin.php` | Submissions menu (one entry per form), Form settings with Turnstile keys, list columns, Quick view, Resend, privacy export and erase |
| `assets/js/forms.js` | Background send for every `[data-vs-form]` |
| `assets/css/forms.css` | `.vs-form` layout, `.vs-form-err`, `.vs-hp` (hides the honeypot) |
| `patterns/contact-form.php`, `patterns/newsletter-form.php` | The two shipped forms |
| `bin/check-forms.php` | Self-check for validation and the webhook template |

## Step 1: define the form

Add an entry to the array in `forms()`. The key is the form's name everywhere:
the pattern, the Submissions menu, Form settings and the `vs_form` term.

```php
'my_form' => array(
	'label'  => __( 'My form', 'vanilla-starter' ),          // Menu and email label.
	'sent'   => __( 'Thanks, it is in.', 'vanilla-starter' ), // Shown after a successful send.
	'once'   => true,                                         // Optional: one row per email (signups).
	'fields' => array(
		// name => [ label, type, message when missing, choices ]. Empty message = optional.
		'name'  => array( __( 'Name', 'vanilla-starter' ), 'text', __( 'Enter your name.', 'vanilla-starter' ) ),
		'email' => array( __( 'Email', 'vanilla-starter' ), 'email', __( 'Enter your email address.', 'vanilla-starter' ) ),
		'plan'  => array( __( 'Plan', 'vanilla-starter' ), 'choice', '', array( 'Basic', 'Pro' ) ),
	),
),
```

### Field types

| Type | Accepts |
| --- | --- |
| `text` | Any text, trimmed to 200 characters |
| `email` | A valid email. A bad one gets "Enter an email address like name@company.com." |
| `url` | An http or https address |
| `textarea` | Multi-line text, up to 5000 characters |
| `choice` | One of the values in the field's fourth element (radio or select). Anything else counts as missing |

Every form needs an `email` field. The handler uses it for the Submission
title, the `once` check, the privacy tools and the confirmation email.

Fields not listed in `forms()` are ignored, so the HTML `name` attributes must
match the keys exactly.

## Step 2: write the pattern

The form markup is plain HTML inside a `wp:html` block in a pattern. Four things
are required:

- `action="<?php echo esc_url( \VanillaStarter\form_action() ); ?>"` and `method="post"`
- `data-vs-form` on the `<form>`, so the script and styles load and pick it up
- `<?php \VanillaStarter\form_fields( 'my_form' ); ?>` inside the form
- `<?php \VanillaStarter\form_error(); ?>` near the submit button

Optional: `class="vs-form"` for the default layout, `data-busy` on the button
for its sending text, and an element with `data-sent` for the success text. All
copy goes through `esc_html_e()` with the `vanilla-starter` text domain. Give
the button `class="wp-element-button"` so it picks up the theme.json button style.

Do not add a nonce. These are public forms and pages are cached, so a nonce
would go stale and fail real visitors. The honeypot, render time and rate limit
cover spam.

Use the pattern from a template, or insert it from the inserter. Inserting
copies the markup into the page, which freezes the render time, so the theme
rewrites `vs_ts` to the current time each time the block renders.

See `patterns/contact-form.php` (text, email, radio `choice`, url, textarea)
and `patterns/newsletter-form.php` (optional first name, email, `once` on, so a
repeat signup just says yes).

### A new signup list

For a separate list (say, a webinar), copy the newsletter entry in `forms()`
under a new key and use that key in `form_fields()`:

```php
'webinar' => array(
	'label'  => __( 'Webinar', 'vanilla-starter' ),
	'sent'   => __( 'You are on the list. We will email you the link.', 'vanilla-starter' ),
	'once'   => true,
	'fields' => array(
		'email' => array( __( 'Email', 'vanilla-starter' ), 'email', __( 'Enter your email address to sign up.', 'vanilla-starter' ) ),
	),
),
```

## Step 3: set where it goes

In wp-admin, go to **Submissions > Form settings**. Each form has a tab:

- **Notify emails:** who gets an email for each submission, comma separated.
  Empty means the site admin email.
- **Endpoint URL:** where each submission is posted as JSON (for a CRM or
  mailing list). Put any token in the URL.
- **Data:** the JSON shape, with `{{field}}` placeholders, for example
  `{"email": "{{email}}", "source_id": 1}`. Values are JSON-escaped. Empty sends
  every field. Invalid JSON is refused on save.

Each form also gets its own entry under the Submissions menu, which lists only
its rows. Each row has View, Quick view (the full details in a native
`<dialog>`, no admin JavaScript) and, once a webhook ran, Resend.

### Cloudflare Turnstile

Optional, per form. Below the tabs, the **Cloudflare Turnstile** section holds
the site key and secret key, shared by all forms (from Cloudflare dashboard >
Turnstile). Then tick **Turnstile** on each form's tab that should use it.

- A form uses Turnstile only when it is ticked and both keys are saved.
  Clearing the site key turns it off everywhere.
- The secret is never printed back into the page. Leave the field empty to
  keep the saved one.
- The theme adds the widget before the form's error slot and loads Cloudflare's
  `api.js` only on pages with such a form (see `docs/EXCEPTIONS.md`). Patterns
  need no change.
- `handle_form()` verifies the token with Cloudflare after the fields pass, and
  refuses the send if the check fails or Cloudflare cannot be reached.
  `forms.js` resets the widget after each send, since tokens are single use.
- Without JavaScript the widget cannot run, so a form with Turnstile on needs JS.
- After ticking or unticking, purge the page cache, or cached pages keep the
  old form markup.
- For local testing use Cloudflare's test keys: site
  `1x00000000000000000000AA` with secret `1x0000000000000000000000000000000AA`
  (always passes) or `2x0000000000000000000000000000000AA` (always fails).

Submissions are included in Tools > Export Personal Data and Erase Personal
Data, matched by email.

## Step 4: check it

- `php -l` on `inc/forms.php`, `inc/forms-admin.php` and the new pattern
- New pattern file not showing? Core caches the theme's pattern list:
  `studio wp eval 'wp_get_theme()->delete_pattern_cache();'`
- Send the form on the site. Wait more than 3 seconds after the page loads, or
  it refuses the send
- The row shows under Submissions, the notify email and confirmation arrive
- Send with a bad email and with a missing required field; each shows its
  message in the error slot
- From the site root: `studio wp eval-file wp-content/themes/wp-vanilla-starter/bin/check-forms.php`
- `bin/budget.sh` and the em dash check before committing
