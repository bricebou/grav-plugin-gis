# PHPStan stubs

Declarations of the Grav 2, Twig 3, shortcode-core and Thunder Shortcode
symbols the plugin uses, so PHPStan can analyse it without a Grav install.
They only hold what the plugin calls, with the signatures and PHPDoc types
of Grav 2.2, shortcode-core 6.2 and Twig 3.30. Extend them when the plugin
starts using something new.

These files are only scanned for symbols: they are never loaded at runtime
nor analysed.
