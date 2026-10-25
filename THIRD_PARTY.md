# Third-party components

Zinema includes upstream software. Import commits do not imply authorship of
those components or ownership of their trademarks, services or demonstration
references.

- `includes/PHPMailer/`: vendored PHPMailer. Upstream authors, copyright notices,
  version, security guidance and LGPL license remain in that directory. See
  `includes/PHPMailer/LICENSE` and `includes/PHPMailer/composer.json`.
- `whatsapp-bot-deploy/package.json` and its lockfile describe the bot's npm
  dependencies. Their respective licenses continue to apply. Installed packages
  and authentication stores are excluded from Git.
- `fb-video-api/package.json` describes the extractor's dependencies. This import
  preserves the local source files as ordinary files while retaining the existing
  embedded Git metadata locally. The embedded history is not imported or claimed
  as newly authored work. Live extraction remains disabled.
- `docs/recovery/tests/extractor-http/` locks the isolated HTTP test dependencies;
  this is a subset of the extractor, not its full runtime installation.

External fonts, icons, players and CDN URLs referenced by legacy templates are
not bundled assets. Their availability, licensing and suitability for a future
deployment need a separate review. No film library, production uploads, original
database rows or WhatsApp media are included. Recovery screenshots show fictional
fixtures and generated demonstration media.

This inventory is not a comprehensive license audit or a new blanket license for
the project. Preserve upstream notices when distributing third-party code.
