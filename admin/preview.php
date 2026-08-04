<?php
/* ---------------------------------------------------------------------
   /admin/preview.php?slug=… — read a draft as a finished page.

   Lives under /admin/ on purpose: the session cookie is scoped to this
   folder, so the signed-in check works here and public post URLs stay
   completely stateless.
   --------------------------------------------------------------------- */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

admin_require_login();

/* post.php reads this instead of doing its own auth. */
define('POST_PREVIEW', true);

require __DIR__ . '/../post.php';
