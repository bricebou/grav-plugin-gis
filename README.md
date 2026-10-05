# GIS Plugin

The **Gis** Plugin is an extension for [Grav CMS](http://github.com/getgrav/grav). This plugin, using the Leaflet javascript library, aims to provide a simple way to geolocate contents and to display interactive maps.

## Installation

<!-- Installing the Gis plugin can be done in one of three ways: The GPM (Grav Package Manager) installation method lets you quickly install the plugin with a simple terminal command, the manual method lets you do so via a zip file, and the admin method lets you do so via the Admin Plugin. -->

<!-- ### GPM Installation (Preferred)

To install the plugin via the [GPM](http://learn.getgrav.org/advanced/grav-gpm), through your system's terminal (also called the command line), navigate to the root of your Grav-installation, and enter:

    bin/gpm install gis

This will install the Gis plugin into your `/user/plugins`-directory within Grav. Its files can be found under `/your/site/grav/user/plugins/gis`. -->

### Manual Installation

To install the plugin manually, download the zip-version of this repository and unzip it under `/your/site/grav/user/plugins`. Then rename the folder to `gis`. You can find these files on [GitHub](https://github.com/bricebou/grav-plugin-gis) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/gis
	
> NOTE: This plugin is a modular component for Grav which may require other plugins to operate, please see its [blueprints.yaml-file on GitHub](https://github.com/bricebou/grav-plugin-gis/blob/master/blueprints.yaml).
>
> **Requirements**
>
> - Grav 2.0 or later, and therefore PHP 8.3 or later. Sites still on Grav 1.7 should stay on the 0.2 releases
> - [Grav Shortcode Core Plugin](https://github.com/getgrav/grav-plugin-shortcode-core) 6.0 or later

<!-- ### Admin Plugin

If you use the Admin Plugin, you can install the plugin directly by browsing the `Plugins`-menu and clicking on the `Add` button. -->

## Configuration

Before configuring this plugin, you should copy the `user/plugins/gis/gis.yaml` to `user/config/plugins/gis.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true              #
private:                   # Private Area
  load: true               # Loading Leaflet library inside the Private Area
  height: 340              # Height of the private area maps
  center: '51.505, -0.093' # Default coordinates for centering private area maps
  zoom: 13                 # Default zoom for private area maps
public:                    # Frontend
  load: false              # Loading Leaflet on every frontend page, not only those showing a map
  height: 340              # Default height for frontend maps
  center: '51.505, -0.093' # Default coordinates for centering frontend maps
  zoom: 13                 # Default zoom for frontend maps

```

Maps load Leaflet and the plugin's script on their own, on the pages that show one. `public.load` forces them on every page, which only matters for a theme drawing its own Leaflet maps, or one rendering its assets before the page content: `{{ gis() }}` called from a template relies on a deferred assets block, as Quark's.

Note that if you use the Admin Plugin, a file with your configuration named gis.yaml will be saved in the `user/config/plugins/`-folder once the configuration is saved in the Admin.

## Usage

### For editors

The plugin provides two features.
First, you can geolocate your page: the plugin provide a Gis page, based on the default one, in which you can add several coordinates to your page frontmatter through interactive maps. These coordinates are then displayed on your site frontend through the `{{ gis() }}` Twig function (see below).

In Admin2, the Geolocation tab shows a single map holding every marker of the page, above the list of their names, icons and coordinates. Select a marker in the list, then click the map to place it, or drag a marker to move it. The map's default centre, zoom and height come from the plugin's admin area settings.

The second allows you to display interactive maps into your content: the plugin provides the `[gis /]` shortcode you can use in your content to display interactive maps, with or without markers. For example:

```
[gis /]
```

will display a simple map centered and zoomed accordingly to the plugin settings. You can pass several arguments as map height, center coordinates, zoom level:

```
[gis height=220 zoom=9 center=34.23,1.43 /]
```

Maps take the full width by default. The `width` argument takes a number of pixels, or a number followed by `px`, `%`, `em`, `rem` or `vw`; anything else is ignored:

```
[gis width=50% height=220 /]
```

You can add some markers to your map, using multiple `markerN` arguments. Each one takes a name, a latitude, a longitude and an icon:

```
[gis height=320 marker1="Test 1, 43, 2, pink" marker2="Test 2, 44, 1, orange" /]
```

The icon is optional and falls back to `blue`:

```
[gis height=320 marker1="Test 1, 43, 2" /]
```

Marker names are displayed in a popup when the marker is clicked, as plain text: HTML in a name is shown, not interpreted. A marker whose latitude or longitude isn't a number is skipped, so a typo in one marker doesn't prevent the others, nor the map itself, from being displayed.

The displayed maps are automatically centered and zoomed to fit all markers.

### For developers

#### **Inside your templates**

The plugin provides the `{{ gis() }}` Twig function. Without parameters, it displays a map (which height is taken from the plugin settings) and populate it with markers, based on the page frontmatter. If there isn't any marker associated to the page, the map is centered and zoomed based on the plugin configuration.

You can specify the height of the map :

```twig
{{ gis({'height': 320}) }}
```

and its width, which accepts the same values as the shortcode's `width` argument:

```twig
{{ gis({'width': '480px', 'height': 320}) }}
```

You can prevent the map from being populated with markers:

```twig
{{ gis({'markers': false}) }}
```

You can also pass an array of markers:

```twig
{{ gis({'height': 240, 'markers': [{'name': 'Test', 'icon': 'pink', 'latitude': '51.505', 'longitude': '-0.093'},{'name': 'Test2', 'icon': 'orange', 'latitude': '51', 'longitude': '-0.1'}]}) }}
```

#### **Overriding the map template**

`partials/leaflet.html.twig` renders an empty container whose `data-gis` attribute holds the map, drawn by `assets/js/gis.js`. Maps added to the page after it loaded can be drawn with `GisPlugin.init(element)`.

#### **Adding geolocation to page blueprints**

As said above, the GIS plugin provides a Gis page blueprint that simply adds a Geolocation tab.

You can add this feature in your own page blueprints; for example, to add a tab:

```yaml
geolocation:
  type: tab
  title: PLUGIN_GIS.GEOLOCATION
  import@:
    type: partials/gis
```

The partial declares `header.markers` as a `gis-markers` field, drawn in Admin2 by `admin-next/fields/gis-markers.js`. A blueprint declaring the field itself must keep `validate: type: list`: Grav filters a field type it doesn't know as text, which would empty the markers on save.

### Available markers

| blue 	 | green   | orange   | pink   | purple   | red   | teal   | yellow   |
|:------:|:-------:|:--------:|:------:|:--------:|:-----:|:------:|:--------:|
| ![](assets/images/marker-blue.png) | ![](assets/images/marker-green.png) | ![](assets/images/marker-orange.png) | ![](assets/images/marker-pink.png) | ![](assets/images/marker-purple.png) | ![](assets/images/marker-red.png) | ![](assets/images/marker-teal.png) | ![](assets/images/marker-yellow.png) |


## Development

### Updating Leaflet

Leaflet is declared in `package.json` with an exact version, and the files the plugin loads are copied into `lib/leaflet`, which is committed since Grav installs plugins without a build step:

```
npm install leaflet@<version> --save-exact --save-dev
npm run leaflet
```

`npm audit` reports advisories affecting the bundled version. `lib/` is left out of opengrep through `.semgrepignore`, so the scan covers the plugin's own code.

### Coding standards

The development tools live in `tools/`, with their own `composer.json`, so the `vendor/` folder shipped with the plugin only ever holds the autoloader. Install them once, then run them through Composer scripts:

```
composer tools
composer ecs:check   # report coding standard violations
composer ecs         # fix them
composer rector:check  # report the refactorings Rector would apply
composer rector        # apply them
composer phpstan       # static analysis, at level 8
composer check         # all three, without changing anything
```

PHPStan runs without a Grav install: the Grav, Twig and shortcode-core symbols the plugin uses are declared in `stubs/`, to be extended when the plugin starts using new ones.

## Credits

- [Leaflet javascript library](https://leafletjs.com/)
- [Grav Leaflet Plugin](https://github.com/magikcypress/grav-plugin-leaflet)
- [Map Leaflet Plugin](https://github.com/finanalyst/grav-plugin-map-marker-leaflet)