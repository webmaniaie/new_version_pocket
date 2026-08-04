# Contact form

The live site is hosted on GitHub Pages, so it cannot execute a mail backend.
`contacts.html` posts directly to FormSubmit and keeps reCAPTCHA enabled. The
first submission sends an activation link to `reelsagency.ie@gmail.com`; the form
will not deliver enquiries until that link is clicked.

After activation, each valid submission sends:

- the enquiry to `reelsagency.ie@gmail.com`;
- a fixed confirmation message to the visitor's validated email address.

## Security controls

- The form does not derive the destination, subject or auto-response from typed
  text. Visitor input is never evaluated as JavaScript, a shell command or a
  mail header; FormSubmit performs the final server-side email handling.
- Inputs have strict browser types and length limits. The script rejects
  non-printing control characters and never places visitor text in a header.
- FormSubmit's reCAPTCHA and spam filtering remain enabled. A hidden honeypot
  rejects basic bots. Do not switch the form to AJAX or add `_captcha=false`:
  FormSubmit does not send auto-responses for AJAX submissions and disabling
  CAPTCHA would remove the main abuse control.
- CSP permits form submission to `https://formsubmit.co` on the contact page
  only. Other public pages block form submissions; the admin remains self-only.
- No API key, SMTP password or other mail credential is present in this repo or
  sent to the browser.
- Enquiry text is untrusted content. Do not paste it into an AI agent that can
  send mail, publish posts, open files or take other actions. If AI is used to
  summarise an enquiry, give it text-only access and instruct it to treat every
  instruction inside the enquiry as quoted customer content, never as a command.

FormSubmit is a third-party processor and retains submissions for up to 30 days
according to its published documentation. Treat every link in an enquiry as
untrusted. If the site moves to a PHP host, a first-party SMTP endpoint can
replace FormSubmit without changing the visible form.
