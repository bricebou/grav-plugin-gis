# v0.2.0
## 09/16/2026

1. [](#bugfix)
    * Marker names are now escaped for a JavaScript context. The documented syntax wrapped the name in single quotes, which ended up inside the name itself. Where Twig auto-escaping is off (`strict_mode.twig_compat` on together with `twig.autoescape` off), that produced `createMarker(..., ''Test 1'')`: a syntax error that dropped the whole inline script, so no map was drawn at all. May be the disappearing map of [#3](https://github.com/bricebou/grav-plugin-gis/issues/3)
    * [security] The `center`, `zoom`, `height` and `id` shortcode arguments are now validated server side instead of being interpolated into the inline script and the container's style attribute. Escaping was no help there, since a payload made only of plain characters went straight through: `[gis center="0,0);alert(document.domain);//" /]` produced `map.setView(new L.LatLng(0,0);alert(document.domain);//), 13)`. Anyone able to edit a page could run script in a visitor's browser
    * [security] The same escaping closes an injection: a marker name containing `</script>`, set from a page's frontmatter, could break out of the inline script and run arbitrary code in a visitor's browser
    * A `markerN` argument without its trailing icon no longer triggers a fatal error. Reading the missing fourth value raised an `Undefined array key` warning, which Grav's error handler turns into an exception. The icon now falls back to `blue`
    * A marker with a non numeric latitude or longitude no longer takes the whole map down. Leaflet throws on `NaN` coordinates, which aborted the script before the map was given a view. Faulty markers are now skipped and the remaining ones are still drawn
    * Quotes surrounding a marker name are stripped instead of ending up inside the name itself
    * `{{ gis({'center': '45.75, 4.85'}) }}` no longer raises a `TypeError`. The Twig function assumed `center` was always an array and passed it to `implode()`
    * A page mixing `[gis /]` shortcodes with `{{ gis() }}` no longer loses maps once the page is cached. Container ids came from a per request counter, but Grav caches the shortcode output while re-rendering content Twig on every request, so the counter restarted and the later containers reused `leaflet-0`, `leaflet-1`. Leaflet bound to the first, already initialised container and those maps never appeared. Ids are now random per map; an explicit `id=` is still honoured
    * Markers no longer pick up a background and rounded corners from the theme. Leaflet marks marker icons and control buttons with `role="button"` for keyboard access, and a CSS reset that styles `[role="button"]` like a `<button>` paints them, since Leaflet sets nothing there to compete with. Seen with Pico based themes such as Quark2; the plugin now undoes exactly the properties Leaflet leaves alone
    * Icon and marker shadow URLs are resolved through Grav's locator rather than assuming `/user/plugins/gis`, so markers keep their icons when Grav is served from a subdirectory

2. [](#improved)
    * [BC BREAK] The map template is now handed `center_lat` and `center_lng` instead of `center`, because the centre is validated as two numbers before it reaches Twig. A theme that overrides `partials/leaflet.html.twig` must be updated: `new L.LatLng({{ center }})` becomes `new L.LatLng({{ center_lat }}, {{ center_lng }})`. An un-updated override renders a map with no view at all
    * Grav 2: `{{ gis() }}` now works inside page content. Grav 2 runs editor authored Twig through a sandbox, which silently rejected the function and made such pages render raw. The plugin declares itself through `onBuildTwigSandboxPolicy`, so no site configuration is needed. Harmless on Grav 1.x, where the event never fires
    * Marker names are now shown in a popup. The name was collected from the shortcode and the page frontmatter but never displayed
    * Bundled Leaflet moved from 1.8.0 to 1.9.4. The plugin now ships Leaflet's own `dist/` unchanged, so the hand made `leaflet.min.css` is gone and `leaflet.css` is loaded instead
    * Documented that the marker icon is optional, and dropped the misleading quotes from the shortcode example

# v0.1.2
## 09/16/2026

1. [](#bugfix)
    * Reported the plugin version in `blueprints.yaml`, so GPM can see the release [#1](https://github.com/bricebou/grav-plugin-gis/issues/1), [#2](https://github.com/bricebou/grav-plugin-gis/issues/2)

# v0.1.1
## 09/15/2026

1. [](#bugfix)
    * Bundled the `vendor/` folder with the plugin: it was excluded by `.gitignore`, so the plugin failed to load with `require(vendor/autoload.php): Failed to open stream` [#1](https://github.com/bricebou/grav-plugin-gis/issues/1), [#2](https://github.com/bricebou/grav-plugin-gis/issues/2)

# v0.1.0
## 04/25/2022

1. [](#new)
    * First release : page coordinates, `[gis /]` shortcode and `{{ gis() }}` Twig function.
